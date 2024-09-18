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
	const SETTINGS_SLUG = 'vw-settings';

	public $module;

	/**
	 * Register the module with VIP Workflow but don't do anything else
	 */
	public function __construct() {
		// Register the module with VIP Workflow
		$this->module_url = $this->get_module_url( __FILE__ );
		$args             = array(
			'module_url'        => $this->module_url,
			'slug'              => 'settings',
			'default_options'   => array(
				'post_types'          => [
					'post' => 'on',
					'page' => 'on',
				],
				'publish_guard'       => 'on',
				'email_address'       => '',
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
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

		add_action( 'admin_init', array( $this, 'helper_settings_validate_and_save' ), 100 );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		add_action( 'admin_print_styles', array( $this, 'action_admin_print_styles' ) );
		add_action( 'admin_print_scripts', array( $this, 'action_admin_print_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'action_admin_enqueue_scripts' ) );
	}

	/**
	 * Add the settings page to the admin menu
	 */
	public function add_admin_menu() {
		$menu_title = __( 'Settings', 'vip-workflow' );

		add_submenu_page( Custom_Status::SETTINGS_SLUG, $menu_title, $menu_title, 'manage_options', self::SETTINGS_SLUG, [ $this, 'render_settings_view' ] );
	}

	/**
	 * Add settings JS to the settings page
	 */
	public function action_admin_enqueue_scripts() {
		if ( VIP_Workflow::is_settings_view_loaded( self::SETTINGS_SLUG ) ) {
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
		add_settings_field( 'publish_guard', __( 'Publish Guard', 'vip-workflow' ), [ $this, 'settings_publish_guard' ], $this->module->options_group_name, $this->module->options_group_name . '_general' );

		add_settings_field( 'email_address', __( 'Email Address', 'vip-workflow' ), [ $this, 'settings_email_address' ], $this->module->options_group_name, $this->module->options_group_name . '_general' );
		add_settings_field( 'webhook_url', __( 'Webhook URL', 'vip-workflow' ), [ $this, 'settings_webhook_url' ], $this->module->options_group_name, $this->module->options_group_name . '_general' );
	}

	/**
	 * Adds Settings page for VIP Workflow.
	 */
	public function render_settings_view() {
		include_once __DIR__ . '/views/settings.php';
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

		printf( '<p class="description">%s</p>', esc_html__( 'Require posts to travel through custom statuses before publishing.', 'vip-workflow' ) );
	}

	/**
	 * Option to set an email address to send notifications to
	 */
	public function settings_email_address() {
		printf( '<input type="text" id="email_address" name="%s[email_address]" value="%s" />', esc_attr( $this->module->options_group_name ), esc_attr( $this->module->options->email_address ) );
		printf( '<p class="description">%s</p>', esc_html__( 'Notify via email, when posts change custom statuses.', 'vip-workflow' ) );
	}

	/**
	 * Option to set the Slack webhook URL
	 */
	public function settings_webhook_url() {
		printf( '<input type="text" id="webhook_url" name="%s[webhook_url]" value="%s" />', esc_attr( $this->module->options_group_name ), esc_attr( $this->module->options->webhook_url ) );
		printf( '<p class="description">%s</p>', esc_html__( 'Notify a webhook URL when posts change custom statuses.', 'vip-workflow' ) );
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

		printf( '<p class="description" style="margin-top: 0.5rem">%s</p>', esc_html__( 'Enable workflow custom statuses on the above post types.', 'vip-workflow' ) );
	}

	/**
	 * Validate input from the end user
	 */
	public function settings_validate( $new_options ) {
		// ToDo: There's no error messages shown right now, or any kind of notice that data is invalid.

		// Whitelist validation for the post type options
		if ( ! isset( $new_options['post_types'] ) ) {
			$new_options['post_types'] = [];
		}
		$new_options['post_types'] = $this->clean_post_type_options( $new_options['post_types'], $this->module->post_type_support );

		// Whitelist validation for the 'publish_guard' optoins
		if ( ! isset( $new_options['publish_guard'] ) || 'on' != $new_options['publish_guard'] ) {
			$new_options['publish_guard'] = 'off';
		}

		// White list validation for the 'email_address' option
		if ( ! isset( $new_options['email_address'] ) || ! filter_var( $new_options['email_address'], FILTER_VALIDATE_EMAIL ) ) {
			$new_options['email_address'] = '';
		} else {
			$new_options['email_address'] = sanitize_email( $new_options['email_address'] );
		}

		// White list validation for the 'slack_webhook_url' option
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

		if ( ! isset( $_POST['action'], $_POST['_wpnonce'], $_POST['option_page'], $_POST['_wp_http_referer'], $_POST['submit'] ) || ! is_admin() ) {
			return false;
		}

		$module_name = 'settings';

		if ( 'update' != $_POST['action']
		|| VIP_Workflow::instance()->$module_name->module->options_group_name != $_POST['option_page'] ) {
			return false;
		}

		if ( ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), VIP_Workflow::instance()->$module_name->module->options_group_name . '-options' ) ) {
			wp_die( esc_html__( 'Cheatin&#8217; uh?' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- check is already done above
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
