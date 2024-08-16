<?php
/**
 * class Custom_Status
 * Custom statuses make it simple to define the different stages in your publishing workflow.
 */

namespace VIPWorkflow\Modules;

require_once __DIR__ . '/rest/custom-status.php';

use VIPWorkflow\VIP_Workflow;
use VIPWorkflow\Common\PHP\Module;
use VIPWorkflow\Modules\CustomStatus\REST\EditStatus;
use WP_Error;
use WP_Query;
use function VIPWorkflow\Common\PHP\_vw_wp_link_page;

class Custom_Status extends Module {

	public $module;

	private $custom_statuses_cache = [];

	// This is taxonomy name used to store all our custom statuses
	const TAXONOMY_KEY = 'post_status';

	/**
	 * Register the module with VIP Workflow but don't do anything else
	 */
	public function __construct() {

		$this->module_url = $this->get_module_url( __FILE__ );
		// Register the module with VIP Workflow
		$args         = [
			'title'                => __( 'Workflow Config', 'vip-workflow' ),
			'short_description'    => __( 'Configure your editorial workflow.', 'vip-workflow' ),
			'extended_description' => __( 'Using the power of your own post statuses, configure the different stages of your workflow. You can change existing or add new ones anytime, and drag and drop to change their order.', 'vip-workflow' ),
			'module_url'           => $this->module_url,
			'img_url'              => $this->module_url . 'lib/custom_status_s128.png',
			'slug'                 => 'custom-status',
			'configure_page_cb'    => 'print_configure_view',
			'autoload'             => true,
		];
		$this->module = VIP_Workflow::instance()->register_module( 'custom_status', $args );
	}

	/**
	 * Initialize the Custom_Status class if the module is active
	 */
	public function init() {
		// Register custom statuses as a taxonomy
		$this->register_custom_statuses();

		// Register our settings
		if ( ! $this->disable_custom_statuses_for_post_type() ) {
			// Load CSS and JS resources that we probably need in the admin page
			add_action( 'admin_enqueue_scripts', [ $this, 'action_admin_enqueue_scripts' ] );

			// Assets for block editor UI.
			add_action( 'enqueue_block_editor_assets', [ $this, 'load_scripts_for_block_editor' ] );

			// Assets for iframed block editor and editor UI.
			add_action( 'enqueue_block_editor_assets', [ $this, 'load_styles_for_block_editor' ] );
		}

		add_action( 'admin_print_scripts', [ $this, 'post_admin_header' ] );

		// Add custom statuses to the post states.
		add_filter( 'display_post_states', [ $this, 'add_status_to_post_states' ], 10, 2 );

		// These seven-ish methods are hacks for fixing bugs in WordPress core
		add_action( 'admin_init', [ $this, 'check_timestamp_on_publish' ] );
		add_filter( 'wp_insert_post_data', [ $this, 'fix_custom_status_timestamp' ], 10, 2 );
		add_filter( 'wp_insert_post_data', [ $this, 'maybe_keep_post_name_empty' ], 10, 2 );
		add_filter( 'pre_wp_unique_post_slug', [ $this, 'fix_unique_post_slug' ], 10, 6 );
		add_filter( 'preview_post_link', [ $this, 'fix_preview_link_part_one' ] );
		add_filter( 'post_link', [ $this, 'fix_preview_link_part_two' ], 10, 3 );
		add_filter( 'page_link', [ $this, 'fix_preview_link_part_two' ], 10, 3 );
		add_filter( 'post_type_link', [ $this, 'fix_preview_link_part_two' ], 10, 3 );
		add_filter( 'preview_post_link', [ $this, 'fix_preview_link_part_three' ], 11, 2 );
		add_filter( 'get_sample_permalink', [ $this, 'fix_get_sample_permalink' ], 10, 5 );
		add_filter( 'get_sample_permalink_html', [ $this, 'fix_get_sample_permalink_html' ], 10, 5 );
		add_filter( 'post_row_actions', [ $this, 'fix_post_row_actions' ], 10, 2 );
		add_filter( 'page_row_actions', [ $this, 'fix_post_row_actions' ], 10, 2 );

		// Pagination for custom post statuses when previewing posts
		add_filter( 'wp_link_pages_link', [ $this, 'modify_preview_link_pagination_url' ], 10, 2 );

		// REST endpoints
		EditStatus::init();

		add_filter( 'user_has_cap', [ $this, 'remove_or_add_publish_capability_for_user' ], 10, 3 );
	}

	/**
	 * Create the default set of custom statuses the first time the module is loaded
	 */
	public function install() {

		$default_terms = [
			[
				'term' => __( 'Pitch', 'vip-workflow' ),
				'args' => [
					'slug'        => 'pitch',
					'description' => __( 'Idea proposed; waiting for acceptance.', 'vip-workflow' ),
					'position'    => 1,
				],
			],
			[
				'term' => __( 'Assigned', 'vip-workflow' ),
				'args' => [
					'slug'        => 'assigned',
					'description' => __( 'Post idea assigned to writer.', 'vip-workflow' ),
					'position'    => 2,
				],
			],
			[
				'term' => __( 'In Progress', 'vip-workflow' ),
				'args' => [
					'slug'        => 'in-progress',
					'description' => __( 'Writer is working on the post.', 'vip-workflow' ),
					'position'    => 3,
				],
			],
			[
				'term' => __( 'Draft', 'vip-workflow' ),
				'args' => [
					'slug'        => 'draft',
					'description' => __( 'Post is a draft; not ready for review or publication.', 'vip-workflow' ),
					'position'    => 4,
				],
			],
			[
				'term' => __( 'Pending Review' ),
				'args' => [
					'slug'        => 'pending',
					'description' => __( 'Post needs to be reviewed by an editor.', 'vip-workflow' ),
					'position'    => 5,
				],
			],
		];

		// Okay, now add the default statuses to the db if they don't already exist
		foreach ( $default_terms as $term ) {
			if ( ! term_exists( $term['term'], self::TAXONOMY_KEY ) ) {
				$this->add_custom_status( $term['term'], $term['args'] );
			}
		}
	}

