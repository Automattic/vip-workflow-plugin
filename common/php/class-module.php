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
		$custom_post_types  = $this->get_supported_post_types_for_module();

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
		$all_post_types               = array_keys( $this->get_all_post_types() );
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
	 * Get all of the possible post types that can be used
	 *
	 * @return array $post_types An array of post type objects
	 */
	public function get_supported_post_types_for_module() {

		$pt_args = array(
			'_builtin' => false,
			'public'   => true,
		);
		return get_post_types( $pt_args, 'objects' );
	}

	/**
	 * Collect all of the active post types
	 *
	 * @return array $post_types All of the post types that are 'on'
	 */
	public function get_post_types_for_module() {
		global $vip_workflow;

		$post_types = array();
		foreach ( $vip_workflow->settings->options->post_types as $post_type => $value ) {
			if ( 'on' === $value ) {
				$post_types[] = $post_type;
			}
		}
		return $post_types;
	}

	/**
	 * Get core's 'draft' and 'pending' post statuses, but include our special attributes
	 *
	 * @return array
	 */
	protected function get_core_post_statuses() {

		return array(
			(object) array(
				'name'        => __( 'Draft' ),
				'description' => '',
				'slug'        => 'draft',
				'position'    => 1,
			),
			(object) array(
				'name'        => __( 'Pending Review' ),
				'description' => '',
				'slug'        => 'pending',
				'position'    => 2,
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
	 * Whether or not the current page is our settings view
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

		// if ( $module_name && $vip_workflow->modules->$module_name->settings_slug != $_GET['page'] ) {
		//  return false;
		// }

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
}
