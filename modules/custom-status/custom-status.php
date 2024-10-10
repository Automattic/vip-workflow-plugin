<?php
/**
 * class CustomStatus
 * Custom statuses make it simple to define the different stages in your publishing workflow.
 */

namespace VIPWorkflow\Modules;

// REST endpoints
require_once __DIR__ . '/rest/custom-status-endpoint.php';

// Term meta
require_once __DIR__ . '/meta/required-user-id-handler.php';
require_once __DIR__ . '/meta/required-metadata-id-handler.php';
require_once __DIR__ . '/meta/position-handler.php';

use VIPWorkflow\Modules\Shared\PHP\InstallUtilities;
use VIPWorkflow\Modules\Shared\PHP\OptionsUtilities;
use VIPWorkflow\Modules\CustomStatus\REST\CustomStatusEndpoint;
use VIPWorkflow\Modules\Shared\PHP\HelperUtilities;

use WP_Error;
use WP_Query;
use WP_Term;
use WP_Post;

class CustomStatus {

	// This is taxonomy name used to store all our custom statuses
	const TAXONOMY_KEY = 'vw_post_status';

	const SETTINGS_SLUG = 'vw-custom-status';

	// The metadata keys for the custom status term
	const METADATA_POSITION_KEY = 'position';
	const METADATA_REQ_EDITORIAL_IDS_KEY = 'required_metadata_ids';
	const METADATA_REQ_EDITORIALS_KEY = 'required_metadatas';
	const METADATA_REQ_USER_IDS_KEY = 'required_user_ids';
	const METADATA_REQ_USERS_KEY = 'required_users';

	private static $custom_statuses_cache = [];

	public static function init(): void {
		// Register the taxonomy we use with WordPress core, and ensure it's registered after editorial metadata
		add_action( 'init', [ __CLASS__, 'register_custom_status_taxonomy' ] );

		// Register the custom statuses in core
		add_action( 'init', [ __CLASS__, 'register_custom_statuses' ] );

		// Setup custom statuses on first install
		add_action( 'init', [ __CLASS__, 'setup_install' ] );

		// Register our settings
		if ( ! HelperUtilities::is_current_post_type_unsupported() ) {
			// Load CSS and JS resources for the admin page
			add_action( 'admin_enqueue_scripts', [ __CLASS__, 'action_admin_enqueue_scripts' ] );

			// Assets for block editor UI.
			add_action( 'enqueue_block_editor_assets', [ __CLASS__, 'load_scripts_for_block_editor' ] );

			// Assets for iframed block editor and editor UI.
			add_action( 'enqueue_block_editor_assets', [ __CLASS__, 'load_styles_for_block_editor' ] );
		}

		add_action( 'admin_print_scripts', [ __CLASS__, 'post_admin_header' ] );

		// Add custom statuses to the post states.
		add_filter( 'display_post_states', [ __CLASS__, 'add_status_to_post_states' ], 10, 2 );

		// Register sidebar menu
		add_action( 'admin_menu', [ __CLASS__, 'add_admin_menu' ], 6 /* Prior to default registration of sub-pages */ );

		// Add server-side controls to block post status movements that are prohihibited by workflow rules
		add_filter( 'wp_insert_post_data', [ __CLASS__, 'maybe_block_post_update' ], 1000, 4 );
		add_filter( 'user_has_cap', [ __CLASS__, 'remove_or_add_publish_capability_for_user' ], 10, 3 );
	}

	/**
	 * Register the post metadata taxonomy
	 *
	 * @access private
	 */
	public static function register_custom_status_taxonomy(): void {
		// We need to make sure taxonomy is registered for all of the post types that support it
		$supported_post_types = HelperUtilities::get_supported_post_types();

		register_taxonomy( self::TAXONOMY_KEY, $supported_post_types,
			[
				'hierarchical'          => false,
				'update_count_callback' => '_update_post_term_count',
				'label'                 => false,
				'query_var'             => false,
				'rewrite'               => false,
				'show_ui'               => false,
			]
		);
	}