	/**
	 * Makes the call to register_post_status to register the user's custom statuses.
	 * Also unregisters draft and pending, in case the user doesn't want them.
	 */
	public function register_custom_statuses() {
		global $wp_post_statuses;

		if ( $this->disable_custom_statuses_for_post_type() ) {
			return;
		}

		// Register new taxonomy so that we can store all our fancy new custom statuses (or is it stati?)
		if ( ! taxonomy_exists( self::TAXONOMY_KEY ) ) {
			$args = [
				'hierarchical'          => false,
				'update_count_callback' => '_update_post_term_count',
				'label'                 => false,
				'query_var'             => false,
				'rewrite'               => false,
				'show_ui'               => false,
			];
			register_taxonomy( self::TAXONOMY_KEY, 'post', $args );
		}

		if ( function_exists( 'register_post_status' ) ) {
			// Users can delete draft and pending statuses if they want, so let's get rid of them
			// They'll get re-added if the user hasn't "deleted" them
			unset( $wp_post_statuses['draft'] );
			unset( $wp_post_statuses['pending'] );

			$custom_statuses = $this->get_custom_statuses();

			// Unfortunately, register_post_status() doesn't accept a
			// post type argument, so we have to register the post
			// statuses for all post types. This results in
			// all post statuses for a post type appearing at the top
			// of manage posts if there is a post with the status
			foreach ( $custom_statuses as $status ) {
				register_post_status( $status->slug, [
					'label'                     => $status->name,
					'protected'                 => true,
					'_builtin'                  => false,
					'label_count'               => _n_noop( "{$status->name} <span class='count'>(%s)</span>", "{$status->name} <span class='count'>(%s)</span>" ),
					'show_in_admin_status_list' => true,
					'show_in_admin_all_list'    => true,
				] );
			}
		}
	}

	/**
	 * Whether custom post statuses should be disabled for this post type.
	 * Used to stop custom statuses from being registered for post types that don't support them.
	 *
	 * @return bool
	 */
	public function disable_custom_statuses_for_post_type( $post_type = null ) {
		global $pagenow;

		// Only allow deregistering on 'edit.php' and 'post.php'
		if ( ! in_array( $pagenow, [ 'edit.php', 'post.php', 'post-new.php' ] ) ) {
			return false;
		}

		if ( is_null( $post_type ) ) {
			$post_type = $this->get_current_post_type();
		}

		if ( $post_type && ! in_array( $post_type, $this->get_post_types_for_module() ) ) {
			return true;
		}

		return false;
	}

	public function configure_page_cb() {
		// do nothing
	}

	/**
	 * Enqueue resources that we need in the admin settings page
	 */
	public function action_admin_enqueue_scripts() {
		// Load Javascript we need to use on the configuration views
		if ( $this->is_whitelisted_settings_view() ) {
			$asset_file = include VIP_WORKFLOW_ROOT . '/dist/modules/custom-status/custom-status-configure.asset.php';
			wp_enqueue_script( 'vip-workflow-custom-status-configure', VIP_WORKFLOW_URL . 'dist/modules/custom-status/custom-status-configure.js', $asset_file['dependencies'], $asset_file['version'], true );
			wp_enqueue_style( 'vip-workflow-custom-status-styles', VIP_WORKFLOW_URL . 'dist/modules/custom-status/custom-status-configure.css', [ 'wp-components' ], $asset_file['version'] );

			wp_localize_script( 'vip-workflow-custom-status-configure', 'VW_CUSTOM_STATUS_CONFIGURE', [
				'custom_statuses'    => $this->get_custom_statuses(),
				'url_edit_status'    => EditStatus::get_crud_url(),
				'url_reorder_status' => EditStatus::get_reorder_url(),
			] );
		}

		// Custom javascript to modify the post status dropdown where it shows up
		if ( $this->is_whitelisted_page() ) {
			$asset_file   = include VIP_WORKFLOW_ROOT . '/dist/modules/custom-status/custom-status.asset.php';
			$dependencies = [ ...$asset_file['dependencies'], 'jquery', 'post' ];
			wp_enqueue_script( 'vip_workflow-custom_status', VIP_WORKFLOW_URL . 'dist/modules/custom-status/custom-status.js', $dependencies, $asset_file['version'], true );

			wp_localize_script('vip_workflow-custom_status', '__vw_localize_custom_status', [
				'no_change' => esc_html__( '&mdash; No Change &mdash;', 'vip-workflow' ),
				'published' => esc_html__( 'Published', 'vip-workflow' ),
				'save_as'   => esc_html__( 'Save as', 'vip-workflow' ),
				'save'      => esc_html__( 'Save', 'vip-workflow' ),
				'edit'      => esc_html__( 'Edit', 'vip-workflow' ),
				'ok'        => esc_html__( 'OK', 'vip-workflow' ),
				'cancel'    => esc_html__( 'Cancel', 'vip-workflow' ),
			] );
		}
	}

	public function load_scripts_for_block_editor() {
		$asset_file = include VIP_WORKFLOW_ROOT . '/dist/modules/custom-status/custom-status-block.asset.php';
		wp_enqueue_script( 'vip-workflow-block-custom-status-script', VIP_WORKFLOW_URL . 'dist/modules/custom-status/custom-status-block.js', $asset_file['dependencies'], $asset_file['version'], true );

		$custom_statuses = $this->get_custom_statuses();
		wp_localize_script( 'vip-workflow-block-custom-status-script', 'VipWorkflowCustomStatuses', $custom_statuses );
	}

	public function load_styles_for_block_editor() {
		$asset_file = include VIP_WORKFLOW_ROOT . '/dist/modules/custom-status/custom-status-block.asset.php';

		wp_enqueue_style( 'edit-flow-workflow-manager-styles', VIP_WORKFLOW_URL . 'dist/modules/custom-status/custom-status-block.css', [ 'wp-components' ], $asset_file['version'] );
	}

