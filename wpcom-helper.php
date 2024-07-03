<?php
/**
 * Ensure VIP Workflow is instantiated
 */
add_action( 'after_setup_theme', 'vip_workflow' );

/**
 * Don't load caps on install for WP.com. Instead, let's add
 * them with the WP.com + core caps approach
 */
add_filter( 'vw_kill_add_caps_to_role', '__return_true' );
add_filter( 'vw_edit_post_subscriptions_cap', function () {
	return 'edit_others_posts';
} );
add_filter( 'vw_manage_usergroups_cap', function () {
	return 'manage_options';
} );

/**
 * VIP Workflow loads modules after plugins_loaded, which has already been fired on WP.com
 * Let's run the method at after_setup_themes
 */
add_filter( 'after_setup_theme', 'vip_workflow_wpcom_load_modules' );
function vip_workflow_wpcom_load_modules() {
	global $vip_workflow;
	if ( method_exists( $vip_workflow, 'action_vw_loaded_load_modules' ) ) {
		$vip_workflow->action_vw_loaded_load_modules();
	}
}

/**
 * Share A Draft on WordPress.com breaks when redirect canonical is enabled
 * get_permalink() doesn't respect custom statuses
 *
 * @see http://core.trac.wordpress.org/browser/tags/3.4.2/wp-includes/canonical.php#L113
 */
add_filter( 'redirect_canonical', 'vip_workflow_wpcom_redirect_canonical' );
function edit_flow_wpcom_redirect_canonical( $redirect ) {

	if ( ! empty( $_GET['shareadraft'] ) ) {
		return false;
	}

	return $redirect;
}

// This should fix a caching race condition that can sometimes create a published post with an empty slug
add_filter( 'vw_fix_post_name_post', 'vip_workflow_fix_fix_post_name' );
function vip_workflow_fix_fix_post_name( $post ) {
	global $wpdb;
	$post_status = $wpdb->get_var( $wpdb->prepare( 'SELECT post_status FROM ' . $wpdb->posts . ' WHERE ID = %d', $post->ID ) );
	if ( null !== $post_status ) {
		$post->post_status = $post_status;
	}

	return $post;
}
