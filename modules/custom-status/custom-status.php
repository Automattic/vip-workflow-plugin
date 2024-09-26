<?php
/**
 * class Custom_Status
 * Custom statuses make it simple to define the different stages in your publishing workflow.
 */

namespace VIPWorkflow\Modules;

require_once __DIR__ . '/rest/custom-status-endpoint.php';

use VIPWorkflow\Modules\CustomStatus\REST\CustomStatusEndpoint;
use VIPWorkflow\VIP_Workflow;
use VIPWorkflow\Modules\Shared\PHP\Module;
use WP_Error;
use WP_Query;
use function VIPWorkflow\Modules\Shared\PHP\_vw_wp_link_page;
use WP_Term;
use WP_Post;

class Custom_Status extends Module {

	public $module;

	private $custom_statuses_cache = [];

	// This is taxonomy name used to store all our custom statuses
	const TAXONOMY_KEY = 'vw_post_status';

	const SETTINGS_SLUG = 'vw-custom-status';

	// The metadata keys for the custom status term
	const METADATA_POSITION_KEY = 'position';
	const METADATA_REQ_EDITORIAL_FIELDS_KEY = 'required_metadata_fields';

	/**
	 * Register the module with VIP Workflow but don't do anything else
	 */
	public function __construct() {

		$this->module_url = $this->get_module_url( __FILE__ );
		// Register the module with VIP Workflow
		$args         = [
			'module_url'        => $this->module_url,
			'slug'              => 'custom-status',
			'configure_page_cb' => 'print_configure_view',
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
			// Load CSS and JS resources for the admin page
			add_action( 'admin_enqueue_scripts', [ $this, 'action_admin_enqueue_scripts' ] );

			// Assets for block editor UI.
			add_action( 'enqueue_block_editor_assets', [ $this, 'load_scripts_for_block_editor' ] );

			// Assets for iframed block editor and editor UI.
			add_action( 'enqueue_block_editor_assets', [ $this, 'load_styles_for_block_editor' ] );
		}

		add_action( 'admin_print_scripts', [ $this, 'post_admin_header' ] );

		// Add custom statuses to the post states.
		add_filter( 'display_post_states', [ $this, 'add_status_to_post_states' ], 10, 2 );

		// Register sidebar menu
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ], 6 /* Prior to default registration of sub-pages */ );

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
		CustomStatusEndpoint::init();

		add_filter( 'user_has_cap', [ $this, 'remove_or_add_publish_capability_for_user' ], 10, 3 );
	}

	/**
	 * Create the default set of custom statuses the first time the module is loaded
	 */
	public function install() {

		$default_terms = [
			[
				'name'        => __( 'Pitch', 'vip-workflow' ),
				'slug'        => 'pitch',
				'description' => __( 'Idea proposed; waiting for acceptance.', 'vip-workflow' ),
				'position'    => 1,
			],
			[
				'name'        => __( 'Assigned', 'vip-workflow' ),
				'slug'        => 'assigned',
				'description' => __( 'Post idea assigned to writer.', 'vip-workflow' ),
				'position'    => 2,
			],
			[
				'name'        => __( 'In Progress', 'vip-workflow' ),
				'slug'        => 'in-progress',
				'description' => __( 'Writer is working on the post.', 'vip-workflow' ),
				'position'    => 3,
			],
			[
				'name'        => __( 'Draft', 'vip-workflow' ),
				'slug'        => 'draft',
				'description' => __( 'Post is a draft; not ready for review or publication.', 'vip-workflow' ),
				'position'    => 4,
			],
			[
				'name'               => __( 'Pending Review' ),
				'slug'               => 'pending',
				'description'        => __( 'Post needs to be reviewed by an editor.', 'vip-workflow' ),
				'position'           => 5,
			],
		];

		// Okay, now add the default statuses to the db if they don't already exist
		foreach ( $default_terms as $term ) {
			if ( ! term_exists( $term['slug'], self::TAXONOMY_KEY ) ) {
				$this->add_custom_status( $term );
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
			// Users can delete the pending and draft status if they want, so let's get rid of that
			// It'll get re-added if the user hasn't "deleted" them
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

		$supported_post_types = VIP_Workflow::instance()->get_supported_post_types();

		if ( $post_type && ! in_array( $post_type, $supported_post_types ) ) {
			return true;
		}

		return false;
	}

	public function add_admin_menu() {
		$menu_title = __( 'VIP Workflow', 'vip-workflow' );

		add_menu_page( $menu_title, $menu_title, 'manage_options', self::SETTINGS_SLUG, [ $this, 'render_settings_view' ] );
	}

	public function configure_page_cb() {
		// do nothing
	}

	/**
	 * Enqueue resources that we need in the admin settings page
	 */
	public function action_admin_enqueue_scripts() {
		// Load Javascript we need to use on the configuration views
		if ( VIP_Workflow::is_settings_view_loaded( self::SETTINGS_SLUG ) ) {
			$asset_file = include VIP_WORKFLOW_ROOT . '/dist/modules/custom-status/custom-status-configure.asset.php';
			wp_enqueue_script( 'vip-workflow-custom-status-configure', VIP_WORKFLOW_URL . 'dist/modules/custom-status/custom-status-configure.js', $asset_file['dependencies'], $asset_file['version'], true );
			wp_enqueue_style( 'vip-workflow-custom-status-styles', VIP_WORKFLOW_URL . 'dist/modules/custom-status/custom-status-configure.css', [ 'wp-components' ], $asset_file['version'] );

			wp_localize_script( 'vip-workflow-custom-status-configure', 'VW_CUSTOM_STATUS_CONFIGURE', [
				'custom_statuses'    => $this->get_custom_statuses(),
				'url_edit_status'    => CustomStatusEndpoint::get_crud_url(),
				'url_reorder_status' => CustomStatusEndpoint::get_reorder_url(),
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

		$publish_guard_enabled = ( 'on' === VIP_Workflow::instance()->settings->module->options->publish_guard ) ? true : false;

		wp_localize_script( 'vip-workflow-block-custom-status-script', 'VW_CUSTOM_STATUSES', [
			'is_publish_guard_enabled' => $publish_guard_enabled,
			'status_terms'             => $this->get_custom_statuses(),
			'supported_post_types'     => VIP_Workflow::instance()->get_supported_post_types(),
		] );
	}

	public function load_styles_for_block_editor() {
		$asset_file = include VIP_WORKFLOW_ROOT . '/dist/modules/custom-status/custom-status-block.asset.php';

		wp_enqueue_style( 'vip-workflow-custom-status-styles', VIP_WORKFLOW_URL . 'dist/modules/custom-status/custom-status-block.css', [], $asset_file['version'] );
	}

	/**
	 * Check whether custom status stuff should be loaded on this page
	 *
	 * @todo migrate this to the base module class
	 */
	public function is_whitelisted_page() {
		global $pagenow;

		if ( ! in_array( $this->get_current_post_type(), VIP_Workflow::instance()->get_supported_post_types() ) ) {
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

			$post_type_obj = get_post_type_object( $this->get_current_post_type() );

			// Now, let's print the JS vars
			?>
				<script type="text/javascript">
					var custom_statuses = <?php echo json_encode( $all_statuses ); ?>;
					var current_status = '<?php echo esc_js( $selected ); ?>';
					var current_status_name = '<?php echo esc_js( $selected_name ); ?>';
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
		if ( ! in_array( $post->post_type, VIP_Workflow::instance()->get_supported_post_types() ) || ! isset( $supported_publish_caps_map[ $post->post_type ] ) ) {
			return $allcaps;
		}

		// Bail early if the publish_{post_type} capability is not being checked or if the user doesn't have the capability set
		$cap_to_check = $supported_publish_caps_map[ $post->post_type ];
		if ( $cap_to_check !== $args[0] || ! isset( $allcaps[ $cap_to_check ] ) ) {
			return $allcaps;
		}

		$custom_statuses = VIP_Workflow::instance()->custom_status->get_custom_statuses();
		$status_slugs    = wp_list_pluck( $custom_statuses, 'slug' );

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
	 * Add all the metadata fields to a term
	 *
	 * @param WP_Term $term The term to add metadata to
	 * @return WP_Term $term The term with metadata added
	 */
	public static function add_metadata_to_term( WP_Term $term ): WP_Term {
		if ( ! isset( $term->taxonomy ) || self::TAXONOMY_KEY !== $term->taxonomy ) {
			return $term;
		}

		// if metadata is already set, don't overwrite it
		if ( isset( $term->meta ) && isset( $term->meta[ self::METADATA_POSITION_KEY ] ) && isset( $term->meta[ self::METADATA_REQ_EDITORIAL_FIELDS_KEY ] ) ) {
			return $term;
		}

		$term_meta = [];
		$term_meta[ self::METADATA_POSITION_KEY ] = get_term_meta( $term->term_id, self::METADATA_POSITION_KEY, true );
		$term_meta[ self::METADATA_REQ_EDITORIAL_FIELDS_KEY ] = get_term_meta( $term->term_id, self::METADATA_REQ_EDITORIAL_FIELDS_KEY, true );

		// Required postmeta fields is not required, so we set it to an empty array on purpose. The position is required however.
		if ( '' === $term_meta[ self::METADATA_POSITION_KEY ] ) {
			return $term;
		}

		$term->meta = $term_meta;

		return $term;
	}

	/**
	 * Add all the metadata fields to all terms in a list
	 *
	 * @param array $terms The terms to add metadata to
	 * @param array $taxonomies The taxonomies to add metadata to
	 * @return array $terms The terms with metadata added
	 */
	public static function add_metadata_to_terms( array $terms, array $taxonomies ): array {
		if ( ! in_array( self::TAXONOMY_KEY, $taxonomies ) ) {
			return $terms;
		}

		foreach ( $terms as $term ) {
			$term = self::add_metadata_to_term( $term );
		}

		return $terms;
	}

	/**
	 * Adds a new custom status as a term in the wp_terms table.
	 * Basically a wrapper for the wp_insert_term class.
	 *
	 * The arguments decide how the term is handled based on the $args parameter.
	 * The following is a list of the available overrides and the defaults.
	 *
	 * 'slug'. Expected to be a string. There is no default.
	 *
	 * 'description'. There is no default. If exists, will be added to the database
	 * along with the term. Expected to be a string.
	 *
	 * 'is_review_required'. Expected to be a boolean. Default is false.
	 *
	 * @param int|string $term The status to add or update
	 * @param array|string $args Change the values of the inserted term
	 *
	 * @return object|WP_Error $inserted_term The newly inserted term object or a WP_Error object
	 */
	public function add_custom_status( array $args ): WP_Term|WP_Error {
		if ( ! isset( $args['position'] ) ) {
			// get the existing statuses, ordered by position
			$custom_statuses = $this->get_custom_statuses();

			// get the last status position
			$last_position = $custom_statuses[ array_key_last( $custom_statuses ) ]->meta[ self::METADATA_POSITION_KEY ];

			// set the new status position to be one more than the last status
			$args['position'] = $last_position + 1;
		}

		$term_to_save = [
			'slug'        => $args['slug'] ?? sanitize_title( $args['name'] ),
			'description' => $args['description'] ?? '',
		];

		$term_name = $args['name'];

		$inserted_term = wp_insert_term( $term_name, self::TAXONOMY_KEY, $term_to_save );

		if ( is_wp_error( $inserted_term ) ) {
			return $inserted_term;
		}

		// Reset our internal object cache
		$this->custom_statuses_cache = [];

		$term_id = $inserted_term['term_id'];

		$position = $args['position'];
		$required_metadata_fields = $args['required_metadata_fields'] ?? [];

		$position_meta_result = add_term_meta( $term_id, self::METADATA_POSITION_KEY, $position );
		if ( is_wp_error( $position_meta_result ) ) {
			return $position_meta_result;
		} else if ( ! $position_meta_result ) {
			// If we can't save the type, we should delete the term
			wp_delete_term( $term_id, self::TAXONOMY_KEY );
			return new WP_Error( 'invalid', __( 'Unable to create custom status.', 'vip-workflow' ) );
		}

		$req_postmeta_fields_meta_result = add_term_meta( $term_id, self::METADATA_REQ_EDITORIAL_FIELDS_KEY, $required_metadata_fields );
		if ( is_wp_error( $req_postmeta_fields_meta_result ) ) {
			return $req_postmeta_fields_meta_result;
		} else if ( ! $req_postmeta_fields_meta_result ) {
			// If we can't save the postmeta key, we should delete the term
			delete_term_meta( $term_id, self::METADATA_POSITION_KEY );
			wp_delete_term( $term_id, self::TAXONOMY_KEY );
			return new WP_Error( 'invalid', __( 'Unable to create editorial metadata.', 'vip-workflow' ) );
		}

		$term_result = $this->get_custom_status_by( 'id', $term_id );

		return $term_result;
	}

	/**
	 * Update an existing custom status
	 *
	 * @param int @status_id ID for the status
	 * @param array $args Any arguments to be updated
	 * @return object $updated_status Newly updated status object
	 */
	public function update_custom_status( int $status_id, array $args = [] ): WP_Term|WP_Error {
		$old_status = $this->get_custom_status_by( 'id', $status_id );
		if ( is_wp_error( $old_status ) ) {
			return $old_status;
		} else if ( ! $old_status ) {
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

		$term_fields_to_update = [
			'name'    => isset( $args['name'] ) ? $args['name'] : $old_status->name,
			'slug'    => isset( $args['slug'] ) ? $args['slug'] : $old_status->slug,
			'description' => isset( $args['description'] ) ? $args['description'] : $old_status->description,
		];

		// Update the metadata first, as if it fails we don't want to update the term

		if ( isset( $args['position'] ) ) {
			$position_meta_result = update_term_meta( $status_id, self::METADATA_POSITION_KEY, $args['position'] );
			if ( is_wp_error( $position_meta_result ) ) {
				return $position_meta_result;
			}
		}

		if ( isset( $args['required_metadata_fields'] ) ) {
			$req_postmeta_fields_meta_result = update_term_meta( $status_id, self::METADATA_REQ_EDITORIAL_FIELDS_KEY, $args['required_metadata_fields'] );
			if ( is_wp_error( $req_postmeta_fields_meta_result ) ) {
				return $req_postmeta_fields_meta_result;
			}
		}

		$updated_term = wp_update_term( $status_id, self::TAXONOMY_KEY, $term_fields_to_update );

		// Reset status cache again, as reassign_post_status() will recache prior statuses
		$this->custom_statuses_cache = [];

		if ( is_wp_error( $updated_term ) ) {
			return $updated_term;
		}

		$status_result = $this->get_custom_status_by( 'id', $status_id );

		return $status_result;
	}

	/**
	 * Deletes a custom status from the wp_terms table.
	 *
	 * Partly a wrapper for the wp_delete_term function.
	 * BUT, also reassigns posts that currently have the deleted status assigned.
	 */
	public function delete_custom_status( int $status_id ): bool|WP_Error {
		// Get slug for the old status
		$old_status = $this->get_custom_status_by( 'id', $status_id );
		if ( ! $old_status ) {
			return new WP_Error( 'invalid', __( "Custom status doesn't exist.", 'vip-workflow' ) );
		} else if ( ! $old_status ) {
			return new WP_Error( 'invalid', __( "Custom status doesn't exist.", 'vip-workflow' ) );
		}

		$old_status_slug = $old_status->slug;

		if ( $this->is_restricted_status( $old_status_slug ) || 'draft' === $old_status_slug ) {
			return new WP_Error( 'restricted', __( 'Restricted status ', 'vip-workflow' ) . '(' . $old_status->name . ')' );
		}

		// Reset our internal object cache
		$this->custom_statuses_cache = [];

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

		delete_term_meta( $status_id, self::METADATA_POSITION_KEY );

		delete_term_meta( $status_id, self::METADATA_REQ_EDITORIAL_FIELDS_KEY );

		$result = wp_delete_term( $status_id, self::TAXONOMY_KEY );
		if ( ! $result ) {
			return new WP_Error( 'invalid', __( 'Unable to delete custom status.', 'vip-workflow' ) );
		}

		// Reset status cache again, as reassign_post_status() will recache prior statuses
		$this->custom_statuses_cache = [];

		// Re-order the positions after deletion
		$custom_statuses = $this->get_custom_statuses();

		// ToDo: Optimize this to only work on the next or previous item.
		$current_postition = 1;

		// save each status with the new position
		foreach ( $custom_statuses as $status ) {
			$this->update_custom_status( $status->term_id, [ 'position' => $current_postition ] );

			++$current_postition;
		}

		return $result;
	}

	/**
	 * Get all custom statuses as an ordered array
	 *
	 * @param array|string $statuses
	 * @return array $statuses All of the statuses
	 */
	public function get_custom_statuses(): array {
		if ( $this->disable_custom_statuses_for_post_type() ) {
			return $this->get_core_post_statuses();
		}

		// Internal object cache for repeat requests
		if ( ! empty( $this->custom_statuses_cache ) ) {
			return $this->custom_statuses_cache;
		}

		$statuses = get_terms( [
			'taxonomy'   => self::TAXONOMY_KEY,
			'hide_empty' => false,
			'orderby' => 'meta_value_num',
			'order' => 'ASC',
			'meta_key' => self::METADATA_POSITION_KEY,
		]);

		if ( is_wp_error( $statuses ) || empty( $statuses ) ) {
			$statuses = [];
		}

		$statuses = array_map( [ $this, 'add_metadata_to_term' ], $statuses );

		// Set the internal object cache
		$this->custom_statuses_cache = $statuses;

		return $statuses;
	}

	/**
	 * Returns the a single status object based on ID, title, or slug
	 *
	 * @param string $field The field to search by
	 * @param int|string $value The value to search for
	 * @return WP_Term|false $status The object for the matching status
	 */
	public function get_custom_status_by( string $field, int|string $value ): WP_Term|false {
		// We only support id, slug and name for lookup.
		if ( ! in_array( $field, [ 'id', 'slug', 'name' ] ) ) {
			return false;
		}

		$custom_status = false;

		if ( 'id' === $field ) {
			$custom_status = get_term( $value, self::TAXONOMY_KEY );
		} else {
			$custom_status = get_term_by( $field, $value, self::TAXONOMY_KEY );
		}

		if ( is_wp_error( $custom_status ) || ! $custom_status ) {
			$custom_status = false;
		} else {
			$custom_status = $this->add_metadata_to_term( $custom_status );
		}

		return $custom_status;
	}

	/**
	 * Assign new statuses to posts using value provided. Returns true if successful, or a WP_Error describing an error otherwise.
	 *
	 * @param string $old_status Slug for the old status
	 * @param string $new_status Slug for the new status
	 * @return true|WP_Error
	 */
	public function reassign_post_status( string $old_status, string $new_status ): bool|WP_Error {
		$old_status_post_ids = ( new WP_Query( [
			'post_type'      => VIP_Workflow::instance()->get_supported_post_types(),
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
	public function add_status_to_post_states( array $post_states, WP_Post $post ) {
		if ( ! in_array( $post->post_type, VIP_Workflow::instance()->get_supported_post_types(), true ) ) {
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
	 * Primary configuration page for custom status class, which is also the main entry point for configuring the plugin
	 */
	public function render_settings_view() {
		include_once __DIR__ . '/views/manage-workflow.php';
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

		if ( ! in_array( $post->post_status, $status_slugs ) || ! in_array( $post->post_type, VIP_Workflow::instance()->get_supported_post_types() ) ) {
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
	 * Given a post ID, return true if the post type is supported and using a custom status, false otherwise.
	 *
	 * @param int $post_id The post ID being queried.
	 * @return bool True if the post is using a custom status, false otherwise.
	 */
	public function is_post_using_custom_status( $post_id ) {
		$post = get_post( $post_id );

		if ( null === $post ) {
			return false;
		}

		$custom_post_types = VIP_Workflow::instance()->get_supported_post_types();
		$custom_statuses   = $this->get_custom_statuses();
		$status_slugs      = wp_list_pluck( $custom_statuses, 'slug' );

		return in_array( $post->post_type, $custom_post_types ) && in_array( $post->post_status, $status_slugs );
	}

	// Hacks for custom statuses to work with core

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
		|| ! in_array( $data['post_type'], VIP_Workflow::instance()->get_supported_post_types() ) ) {
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
		|| ! in_array( $post_type, VIP_Workflow::instance()->get_supported_post_types() ) ) {
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
		|| ! in_array( $post->post_type, VIP_Workflow::instance()->get_supported_post_types() )
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
		if ( ! in_array( $post->post_type, VIP_Workflow::instance()->get_supported_post_types() ) ) {
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
		|| ! in_array( $post->post_type, VIP_Workflow::instance()->get_supported_post_types() ) ) {
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
		|| ! in_array( $post->post_type, VIP_Workflow::instance()->get_supported_post_types() ) ) {
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
		|| ! in_array( $post->post_type, VIP_Workflow::instance()->get_supported_post_types() ) ) {
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
}

