<?php
/**
 * class Module
 *
 * @desc Base class any module should extend
 */

namespace VIPWorkflow\Modules\Shared\PHP;

use VIPWorkflow\VIP_Workflow;

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
		return $allowed_post_types;
	}

	/**
	 * Cleans up the 'on' and 'off' for post types on a given module (so we don't get warnings all over)
	 * For every post type that doesn't explicitly have the 'on' value, turn it 'off'
	 *
	 * @param array $module_post_types Current state of post type options for the module
	 * @return array $normalized_post_type_options The setting for each post type, normalized based on rules
	 */
	public function clean_post_type_options( $module_post_types = array() ) {
		$normalized_post_type_options = array();
		$all_post_types               = array_keys( $this->get_all_post_types() );
		foreach ( $all_post_types as $post_type ) {
			if ( ( isset( $module_post_types[ $post_type ] ) && 'on' == $module_post_types[ $post_type ] ) ) {
				$normalized_post_type_options[ $post_type ] = 'on';
			} else {
				$normalized_post_type_options[ $post_type ] = 'off';
			}
		}
		return $normalized_post_type_options;
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
