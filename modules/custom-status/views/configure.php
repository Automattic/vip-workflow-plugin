<?php

namespace VIPWorkflow\Modules\CustomStatus;

defined( 'ABSPATH' ) || exit();

// phpcs:disable:WordPress.Security.NonceVerification.Missing -- Disabling nonce verification because that is not available here, it's just rendering it. The actual save is done in helper_settings_validate_and_save and that's guarded well.

global $vip_workflow;

?>

<div id="col-right">
	<div class="col-wrap">
		<?php $custom_status_list_table->display(); ?>
		<p class="description" style="padding-top:10px;"><?php esc_html_e( 'Deleting a post status will assign all posts to the default post status.', 'vip-workflow' ); ?></p>
	</div>
</div>

<div id="col-left">
	<div class="col-wrap">
		<div class="form-wrap">
			<h3 class="nav-tab-wrapper">
				<?php $add_new_nav_class = empty( $action ) ? 'nav-tab-active' : ''; ?>
				<a href="<?php echo esc_url( $this->get_link() ); ?>" class="nav-tab <?php echo esc_attr( $add_new_nav_class ); ?>"><?php esc_html_e( 'Add New', 'vip-workflow' ); ?></a>
				<?php $options_nav_class = 'change-options' === $action ? 'nav-tab-active' : ''; ?>
				<a href="<?php echo esc_url( $this->get_link( [ 'action' => 'change-options' ] ) ); ?>" class="nav-tab <?php echo esc_attr( $options_nav_class ); ?>"><?php esc_html_e( 'Options', 'vip-workflow' ); ?></a>
				<?php $options_nav_class = 'manage-workflow' === $action ? 'nav-tab-active' : ''; ?>
				<a href="<?php echo esc_url( $this->get_link( [ 'action' => 'manage-workflow' ] ) ); ?>" class="nav-tab <?php echo esc_attr( $options_nav_class ); ?>"><?php esc_html_e( 'Workflow', 'vip-workflow' ); ?></a>
			</h3>

			<?php if ( 'change-options' === $action ) { ?>
			<form class="basic-settings" action="<?php echo esc_url( $this->get_link( [ 'action' => 'change-options' ] ) ); ?>" method="post">
				<?php settings_fields( $this->module->options_group_name ); ?>
				<?php do_settings_sections( $this->module->options_group_name ); ?>
				<input id="vip_workflow_module_name" name="vip_workflow_module_name" type="hidden" value="<?php echo esc_attr( $this->module->name ); ?>" />
				<?php submit_button(); ?>
			</form>
			<?php } else { ?>
			<!-- Custom form for adding a new Custom Status term -->
			<form class="add:the-list:" action="<?php echo esc_url( $this->get_link() ); ?>" method="post" id="addstatus" name="addstatus">
				<div class="form-field form-required">
					<label for="status_name"><?php esc_html_e( 'Name', 'vip-workflow' ); ?></label>
					<input type="text" aria-required="true" size="20" maxlength="20" id="status_name" name="status_name" value="<?php echo ( empty( $_POST['status_name'] ) ? '' : esc_attr( $_POST['status_name'] ) ); ?>" />
					<?php $vip_workflow->settings->helper_print_error_or_description( 'name', __( 'The name is used to identify the status. (Max: 20 characters)', 'vip-workflow' ) ); ?>
				</div>

				<div class="form-field">
					<label for="status_description"><?php esc_html_e( 'Description', 'vip-workflow' ); ?></label>
					<textarea cols="40" rows="5" id="status_description" name="status_description"><?php echo ( empty( $_POST['status_description'] ) ? '' : esc_textarea( $_POST['status_description'] ) ); ?></textarea>
					<?php $vip_workflow->settings->helper_print_error_or_description( 'description', __( 'The description is primarily for administrative use, to give you some context on what the custom status is to be used for.', 'vip-workflow' ) ); ?>
				</div>

				<?php wp_nonce_field( 'custom-status-add-nonce' ); ?>
				<input id="action" name="action" type="hidden" value="add-new" />
				<p class="submit"><?php submit_button( __( 'Add New Status', 'vip-workflow' ), 'primary', 'submit', false ); ?><a class="cancel-settings-link" href="<?php echo esc_url( VIP_WORKFLOW_SETTINGS_PAGE ); ?>"><?php esc_html_e( 'Back to VIP Workflow', 'vip-workflow' ); ?></a></p>
			</form>
			<?php } ?>
		</div>
	</div>
</div>

<?php

// phpcs:enable:WordPress.Security.NonceVerification.Missing

$custom_status_list_table->inline_edit();