	/**
	 * Check whether custom status stuff should be loaded on this page
	 *
	 * @todo migrate this to the base module class
	 */
	public function is_whitelisted_page() {
		global $pagenow;

		if ( ! in_array( $this->get_current_post_type(), $this->get_post_types_for_module() ) ) {
			return false;
		}

		$post_type_obj = get_post_type_object( $this->get_current_post_type() );

		if ( ! current_user_can( $post_type_obj->cap->edit_posts ) ) {
			return false;
		}

		// Only add the script to Edit Post and Edit Page pages -- don't want to bog down the rest of the admin with unnecessary javascript
		return in_array( $pagenow, [ 'post.php', 'edit.php', 'post-new.php', 'page.php', 'edit-pages.php', 'page-new.php' ] );
	}

	/**
	 * Adds all necessary javascripts to make custom statuses work
	 *
	 * @todo Support private and future posts on edit.php view
	 */
	public function post_admin_header() {
		global $post, $pagenow;

		if ( $this->disable_custom_statuses_for_post_type() ) {
			return;
		}

		// Get current user
		wp_get_current_user();

		// Only add the script to Edit Post and Edit Page pages -- don't want to bog down the rest of the admin with unnecessary javascript
		if ( $this->is_whitelisted_page() ) {

			$custom_statuses = $this->get_custom_statuses();

			// $selected can be empty, but must be set because it's used as a JS variable
			$selected      = '';
			$selected_name = '';

			if ( ! empty( $post ) ) {
				// Get the status of the current post
				if ( 0 === $post->ID || 'auto-draft' === $post->post_status || 'edit.php' === $pagenow ) {
					$selected = $custom_statuses[0]->slug;
				} else {
					$selected = $post->post_status;
				}

				// Get the label of current status
				foreach ( $custom_statuses as $status ) {
					if ( $status->slug === $selected ) {
						$selected_name = $status->name;
					}
				}
			}

			// All right, we want to set up the JS var which contains all custom statuses
			$all_statuses = [];

			// The default statuses from WordPress
			$all_statuses[] = [
				'name'        => __( 'Published', 'vip-workflow' ),
				'slug'        => 'publish',
				'description' => '',
			];
			$all_statuses[] = [
				'name'        => __( 'Privately Published', 'vip-workflow' ),
				'slug'        => 'private',
				'description' => '',
			];
			$all_statuses[] = [
				'name'        => __( 'Scheduled', 'vip-workflow' ),
				'slug'        => 'future',
				'description' => '',
			];

			// Load the custom statuses
			foreach ( $custom_statuses as $status ) {
				$all_statuses[] = [
					'name'        => esc_js( $status->name ),
					'slug'        => esc_js( $status->slug ),
					'description' => esc_js( $status->description ),
				];
			}

			$publish_guard_enabled = ( 'on' === VIP_Workflow::instance()->settings->module->options->publish_guard ) ? 1 : 0;

			$post_type_obj = get_post_type_object( $this->get_current_post_type() );

			// Now, let's print the JS vars
			?>
				<script type="text/javascript">
					var custom_statuses = <?php echo json_encode( $all_statuses ); ?>;
					var current_status = '<?php echo esc_js( $selected ); ?>';
					var current_status_name = '<?php echo esc_js( $selected_name ); ?>';
					var vw_publish_guard_enabled = '<?php echo esc_js( $publish_guard_enabled ); ?>';
					var current_user_can_publish_posts = <?php echo current_user_can( $post_type_obj->cap->publish_posts ) ? 1 : 0; ?>;
				</script>
			<?php
		}
	}

	/**
	 * Remove or add the publish capability for users based on the post status
	 *
	 * @param array $allcaps All capabilities for the user
	 * @param string $cap Capability name
	 * @param array $args Arguments
	 *
	 * @return array $allcaps All capabilities for the user
	 */
	public function remove_or_add_publish_capability_for_user( $allcaps, $cap, $args ) {
		global $post;
		$supported_publish_caps_map = [
			'post' => 'publish_posts',
			'page' => 'publish_pages',
		];

		// Bail early if publish guard is off, or the post is already published, or the post is not available
		if ( 'off' === VIP_Workflow::instance()->settings->module->options->publish_guard || ! $post || 'publish' === $post->post_status ) {
			return $allcaps;
		}

		// Bail early if the post type is not supported or if its a not supported capability for this guard
		if ( ! in_array( $post->post_type, $this->get_post_types_for_module() ) || ! isset( $supported_publish_caps_map[ $post->post_type ] ) ) {
			return $allcaps;
		}

		// Bail early if the publish_{post_type} capability is not being checked or if the user doesn't have the capability set
		$cap_to_check = $supported_publish_caps_map[ $post->post_type ];
		if ( $cap_to_check !== $args[0] || ! isset( $allcaps[ $cap_to_check ] ) ) {
			return $allcaps;
		}

		$custom_statuses = VIP_Workflow::instance()->custom_status->get_custom_statuses();
		$status_slugs = wp_list_pluck( $custom_statuses, 'slug' );

		// Bail early if the post is not using a custom status
		if ( ! in_array( $post->post_status, $status_slugs ) ) {
			return $allcaps;
		}

		$status_before_publish = $custom_statuses[ array_key_last( $custom_statuses ) ];

		// If the post status is not the last status, remove the publish capability or else add it back in
		if ( $status_before_publish->slug !== $post->post_status ) {
			// Remove the publish capability based on the post type
			$allcaps[ $supported_publish_caps_map[ $post->post_type ] ] = false;
		} else {
			// Remove the publish capability based on the post type
			$allcaps[ $supported_publish_caps_map[ $post->post_type ] ] = true;
		}

		return $allcaps;
	}

