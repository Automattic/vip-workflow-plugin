<?php
/**
 * class Module
 *
 * @desc Base class any module should extend
 */

namespace VIPWorkflow\Common\PHP;

class Module {

	public $published_statuses = array(
		'publish',
		'future',
		'private',
	);

	public $module_url;

	public $module;

	public function __construct() {}

	/**
	 * Returns whether analytics has been enabled or not.
	 *
	 * It's only enabled if the site is a production WPVIP site.
	 *
	 * @return true, if analytics is enabled, false otherwise
	 */
	public function is_analytics_enabled() {
		// Check if the site is a production WPVIP site and only then enable it
		$is_analytics_enabled = $this->is_vip_site( true );

		// filter to disable it.
		$is_analytics_enabled = apply_filters( 'vw_should_analytics_be_enabled', $is_analytics_enabled );

		return $is_analytics_enabled;
	}

	/**
	 * Check if the site is a WPVIP site.
	 *
	 * @param bool $only_production Whether to only allow production sites to be considered WPVIP sites
	 * @return true, if it is a WPVIP site, false otherwise
	 */
	protected function is_vip_site( $only_production = false ) {
		$is_vip_site = defined( 'VIP_GO_ENV' )
			&& defined( 'WPCOM_SANDBOXED' ) && constant( 'WPCOM_SANDBOXED' ) === false
			&& defined( 'FILES_CLIENT_SITE_ID' );

		if ( $only_production ) {
			$is_vip_site = $is_vip_site && defined( 'VIP_GO_ENV' ) && 'production' === constant( 'VIP_GO_ENV' );
		}

		return $is_vip_site;
	}

	/**
	 * Gets an array of allowed post types for a module
	 *
	 * @return array post-type-slug => post-type-label
	 */
	public function get_all_post_types() {

		$allowed_post_types = array(
			'post' => __( 'Post' ),
			'page' => __( 'Page' ),
		);
		$custom_post_types = $this->get_supported_post_types_for_module();

		foreach ( $custom_post_types as $custom_post_type => $args ) {
			$allowed_post_types[ $custom_post_type ] = $args->label;
		}
		return $allowed_post_types;
	}

	/**
	 * Cleans up the 'on' and 'off' for post types on a given module (so we don't get warnings all over)
	 * For every post type that doesn't explicitly have the 'on' value, turn it 'off'
	 * If add_post_type_support() has been used anywhere (legacy support), inherit the state
	 *
	 * @param array $module_post_types Current state of post type options for the module
	 * @param string $post_type_support What the feature is called for post_type_support (e.g. 'vw_calendar')
	 * @return array $normalized_post_type_options The setting for each post type, normalized based on rules
	 */
	public function clean_post_type_options( $module_post_types = array(), $post_type_support = null ) {
		$normalized_post_type_options = array();
		$all_post_types = array_keys( $this->get_all_post_types() );
		foreach ( $all_post_types as $post_type ) {
			if ( ( isset( $module_post_types[ $post_type ] ) && 'on' == $module_post_types[ $post_type ] ) || post_type_supports( $post_type, $post_type_support ) ) {
				$normalized_post_type_options[ $post_type ] = 'on';
			} else {
				$normalized_post_type_options[ $post_type ] = 'off';
			}
		}
		return $normalized_post_type_options;
	}

	/**
	 * Get all of the possible post types that can be used with a given module
	 *
	 * @param object $module The full module
	 * @return array $post_types An array of post type objects
	 */
	public function get_supported_post_types_for_module( $module = null ) {

		$pt_args = array(
			'_builtin' => false,
			'public' => true,
		);
		$pt_args = apply_filters( 'vip_workflow_supported_module_post_types_args', $pt_args, $module );
		return get_post_types( $pt_args, 'objects' );
	}

	/**
	 * Collect all of the active post types for a given module
	 *
	 * @param object $module Module's data
	 * @return array $post_types All of the post types that are 'on'
	 */
	public function get_post_types_for_module( $module ) {

		$post_types = array();
		if ( isset( $module->options->post_types ) && is_array( $module->options->post_types ) ) {
			foreach ( $module->options->post_types as $post_type => $value ) {
				if ( 'on' == $value ) {
					$post_types[] = $post_type;
				}
			}
		}
		return $post_types;
	}

	/**
	 * Get all of the currently available post statuses
	 *
	 * @return array $post_statuses All of the post statuses that aren't a published state
	 */
	public function get_post_statuses() {
		global $vip_workflow;

		return $vip_workflow->custom_status->get_custom_statuses();
	}

	/**
	 * Get core's 'draft' and 'pending' post statuses, but include our special attributes
	 *
	 * @return array
	 */
	protected function get_core_post_statuses() {

		return array(
			(object) array(
				'name'         => __( 'Draft' ),
				'description'  => '',
				'slug'         => 'draft',
				'position'     => 1,
			),
			(object) array(
				'name'         => __( 'Pending Review' ),
				'description'  => '',
				'slug'         => 'pending',
				'position'     => 2,
			),
		);
	}

