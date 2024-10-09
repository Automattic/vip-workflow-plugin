<?php
/**
 * class OptionsUtilities
 *
 * @desc Utility methods for initializing a module after first install
 */

namespace VIPWorkflow\Modules\Shared\PHP;

use VIPWorkflow\Modules\Settings;
use stdClass;

class OptionsUtilities {
	const OPTIONS_GROUP = 'vip_workflow_';
	const OPTIONS_GROUP_NAME = 'vip_workflow_options';

	/**
	 * Given a module name, return a set of saved module options
	 *
	 * @param string $module_slug The slug used for this module
	 * @param array $default_options The default options for the module
	 * @return object The set of saved module options for this module, or an empty stdClass if none are found
	 */
	public static function get_module_options( string $module_slug, array $default_options = [] ): object|null {
		$module_options_key = self::get_module_options_key( $module_slug );
		$module_options = get_option( $module_options_key, new stdClass() );

		if ( [] !== $module_options ) {
			// Ensure all default options are set
			foreach ( $default_options as $key => $value ) {
				if ( ! isset( $module_options->$key ) ) {
					$module_options->$key = $value;
				}
			}
		}

		return $module_options;
	}

	/**
	 * Get a specific key in the options, stored in the setings options.
	 *
	 * By default, it assumes the settings is the module that's being references as all the options are stored there.
	 *
	 * @param string $key The key to get
	 * @return string|array|boolean|null The value of the key, or null if it doesn't exist
	 */
	public static function get_options_by_key( string $key ): string|array|bool|null {
		$module_options = self::get_module_options( Settings::SETTINGS_SLUG, Settings::DEFAULT_SETTINGS_OPTIONS );
		return $module_options->$key;
	}

	/**
	 * Update a module option, using the module's name and the key.
	 *
	 * Note: This method is used to update a single key in the module options, so it will override the entire options object.
	 *
	 * @param string $module_slug The slug used for this module
	 * @param string $key The option key
	 * @param string $value The option value
	 * @param array $default_options The default options for the module
	 * @return bool True if the option was updated, false otherwise.
	 */
	public static function update_module_option_key( string $module_slug, string $key, string $value, array $default_options = [] ): bool {
		$module_options       = self::get_module_options( $module_slug, $default_options );
		$module_options->$key = $value;

		$module_options_key = self::get_module_options_key( $module_slug );
		return update_option( $module_options_key, $module_options );
	}

	/**
	 * Update a module options, using the module's name
	 *
	 * @param object $new_options The new options to save
	 * @return bool True if the options were updated, false otherwise.
	 */
	public static function update_module_options( array $new_options ): bool {
		$module_options_key = self::get_module_options_key( Settings::SETTINGS_SLUG );
		$old_options       = self::get_module_options( Settings::SETTINGS_SLUG, Settings::DEFAULT_SETTINGS_OPTIONS );
		$new_options = (object) array_merge( (array) $old_options, $new_options );

		return update_option( $module_options_key, $new_options );
	}

	/**
	 * Reset all module options, this will reset the plugin back to its default settings.
	 *
	 * It's meant for testing purposes only.
	 *
	 * @return void
	 *
	 * @access private
	 */
	public static function reset_all_module_options(): void {
		$modules_to_delete = [ 'custom-status', 'editorial-metadata', 'settings' ];
		foreach ( $modules_to_delete as $module_slug ) {
			$module_options_key = self::get_module_options_key( $module_slug );
			delete_option( $module_options_key );
		}
	}

	/**
	 * Given a module name, return the options key for the module
	 *
	 * @param string $module_slug The slug used for this module
	 * @return string the options key for the module
	 */
	public static function get_module_options_key( string $module_slug = Settings::SETTINGS_SLUG ): string {
		// Transform module settings slug into a slugified name, e.g. 'vw-editorial-metadata' => 'editorial_metadata'
		$module_options_name = str_replace( 'vw-', '', $module_slug );
		$module_options_name = str_replace( '-', '_', $module_options_name );

		return sprintf( '%s%s_options', self::OPTIONS_GROUP, $module_options_name );
	}

	/**
	 * Given a module name, return the options key for the module with the '_general' suffix
	 *
	 * @param string $module_slug The slug used for this module
	 * @return string the options key for the module with the '_general' suffix
	 */
	public static function get_module_options_general_key( string $module_slug = Settings::SETTINGS_SLUG ): string {
		$module_options_key = self::get_module_options_key( $module_slug );
		return $module_options_key . '_general';
	}
}
