<?php
/**
 * class InstallUtilities
 *
 * @desc Utility methods for initializing a module after first install
 */

namespace VIPWorkflow\Modules\Shared\PHP;

use stdClass;
use VIPWorkflow\VIP_Workflow;

class InstallUtilities {
	/**
	 * Given a module name, run a callback function if the module is being run for the first time
	 *
	 * @param array $args The arguments to encode
	 * @return string Arguments encoded in base64
	 */
	public static function install_if_first_run( $module_slug, $callback_function ) {
		$module_options = OptionsUtilities::get_module_options( $module_slug );

		if ( ! $module_options->loaded_once ) {
			call_user_func( $callback_function );

			OptionsUtilities::update_module_option_key( $module_slug, 'loaded_once', true );
		}
	}
}