	/**
	 * Filter to all posts with a given post status (can be a custom status or a built-in status) and optional custom post type.
	 *
	 * @param string $slug The slug for the post status to which to filter
	 * @param string $post_type Optional post type to which to filter
	 * @return an edit.php link to all posts with the given post status and, optionally, the given post type
	 */
	public function filter_posts_link( $slug, $post_type = 'post' ) {
		$filter_link = add_query_arg( 'post_status', $slug, get_admin_url( null, 'edit.php' ) );
		if ( 'post' != $post_type && in_array( $post_type, get_post_types( '', 'names' ) ) ) {
			$filter_link = add_query_arg( 'post_type', $post_type, $filter_link );
		}
		return $filter_link;
	}

	/**
	 * Checks for the current post type
	 *
	 * @return string|null $post_type The post type we've found, or null if no post type
	 */
	public function get_current_post_type() {
		global $post, $typenow, $pagenow, $current_screen;
		//get_post() needs a variable
		$post_id = isset( $_REQUEST['post'] ) ? (int) $_REQUEST['post'] : false;

		if ( $post && $post->post_type ) {
			$post_type = $post->post_type;
		} elseif ( $typenow ) {
			$post_type = $typenow;
		} elseif ( $current_screen && ! empty( $current_screen->post_type ) ) {
			$post_type = $current_screen->post_type;
		} elseif ( isset( $_REQUEST['post_type'] ) ) {
			$post_type = sanitize_key( $_REQUEST['post_type'] );
		} elseif ( 'post.php' == $pagenow
		&& $post_id
		&& ! empty( get_post( $post_id )->post_type ) ) {
			$post_type = get_post( $post_id )->post_type;
		} elseif ( 'edit.php' == $pagenow && empty( $_REQUEST['post_type'] ) ) {
			$post_type = 'post';
		} else {
			$post_type = null;
		}

		return $post_type;
	}

	/**
	 * Wrapper for the get_user_meta() function so we can replace it if we need to
	 *
	 * @param int $user_id Unique ID for the user
	 * @param string $key Key to search against
	 * @param bool $single Whether or not to return just one value
	 * @return string|bool|array $value Whatever the stored value was
	 */
	public function get_user_meta( $user_id, $key, $string = true ) {

		$response = null;
		$response = apply_filters( 'vw_get_user_meta', $response, $user_id, $key, $string );
		if ( ! is_null( $response ) ) {
			return $response;
		}

		return get_user_meta( $user_id, $key, $string );
	}

	/**
	 * Wrapper for the update_user_meta() function so we can replace it if we need to
	 *
	 * @param int $user_id Unique ID for the user
	 * @param string $key Key to search against
	 * @param string|bool|array $value Whether or not to return just one value
	 * @param string|bool|array $previous (optional) Previous value to replace
	 * @return bool $success Whether we were successful in saving
	 */
	public function update_user_meta( $user_id, $key, $value, $previous = null ) {

		$response = null;
		$response = apply_filters( 'vw_update_user_meta', $response, $user_id, $key, $value, $previous );
		if ( ! is_null( $response ) ) {
			return $response;
		}

		return update_user_meta( $user_id, $key, $value, $previous );
	}

	/**
	 * Take a status and a message, JSON encode and print
	 *
	 * @param string $status Whether it was a 'success' or an 'error'
	 */
	protected function print_ajax_response( $status, $message = '', $http_code = 200 ) {
		header( 'Content-type: application/json;' );
		http_response_code( $http_code );
		echo wp_json_encode(
			array(
				'status'  => $status,
				'message' => $message,
			)
		);
		exit;
	}

	/**
	 * Whether or not the current page is a user-facing Edit Flow View
	 * @todo Think of a creative way to make this work
	 *
	 * @param string $module_name (Optional) Module name to check against
	 */
	public function is_whitelisted_functional_view( $module_name = null ) {

		// @todo complete this method

		return true;
	}

	/**
	 * Whether or not the current page is an Edit Flow settings view (either main or module)
	 * Determination is based on $pagenow, $_GET['page'], and the module's $settings_slug
	 * If there's no module name specified, it will return true against all Edit Flow settings views
	 *
	 * @param string $module_name (Optional) Module name to check against
	 * @return bool $is_settings_view Return true if it is
	 */
	public function is_whitelisted_settings_view( $module_name = null ) {
		global $pagenow, $vip_workflow;

		// All of the settings views are based on admin.php and a $_GET['page'] parameter
		if ( 'admin.php' != $pagenow || ! isset( $_GET['page'] ) ) {
			return false;
		}

		// Load all of the modules that have a settings slug/ callback for the settings page
		foreach ( $vip_workflow->modules as $mod_name => $mod_data ) {
			if ( $mod_data->configure_page_cb ) {
				$settings_view_slugs[] = $mod_data->settings_slug;
			}
		}

		// The current page better be in the array of registered settings view slugs
		if ( ! in_array( $_GET['page'], $settings_view_slugs ) ) {
			return false;
		}

		if ( $module_name && $vip_workflow->modules->$module_name->settings_slug != $_GET['page'] ) {
			return false;
		}

		return true;
	}


