<?php
/**
 * Plugin Name: WordPress VIP Workflow
 * Plugin URI: https://github.com/Automattic/vip-workflow-plugin
 * Description: Adding additional editorial workflow capabilities to WordPress.
 * Author: WordPress VIP
 * Text Domain: vip-workflow
 * Version: 0.2.0
 * Requires at least: 6.2
 * Requires PHP: 8.0
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

// ToDo: When 6.4 is our min version, switch to wp_admin_notice.
global $wp_version;
if ( version_compare( phpversion(), '8.0', '<' ) || version_compare( $wp_version, '6.2', '<' ) ) {
	add_action( 'admin_notices', function () {
		?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'VIP Workflow requires PHP 8.0+ and WordPress 6.2+.', 'vip-workflow' ); ?></p>
		</div>
		<?php
	}, 10, 0 );
	return;
}

// Define contants
define( 'VIP_WORKFLOW_VERSION', '0.2.0' );
define( 'VIP_WORKFLOW_ROOT', __DIR__ );
define( 'VIP_WORKFLOW_URL', plugins_url( '/', __FILE__ ) );
define( 'VIP_WORKFLOW_SETTINGS_PAGE', add_query_arg( 'page', 'vw-settings', get_admin_url( null, 'admin.php' ) ) );
define( 'VIP_WORKFLOW_REST_NAMESPACE', 'vip-workflow/v1' );

// Main plugin class
require_once VIP_WORKFLOW_ROOT . '/class-workflow.php';

// Modules
require_once VIP_WORKFLOW_ROOT . '/modules/preview/preview.php';