	/**
	 * Adds a new custom status as a term in the wp_terms table.
	 * Basically a wrapper for the wp_insert_term class.
	 *
	 * The arguments decide how the term is handled based on the $args parameter.
	 * The following is a list of the available overrides and the defaults.
	 *
	 * 'description'. There is no default. If exists, will be added to the database
	 * along with the term. Expected to be a string.
	 *
	 * 'slug'. Expected to be a string. There is no default.
	 *
	 * @param int|string $term The status to add or update
	 * @param array|string $args Change the values of the inserted term
	 * @return array|WP_Error $response The Term ID and Term Taxonomy ID
	 */
	public function add_custom_status( $term, $args = [] ) {
		// Term is always added to the end of the list
		$default_position = count( $this->get_custom_statuses() ) + 2;

		$slug = ( ! empty( $args['slug'] ) ) ? $args['slug'] : sanitize_title( $term );
		unset( $args['slug'] );

		if ( ! isset( $args['position'] ) ) {
			$args['position'] = $default_position;
		}

		$encoded_description = $this->get_encoded_description( $args );

		$response            = wp_insert_term( $term, self::TAXONOMY_KEY, [
			'slug'        => $slug,
			'description' => $encoded_description,
		] );

		// Reset our internal object cache
		$this->custom_statuses_cache = [];

		return $response;
	}

	/**
	 * Update an existing custom status
	 *
	 * @param int @status_id ID for the status
	 * @param array $args Any arguments to be updated
	 * @return object $updated_status Newly updated status object
	 */
	public function update_custom_status( $status_id, $args = [] ) {
		$old_status = $this->get_custom_status_by( 'id', $status_id );
		if ( ! $old_status || is_wp_error( $old_status ) ) {
			return new WP_Error( 'invalid', __( "Custom status doesn't exist.", 'vip-workflow' ) );
		}

		// Reset our internal object cache
		$this->custom_statuses_cache = [];

		// Prevent user from changing draft name or slug
		if ( 'draft' === $old_status->slug
		&& (
			( isset( $args['name'] ) && $args['name'] !== $old_status->name )
			||
			( isset( $args['slug'] ) && $args['slug'] !== $old_status->slug )
		) ) {
			return new WP_Error( 'invalid', __( 'Changing the name and slug of "Draft" is not allowed', 'vip-workflow' ) );
		}

		// If the name was changed, we need to change the slug
		if ( isset( $args['name'] ) && $args['name'] != $old_status->name ) {
			$args['slug'] = sanitize_title( $args['name'] );
		}

		// Reassign posts to new status slug if the slug changed and isn't restricted
		if ( isset( $args['slug'] ) && $args['slug'] != $old_status->slug && ! $this->is_restricted_status( $old_status->slug ) ) {
			$new_status        = $args['slug'];
			$reassigned_result = $this->reassign_post_status( $old_status->slug, $new_status );
			// If the reassignment failed, return the error
			if ( is_wp_error( $reassigned_result ) ) {
				return $reassigned_result;
			}
		}
		// We're encoding metadata that isn't supported by default in the term's description field
		$args_to_encode                = [];
		$args_to_encode['description'] = ( isset( $args['description'] ) ) ? $args['description'] : $old_status->description;
		$args_to_encode['position']    = ( isset( $args['position'] ) ) ? $args['position'] : $old_status->position;
		$encoded_description           = $this->get_encoded_description( $args_to_encode );
		$args['description']           = $encoded_description;

		$updated_status_array = wp_update_term( $status_id, self::TAXONOMY_KEY, $args );
		$updated_status       = $this->get_custom_status_by( 'id', $updated_status_array['term_id'] );

		// Reset status cache again, as reassign_post_status() will recache prior statuses
		$this->custom_statuses_cache = [];

		return $updated_status;
	}

	/**
	 * Deletes a custom status from the wp_terms table.
	 *
	 * Partly a wrapper for the wp_delete_term function.
	 * BUT, also reassigns posts that currently have the deleted status assigned.
	 */
	public function delete_custom_status( $status_id, $args = [] ) {
		// Get slug for the old status
		$old_status = $this->get_custom_status_by( 'id', $status_id );
		if ( ! $old_status || is_wp_error( $old_status ) ) {
			return new WP_Error( 'invalid', __( "Custom status doesn't exist.", 'vip-workflow' ) );
		}

		$old_status_slug = $old_status->slug;

		// Reset our internal object cache
		$this->custom_statuses_cache = [];

		if ( ! $this->is_restricted_status( $old_status_slug ) && 'draft' !== $old_status_slug ) {
			// Get the new status to reassign posts to, which would be the first custom status.
			// In the event that the first custom status is being deleted, we'll reassign to the second custom status.
			// Since draft cannot be deleted, we don't need to worry about ever getting index out of bounds.
			$custom_statuses = $this->get_custom_statuses();
			$new_status_slug = $custom_statuses[0]->slug;
			if ( $old_status_slug === $new_status_slug ) {
				$new_status_slug = $custom_statuses[1]->slug;
			}

			$reassigned_result = $this->reassign_post_status( $old_status_slug, $new_status_slug );
			// If the reassignment failed, return the error
			if ( is_wp_error( $reassigned_result ) ) {
				return $reassigned_result;
			}

			// Reset status cache again, as reassign_post_status() will recache prior statuses
			$this->custom_statuses_cache = [];

			$result = wp_delete_term( $status_id, self::TAXONOMY_KEY, $args );
			if ( ! $result || is_wp_error( $result ) ) {
				return new WP_Error( 'invalid', __( 'Unable to delete custom status.', 'vip-workflow' ) );
			}

			return $result;
		} else {
			return new WP_Error( 'restricted', __( 'Restricted status ', 'vip-workflow' ) . '(' . $this->get_custom_status_by( 'id', $status_id )->name . ')' );
		}
	}

