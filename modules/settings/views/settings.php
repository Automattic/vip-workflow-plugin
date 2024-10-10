<?php

use VIPWorkflow\Modules\Settings;
use VIPWorkflow\Modules\Shared\PHP\OptionsUtilities;

defined( 'ABSPATH' ) || exit();


// If there's been a message, let's display it
$messages = [
	'settings-updated' => __( 'Settings updated.', 'vip-workflow' ),
];

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Message slugs correspond to preset read-only strings and do not require nonce checks.
$message_slug = isset( $_REQUEST['message'] ) ? sanitize_title( $_REQUEST['message'] ) : false;

?>

<div class="wrap vip-workflow-admin">
	<div class="explanation">
		<h3>
			<?php esc_html_e( 'Configure VIP Workflow settings.', 'vip-workflow' ); ?>
			<?php if ( $message_slug && isset( $messages[ $message_slug ] ) ) { ?>
				<?php
				wp_admin_notice( esc_html( $messages[ $message_slug ] ), [
					'type' => 'success',
					'dismissible' => true,
				] );
				?>
			<?php } ?>
		</h3>
	</div>

	<form class="basic-settings" action="<?php echo esc_url( menu_page_url( Settings::SETTINGS_SLUG, false ) ); ?>" method="post">
		<?php settings_fields( OptionsUtilities::get_module_options_key() ); ?>
		<?php do_settings_sections( OptionsUtilities::get_module_options_key() ); ?>

		<p class="submit"><?php submit_button( null, 'primary', 'submit', false ); ?></p>
	</form>
</div>
