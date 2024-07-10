<?php

defined( 'ABSPATH' ) || exit();

global $vip_workflow;

?>
<form method="get" action=""><table style="display: none"><tbody id="inlineedit">
	<tr id="inline-edit" class="inline-edit-row" style="display: none"><td colspan="<?php echo esc_attr( $this->get_column_count() ); ?>" class="colspanchange">
		<fieldset><div class="inline-edit-col">
			<h4><?php _e( 'Quick Edit' ); ?></h4>
			<label>
				<span class="title"><?php _e( 'Name', 'vip-workflow' ); ?></span>
				<span class="input-text-wrap"><input type="text" name="name" class="ptitle" value="" maxlength="20" /></span>
			</label>
			<label>
				<span class="title"><?php _e( 'Description', 'vip-workflow' ); ?></span>
				<span class="input-text-wrap"><input type="text" name="description" class="pdescription" value="" /></span>
			</label>
		</div></fieldset>
	<p class="inline-edit-save submit">
		<a accesskey="c" href="#inline-edit" title="<?php _e( 'Cancel' ); ?>" class="cancel button-secondary alignleft"><?php _e( 'Cancel' ); ?></a>
		<?php $update_text = __( 'Update Status', 'vip-workflow' ); ?>
		<a accesskey="s" href="#inline-edit" title="<?php echo esc_attr( $update_text ); ?>" class="save button-primary alignright"><?php echo esc_html( $update_text ); ?></a>
		<img class="waiting" style="display:none;" src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" alt="" />
		<span class="error" style="display:none;"></span>
		<?php wp_nonce_field( 'custom-status-inline-edit-nonce', 'inline_edit', false ); ?>
		<br class="clear" />
	</p>
	</td></tr>
	</tbody></table>
</form>
