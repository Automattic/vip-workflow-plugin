<?php

/**
 * class Settings
 *
 * Settings module for VIP Workflow
 */
namespace VIPWorkflow\Modules;

use VIPWorkflow\VIP_Workflow;
use VIPWorkflow\Modules\Shared\PHP\Module;

class Settings extends Module {

	public $module;

	/**
	 * Register the module with VIP Workflow but don't do anything else
	 */
	public function __construct() {
		// Register the module with VIP Workflow
		$this->module_url = $this->get_module_url( __FILE__ );
		$args             = array(
			'title'             => __( 'Settings', 'vip-workflow' ),
			'short_description' => __( 'Configure VIP Workflow settings.', 'vip-workflow' ),
			'module_url'        => $this->module_url,
			'slug'              => 'settings',
			'default_options'   => array(
				'post_types'          => [
					'post' => 'on',
					'page' => 'on',
				],
				'publish_guard'       => 'on',
				'always_notify_admin' => 'on',
				'send_to_webhook'     => 'off',
				'webhook_url'         => '',
			),
			'configure_page_cb' => 'print_default_settings',
		);
		$this->module     = VIP_Workflow::instance()->register_module( 'settings', $args );
	}

	/**
	 * Initialize the rest of the stuff in the class if the module is active
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'helper_settings_validate_and_save' ), 100 );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		add_action( 'admin_print_styles', array( $this, 'action_admin_print_styles' ) );
		add_action( 'admin_print_scripts', array( $this, 'action_admin_print_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'action_admin_enqueue_scripts' ) );
	}

	/**
	 * Add settings JS to the settings page
	 */
	public function action_admin_enqueue_scripts() {
		if ( $this->is_whitelisted_settings_view() ) {
			wp_enqueue_script( 'vip-workflow-settings-js', $this->module_url . 'lib/settings.js', array( 'jquery' ), VIP_WORKFLOW_VERSION, true );
		}
	}

	/**
	 * Add settings styles to the settings page
	 */
	public function action_admin_print_styles() {
		wp_enqueue_style( 'vip_workflow-settings-css', $this->module_url . 'lib/settings.css', false, VIP_WORKFLOW_VERSION );
	}

	/**
	 * Extra data we need on the page for transitions, etc.
	 */
	public function action_admin_print_scripts() {
		?>
		<script type="text/javascript">
			var vw_admin_url = '<?php echo esc_url( get_admin_url() ); ?>';
		</script>
			<?php
	}

	/**
	 * Register the settings for the module
	 */
	public function register_settings() {
		add_settings_section( $this->module->options_group_name . '_general', false, '__return_false', $this->module->options_group_name );

		add_settings_field( 'post_types', __( 'Use on these post types:', 'vip-workflow' ), [ $this, 'helper_option_custom_post_type' ], $this->module->options_group_name, $this->module->options_group_name . '_general' );
		add_settings_field( 'publish_guard', __( 'Publish Guard:', 'vip-workflow' ), [ $this, 'settings_publish_guard' ], $this->module->options_group_name, $this->module->options_group_name . '_general' );

		add_settings_field( 'always_notify_admin', __( 'Always notify blog admin', 'vip-workflow' ), [ $this, 'settings_always_notify_admin_option' ], $this->module->options_group_name, $this->module->options_group_name . '_general' );
		add_settings_field( 'send_to_webhook', __( 'Send to Webhook', 'vip-workflow' ), [ $this, 'settings_send_to_webhook' ], $this->module->options_group_name, $this->module->options_group_name . '_general' );

		// Hide the Webhook URL field by default if "Send to Webhook" is disabled
		$webhook_url_class = 'on' === $this->module->options->send_to_webhook ? '' : 'hidden';

		add_settings_field( 'webhook_url', __( 'Webhook URL', 'vip-workflow' ), [ $this, 'settings_webhook_url' ], $this->module->options_group_name, $this->module->options_group_name . '_general', [ 'class' => $webhook_url_class ] );
	}

	/**
	 * Adds Settings page for VIP Workflow.
	 */
	public function print_default_settings() {
		?>
		<form class="basic-settings" action="<?php echo esc_url( menu_page_url( $this->module->settings_slug, false ) ); ?>" method="post">
			<?php settings_fields( $this->module->options_group_name ); ?>
			<?php do_settings_sections( $this->module->options_group_name ); ?>
			<?php
				echo '<input id="vip_workflow_module_name" name="vip_workflow_module_name" type="hidden" value="' . esc_attr( $this->module->name ) . '" />';
			?>
			<p class="submit"><?php submit_button( null, 'primary', 'submit', false ); ?></p>
		</form>
			<?php
	}

