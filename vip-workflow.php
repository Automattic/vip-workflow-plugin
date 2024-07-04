<?php
/**
 * Plugin Name: WordPress VIP Workflow
 * Plugin URI: https://github.com/Automattic/vip-workflow-plugin
 * Description: Adding additional editorial workflow capabilities to WordPress.
 * Author: WordPress VIP
 * Text Domain: vip-workflow
 * Version: 0.0.1
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * License: GPL-3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package vip-workflow
 */

 namespace VIPWorkflow;

 /**
  * Print admin notice regarding having an old version of PHP.
  */
function vip_workflow_print_incompatibility_notice() {
	?>
	<div class="notice notice-error">
			<p><?php esc_html_e( 'VIP Workflow requires PHP 8.0+.', 'vip-workflow' ); ?></p>
		</div>
	<?php
}

// ToDo: Add a check for the WP version as well.
if ( version_compare( phpversion(), '8.0', '<' ) ) {
	add_action( 'admin_notices', 'vip_workflow_print_incompatibility_notice' );
	return;
}

// Define contants
define( 'VIP_WORKFLOW_VERSION', '0.0.1' );
define( 'VIP_WORKFLOW_ROOT', __DIR__ );
define( 'VIP_WORKFLOW_URL', plugins_url( '/', __FILE__ ) );
define( 'VIP_WORKFLOW_SETTINGS_PAGE', add_query_arg( 'page', 'vw-settings', get_admin_url( null, 'admin.php' ) ) );

require_once VIP_WORKFLOW_ROOT . '/class-workflow.php';
