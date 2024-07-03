// Hide a given message
function vip_workflow_hide_message() {
	jQuery( '.vip-workflow-message' ).fadeOut( function () {
		jQuery( this ).remove();
	} );
}

jQuery( document ).ready( function () {
	// Restore the VIP Workflow submenu if there are no modules enabled
	// We need it down below for dynamically rebuilding the link list when on the settings page
	const vw_settings_submenu_html =
		'<div class="wp-submenu"><div class="wp-submenu-wrap"><div class="wp-submenu-head">VIP Workflow</div><ul><li class="wp-first-item current"><a tabindex="1" class="wp-first-item current" href="admin.php?page=vw-settings">VIP Workflow</a></li></ul></div></div>';
	if ( jQuery( 'li#toplevel_page_vw-settings .wp-submenu' ).length == 0 ) {
		jQuery( 'li#toplevel_page_vw-settings' ).addClass(
			'wp-has-submenu wp-has-current-submenu wp-menu-open'
		);
		jQuery( 'li#toplevel_page_vw-settings' ).append( vw_settings_submenu_html );
		jQuery( 'li#toplevel_page_vw-settings .wp-submenu' ).show();
	}

	// Set auto-removal to 8 seconds
	if ( jQuery( '.vip-workflow-message' ).length > 0 ) {
		setTimeout( vip_workflow_hide_message, 8000 );
	}
} );