	/**
	 * Get all custom statuses as an ordered array
	 *
	 * @param array|string $statuses
	 * @return array $statuses All of the statuses
	 */
	public function get_custom_statuses() {
		if ( $this->disable_custom_statuses_for_post_type() ) {
			return $this->get_core_post_statuses();
		}

		// Internal object cache for repeat requests
		if ( ! empty( $this->custom_statuses_cache ) ) {
			return $this->custom_statuses_cache;
		}

		// Handle if the requested taxonomy doesn't exist
		$statuses = get_terms( [
			'taxonomy'   => self::TAXONOMY_KEY,
			'hide_empty' => false,
		]);

		if ( is_wp_error( $statuses ) || empty( $statuses ) ) {
			$statuses = [];
		}

		// Expand and order the statuses
		$ordered_statuses = [];
		$hold_to_end      = [];
		foreach ( $statuses as $key => $status ) {
			// Unencode and set all of our psuedo term meta because we need the position if it exists
			$unencoded_description = $this->get_unencoded_description( $status->description );
			if ( is_array( $unencoded_description ) ) {
				foreach ( $unencoded_description as $key => $value ) {
					$status->$key = $value;
				}
			}
			// We require the position key later on (e.g. management table)
			if ( ! isset( $status->position ) ) {
				$status->position = false;
			}
			// Only add the status to the ordered array if it has a set position and doesn't conflict with another key
			// Otherwise, hold it for later
			if ( $status->position && ! array_key_exists( $status->position, $ordered_statuses ) ) {
				$ordered_statuses[ (int) $status->position ] = $status;
			} else {
				$hold_to_end[] = $status;
			}
		}
		// Sort the items numerically by key
		ksort( $ordered_statuses, SORT_NUMERIC );
		// Append all of the statuses that didn't have an existing position
		foreach ( $hold_to_end as $unpositioned_status ) {
			$ordered_statuses[] = $unpositioned_status;
		}

		$ordered_statuses = array_values( $ordered_statuses );

		$this->custom_statuses_cache = $ordered_statuses;
		return $ordered_statuses;
	}

	/**
	 * Returns the a single status object based on ID, title, or slug
	 *
	 * @param string|int $string_or_int The status to search for, either by slug, name or ID
	 * @return object|WP_Error $status The object for the matching status
	 */
	public function get_custom_status_by( $field, $value ) {

		if ( ! in_array( $field, [ 'id', 'slug', 'name' ] ) ) {
			return false;
		}

		if ( 'id' === $field ) {
			$field = 'term_id';
		}

		$custom_statuses = $this->get_custom_statuses();
		$custom_status   = wp_filter_object_list( $custom_statuses, [ $field => $value ] );

		if ( ! empty( $custom_status ) ) {
			return array_shift( $custom_status );
		} else {
			return false;
		}
	}

	/**
	 * Assign new statuses to posts using value provided. Returns true if successful, or a WP_Error describing an error otherwise.
	 *
	 * @param string $old_status Slug for the old status
	 * @param string $new_status Slug for the new status
	 * @return true|WP_Error
	 */
	public function reassign_post_status( $old_status, $new_status ) {
		$old_status_post_ids = ( new WP_Query( [
			'post_type'      => $this->get_post_types_for_module( $this->module ),
			'post_status'    => $old_status,
			'posts_per_page' => -1,
			'fields'         => 'ids',
		] ) )->posts;

		if ( empty( $old_status_post_ids ) ) {
			// No existing posts to reassign
			return true;
		}

		global $wpdb;
		$prepared_post_ids = array_map( function ( $post_id ) use ( $wpdb ) {
			return $wpdb->prepare( '%d', $post_id );
		}, $old_status_post_ids );

		// Note: The code below is a direct query because the WP_Query class doesn't support bulk updates.
		// We're using $wpdb->query() instead of using $wpdb->update() because update() doesn't support WHERE clauses
		// with array values like the IDs we're specifying here. We need individual post IDs for cache clearing later.

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Bulk update required for performance, cache is manually cleared below.
		$query_result = $wpdb->query( $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- IDs are prepared in statement above
			"UPDATE $wpdb->posts SET post_status = %s WHERE ID IN (" . implode( ',', $prepared_post_ids ) . ')',
			$new_status
		) );

		if ( false === $query_result ) {
			return new WP_Error( 'invalid', __( 'Failed to reassign post statuses.', 'vip-workflow' ) );
		}

		// We don't have an option to bulk clean cache for the affected posts, so do it in a loop. This step will
		// usually take the longest due to serialized cache calls. We are running this step last, so at least the
		// underlying data is updated even if cache clearing fails.
		foreach ( $old_status_post_ids as $post_id ) {
			clean_post_cache( $post_id );
		}

