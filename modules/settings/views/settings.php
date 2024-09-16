<?php

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
			<?php esc_html_e('Configure VIP Workflow settings.', 'vip-workflow'); ?>
			<?php if ( $message_slug && isset( $messages[ $message_slug ] ) ) { ?>
			<?php printf( '<span class="vip-workflow-updated-message vip-workflow-message">%s</span>', esc_html( $messages[ $message_slug ] ) ); ?>
			<?php } ?>
		</h3>
	</div>

	<form class="basic-settings" action="<?php echo esc_url( menu_page_url( $this->module->settings_slug, false ) ); ?>" method="post">
		<?php settings_fields( $this->module->options_group_name ); ?>
		<?php do_settings_sections( $this->module->options_group_name ); ?>
		<input id="vip_workflow_module_name" name="vip_workflow_module_name" type="hidden" value="<?php echo esc_attr( $this->module->name ); ?>" />

		<p class="submit"><?php submit_button( null, 'primary', 'submit', false ); ?></p>
	</form>
</div>
