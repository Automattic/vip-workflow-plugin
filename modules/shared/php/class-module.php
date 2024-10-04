<?php
/**
 * class Module
 *
 * @desc Base class any module should extend
 */

namespace VIPWorkflow\Modules\Shared\PHP;

use VIPWorkflow\VIP_Workflow;

class Module {

	public $module_url;

	public $module;

	public function __construct() {}

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
