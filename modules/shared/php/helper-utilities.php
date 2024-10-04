<?php
/**
 * class HelperUtilities
 *
 * @desc Utility methods for common tasks used throughout the modules
 */

namespace VIPWorkflow\Modules\Shared\PHP;

use VIPWorkflow\Modules\Settings;
use VIPWorkflow\Modules\Shared\PHP\OptionsUtilities;

class HelperUtilities {

	/**
	 * Collect all of the active post types
	 *
	 * @return array $post_types All of the post types that are 'on'
	 */
	public static function get_supported_post_types(): array {
		$post_types         = [];
		$post_types_options = OptionsUtilities::get_module_option_by_key( Settings::SETTINGS_SLUG, 'post_types' );

		foreach ( $post_types_options as $post_type => $value ) {
			if ( 'on' === $value ) {
				$post_types[] = $post_type;
			}
		}

		return $post_types;
	}
}
