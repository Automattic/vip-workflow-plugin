<?php

defined( 'ABSPATH' ) || exit();

?>

<div class="wrap vip-workflow-admin">
	<?php /* ToDo: Convert this message into a proper notice for errors and success messages */ ?>
	<?php echo ( isset( $display_text ) ? wp_kses_post( $display_text ) : '' ); ?>
	<div class="explanation">
		<?php if ( $current_module->short_description ) { ?>
		<h3><?php echo wp_kses_post( $current_module->short_description ); ?></h3>
		<?php } ?>

		<?php if ( $current_module->extended_description ) { ?>
		<p><?php echo wp_kses_post( $current_module->extended_description ); ?></p>
		<?php } ?>
	</div>

<?php /* Wrapper div is closed by footer */ ?>