	/**
	 * Option for whether the publish guard feature should be enabled
	 */
	public function settings_publish_guard() {
		$options = [
			'off' => __( 'Disabled', 'vip-workflow' ),
			'on'  => __( 'Enabled', 'vip-workflow' ),
		];
		echo '<select id="publish_guard" name="' . esc_attr( $this->module->options_group_name ) . '[publish_guard]">';
		foreach ( $options as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '"';
			echo selected( $this->module->options->publish_guard, $value );
			echo '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
	}

	/**
	 * Option for whether the blog admin email address should be always notified or not
	 */
	public function settings_always_notify_admin_option() {
		$options = [
			'off' => __( 'Disabled', 'vip-workflow' ),
			'on'  => __( 'Enabled', 'vip-workflow' ),
		];
		echo '<select id="always_notify_admin" name="' . esc_attr( $this->module->options_group_name ) . '[always_notify_admin]">';
		foreach ( $options as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '"';
			echo selected( $this->module->options->always_notify_admin, $value );
			echo '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
	}

	/**
	 * Option to enable sending notifications to Slack
	 */
	public function settings_send_to_webhook() {
		$options = [
			'off' => __( 'Disabled', 'vip-workflow' ),
			'on'  => __( 'Enabled', 'vip-workflow' ),
		];
		echo '<select id="send_to_webhook" name="' . esc_attr( $this->module->options_group_name ) . '[send_to_webhook]">';
		foreach ( $options as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '"';
			echo selected( $this->module->options->send_to_webhook, $value );
			echo '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
	}

	/**
	 * Option to set the Slack webhook URL
	 */
	public function settings_webhook_url() {
		printf( '<input type="text" id="webhook_url" name="%s[webhook_url]" value="%s" />', esc_attr( $this->module->options_group_name ), esc_attr( $this->module->options->webhook_url ) );
	}

	/**
	 * Generate an option field to turn post type support on/off for a given module
	 *
	 * @param array $args An array of arguments to pass to the function
	 */
	public function helper_option_custom_post_type( $args = array() ) {

		$all_post_types = array(
			'post' => __( 'Posts' ),
			'page' => __( 'Pages' ),
		);

		foreach ( $all_post_types as $post_type => $title ) {
			echo '<label for="' . esc_attr( $post_type ) . '">';
			echo '<input id="' . esc_attr( $post_type ) . '" name="'
			. esc_attr( $this->module->options_group_name ) . '[post_types][' . esc_attr( $post_type ) . ']"';
			if ( isset( $this->module->options->post_types[ $post_type ] ) ) {
				checked( $this->module->options->post_types[ $post_type ], 'on' );
			}
			// Defining post_type_supports in the functions.php file or similar should disable the checkbox
			disabled( post_type_supports( $post_type, $this->module->post_type_support ), true );
			echo ' type="checkbox" />&nbsp;&nbsp;&nbsp;' . esc_html( $title ) . '</label>';
			echo '<br />';
		}
	}

	/**
	 * Validate input from the end user
	 */
	public function settings_validate( $new_options ) {

		// Whitelist validation for the post type options
		if ( ! isset( $new_options['post_types'] ) ) {
			$new_options['post_types'] = [];
		}
		$new_options['post_types'] = $this->clean_post_type_options( $new_options['post_types'], $this->module->post_type_support );

		// Whitelist validation for the 'publish_guard' optoins
		if ( ! isset( $new_options['publish_guard'] ) || 'on' != $new_options['publish_guard'] ) {
			$new_options['publish_guard'] = 'off';
		}

		// Whitelist validation for the 'always_notify_admin' options
		if ( ! isset( $new_options['always_notify_admin'] ) || 'on' != $new_options['always_notify_admin'] ) {
			$new_options['always_notify_admin'] = 'off';
		}

		// White list validation for the 'send_to_slack' option
		if ( ! isset( $new_options['send_to_webhook'] ) || 'on' != $new_options['send_to_webhook'] ) {
			$new_options['send_to_webhook'] = 'off';
		}

		// White list validation for the 'slack_webhook_url' option
		$new_options['webhook_url'] = trim( $new_options['webhook_url'] );
		if ( ! isset( $new_options['webhook_url'] ) || esc_url_raw( $new_options['webhook_url'] ) !== $new_options['webhook_url'] ) {
			$new_options['webhook_url'] = '';
		} else {
			$new_options['webhook_url'] = esc_url_raw( $new_options['webhook_url'] );
		}

		return $new_options;
	}


	/**
	 * Validation and sanitization on the settings field
	 * This method is called automatically/ doesn't need to be registered anywhere
	 */
	public function helper_settings_validate_and_save() {

		if ( ! isset( $_POST['action'], $_POST['_wpnonce'], $_POST['option_page'], $_POST['_wp_http_referer'], $_POST['vip_workflow_module_name'], $_POST['submit'] ) || ! is_admin() ) {
			return false;
		}

		$module_name = sanitize_key( $_POST['vip_workflow_module_name'] );

		if ( 'update' != $_POST['action']
		|| VIP_Workflow::instance()->$module_name->module->options_group_name != $_POST['option_page'] ) {
			return false;
		}

		if ( ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( $_POST['_wpnonce'], VIP_Workflow::instance()->$module_name->module->options_group_name . '-options' ) ) {
			wp_die( esc_html__( 'Cheatin&#8217; uh?' ) );
		}

		$new_options = ( isset( $_POST[ VIP_Workflow::instance()->$module_name->module->options_group_name ] ) ) ? $_POST[ VIP_Workflow::instance()->$module_name->module->options_group_name ] : array();

		// Only call the validation callback if it exists?
		if ( method_exists( VIP_Workflow::instance()->$module_name, 'settings_validate' ) ) {
			$new_options = VIP_Workflow::instance()->$module_name->settings_validate( $new_options );
		}

		// Cast our object and save the data.
		$new_options = (object) array_merge( (array) VIP_Workflow::instance()->$module_name->module->options, $new_options );
		VIP_Workflow::instance()->update_all_module_options( VIP_Workflow::instance()->$module_name->module->name, $new_options );

		// Redirect back to the settings page that was submitted without any previous messages
		$goback = add_query_arg( 'message', 'settings-updated', remove_query_arg( array( 'message' ), wp_get_referer() ) );
		wp_safe_redirect( $goback );
		exit;
	}
}