		return true;
	}

	/**
	 * Display our custom post statuses in post listings when needed.
	 *
	 * @param array   $post_states An array of post display states.
	 * @param WP_Post $post The current post object.
	 *
	 * @return array $post_states
	 */
	public function add_status_to_post_states( $post_states, $post ) {
		if ( ! in_array( $post->post_type, $this->get_post_types_for_module(), true ) ) {
			// Return early if this post type doesn't support custom statuses.
			return $post_states;
		}

		$post_status = get_post_status_object( get_post_status( $post->ID ) );

		$filtered_status = isset( $_REQUEST['post_status'] ) ? $_REQUEST['post_status'] : '';
		if ( $filtered_status === $post_status->name ) {
			// No need to display the post status if a specific status was already requested.
			return $post_states;
		}

		$statuses_to_ignore = [ 'future', 'trash', 'publish' ];
		if ( in_array( $post_status->name, $statuses_to_ignore, true ) ) {
			// Let WP core handle these more gracefully.
			return $post_states;
		}

		// Add the post status to display. Will also ensure the same status isn't shown twice.
		$post_states[ $post_status->name ] = $post_status->label;

		return $post_states;
	}

	/**
	 * Determines whether the slug indicated belongs to a restricted status or not
	 *
	 * @param string $slug Slug of the status
	 * @return bool $restricted True if restricted, false if not
	 */
	public function is_restricted_status( $slug ) {

		switch ( $slug ) {
			case 'publish':
			case 'private':
			case 'future':
			case 'new':
			case 'inherit':
			case 'auto-draft':
			case 'trash':
				$restricted = true;
				break;

			default:
				$restricted = false;
				break;
		}
		return $restricted;
	}

	/**
	 * Primary configuration page for custom status class.
	 * Shows form to add new custom statuses on the left and a
	 * WP_List_Table with the custom status terms on the right
	 *
	 */
	public function print_configure_view() {
		include_once __DIR__ . '/views/manage-workflow.php';
	}

	// phpcs:disable:WordPress.Security.NonceVerification.Missing -- Disabling nonce verification but we should renable it.

	/**
	 * This is a hack! hack! hack! until core is fixed/better supports custom statuses
	 *
	 * When publishing a post with a custom status, set the status to 'pending' temporarily
	 * @see Works around this limitation: http://core.trac.wordpress.org/browser/tags/3.2.1/wp-includes/post.php#L2694
	 * @see Original thread: http://wordpress.org/support/topic/plugin-edit-flow-custom-statuses-create-timestamp-problem
	 * @see Core ticket: http://core.trac.wordpress.org/ticket/18362
	 */
	public function check_timestamp_on_publish() {
		global $pagenow, $wpdb;

		if ( $this->disable_custom_statuses_for_post_type() ) {
			return;
		}

		// Handles the transition to 'publish' on edit.php
		if ( VIP_Workflow::instance() !== null && 'edit.php' === $pagenow && isset( $_REQUEST['bulk_edit'] ) ) {
			// For every post_id, set the post_status as 'pending' only when there's no timestamp set for $post_date_gmt
			if ( isset( $_REQUEST['post'] ) && isset( $_REQUEST['_status'] ) && 'publish' === $_REQUEST['_status'] ) {
				$post_ids = array_map( 'intval', (array) $_REQUEST['post'] );
				foreach ( $post_ids as $post_id ) {
					$wpdb->update( $wpdb->posts, [ 'post_status' => 'pending' ], [
						'ID'            => $post_id,
						'post_date_gmt' => '0000-00-00 00:00:00',
					] );
					clean_post_cache( $post_id );
				}
			}
		}

		// Handles the transition to 'publish' on post.php
		if ( VIP_Workflow::instance() !== null && 'post.php' === $pagenow && isset( $_POST['publish'] ) ) {
			// Set the post_status as 'pending' only when there's no timestamp set for $post_date_gmt
			if ( isset( $_POST['post_ID'] ) ) {
				$post_id = (int) $_POST['post_ID'];
				$ret     = $wpdb->update( $wpdb->posts, [ 'post_status' => 'pending' ], [
					'ID'            => $post_id,
					'post_date_gmt' => '0000-00-00 00:00:00',
				] );
				clean_post_cache( $post_id );
				foreach ( [ 'aa', 'mm', 'jj', 'hh', 'mn' ] as $timeunit ) {
					if ( isset( $_POST[ $timeunit ] ) && ! empty( $_POST[ 'hidden_' . $timeunit ] ) && $_POST[ 'hidden_' . $timeunit ] != $_POST[ $timeunit ] ) {
						$edit_date = '1';
						break;
					}
				}
				if ( $ret && empty( $edit_date ) ) {
					add_filter( 'pre_post_date', [ $this, 'helper_timestamp_hack' ] );
					add_filter( 'pre_post_date_gmt', [ $this, 'helper_timestamp_hack' ] );
				}
			}
		}
	}
	//phpcs:enable:WordPress.Security.NonceVerification.Missing

	/**
	 * PHP < 5.3.x doesn't support anonymous functions
	 * This helper is only used for the check_timestamp_on_publish method above
	 */
	public function helper_timestamp_hack() {
		return ( 'pre_post_date' === current_filter() ) ? current_time( 'mysql' ) : '';
	}

	/**
	 * This is a hack! hack! hack! until core is fixed/better supports custom statuses
	 *
	 * Normalize post_date_gmt if it isn't set to the past or the future
	 * @see Works around this limitation: https://core.trac.wordpress.org/browser/tags/4.5.1/src/wp-includes/post.php#L3182
	 * @see Original thread: http://wordpress.org/support/topic/plugin-edit-flow-custom-statuses-create-timestamp-problem
	 * @see Core ticket: http://core.trac.wordpress.org/ticket/18362
	 */
	public function fix_custom_status_timestamp( $data, $postarr ) {
		// Don't run this if VIP Workflow isn't active, or we're on some other page
		if ( $this->disable_custom_statuses_for_post_type()
		|| VIP_Workflow::instance() === null ) {
			return $data;
		}

		$status_slugs = wp_list_pluck( $this->get_custom_statuses(), 'slug' );

		//Post is scheduled or published? Ignoring.
		if ( ! in_array( $postarr['post_status'], $status_slugs ) ) {
			return $data;
		}

		//If empty, keep empty.
		if ( empty( $postarr['post_date_gmt'] )
		|| '0000-00-00 00:00:00' === $postarr['post_date_gmt'] ) {
			$data['post_date_gmt'] = '0000-00-00 00:00:00';
		}

		return $data;
	}

	/**
	 * A new hack! hack! hack! until core better supports custom statuses`
	 *
	 * If the post_name is set, set it, otherwise keep it empty
	 *
	 * @see https://github.com/Automattic/Edit-Flow/issues/523
	 * @see https://github.com/Automattic/Edit-Flow/issues/633
	 */
	public function maybe_keep_post_name_empty( $data, $postarr ) {
		$status_slugs = wp_list_pluck( $this->get_custom_statuses(), 'slug' );

		// Ignore if it's not a post status and post type we support
		if ( ! in_array( $data['post_status'], $status_slugs )
		|| ! in_array( $data['post_type'], $this->get_post_types_for_module() ) ) {
			return $data;
		}

		// If the post_name was intentionally set, set the post_name
		if ( ! empty( $postarr['post_name'] ) ) {
			$data['post_name'] = sanitize_title( $postarr['post_name'] );
			return $data;
		}

		// Otherwise, keep the post_name empty
		$data['post_name'] = '';

		return $data;
	}

	/**
	 * A new hack! hack! hack! until core better supports custom statuses`
	 *
	 * `wp_unique_post_slug` is used to set the `post_name`. When a custom status is used, WordPress will try
	 * really hard to set `post_name`, and we leverage `wp_unique_post_slug` to prevent it being set
	 *
	 * @see: https://github.com/WordPress/WordPress/blob/396647666faebb109d9cd4aada7bb0c7d0fb8aca/wp-includes/post.php#L3932
	*/
	public function fix_unique_post_slug( $override_slug, $slug, $post_ID, $post_status, $post_type, $post_parent ) {
		$status_slugs = wp_list_pluck( $this->get_custom_statuses(), 'slug' );

		if ( ! in_array( $post_status, $status_slugs )
		|| ! in_array( $post_type, $this->get_post_types_for_module() ) ) {
			return null;
		}

		$post = get_post( $post_ID );

		if ( empty( $post ) ) {
			return null;
		}

		if ( $post->post_name ) {
			return $slug;
		}

		return '';
	}


	/**
	 * Another hack! hack! hack! until core better supports custom statuses
	 *
	 * The preview link for an unpublished post should always be ?p=
	 */
	public function fix_preview_link_part_one( $preview_link ) {
		global $pagenow;

		$post = get_post( get_the_ID() );

		// Only modify if we're using a pre-publish status on a supported custom post type
		$status_slugs = wp_list_pluck( $this->get_custom_statuses(), 'slug' );
		if ( ! $post
		|| ! is_admin()
		|| 'post.php' != $pagenow
		|| ! in_array( $post->post_status, $status_slugs )
		|| ! in_array( $post->post_type, $this->get_post_types_for_module() )
		|| strpos( $preview_link, 'preview_id' ) !== false
		|| 'sample' === $post->filter ) {
			return $preview_link;
		}

		return $this->get_preview_link( $post );
	}

	/**
	 * Another hack! hack! hack! until core better supports custom statuses
	 *
	 * The preview link for an unpublished post should always be ?p=
	 * The code used to trigger a post preview doesn't also apply the 'preview_post_link' filter
	 * So we can't do a targeted filter. Instead, we can even more hackily filter get_permalink
	 * @see http://core.trac.wordpress.org/ticket/19378
	 */
	public function fix_preview_link_part_two( $permalink, $post, $sample ) {
		global $pagenow;

		if ( is_int( $post ) ) {
			$post = get_post( $post );
		}

		//Should we be doing anything at all?
		if ( ! in_array( $post->post_type, $this->get_post_types_for_module() ) ) {
			return $permalink;
		}

		//Is this published?
		if ( in_array( $post->post_status, $this->published_statuses ) ) {
			return $permalink;
		}

		//Are we overriding the permalink? Don't do anything
		// phpcs:ignore:WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['action'] ) && 'sample-permalink' === $_POST['action'] ) {
			return $permalink;
		}

		//Are we previewing the post from the normal post screen?
		if ( ( 'post.php' === $pagenow || 'post-new.php' === $pagenow )
		// phpcs:ignore:WordPress.Security.NonceVerification.Missing
		&& ! isset( $_POST['wp-preview'] ) ) {
			return $permalink;
		}

		//If it's a sample permalink, not a preview
		if ( $sample ) {
			return $permalink;
		}

		return $this->get_preview_link( $post );
	}

	/**
	 * Another hack! hack! hack! until core better supports custom statuses
	 *
	 * The preview link for a saved unpublished post with a custom status returns a 'preview_nonce'
	 * in it and needs to be removed when previewing it to return a viewable preview link.
	 * @see https://github.com/Automattic/Edit-Flow/issues/513
	 */
	public function fix_preview_link_part_three( $preview_link, $query_args ) {
		$autosave = wp_get_post_autosave( $query_args->ID, get_current_user_id() );
		if ( $autosave ) {
			foreach ( array_intersect( array_keys( _wp_post_revision_fields( $query_args ) ), array_keys( _wp_post_revision_fields( $autosave ) ) ) as $field ) {
				if ( normalize_whitespace( $query_args->$field ) != normalize_whitespace( $autosave->$field ) ) {
					// Pass through, it's a personal preview.
					return $preview_link;
				}
			}
		}
		return remove_query_arg( [ 'preview_nonce' ], $preview_link );
	}

	/**
	 * Fix get_sample_permalink. Previosuly the 'editable_slug' filter was leveraged
	 * to correct the sample permalink a user could edit on post.php. Since 4.4.40
	 * the `get_sample_permalink` filter was added which allows greater flexibility in
	 * manipulating the slug. Critical for cases like editing the sample permalink on
	 * hierarchical post types.
	 *
	 * @param string  $permalink Sample permalink
	 * @param int     $post_id   Post ID
	 * @param string  $title     Post title
	 * @param string  $name      Post name (slug)
	 * @param WP_Post $post      Post object
	 * @return string $link Direct link to complete the action
	 */
	public function fix_get_sample_permalink( $permalink, $post_id, $title, $name, $post ) {

		$status_slugs = wp_list_pluck( $this->get_custom_statuses(), 'slug' );

		if ( ! in_array( $post->post_status, $status_slugs )
		|| ! in_array( $post->post_type, $this->get_post_types_for_module() ) ) {
			return $permalink;
		}

		remove_filter( 'get_sample_permalink', [ $this, 'fix_get_sample_permalink' ], 10, 5 );

		$new_name  = ! is_null( $name ) ? $name : $post->post_name;
		$new_title = ! is_null( $title ) ? $title : $post->post_title;

		$post              = get_post( $post_id );
		$status_before     = $post->post_status;
		$post->post_status = 'draft';

		$permalink = get_sample_permalink( $post, $title, sanitize_title( $new_name ? $new_name : $new_title, $post->ID ) );

		$post->post_status = $status_before;

		add_filter( 'get_sample_permalink', [ $this, 'fix_get_sample_permalink' ], 10, 5 );

		return $permalink;
	}

	/**
	 * Hack to work around post status check in get_sample_permalink_html
	 *
	 *
	 * The get_sample_permalink_html checks the status of the post and if it's
	 * a draft generates a certain permalink structure.
	 * We need to do the same work it's doing for custom statuses in order
	 * to support this link
	 * @see https://core.trac.wordpress.org/browser/tags/4.5.2/src/wp-admin/includes/post.php#L1296
	 *
	 * @param string  $return    Sample permalink HTML markup.
	 * @param int     $post_id   Post ID.
	 * @param string  $new_title New sample permalink title.
	 * @param string  $new_slug  New sample permalink slug.
	 * @param WP_Post $post      Post object.
	 */
	public function fix_get_sample_permalink_html( $permalink, $post_id, $new_title, $new_slug, $post ) {
		$status_slugs = wp_list_pluck( $this->get_custom_statuses(), 'slug' );

		if ( ! in_array( $post->post_status, $status_slugs )
		|| ! in_array( $post->post_type, $this->get_post_types_for_module() ) ) {
			return $permalink;
		}

		remove_filter( 'get_sample_permalink_html', [ $this, 'fix_get_sample_permalink_html' ], 10, 5 );

		$post->post_status     = 'draft';
		$sample_permalink_html = get_sample_permalink_html( $post, $new_title, $new_slug );

		add_filter( 'get_sample_permalink_html', [ $this, 'fix_get_sample_permalink_html' ], 10, 5 );

		return $sample_permalink_html;
	}


	/**
	 * Fixes a bug where post-pagination doesn't work when previewing a post with a custom status
	 * @link https://github.com/Automattic/Edit-Flow/issues/192
	 *
	 * This filter only modifies output if `is_preview()` is true
	 *
	 * Used by `wp_link_pages_link` filter
	 *
	 * @param $link
	 * @param $i
	 *
	 * @return string
	 */
	public function modify_preview_link_pagination_url( $link, $i ) {

		// Use the original $link when not in preview mode
		if ( ! is_preview() ) {
			return $link;
		}

		// Get an array of valid custom status slugs
		$custom_statuses = wp_list_pluck( $this->get_custom_statuses(), 'slug' );

		// Apply original link filters from core `wp_link_pages()`
		$r = apply_filters( 'wp_link_pages_args', [
			'link_before' => '',
			'link_after'  => '',
			'pagelink'    => '%',
		]);

		// _wp_link_page() && _vw_wp_link_page() produce an opening link tag ( <a href=".."> )
		// This is necessary to replicate core behavior:
		$link = $r['link_before'] . str_replace( '%', $i, $r['pagelink'] ) . $r['link_after'];
		$link = _vw_wp_link_page( $i, $custom_statuses ) . $link . '</a>';


		return $link;
	}

	/**
	 * Get the proper preview link for a post
	 */
	private function get_preview_link( $post ) {

		if ( 'page' === $post->post_type ) {
			$args = [
				'page_id' => $post->ID,
			];
		} elseif ( 'post' === $post->post_type ) {
			$args = [
				'p'       => $post->ID,
				'preview' => 'true',
			];
		} else {
			$args = [
				'p'         => $post->ID,
				'post_type' => $post->post_type,
			];
		}

		$args['preview_id'] = $post->ID;
		return add_query_arg( $args, home_url( '/' ) );
	}

	/**
	 * Another hack! hack! hack! until core better supports custom statuses
	 *
	 * The preview link for an unpublished post should always be ?p=, even in the list table
	 * @see http://core.trac.wordpress.org/ticket/19378
	 */
	public function fix_post_row_actions( $actions, $post ) {
		global $pagenow;

		// Only modify if we're using a pre-publish status on a supported custom post type
		$status_slugs = wp_list_pluck( $this->get_custom_statuses(), 'slug' );
		if ( 'edit.php' != $pagenow
		|| ! in_array( $post->post_status, $status_slugs )
		|| ! in_array( $post->post_type, $this->get_post_types_for_module() ) ) {
			return $actions;
		}

		// 'view' is only set if the user has permission to post
		if ( empty( $actions['view'] ) ) {
			return $actions;
		}

		if ( 'page' === $post->post_type ) {
			$args = [
				'page_id' => $post->ID,
			];
		} elseif ( 'post' === $post->post_type ) {
			$args = [
				'p' => $post->ID,
			];
		} else {
			$args = [
				'p'         => $post->ID,
				'post_type' => $post->post_type,
			];
		}
		$args['preview'] = 'true';
		$preview_link    = add_query_arg( $args, home_url( '/' ) );

		/* translators: %s: post title */
		$actions['view'] = '<a href="' . esc_url( $preview_link ) . '" title="' . esc_attr( sprintf( __( 'Preview &#8220;%s&#8221;' ), $post->post_title ) ) . '" rel="permalink">' . __( 'Preview' ) . '</a>';
		return $actions;
	}

	/**
	 * Given a post ID, return true if the extended post status allows for publishing.
	 *
	 * @param int $post_id The post ID being queried.
	 * @return bool True if the post should not be published based on the extended post status, false otherwise.
	 */
	public function workflow_is_publish_blocked( $post_id ) {
		$post = get_post( $post_id );

		if ( null === $post ) {
			return false;
		}

		$custom_statuses = $this->get_custom_statuses();
		$status_slugs    = wp_list_pluck( $custom_statuses, 'slug' );

		if ( ! in_array( $post->post_status, $status_slugs ) || ! in_array( $post->post_type, $this->get_post_types_for_module() ) ) {
			// Post is not using a custom status, or is not a supported post type
			return false;
		}

		$status_before_publish = $custom_statuses[ array_key_last( $custom_statuses ) ];

		if ( $status_before_publish->slug === $post->post_status ) {
			// Post is in the last status, so it can be published
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Register REST API endpoints for custom statuses
	 */
	public function register_rest_endpoints() {
		EditStatus::init();
	}
}

