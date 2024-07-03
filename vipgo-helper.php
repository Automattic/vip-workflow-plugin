<?php
/**
 * Ensure VIP Workflow is instantiated
 */
add_action( 'after_setup_theme', 'vip_workflow' );

/**
 * Caps don't get loaded on install on VIP Go. Instead, let's add
 * them via filters.
 */
add_filter( 'vw_kill_add_caps_to_role', '__return_true' );
add_filter( 'vw_edit_post_subscriptions_cap', function () {
	return 'edit_others_posts';
} );
add_filter( 'vw_manage_usergroups_cap', function () {
	return 'manage_options';
} );

/**
 * VIP Workflow loads modules after plugins_loaded, which has already been fired when loading via wpcom_vip_load_plugins
 * Let's run the method at after_setup_themes
 */
add_filter( 'after_setup_theme', 'vip_workflow_wpcom_load_modules' );
function vip_workflow_wpcom_load_modules() {
	global $vip_workflow;
	if ( method_exists( $vip_workflow, 'action_vw_loaded_load_modules' ) ) {
		$vip_workflow->action_vw_loaded_load_modules();
	}
}
