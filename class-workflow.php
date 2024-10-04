<?php

namespace VIPWorkflow;

use VIPWorkflow\Modules\Shared\PHP\Module;
use stdClass;
use VIPWorkflow\Modules\Settings;

// Core class
#[\AllowDynamicProperties]
class VIP_Workflow {

	// Unique identified added as a prefix to all options
	public $options_group      = 'vip_workflow_';
	public $options_group_name = 'vip_workflow_options';

	/**
	 * Setup the default hooks and actions
	 *
	 * @uses add_action() To add various actions
	 */
	private function init() {
		add_action( 'init', [ $this, 'action_init' ], 8 );
		add_action( 'init', [ $this, 'action_init_after' ], 1000 );

		add_action( 'admin_init', [ $this, 'action_admin_init' ] );
	}

	/**
	 * Initialize the plugin for the admin
	 */
	public function action_admin_init() {
		// Upgrade if need be but don't run the upgrade if the plugin has never been used
		$previous_version = get_option( $this->options_group . 'version' );
		if ( $previous_version && version_compare( $previous_version, VIP_WORKFLOW_VERSION, '<' ) ) {
			update_option( $this->options_group . 'version', VIP_WORKFLOW_VERSION );
		} elseif ( ! $previous_version ) {
			update_option( $this->options_group . 'version', VIP_WORKFLOW_VERSION );
		}
	}

	/**
	 * Collect all of the active post types
	 *
	 * @return array $post_types All of the post types that are 'on'
	 */
	public static function get_supported_post_types(): array {
		$post_types         = [];
		$post_types_options = OptionsUtilities::get_module_option_by_key( Settings::SETTINGS_SLUG, 'post_types');

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

VIP_Workflow::instance();
