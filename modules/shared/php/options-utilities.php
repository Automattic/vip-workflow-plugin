<?php
/**
 * class OptionsUtilities
 *
 * @desc Utility methods for initializing a module after first install
 */

namespace VIPWorkflow\Modules\Shared\PHP;

use stdClass;

class OptionsUtilities {
	const OPTIONS_GROUP = 'vip_workflow_';
	const OPTIONS_GROUP_NAME = 'vip_workflow_options';

	// Its key to centralize these here, so we can easily update them in the future
	private const DEFAULT_OPTIONS = [
		'post_types'          => [
			'post' => 'on',
			'page' => 'on',
		],
		'publish_guard'       => 'on',
		'email_address'       => '',
		'webhook_url'         => '',
	];

	/**
	 * Given a module name, return a set of saved module options
	 *
	 * @param string $module_slug The slug used for this module
	 * @return object The set of saved module options for this module, or an empty stdClass if none are found
	 */
	public static function get_module_options( string $module_slug ): object|null {
		$module_options_key = self::get_module_options_key( $module_slug );
		$module_options = get_option( $module_options_key, new stdClass() );

		// Ensure all default options are set
		foreach ( self::DEFAULT_OPTIONS as $key => $value ) {
			if ( ! isset( $module_options->$key ) ) {
				$module_options->$key = $value;
			}
		}

		return $module_options;
	}

	public static function get_module_option_by_key( string $module_slug, string $key ): string|array|bool|null {
		$module_options = self::get_module_options( $module_slug );
		return $module_options->$key;
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
	 * Update a module options, using the module's name
	 *
	 * @param string $module_slug The slug used for this module
	 * @param object $new_options The new options to save
	 * @return bool True if the options were updated, false otherwise.
	 */
	public static function update_module_options( string $module_slug, array $new_options ): bool {
		$module_options_key = self::get_module_options_key( $module_slug );
		$old_options       = self::get_module_options( $module_slug );
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
	public static function get_module_options_key( string $module_slug ): string {
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
	public static function get_module_options_general_key( string $module_slug ): string {
		$module_options_key = self::get_module_options_key( $module_slug );
		return $module_options_key . '_general';
	}
}
