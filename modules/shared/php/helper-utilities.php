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

	/**
	 * Whether or not the current page is our settings view. Determination is based on $pagenow, $_GET['page'], and if it's settings module or not.
	 *
	 * @return bool $is_settings_view Return true if it is
	 */
	public static function is_settings_view_loaded( string $slug ): bool {
		global $pagenow;

		// All of the settings views are based on admin.php and a $_GET['page'] parameter
		if ( 'admin.php' != $pagenow || ! isset( $_GET['page'] ) ) {
			return false;
		}

		// The current page better be in the array of registered settings view slugs
		return $_GET['page'] === $slug;
	}
}