	/**
	 * Makes the call to register_post_status to register the user's custom statuses.
	 * Also unregisters pending, in case the user doesn't want them.
	 */
	public static function register_custom_statuses(): void {
		global $wp_post_statuses;

		// This will ensure that if the names of the pending and draft statuses are changed, they will
		// be registered as the new names, rather than the old ones.
		// They cannot be deleted, so this is only for that.
		unset( $wp_post_statuses['draft'] );
		unset( $wp_post_statuses['pending'] );

		$custom_statuses = self::get_custom_statuses();

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
				'date_floating'             => true,
			] );
		}
	}


	/**
	 * Load default custom statuses the first time the module is loaded
	 *
	 * @access private
	 */
	public static function setup_install(): void {
		InstallUtilities::install_if_first_run( self::SETTINGS_SLUG, function () {
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

			// Add the custom statuses if the slugs don't conflict
			foreach ( $default_terms as $term ) {
				if ( ! term_exists( $term['slug'], self::TAXONOMY_KEY ) ) {
					self::add_custom_status( $term );
				}
			}
		});
	}

	/**
	 * Register admin sidebar menu
	 *
	 * @access private
	 */
	public static function add_admin_menu(): void {
		$menu_title = __( 'VIP Workflow', 'vip-workflow' );

		add_menu_page( $menu_title, $menu_title, 'manage_options', self::SETTINGS_SLUG, [ __CLASS__, 'render_settings_view' ] );
	}

	/**
	 * Primary configuration page for custom status class, which is also the main entry point for configuring the plugin
	 */
	public static function render_settings_view(): void {
		include_once __DIR__ . '/views/manage-workflow.php';
	}

	/**
	 * Enqueue resources that we need in the admin settings page
	 *
	 * @access private
	 */
	public static function action_admin_enqueue_scripts(): void {
		// Load Javascript we need to use on the configuration views
		if ( HelperUtilities::is_settings_view_loaded( self::SETTINGS_SLUG ) ) {
			$asset_file = include VIP_WORKFLOW_ROOT . '/dist/modules/custom-status/custom-status-configure.asset.php';
			wp_enqueue_script( 'vip-workflow-custom-status-configure', VIP_WORKFLOW_URL . 'dist/modules/custom-status/custom-status-configure.js', $asset_file['dependencies'], $asset_file['version'], true );
			wp_enqueue_style( 'vip-workflow-custom-status-styles', VIP_WORKFLOW_URL . 'dist/modules/custom-status/custom-status-configure.css', [ 'wp-components' ], $asset_file['version'] );

			wp_localize_script( 'vip-workflow-custom-status-configure', 'VW_CUSTOM_STATUS_CONFIGURE', [
				'custom_statuses'    => self::modify_custom_statuses_with_editorial_metadata(),
				'editorial_metadatas' => EditorialMetadata::get_editorial_metadata_terms(),
				'url_edit_status'    => CustomStatusEndpoint::get_crud_url(),
				'url_reorder_status' => CustomStatusEndpoint::get_reorder_url(),
			] );
		}

		// Custom javascript to modify the post status dropdown where it shows up
		if ( self::is_whitelisted_page() ) {
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

	/**
	 * Enqueue resources that we need in the admin settings page
	 *
	 * @access private
	 */
	public static function load_scripts_for_block_editor(): void {
		$asset_file = include VIP_WORKFLOW_ROOT . '/dist/modules/custom-status/custom-status-block.asset.php';
		wp_enqueue_script( 'vip-workflow-block-custom-status-script', VIP_WORKFLOW_URL . 'dist/modules/custom-status/custom-status-block.js', $asset_file['dependencies'], $asset_file['version'], true );

		$publish_guard_enabled = ( 'on' === OptionsUtilities::get_options_by_key( 'publish_guard' ) ) ? true : false;

		wp_localize_script( 'vip-workflow-block-custom-status-script', 'VW_CUSTOM_STATUSES', [
			'current_user_id'          => get_current_user_id(),
			'is_publish_guard_enabled' => $publish_guard_enabled,
			'status_terms'             => self::modify_custom_statuses_with_editorial_metadata(),
			'supported_post_types'     => HelperUtilities::get_supported_post_types(),
		] );
	}

	/**
	 * Modify the custom statuses to include the editorial metadatas for UI purposes.
	 *
	 * This isn't done anywhere else due to the taxonomies being registered at different times.
	 * In addition, registering the taxonomies in the wrong order can cause the manage posts page to break
	 * as well as the default status for a post itself.
	 *
	 * @return array $custom_statuses The custom statuses with the editorial metadatas included
	 */
	private static function modify_custom_statuses_with_editorial_metadata(): array {
		// map the editorial metadatas to their respective term_id so the term_id can be used to get the full object quickly.
		$editorial_metadatas = EditorialMetadata::get_editorial_metadata_terms();
		$editorial_metadatas = array_combine( array_column( $editorial_metadatas, 'term_id' ), $editorial_metadatas );

		$custom_statuses = self::get_custom_statuses();

		// Add the required editorial metadata to the custom statuses for UI purposes
		foreach ( $custom_statuses as $status ) {
			$required_metadata_ids = $status->meta[ self::METADATA_REQ_EDITORIAL_IDS_KEY ] ?? [];
			$required_metadatas = [];
			foreach ( $required_metadata_ids as $metadata_id ) {
				$required_metadatas[] = $editorial_metadatas[ $metadata_id ];
			}
			$status->meta[ self::METADATA_REQ_EDITORIALS_KEY ] = $required_metadatas;
		}

		return $custom_statuses;
	}

	/**
	 * Enqueue resources that we need in the block editor
	 *
	 * @access private
	 */
	public static function load_styles_for_block_editor(): void {
		$asset_file = include VIP_WORKFLOW_ROOT . '/dist/modules/custom-status/custom-status-block.asset.php';

		wp_enqueue_style( 'vip-workflow-custom-status-styles', VIP_WORKFLOW_URL . 'dist/modules/custom-status/custom-status-block.css', [], $asset_file['version'] );
	}

	/**
	 * Check whether custom status stuff should be loaded on this page
	 */
	public static function is_whitelisted_page(): bool {
		global $pagenow;

		$current_post_type = HelperUtilities::get_current_post_type();

		if ( ! in_array( $current_post_type, HelperUtilities::get_supported_post_types() ) ) {
			return false;
		}

		$post_type_obj = get_post_type_object( $current_post_type );

		if ( ! current_user_can( $post_type_obj->cap->edit_posts ) ) {
			return false;
		}

		// Only add the script to Edit Post and Edit Page pages -- don't want to bog down the rest of the admin with unnecessary javascript
		return in_array( $pagenow, [ 'post.php', 'edit.php', 'post-new.php', 'page.php', 'edit-pages.php', 'page-new.php' ] );
	}

	/**
	 * Adds all necessary javascripts to make custom statuses work
	 */
	public static function post_admin_header(): void {
		global $post, $pagenow;

		if ( HelperUtilities::is_current_post_type_unsupported() ) {
			return;
		}

		// Set the current user, so we can check if they can publish posts
		wp_get_current_user();

		// Only add the script to Edit Post and Edit Page pages -- don't want to bog down the rest of the admin with unnecessary javascript
		if ( self::is_whitelisted_page() ) {

			$custom_statuses = self::get_custom_statuses();

			// $selected can be empty, but must be set because it's used as a JS variable
			$selected      = '';

			if ( ! empty( $post ) ) {
				// Get the status of the current post
				if ( 0 === $post->ID || 'auto-draft' === $post->post_status || 'edit.php' === $pagenow ) {
					$selected = $custom_statuses[0]->slug;
				} else {
					$selected = $post->post_status;
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

			$post_type_obj = get_post_type_object( HelperUtilities::get_current_post_type() );

			// Now, let's print the JS vars
			?>
				<script type="text/javascript">
					var custom_statuses = <?php echo json_encode( $all_statuses ); ?>;
					var current_status = '<?php echo esc_js( $selected ); ?>';
					var current_user_can_publish_posts = <?php echo current_user_can( $post_type_obj->cap->publish_posts ) ? 1 : 0; ?>;
				</script>
			<?php
		}
	}

	/**
	 * Remove the ability to transition a post if custom status requires review from a nonpresent user
	 *
	 * @param array $data Post data submitted for update
	 *
	 * @return array $allcaps All capabilities for the user
	 */
	public static function maybe_block_post_update( array $data ): array|bool {
		$status_slugs = wp_list_pluck( self::get_custom_statuses(), 'slug' );

		// Ignore if it's not a post status and post type we support
		if ( ! in_array( $data['post_type'], HelperUtilities::get_supported_post_types() ) ) {
			return $data;
		}

		$new_post_status   = $data['post_status'];
		$post_status_index = array_search( $new_post_status, $status_slugs, /* strict */ true );

		if ( false === $post_status_index ) {
			// This is not a supported custom status
			return $data;
		}

		$prior_post_status_slug = $post_status_index > 0 ? $status_slugs[ $post_status_index - 1 ] : false;

		if ( false === $prior_post_status_slug ) {
			// This is the first custom status, so we don't need to block it
			return $data;
		}

		$prior_post_status = self::get_custom_status_by( 'slug', $prior_post_status_slug );
		$required_user_ids = $prior_post_status->meta[ self::METADATA_REQ_USER_IDS_KEY ] ?? [];

		if ( $required_user_ids && ! in_array( get_current_user_id(), $required_user_ids, true ) ) {
			// This status requires review, and the current user is not permitted to transition the post.
			// Return an empty array, which will cause the update to fail with an error.
			return false;
		}

		return $data;
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
	public static function remove_or_add_publish_capability_for_user( array $allcaps, array $cap, array $args ): array {
		global $post;

		$supported_publish_caps_map = [
			'post' => 'publish_posts',
			'page' => 'publish_pages',
		];

		// Bail early if publish guard is off, or the post is already published, or the post is not available
		if ( ! $post || 'off' === OptionsUtilities::get_options_by_key( 'publish_guard' ) || 'publish' === $post->post_status ) {
			return $allcaps;
		}

		// Bail early if the post type is not supported or if its a not supported capability for this guard
		if ( ! in_array( $post->post_type, HelperUtilities::get_supported_post_types() ) || ! isset( $supported_publish_caps_map[ $post->post_type ] ) ) {
			return $allcaps;
		}

		// Bail early if the publish_{post_type} capability is not being checked or if the user doesn't have the capability set
		$cap_to_check = $supported_publish_caps_map[ $post->post_type ];
		if ( $cap_to_check !== $args[0] || ! isset( $allcaps[ $cap_to_check ] ) ) {
			return $allcaps;
		}

		$custom_statuses = self::get_custom_statuses();
		$status_slugs    = wp_list_pluck( $custom_statuses, 'slug' );

		// Bail early if the post is not using a custom status
		if ( ! in_array( $post->post_status, $status_slugs ) ) {
			return $allcaps;
		}

		$status_before_publish = $custom_statuses[ array_key_last( $custom_statuses ) ];

		// Ensure publishing is disabled for all but the last status
		if ( $status_before_publish->slug !== $post->post_status ) {
			$allcaps[ $supported_publish_caps_map[ $post->post_type ] ] = false;
		}

		return $allcaps;
	}

	/**
	 * Perform the necessary data cleanup when an error occurs while saving the custom status,
	 * and generate a WP_Error object.
	 *
	 * @param integer $term_id The ID of the term that failed to save
	 * @param string $meta The metadata that failed to save
	 * @return WP_Error The WP_Error object
	 */
	private static function generate_error_and_delete_bad_data( int $term_id, string $meta ): WP_Error {
		// Trigger the deletion of the metadata associated with the status
		do_action( 'vw_delete_custom_status_meta', $term_id );
		wp_delete_term( $term_id, self::TAXONOMY_KEY );

		/* translators: %s: meta key that failed to save */
		return new WP_Error( 'invalid', sprintf( __( 'Unable to create the custom status, as the %s failed to save.', 'vip-workflow' ), $meta ) );
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
	 * 'required_user_ids'. An optional array of user IDs that are required to review the post in the current status.
	 *
	 * @param int|string $term The status to add or update
	 * @param array|string $args Change the values of the inserted term
	 *
	 * @return object|WP_Error $inserted_term The newly inserted term object or a WP_Error object
	 */
	public static function add_custom_status( array $args ): WP_Term|WP_Error {
		$term_to_save = [
			'slug'        => $args['slug'] ?? sanitize_title( $args['name'] ),
			'description' => $args['description'] ?? '',
		];

		// Check to make sure the slug is not restricted
		if ( self::is_restricted_status( $term_to_save['slug'] ) ) {
			return new WP_Error( 'invalid', 'Status name is restricted. Please chose another name.' );
		}

		if ( ! isset( $args['position'] ) ) {
			// get the existing statuses, ordered by position
			$custom_statuses = self::get_custom_statuses();

			// get the last status position
			$last_position = $custom_statuses[ array_key_last( $custom_statuses ) ]->meta[ self::METADATA_POSITION_KEY ];

			// set the new status position to be one more than the last status
			$args['position'] = $last_position + 1;
		}

		$term_name = $args['name'];

		$inserted_term = wp_insert_term( $term_name, self::TAXONOMY_KEY, $term_to_save );

		if ( is_wp_error( $inserted_term ) ) {
			return $inserted_term;
		}

		// Reset our internal object cache
		self::$custom_statuses_cache = [];

		$term_id = $inserted_term['term_id'];

		$position = $args[ self::METADATA_POSITION_KEY ];
		$required_metadata_ids = $args[ self::METADATA_REQ_EDITORIAL_IDS_KEY ] ?? [];
		$required_user_ids = $args[ self::METADATA_REQ_USER_IDS_KEY ] ?? [];

		// In case of failure, data cleanup happens which includes the term and the meta keys.

		$position_meta_result = update_term_meta( $term_id, self::METADATA_POSITION_KEY, $position );
		if ( is_wp_error( $position_meta_result ) ) {
			return self::generate_error_and_delete_bad_data( $term_id, 'position' );
		}

		$required_metadata_ids_result = update_term_meta( $term_id, self::METADATA_REQ_EDITORIAL_IDS_KEY, $required_metadata_ids );
		if ( is_wp_error( $required_metadata_ids_result ) ) {
			return self::generate_error_and_delete_bad_data( $term_id, 'required editorial metadata fields' );
		}

		$required_user_ids_result = update_term_meta( $term_id, self::METADATA_REQ_USER_IDS_KEY, $required_user_ids );
		if ( is_wp_error( $required_user_ids_result ) ) {
			return self::generate_error_and_delete_bad_data( $term_id, 'required users' );
		}

		$term_result = self::get_custom_status_by( 'id', $term_id );

		return $term_result;
	}

	/**
	 * Update an existing custom status
	 *
	 * @param int @status_id ID for the status
	 * @param array $args Any arguments to be updated
	 * @return object $updated_status Newly updated status object
	 */
	public static function update_custom_status( int $status_id, array $args = [] ): WP_Term|WP_Error {
		$old_status = self::get_custom_status_by( 'id', $status_id );
		if ( is_wp_error( $old_status ) ) {
			return $old_status;
		} else if ( ! $old_status ) {
			return new WP_Error( 'invalid', __( "Custom status doesn't exist.", 'vip-workflow' ) );
		}

		// Reset our internal object cache
		self::$custom_statuses_cache = [];

		// If the name was changed, we need to change the slug unless its banned from slug updates
		if ( isset( $args['name'] ) && $args['name'] !== $old_status->name && ! self::is_status_banned_from_slug_changes( $old_status->slug ) ) {
			$args['slug'] = sanitize_title( $args['name'] );
		}

		// Check to make sure the slug is not restricted
		if ( isset( $args['slug'] ) && self::is_restricted_status( $args['slug'] ) ) {
			return new WP_Error( 'invalid', 'Status name is restricted. Please chose another name.' );
		}

		// If the status is banned from updates, we shouldn't allow the user to change the slug
		if ( self::is_status_banned_from_slug_changes( $old_status->slug ) && isset( $args['slug'] ) ) {
			unset( $args['slug'] );
		}

		// Reassign posts to new status slug if the slug changed
		if ( isset( $args['slug'] ) && $args['slug'] != $old_status->slug ) {
			$new_status        = $args['slug'];
			$reassigned_result = self::reassign_post_status( $old_status->slug, $new_status );
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

		if ( isset( $args[ self::METADATA_POSITION_KEY ] ) ) {
			$position_meta_result = update_term_meta( $status_id, self::METADATA_POSITION_KEY, $args[ self::METADATA_POSITION_KEY ] );
			if ( is_wp_error( $position_meta_result ) ) {
				return new WP_Error( 'invalid', __( 'Unable to update custom status, as the position failed to save.', 'vip-workflow' ) );
			}
		}

		if ( isset( $args[ self::METADATA_REQ_EDITORIAL_IDS_KEY ] ) ) {
			$required_metadata_ids_result = update_term_meta( $status_id, self::METADATA_REQ_EDITORIAL_IDS_KEY, $args[ self::METADATA_REQ_EDITORIAL_IDS_KEY ] );
			if ( is_wp_error( $required_metadata_ids_result ) ) {
				return new WP_Error( 'invalid', __( 'Unable to update custom status, as the required editorial metadata fields failed to save.', 'vip-workflow' ) );
			}
		}

		if ( isset( $args[ self::METADATA_REQ_USER_IDS_KEY ] ) ) {
			$req_user_ids_meta_result = update_term_meta( $status_id, self::METADATA_REQ_USER_IDS_KEY, $args[ self::METADATA_REQ_USER_IDS_KEY ] );
			if ( is_wp_error( $req_user_ids_meta_result ) ) {
				return new WP_Error( 'invalid', __( 'Unable to update custom status, as the required users failed to save.', 'vip-workflow' ) );
			}
		}

		$updated_term = wp_update_term( $status_id, self::TAXONOMY_KEY, $term_fields_to_update );

		// Reset status cache again, as reassign_post_status() will recache prior statuses
		self::$custom_statuses_cache = [];

		if ( is_wp_error( $updated_term ) ) {
			return $updated_term;
		}

		$status_result = self::get_custom_status_by( 'id', $status_id );

		return $status_result;
	}

	/**
	 * Deletes a custom status from the wp_terms table.
	 *
	 * Partly a wrapper for the wp_delete_term function.
	 * BUT, also reassigns posts that currently have the deleted status assigned.
	 */
	public static function delete_custom_status( int $status_id ): bool|WP_Error {
		// Get slug for the old status
		$old_status = self::get_custom_status_by( 'id', $status_id );
		if ( is_wp_error( $old_status ) ) {
			return $old_status;
		} else if ( ! $old_status ) {
			return new WP_Error( 'invalid', __( "Custom status doesn't exist.", 'vip-workflow' ) );
		}

		$old_status_slug = $old_status->slug;

		if ( self::is_restricted_status( $old_status_slug ) && ! self::is_status_banned_from_slug_changes( $old_status_slug ) ) {
			// translators: %s: Post status, like "Draft"
			return new WP_Error( 'restricted', sprintf( __( 'Restricted status (%s) cannot be deleted.', 'vip-workflow' ), $old_status->name ) );
		}

		// Reset our internal object cache
		self::$custom_statuses_cache = [];

		// Get the new status to reassign posts to, which would be the first custom status.
		// In the event that the first custom status is being deleted, we'll reassign to the second custom status.
		// Since draft and pending review cannot be deleted, we don't need to worry about ever getting index out of bounds.
		$custom_statuses = self::get_custom_statuses();
		$new_status_slug = $custom_statuses[0]->slug;
		if ( $old_status_slug === $new_status_slug ) {
			$new_status_slug = $custom_statuses[1]->slug;
		}

		$reassigned_result = self::reassign_post_status( $old_status_slug, $new_status_slug );
		// If the reassignment failed, return the error
		if ( is_wp_error( $reassigned_result ) ) {
			return $reassigned_result;
		}

		// Trigger the deletion of the metadata associated with the status
		do_action( 'vw_delete_custom_status_meta', $status_id );

		$result = wp_delete_term( $status_id, self::TAXONOMY_KEY );
		if ( ! $result ) {
			return new WP_Error( 'invalid', __( 'Unable to delete custom status.', 'vip-workflow' ) );
		}

		// Reset status cache again, as reassign_post_status() will recache prior statuses
		self::$custom_statuses_cache = [];

		// Re-order the positions after deletion
		$custom_statuses = self::get_custom_statuses();

		$current_postition = 1;

		// save each status with the new position
		foreach ( $custom_statuses as $status ) {
			self::update_custom_status( $status->term_id, [ 'position' => $current_postition ] );

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
	public static function get_custom_statuses(): array {
		if ( HelperUtilities::is_current_post_type_unsupported() ) {
			return self::get_core_statuses();
		}

		// Internal object cache for repeat requests
		if ( ! empty( self::$custom_statuses_cache ) ) {
			return self::$custom_statuses_cache;
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

		// Add metadata to each term
		$statuses = array_map( function ( $status ) {
			$term_meta = apply_filters( 'vw_register_custom_status_meta', [], $status );
			$status->meta = $term_meta;

			return $status;
		}, $statuses );

		// Set the internal object cache
		self::$custom_statuses_cache = $statuses;

		return $statuses;
	}

	/**
	 * Returns the a single status object based on ID, title, or slug
	 *
	 * @param string $field The field to search by
	 * @param int|string $value The value to search for
	 * @param bool $include_metadata Whether to include the metadata in the returned status. Useful to avoid unnecessary queries, when just an existence check is needed.
	 * @return WP_Term|false $status The object for the matching status
	 */
	public static function get_custom_status_by( string $field, int|string $value, $include_metadata = true ): WP_Term|false {
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
		} else if ( $include_metadata ) {
			$term_meta = apply_filters( 'vw_register_custom_status_meta', [], $custom_status );
			$custom_status->meta = $term_meta;
		}

		return $custom_status;
	}

	/**
	 * Get the core statuses that are used when the post type is unsupported
	 *
	 * Note: This is necessary because a new post does not have a post type available, and that causes a whole host of problems.
	 *
	 * @return array $default_terms The default statuses
	 */
	public static function get_core_statuses(): array {
		$default_terms = [
			[
				'name'        => __( 'Draft', 'vip-workflow' ),
				'slug'        => 'draft',
				'description' => __( 'Post is a draft; not ready for review or publication.', 'vip-workflow' ),
			],
			[
				'name'               => __( 'Pending Review' ),
				'slug'               => 'pending',
				'description'        => __( 'Post needs to be reviewed by an editor.', 'vip-workflow' ),
			],
		];

		return $default_terms;
	}

	/**
	 * Assign new statuses to posts using value provided. Returns true if successful, or a WP_Error describing an error otherwise.
	 *
	 * @param string $old_status Slug for the old status
	 * @param string $new_status Slug for the new status
	 * @return true|WP_Error
	 */
	public static function reassign_post_status( string $old_status, string $new_status ): bool|WP_Error {
		$old_status_post_ids = ( new WP_Query( [
			'post_type'      => HelperUtilities::get_supported_post_types(),
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
	public static function add_status_to_post_states( array $post_states, WP_Post $post ): array {
		if ( ! in_array( $post->post_type, HelperUtilities::get_supported_post_types(), true ) ) {
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
	 * Determines whether the slug indicated belongs to a restricted status or not.
	 *
	 * It's restricted from changes, and these statuses are not allowed to be created at all.
	 *
	 * @param string $slug Slug of the status
	 * @return bool $restricted True if restricted, false if not
	 */
	public static function is_restricted_status( string $slug ): bool {
		$restricted_statuses = [ 'publish', 'private', 'future', 'new', 'inherit', 'auto-draft', 'trash' ];

		return in_array( $slug, $restricted_statuses, true );
	}

	/**
	 * Determines whether the slug indicated belongs to a status that is banned from updates to its slug.
	 *
	 * @param string $slug
	 * @return boolean $banned True if banned, false if not
	 */
	public static function is_status_banned_from_slug_changes( string $slug ): bool {
		$banned_statuses = [ 'draft', 'pending' ];

		return in_array( $slug, $banned_statuses, true );
	}

	/**
	 * Given a post ID, return true if the post type is supported and using a custom status, false otherwise.
	 *
	 * @param int $post_id The post ID being queried.
	 * @return bool True if the post is using a custom status, false otherwise.
	 */
	public static function is_post_using_custom_status( int $post_id ): bool {
		$post = get_post( $post_id );

		if ( null === $post ) {
			return false;
		}

		$custom_post_types = HelperUtilities::get_supported_post_types();
		$custom_statuses   = self::get_custom_statuses();
		$status_slugs      = wp_list_pluck( $custom_statuses, 'slug' );

		return in_array( $post->post_type, $custom_post_types ) && in_array( $post->post_status, $status_slugs );
	}
}

CustomStatus::init();
