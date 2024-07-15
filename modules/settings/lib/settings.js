jQuery( document ).ready( function ( $ ) {
	// ToDo: Switch this to be react based once the custom status module's settings are converted to react.
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
