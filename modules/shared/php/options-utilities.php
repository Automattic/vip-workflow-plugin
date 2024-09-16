<?php
/**
 * class OptionsUtilities
 *
 * @desc Utility methods for initializing a module after first install
 */

namespace VIPWorkflow\Modules\Shared\PHP;

use stdClass;
use VIPWorkflow\VIP_Workflow;

class OptionsUtilities {
	/**
	 * Given a module name, return a set of saved module options
	 *
	 * @param string $module_slug The slug used for this module
	 * @return object The set of saved module options for this module, or an empty stdClass if none are found
	 */
	public static function get_module_options( string $module_slug ): object {
		$module_options_key = self::get_module_options_key( $module_slug );
		return get_option( $module_options_key, new stdClass() );
	}

	/**
	 * Update a module option, using the module's name and the key
	 *
	 * @param string $module_slug The slug used for this module
	 * @param string $key The option key
	 * @param string $value The option value
	 * @return bool True if the option was updated, false otherwise.
	 */
	public static function update_module_option_key( string $module_slug, string $key, string $value ): bool {
		$module_options       = self::get_module_options( $module_slug );
		$module_options->$key = $value;

		$module_options_key = self::get_module_options_key( $module_slug );
		return update_option( $module_options_key, $module_options );
	}

	/**
	 * Given a module name, return the options key for the module
	 *
	 * @param string $module_slug The slug used for this module
	 * @return string Arguments encoded in base64
	 */
	private static function get_module_options_key( string $module_slug ): string {
		// Transform module settings slug into a slugified name, e.g. 'vw-editorial-metadata' => 'editorial_metadata'
		$module_options_name = str_replace( 'vw-', '', $module_slug );
		$module_options_name = str_replace( '-', '_', $module_options_name );

		$vip_workflow = VIP_Workflow::instance();
		return sprintf( '%s%s_options', $vip_workflow->options_group, $module_options_name );
	}
}