	/**
	 * This is a hack, Hack, HACK!!!
	 * Encode all of the given arguments as a serialized array, and then base64_encode
	 * Used to store extra data in a term's description field
	 *
	 * @param array $args The arguments to encode
	 * @return string Arguments encoded in base64
	 */
	public function get_encoded_description( $args = array() ) {
		return base64_encode( maybe_serialize( $args ) );
	}

	/**
	 * If given an encoded string from a term's description field,
	 * return an array of values. Otherwise, return the original string
	 *
	 * @param string $string_to_unencode Possibly encoded string
	 * @return array Array if string was encoded, otherwise the string as the 'description' field
	 */
	public function get_unencoded_description( $string_to_unencode ) {
		return maybe_unserialize( base64_decode( $string_to_unencode ) );
	}

	/**
	 * Get the publicly accessible URL for the module based on the filename
	 *
	 * @param string $filepath File path for the module
	 * @return string $module_url Publicly accessible URL for the module
	 */
	public function get_module_url( $file ) {
		$module_url = plugins_url( '/', $file );
		return trailingslashit( $module_url );
	}

	/**
	 * Displays a list of users that can be selected!
	 *
	 * @todo Add pagination support for blogs with billions of users
	 *
	 * @param array $selected An array of user IDs that are selected
	 * @param array $args An array of arguments to pass to get_users()
	 */
	public function users_select_form( $selected = null, $args = null ) {
		// Set up arguments
		$defaults = array(
			'list_class' => 'vw-users-select-form',
			'input_id' => 'vw-selected-users',
		);
		$parsed_args = wp_parse_args( $args, $defaults );
		extract( $parsed_args, EXTR_SKIP );

		$args = array(
			'capability' => 'publish_posts',
			'fields' => array(
				'ID',
				'display_name',
				'user_email',
			),
			'orderby' => 'display_name',
		);
		$args = apply_filters( 'vw_users_select_form_get_users_args', $args );

		$users = get_users( $args );

		if ( ! is_array( $selected ) ) {
			$selected = array();
		}
		?>

		<?php if ( ! empty( $users ) ) : ?>
			<ul class="<?php echo esc_attr( $list_class ); ?>">
				<?php
				foreach ( $users as $user ) :
					$checked = ( in_array( $user->ID, $selected ) ) ? 'checked="checked"' : '';
					// Add a class to checkbox of current user so we know not to add them in notified list during notifiedMessage() js function
					$current_user_class = ( get_current_user_id() == $user->ID ) ? 'class="post_following_list-current_user" ' : '';
					?>
					<li>
						<label for="<?php echo esc_attr( $input_id . '-' . $user->ID ); ?>">
							<div class="vw-user-subscribe-actions">
								<?php do_action( 'vw_user_subscribe_actions', $user->ID, $checked ); ?>
								<input type="checkbox" id="<?php echo esc_attr( $input_id . '-' . $user->ID ); ?>" name="<?php echo esc_attr( $input_id ); ?>[]" value="<?php echo esc_attr( $user->ID ); ?>"
																	  <?php
																		echo esc_attr( $checked );
																		echo esc_attr( $current_user_class );
																		?>
								/>
							</div>

							<span class="vw-user_displayname"><?php echo esc_html( $user->display_name ); ?></span>
							<span class="vw-user_useremail"><?php echo esc_html( $user->user_email ); ?></span>
						</label>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
			<?php
	}

	/**
	 * Adds an array of capabilities to a role.
	 *
	 * @param string $role A standard WP user role like 'administrator' or 'author'
	 * @param array $caps One or more user caps to add
	 */
	public function add_caps_to_role( $role, $caps ) {
		// In some contexts, we don't want to add caps to roles
		if ( apply_filters( 'vw_kill_add_caps_to_role', false, $role, $caps ) ) {
			return;
		}

		global $wp_roles;

		if ( $wp_roles->is_role( $role ) ) {
			$role = get_role( $role );
			foreach ( $caps as $cap ) {
				$role->add_cap( $cap );
			}
		}
	}

	/**
	 * Add settings help menus to our module screens if the values exist
	 * Auto-registered in vip-workflow::register_module()
	 */
	public function action_settings_help_menu() {

		$screen = get_current_screen();

		if ( ! method_exists( $screen, 'add_help_tab' ) ) {
			return;
		}

		if ( 'vip-workflow_page_' . $this->module->settings_slug != $screen->id ) {
			return;
		}

		// Make sure we have all of the required values for our tab
		if ( isset( $this->module->settings_help_tab['id'], $this->module->settings_help_tab['title'], $this->module->settings_help_tab['content'] ) ) {
			$screen->add_help_tab( $this->module->settings_help_tab );

			if ( isset( $this->module->settings_help_sidebar ) ) {
				$screen->set_help_sidebar( $this->module->settings_help_sidebar );
			}
		}
	}
}
