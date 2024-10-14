<?php
/**
 * Plugin Name: WordPress VIP Workflow
 * Plugin URI: https://github.com/Automattic/vip-workflow-plugin
 * Description: Adding additional editorial workflow capabilities to WordPress.
 * Author: WordPress VIP
 * Text Domain: vip-workflow
 * Version: 0.4.0
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * License: GPL-3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package vip-workflow
 */

namespace VIPWorkflow;

if ( defined( 'VIP_WORKFLOW_LOADED' ) ) {
	return;
}

define( 'VIP_WORKFLOW_LOADED', true );

global $wp_version;
if ( version_compare( phpversion(), '8.1', '<' ) || version_compare( $wp_version, '6.4', '<' ) ) {
	wp_admin_notice( esc_html( 'VIP Workflow requires PHP 8.1+ and WordPress 6.4+.', 'vip-workflow' ), [
		'type' => 'error',
		'dismissible' => true,
	] );
	return;
}

// Define contants
define( 'VIP_WORKFLOW_VERSION', '0.4.0' );
define( 'VIP_WORKFLOW_ROOT', __DIR__ );
define( 'VIP_WORKFLOW_URL', plugins_url( '/', __FILE__ ) );
define( 'VIP_WORKFLOW_SETTINGS_PAGE', add_query_arg( 'page', 'vw-settings', get_admin_url( null, 'admin.php' ) ) );
define( 'VIP_WORKFLOW_REST_NAMESPACE', 'vip-workflow/v1' );


// Set the version for the plugin.
// It's not used for anything, which is why it's here.
// This should not rely on any other code in the plugin.
add_action( 'admin_init', function () {
	$previous_version = get_option( 'vip_workflow_version' );
	if ( $previous_version && version_compare( $previous_version, VIP_WORKFLOW_VERSION, '<' ) ) {
		update_option( 'vip_workflow_version', VIP_WORKFLOW_VERSION );
	} elseif ( ! $previous_version ) {
		update_option( 'vip_workflow_version', VIP_WORKFLOW_VERSION );
	}
} );

// Utility classes
require_once VIP_WORKFLOW_ROOT . '/modules/shared/php/helper-utilities.php';
require_once VIP_WORKFLOW_ROOT . '/modules/shared/php/install-utilities.php';
require_once VIP_WORKFLOW_ROOT . '/modules/shared/php/options-utilities.php';
require_once VIP_WORKFLOW_ROOT . '/modules/shared/php/meta-cleanup-utilities.php';
require_once VIP_WORKFLOW_ROOT . '/modules/shared/php/util.php';
require_once VIP_WORKFLOW_ROOT . '/modules/shared/php/core-hacks.php';
require_once VIP_WORKFLOW_ROOT . '/modules/shared/php/log-level-enum.php';
require_once VIP_WORKFLOW_ROOT . '/modules/shared/php/logging-utility.php';

// Modules
require_once VIP_WORKFLOW_ROOT . '/modules/settings/settings.php';
require_once VIP_WORKFLOW_ROOT . '/modules/custom-status/custom-status.php';
require_once VIP_WORKFLOW_ROOT . '/modules/editorial-metadata/editorial-metadata.php';
require_once VIP_WORKFLOW_ROOT . '/modules/notifications/notifications.php';
require_once VIP_WORKFLOW_ROOT . '/modules/preview/preview.php';
