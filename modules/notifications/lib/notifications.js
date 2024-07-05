jQuery( document ).ready( function ( $ ) {
	// TODO: Should change this to _not_ use JQuery
	const webhookUrl = $( 'input#webhook_url' ).closest( 'tr' );
	const sendToWebhook = $( 'select#send_to_webhook' );
	if ( sendToWebhook.val() === 'off' ) {
		webhookUrl.hide();
	}
	sendToWebhook.on( 'change', function () {
		if ( $( this ).val() === 'off' ) {
			webhookUrl.hide();
		} else {
			webhookUrl.show();
		}
	} );
} );
