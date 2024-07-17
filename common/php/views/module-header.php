<?php

defined( 'ABSPATH' ) || exit();

?>

<div class="wrap vip-workflow-admin">
	<?php if ( 'settings' != $current_module->name ) { ?>
		<?php echo wp_kses_post( $page_icon ); ?>
		<h2>
			<a href="<?php echo esc_url( VIP_WORKFLOW_SETTINGS_PAGE ); ?>"><?php esc_html_e( 'VIP Workflow', 'vip-workflow' ); ?></a>:&nbsp;<?php echo esc_attr( $current_module->title ); ?><?php echo ( isset( $display_text ) ? wp_kses_post( $display_text ) : '' ); ?>
		</h2>
	<?php } else { ?>
		<?php echo wp_kses_post( $page_icon ); ?>
		<h2><?php esc_html_e( 'VIP Workflow', 'vip-workflow' ); ?><?php echo ( isset( $display_text ) ? wp_kses_post( $display_text ) : '' ); ?></h2>
	<?php } ?>

	<div class="explanation">
		<?php if ( $current_module->short_description ) { ?>
		<h3><?php echo wp_kses_post( $current_module->short_description ); ?></h3>
		<?php } ?>

		<?php if ( $current_module->extended_description ) { ?>
		<p><?php echo wp_kses_post( $current_module->extended_description ); ?></p>
		<?php } ?>
	</div>

<?php /* Wrapper div is closed by footer */ ?>
