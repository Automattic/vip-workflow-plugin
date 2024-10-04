<?php

/**
 * class Settings
 *
 * Settings module for VIP Workflow
 */
namespace VIPWorkflow\Modules;

use VIPWorkflow\VIP_Workflow;
use VIPWorkflow\Modules\Shared\PHP\OptionsUtilities;

class Settings {
	const SETTINGS_SLUG = 'vw-settings';

	/**
	 * Initialize the rest of the stuff in the class if the module is active
	 */
	public static function init(): void {
		add_action( 'admin_menu', [ __CLASS__, 'add_admin_menu' ] );

		add_action( 'admin_init', [ __CLASS__, 'helper_settings_validate_and_save' ], 100 );
		add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );

		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'action_admin_enqueue_scripts' ] );
	}

	/**
	 * Add the settings page to the admin menu
	 */
	public static function add_admin_menu(): void {
		$menu_title = __( 'Settings', 'vip-workflow' );

		add_submenu_page( CustomStatus::SETTINGS_SLUG, $menu_title, $menu_title, 'manage_options', self::SETTINGS_SLUG, [ __CLASS__, 'render_settings_view' ] );
	}

	/**
	 * Enqueue resources that we need in the admin settings page
	 *
	 * @access private
	 */
	public static function action_admin_enqueue_scripts(): void {
		if ( self::is_settings_view_loaded( self::SETTINGS_SLUG ) ) {
			$asset_file = include VIP_WORKFLOW_ROOT . '/dist/modules/settings/settings.asset.php';
			wp_enqueue_script( 'vip-workflow-settings-js', VIP_WORKFLOW_URL . 'dist/modules/settings/settings.js', $asset_file['dependencies'], $asset_file['version'], true );
		}
	}

	/**
	 * Register the settings for the module
	 */
	public static function register_settings(): void {
		$settings_option = OptionsUtilities::get_module_options_key( self::SETTINGS_SLUG );
		$settings_general_option = OptionsUtilities::get_module_options_general_key( self::SETTINGS_SLUG );

		add_settings_section( $settings_general_option, false, '__return_false', $settings_option );

		add_settings_field( 'post_types', __( 'Use on these post types:', 'vip-workflow' ), [ __CLASS__, 'helper_option_custom_post_type' ], $settings_option, $settings_general_option );
		add_settings_field( 'publish_guard', __( 'Publish Guard', 'vip-workflow' ), [ __CLASS__, 'settings_publish_guard' ], $settings_option, $settings_general_option );

		add_settings_field( 'email_address', __( 'Email Address', 'vip-workflow' ), [ __CLASS__, 'settings_email_address' ], $settings_option, $settings_general_option );
		add_settings_field( 'webhook_url', __( 'Webhook URL', 'vip-workflow' ), [ __CLASS__, 'settings_webhook_url' ], $settings_option, $settings_general_option );
	}

	/**
	 * Adds Settings page for VIP Workflow.
	 */
	public static function render_settings_view(): void {
		include_once __DIR__ . '/views/settings.php';
	}

	/**
	 * Option for whether the publish guard feature should be enabled
	 */
	public static function settings_publish_guard(): void {
		$options = [
			'off' => __( 'Disabled', 'vip-workflow' ),
			'on'  => __( 'Enabled', 'vip-workflow' ),
		];
		echo '<select id="publish_guard" name="' . esc_attr( OptionsUtilities::get_module_options_key( self::SETTINGS_SLUG ) ) . '[publish_guard]">';
		foreach ( $options as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '"';
			echo selected( OptionsUtilities::get_module_option_by_key( self::SETTINGS_SLUG, 'publish_guard' ), $value );
			echo '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';

		printf( '<p class="description">%s</p>', esc_html__( 'Require posts to travel through custom statuses before publishing.', 'vip-workflow' ) );
	}

	/**
	 * Option to set an email address to send notifications to
	 */
	public static function settings_email_address(): void {
		printf( '<input type="text" id="email_address" name="%s[email_address]" value="%s" />', esc_attr( OptionsUtilities::get_module_options_key( self::SETTINGS_SLUG ) ), esc_attr( OptionsUtilities::get_module_option_by_key( self::SETTINGS_SLUG, 'email_address' ) ) );
		printf( '<p class="description">%s</p>', esc_html__( 'Notify via email, when posts change custom statuses.', 'vip-workflow' ) );
	}

	/**
	 * Option to set the Slack webhook URL
	 */
	public static function settings_webhook_url(): void {
		printf( '<input type="text" id="webhook_url" name="%s[webhook_url]" value="%s" />', esc_attr( OptionsUtilities::get_module_options_key( self::SETTINGS_SLUG ) ), esc_attr( OptionsUtilities::get_module_option_by_key( self::SETTINGS_SLUG, 'webhook_url' ) ) );
		printf( '<p class="description">%s</p>', esc_html__( 'Notify a webhook URL when posts change custom statuses.', 'vip-workflow' ) );
	}

	/**
	 * Generate an option field to turn post type support on/off for a given module
	 *
	 * @param array $args An array of arguments to pass to the function
	 */
	public static function helper_option_custom_post_type( $args = array() ): void {

		$all_post_types = array(
			'post' => __( 'Posts' ),
			'page' => __( 'Pages' ),
		);

		foreach ( $all_post_types as $post_type => $title ) {
			echo '<label for="' . esc_attr( $post_type ) . '">';
			echo '<input id="' . esc_attr( $post_type ) . '" name="'
			. esc_attr( OptionsUtilities::get_module_options_key( self::SETTINGS_SLUG ) ) . '[post_types][' . esc_attr( $post_type ) . ']"';
			$the_post_types = OptionsUtilities::get_module_option_by_key( self::SETTINGS_SLUG, 'post_types' );
			if ( isset( $the_post_types[ $post_type ] ) ) {
				checked( $the_post_types[ $post_type ], 'on' );
			}
			echo ' type="checkbox" />&nbsp;&nbsp;&nbsp;' . esc_html( $title ) . '</label>';
			echo '<br />';
		}

		printf( '<p class="description" style="margin-top: 0.5rem">%s</p>', esc_html__( 'Enable workflow custom statuses on the above post types.', 'vip-workflow' ) );
	}

	/**
	 * Whether or not the current page is our settings view. Determination is based on $pagenow, $_GET['page'], and if it's settings module or not.
	 *
	 * @return bool $is_settings_view Return true if it is
	 */
	public static function is_settings_view_loaded( string $slug ): bool {
		global $pagenow;

		// All of the settings views are based on admin.php and a $_GET['page'] parameter
		if ( 'admin.php' != $pagenow || ! isset( $_GET['page'] ) ) {
			return false;
		}

		// The current page better be in the array of registered settings view slugs
		return $_GET['page'] === $slug;
	}

	/**
	 * Cleans up the 'on' and 'off' for post types on a given module (so we don't get warnings all over)
	 * For every post type that doesn't explicitly have the 'on' value, turn it 'off'
	 *
	 * @param array $module_post_types Current state of post type options for the module
	 * @return array $normalized_post_type_options The setting for each post type, normalized based on rules
	 */
	private static function clean_post_type_options( $module_post_types = array() ): array {
		$normalized_post_type_options = array();
		$all_post_types               = array_keys( self::get_all_post_types() );
		foreach ( $all_post_types as $post_type ) {
			if ( ( isset( $module_post_types[ $post_type ] ) && 'on' == $module_post_types[ $post_type ] ) ) {
				$normalized_post_type_options[ $post_type ] = 'on';
			} else {
				$normalized_post_type_options[ $post_type ] = 'off';
			}
		}
		return $normalized_post_type_options;
	}

	/**
	 * Gets an array of allowed post types for a module
	 *
	 * @return array post-type-slug => post-type-label
	 */
	private static function get_all_post_types(): array {

		$allowed_post_types = array(
			'post' => __( 'Post' ),
			'page' => __( 'Page' ),
		);
		return $allowed_post_types;
	}

	/**
	 * Validate input from the end user
	 */
	public static function settings_validate( $new_options ): object {
		// Whitelist validation for the post type options
		if ( ! isset( $new_options['post_types'] ) ) {
			$new_options['post_types'] = [];
		}
		$new_options['post_types'] = self::clean_post_type_options( $new_options['post_types'] );

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
	public static function helper_settings_validate_and_save(): bool {

		if ( ! isset( $_POST['action'], $_POST['_wpnonce'], $_POST['option_page'], $_POST['_wp_http_referer'], $_POST['submit'] ) || ! is_admin() ) {
			return false;
		}

		if ( 'update' != $_POST['action']
		|| OptionsUtilities::get_module_options_key( self::SETTINGS_SLUG ) != $_POST['option_page'] ) {
			return false;
		}

		if ( ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), OptionsUtilities::get_module_options_key( self::SETTINGS_SLUG ) . '-options' ) ) {
			wp_die( esc_html__( 'Cheatin&#8217; uh?' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- validation and sanitization is done in the settings_validate method
		$new_options = ( isset( $_POST[ OptionsUtilities::get_module_options_key( self::SETTINGS_SLUG ) ] ) ) ? $_POST[ OptionsUtilities::get_module_options_key( self::SETTINGS_SLUG ) ] : array();

		$new_options = self::settings_validate( $new_options );

		OptionsUtilities::update_module_options( self::SETTINGS_SLUG, $new_options );

		// Redirect back to the settings page that was submitted without any previous messages
		$goback = add_query_arg( 'message', 'settings-updated', remove_query_arg( array( 'message' ), wp_get_referer() ) );
		wp_safe_redirect( $goback );
		exit;
	}
}

Settings::init();
