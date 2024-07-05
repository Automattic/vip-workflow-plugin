<?php
/**
 * class Notifications
 * Email notifications for VIP Workflow and more
 */
namespace VIPWorkflow\Modules;

use VIPWorkflow\VIP_Workflow;
use VIPWorkflow\Common\PHP\Module;
use function VIPWorkflow\Common\PHP\vw_draft_or_post_title;

if ( ! defined( 'VW_NOTIFICATION_USE_CRON' ) ) {
	define( 'VW_NOTIFICATION_USE_CRON', false );
}

class Notifications extends Module {

	public $module;

	public $edit_post_subscriptions_cap = 'edit_post_subscriptions';

	/**
	 * Register the module with VIP Workflow but don't do anything else
	 */
	public function __construct() {

		// Register the module with VIP Workflow
		$this->module_url = $this->get_module_url( __FILE__ );
		$args             = [
			'title'                 => __( 'Notifications', 'vip-workflow' ),
			'short_description'     => __( 'Update your team of important changes to your content.', 'vip-workflow' ),
			'extended_description'  => __( 'You can keep everyone updated about what is happening with a given content. This is possible through webhook notifications, and emails to admins. Each status change sends out a notification to the specified webhook URL(i.e.: Slack incoming webhooks) and/or email notifications to the admin.', 'vip-workflow' ),
			'module_url'            => $this->module_url,
			'img_url'               => $this->module_url . 'lib/notifications_s128.png',
			'slug'                  => 'notifications',
			'default_options'       => [
				'post_types'          => [
					'post' => 'on',
					'page' => 'on',
				],
				'always_notify_admin' => 'on',
				'send_to_webhook'     => 'off',
				'webhook_url'         => '',
			],
			'configure_page_cb'     => 'print_configure_view',
			'post_type_support'     => 'vw_notification',
			'autoload'              => true,
		];
		$this->module     = VIP_Workflow::instance()->register_module( 'notifications', $args );
	}

	/**
	 * Initialize the notifications class if the plugin is enabled
	 */
	public function init() {
		// Send notifications on post status change
		add_action( 'transition_post_status', [ $this, 'notification_status_change' ], 10, 3 );
		// Schedule email sending
		add_action( 'vw_send_scheduled_email', [ $this, 'send_single_email' ], 10, 4 );

		add_action( 'admin_init', [ $this, 'register_settings' ] );

		// Javascript and CSS if we need it
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_styles' ] );
	}

	/**
	 * Load the capabilities onto users the first time the module is run
	 */
	public function install() {
		// Nothing to do here yet
	}

	/**
	 * Upgrade our data in case we need to
	 */
	public function upgrade( $previous_version ) {
		// Nothing to do here yet
	}

	/**
	 * Enqueue necessary admin scripts
	 * @uses wp_enqueue_script()
	 */
	public function enqueue_admin_scripts() {
		if ( $this->is_whitelisted_functional_view() ) {
			wp_enqueue_script( 'vip-workflow-notifications-js', $this->module_url . 'lib/notifications.js', [ 'jquery' ], VIP_WORKFLOW_VERSION, true );
		}
	}

	/**
	 * Enqueue necessary admin styles, but only on the proper pages
	 *
	 * @uses wp_enqueue_style()
	 */
	public function enqueue_admin_styles() {
		if ( $this->is_whitelisted_functional_view() || $this->is_whitelisted_settings_view() ) {
			wp_enqueue_style( 'vip-workflow-notifications-css', $this->module->module_url . 'lib/notifications.css', false, VIP_WORKFLOW_VERSION );
		}
	}

	/**
	 * Set up and send post status change notification email
	 */
	public function notification_status_change( $new_status, $old_status, $post ) {
		$supported_post_types = $this->get_post_types_for_module( $this->module );
		if ( ! in_array( $post->post_type, $supported_post_types ) ) {
			return;
		}

		// No need to notify if it's a revision, auto-draft, or if post status wasn't changed
		$ignored_statuses = apply_filters( 'vw_notification_ignored_statuses', [ $old_status, 'inherit', 'auto-draft' ], $post->post_type );

		if ( ! in_array( $new_status, $ignored_statuses ) ) {
			// Get current user
			$current_user = wp_get_current_user();

			$post_author = get_userdata( $post->post_author );

			$blogname = get_option( 'blogname' );

			$body = '';

			$post_id    = $post->ID;
			$post_title = vw_draft_or_post_title( $post_id );
			$post_type  = get_post_type_object( $post->post_type )->labels->singular_name;

			if ( 0 != $current_user->ID ) {
				$current_user_display_name = $current_user->display_name;
				$current_user_email        = sprintf( '(%s)', $current_user->user_email );
			} else {
				$current_user_display_name = __( 'WordPress Scheduler', 'vip-workflow' );
				$current_user_email        = '';
			}

			$old_status_post_obj      = get_post_status_object( $old_status );
			$new_status_post_obj      = get_post_status_object( $new_status );
			$old_status_friendly_name = '';
			$new_status_friendly_name = '';

			/**
			 * get_post_status_object will return null for certain statuses (i.e., 'new')
			 * The mega if/else block below should catch all cases, but just in case, we
			 * make sure to at least set $old_status_friendly_name and $new_status_friendly_name
			 * to an empty string to ensure they're at least set.
			 *
			 * Then, we attempt to set them to a sensible default before we start the
			 * mega if/else block
			 */
			if ( ! is_null( $old_status_post_obj ) ) {
				$old_status_friendly_name = $old_status_post_obj->label;
			}

			if ( ! is_null( $new_status_post_obj ) ) {
				$new_status_friendly_name = $new_status_post_obj->label;
			}

			// Email subject and first line of body
			// Set message subjects according to what action is being taken on the Post
			if ( 'new' == $old_status || 'auto-draft' == $old_status ) {
				$old_status_friendly_name = 'New';
				/* translators: 1: site name, 2: post type, 3. post title */
				$subject = sprintf( __( '[%1$s] New %2$s Created: "%3$s"', 'vip-workflow' ), $blogname, $post_type, $post_title );
				/* translators: 1: post type, 2: post id, 3. post title, 4. user name, 5. user email */
				$body .= sprintf( __( 'A new %1$s (#%2$s "%3$s") was created by %4$s %5$s', 'vip-workflow' ), $post_type, $post_id, $post_title, $current_user->display_name, $current_user->user_email ) . "\r\n";
			} elseif ( 'trash' == $new_status ) {
				/* translators: 1: site name, 2: post type, 3. post title */
				$subject = sprintf( __( '[%1$s] %2$s Trashed: "%3$s"', 'vip-workflow' ), $blogname, $post_type, $post_title );
				/* translators: 1: post type, 2: post id, 3. post title, 4. user name, 5. user email */
				$body .= sprintf( __( '%1$s #%2$s "%3$s" was moved to the trash by %4$s %5$s', 'vip-workflow' ), $post_type, $post_id, $post_title, $current_user_display_name, $current_user_email ) . "\r\n";
			} elseif ( 'trash' == $old_status ) {
				/* translators: 1: site name, 2: post type, 3. post title */
				$subject = sprintf( __( '[%1$s] %2$s Restored (from Trash): "%3$s"', 'vip-workflow' ), $blogname, $post_type, $post_title );
				/* translators: 1: post type, 2: post id, 3. post title, 4. user name, 5. user email */
				$body .= sprintf( __( '%1$s #%2$s "%3$s" was restored from trash by %4$s %5$s', 'vip-workflow' ), $post_type, $post_id, $post_title, $current_user_display_name, $current_user_email ) . "\r\n";
			} elseif ( 'future' == $new_status ) {
				/* translators: 1: site name, 2: post type, 3. post title */
				$subject = sprintf( __( '[%1$s] %2$s Scheduled: "%3$s"' ), $blogname, $post_type, $post_title );
				/* translators: 1: post type, 2: post id, 3. post title, 4. user name, 5. user email 6. scheduled date  */
				$body .= sprintf( __( '%1$s #%2$s "%3$s" was scheduled by %4$s %5$s.  It will be published on %6$s' ), $post_type, $post_id, $post_title, $current_user_display_name, $current_user_email, $this->get_scheduled_datetime( $post ) ) . "\r\n";
			} elseif ( 'publish' == $new_status ) {
				/* translators: 1: site name, 2: post type, 3. post title */
				$subject = sprintf( __( '[%1$s] %2$s Published: "%3$s"', 'vip-workflow' ), $blogname, $post_type, $post_title );
				/* translators: 1: post type, 2: post id, 3. post title, 4. user name, 5. user email */
				$body .= sprintf( __( '%1$s #%2$s "%3$s" was published by %4$s %5$s', 'vip-workflow' ), $post_type, $post_id, $post_title, $current_user_display_name, $current_user_email ) . "\r\n";
			} elseif ( 'publish' == $old_status ) {
				/* translators: 1: site name, 2: post type, 3. post title */
				$subject = sprintf( __( '[%1$s] %2$s Unpublished: "%3$s"', 'vip-workflow' ), $blogname, $post_type, $post_title );
				/* translators: 1: post type, 2: post id, 3. post title, 4. user name, 5. user email */
				$body .= sprintf( __( '%1$s #%2$s "%3$s" was unpublished by %4$s %5$s', 'vip-workflow' ), $post_type, $post_id, $post_title, $current_user_display_name, $current_user_email ) . "\r\n";
			} else {
				/* translators: 1: site name, 2: post type, 3. post title */
				$subject = sprintf( __( '[%1$s] %2$s Status Changed for "%3$s"', 'vip-workflow' ), $blogname, $post_type, $post_title );
				/* translators: 1: post type, 2: post id, 3. post title, 4. user name, 5. user email */
				$body .= sprintf( __( 'Status was changed for %1$s #%2$s "%3$s" by %4$s %5$s', 'vip-workflow' ), $post_type, $post_id, $post_title, $current_user_display_name, $current_user_email ) . "\r\n";
			}

			/* translators: 1: date, 2: time, 3: timezone */
			$body .= sprintf( __( 'This action was taken on %1$s at %2$s %3$s', 'vip-workflow' ), date_i18n( get_option( 'date_format' ) ), date_i18n( get_option( 'time_format' ) ), get_option( 'timezone_string' ) ) . "\r\n";

			// Email body
			$body .= "\r\n";
			/* translators: 1: old status, 2: new status */
			$body .= sprintf( __( '%1$s => %2$s', 'vip-workflow' ), $old_status_friendly_name, $new_status_friendly_name );
			$body .= "\r\n\r\n";

			$body .= "--------------------\r\n\r\n";

			/* translators: 1: post type */
			$body .= sprintf( __( '== %s Details ==', 'vip-workflow' ), $post_type ) . "\r\n";
			/* translators: 1: post title */
			$body .= sprintf( __( 'Title: %s', 'vip-workflow' ), $post_title ) . "\r\n";
			if ( ! empty( $post_author ) ) {
				/* translators: 1: author name, 2: author email */
				$body .= sprintf( __( 'Author: %1$s (%2$s)', 'vip-workflow' ), $post_author->display_name, $post_author->user_email ) . "\r\n";
			}

			$edit_link = htmlspecialchars_decode( get_edit_post_link( $post_id ) );
			if ( 'publish' != $new_status ) {
				$view_link = add_query_arg( [ 'preview' => 'true' ], wp_get_shortlink( $post_id ) );
			} else {
				$view_link = htmlspecialchars_decode( get_permalink( $post_id ) );
			}
			$body .= "\r\n";
			$body .= __( '== Actions ==', 'vip-workflow' ) . "\r\n";
			/* translators: 1: edit link */
			$body .= sprintf( __( 'Edit: %s', 'vip-workflow' ), $edit_link ) . "\r\n";
			/* translators: 1: view link */
			$body .= sprintf( __( 'View: %s', 'vip-workflow' ), $view_link ) . "\r\n";

			$body .= $this->get_notification_footer( $post );

			$this->send_email( 'status-change', $post, $subject, $body );

			if ( 'on' === $this->module->options->send_to_webhook ) {
				/* translators: 1: user name, 2: post type, 3: post id, 4: edit link, 5: post title, 6: old status, 7: new status */
				$format = __( '*%1$s* changed the status of *%2$s #%3$s - <%4$s|%5$s>* from *%6$s* to *%7$s*', 'vip-workflow' );
				$text   = sprintf( $format, $current_user->display_name, $post_type, $post_id, $edit_link, $post_title, $old_status_friendly_name, $new_status_friendly_name );

				$this->send_to_webhook( $text, 'status-change', $current_user, $post );
			}
		}
	}

	public function get_notification_footer( $post ) {
		$body  = '';
		$body .= "\r\n--------------------\r\n";
		/* translators: 1: post title */
		$body .= sprintf( __( 'You are receiving this email because you are subscribed to "%s".', 'vip-workflow' ), vw_draft_or_post_title( $post->ID ) );
		$body .= "\r\n";
		/* translators: 1: date */
		$body .= sprintf( __( 'This email was sent %s.', 'vip-workflow' ), gmdate( 'r' ) );
		$body .= "\r\n \r\n";
		$body .= get_option( 'blogname' ) . ' | ' . get_bloginfo( 'url' ) . ' | ' . admin_url( '/' ) . "\r\n";
		return $body;
	}

	/**
	 * send_email()
	 */
	public function send_email( $action, $post, $subject, $message, $message_headers = '' ) {

		// Get list of email recipients -- set them CC
		$recipients = $this->_get_notification_recipients( $post, true );

		if ( $recipients && ! is_array( $recipients ) ) {
			$recipients = explode( ',', $recipients );
		}

		// ToDo: Do we want to keep these filters for these email parts?
		$subject         = apply_filters( 'vw_notification_send_email_subject', $subject, $action, $post );
		$message         = apply_filters( 'vw_notification_send_email_message', $message, $action, $post );
		$message_headers = apply_filters( 'vw_notification_send_email_message_headers', $message_headers, $action, $post );

		if ( VW_NOTIFICATION_USE_CRON ) {
			$this->schedule_emails( $recipients, $subject, $message, $message_headers );
		} elseif ( ! empty( $recipients ) ) {
			foreach ( $recipients as $recipient ) {
				$this->send_single_email( $recipient, $subject, $message, $message_headers );
			}
		}
	}

	/**
	 * Send notifications to Slack
	 *
	 * @param string $message Message to be sent to webhook
	 * @param string $action Action being taken. Currently only `status-change`
	 * @param WP_User $user User who is taking the action
	 * @param WP_Post $post Post that the action is being taken on
	 */
	public function send_to_webhook( $message, $action, $user, $post ) {
		$webhook_url = $this->module->options->webhook_url;

		// Bail if the webhook URL is not set
		if ( empty( $webhook_url ) ) {
			return;
		}

		// Set up the payload
		$payload = [
			'text' => $message,
		];

		// apply filters to the payload
		$payload = apply_filters( 'vw_notification_send_to_webhook_payload', $payload, $action, $user, $post );

		// Send the notification
		$response = wp_remote_post(
			$webhook_url,
			[
				'body'    => wp_json_encode( $payload ),
				'headers' => [ 'Content-Type' => 'application/json' ],
			]
		);
		if ( is_wp_error( $response ) ) {
			$this->print_ajax_response( 'error', 'Unable to send notification to webhook provided', 400 );
		}
	}

	/**
	 * Schedules emails to be sent in succession
	 *
	 * @param mixed $recipients Individual email or array of emails
	 * @param string $subject Subject of the email
	 * @param string $message Body of the email
	 * @param string $message_headers. (optional) Message headers
	 * @param int $time_offset (optional) Delay in seconds per email
	 */
	public function schedule_emails( $recipients, $subject, $message, $message_headers = '', $time_offset = 1 ) {
		$recipients = (array) $recipients;

		$send_time = time();

		foreach ( $recipients as $recipient ) {
			wp_schedule_single_event( $send_time, 'vw_send_scheduled_email', [ $recipient, $subject, $message, $message_headers ] );
			$send_time += $time_offset;
		}
	}

	/**
	 * Sends an individual email
	 *
	 * @param mixed $to Email to send to
	 * @param string $subject Subject of the email
	 * @param string $message Body of the email
	 * @param string $message_headers. (optional) Message headers
	 */
	public function send_single_email( $to, $subject, $message, $message_headers = '' ) {
		wp_mail( $to, $subject, $message, $message_headers );
	}

	/**
	 * Returns a list of recipients for a given post.
	 *
	 * @param WP_Post $post
	 * @param bool $string Whether to return recipients as comma-delimited string or array.
	 * @return string|array Recipients to receive notification.
	 */
	private function _get_notification_recipients( $post, $string = false ) {
		$post_id = $post->ID;
		if ( ! $post_id ) {
			return $string ? '' : [];
		}

		// Email all admins if enabled.
		$admins = [];
		if ( 'on' === $this->module->options->always_notify_admin ) {
			$admins[] = get_option( 'admin_email' );
		}

		// If string set to true, return comma-delimited.
		if ( $string && is_array( $admins ) ) {
			return implode( ',', $admins );
		} else {
			return $admins;
		}
	}

	/**
	 * Register settings for notifications so we can partially use the Settings API
	 * (We use the Settings API for form generation, but not saving)
	 */
	public function register_settings() {
			add_settings_section( $this->module->options_group_name . '_general', false, '__return_false', $this->module->options_group_name );
			add_settings_field( 'post_types', __( 'Post types for notifications:', 'vip-workflow' ), [ $this, 'settings_post_types_option' ], $this->module->options_group_name, $this->module->options_group_name . '_general' );
			add_settings_field( 'always_notify_admin', __( 'Always notify blog admin', 'vip-workflow' ), [ $this, 'settings_always_notify_admin_option' ], $this->module->options_group_name, $this->module->options_group_name . '_general' );
			add_settings_field( 'send_to_webhook', __( 'Send to Webhook', 'vip-workflow' ), [ $this, 'settings_send_to_webhook' ], $this->module->options_group_name, $this->module->options_group_name . '_general' );
			add_settings_field( 'webhook_url', __( 'Webhook URL', 'vip-workflow' ), [ $this, 'settings_webhook_url' ], $this->module->options_group_name, $this->module->options_group_name . '_general' );
	}

	/**
	 * Chose the post types for notifications
	 */
	public function settings_post_types_option() {
		global $vip_workflow;
		$vip_workflow->settings->helper_option_custom_post_type( $this->module );
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
		echo '<input type="text" id="webhook_url" name="' . esc_attr( $this->module->options_group_name ) . '[webhook_url]" value="' . esc_attr( $this->module->options->webhook_url ) . '" />';
	}

	/**
	 * Validate our user input as the settings are being saved
	 */
	public function settings_validate( $new_options ) {

		// Whitelist validation for the post type options
		if ( ! isset( $new_options['post_types'] ) ) {
			$new_options['post_types'] = [];
		}
		$new_options['post_types'] = $this->clean_post_type_options( $new_options['post_types'], $this->module->post_type_support );

		// Whitelist validation for the 'always_notify_admin' options
		if ( ! isset( $new_options['always_notify_admin'] ) || 'on' != $new_options['always_notify_admin'] ) {
			$new_options['always_notify_admin'] = 'off';
		}

		// White list validation for the 'send_to_slack' option
		if ( ! isset( $new_options['send_to_webhook'] ) || 'on' != $new_options['send_to_webhook'] ) {
			$new_options['send_to_webhook'] = 'off';
			// Reset the webhook URL if it's not turned on.
			$new_options['webhook_url'] = '';
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
	 * Settings page for notifications
	 */
	public function print_configure_view() {
		?>
			<form class="basic-settings" action="<?php echo esc_url( menu_page_url( $this->module->settings_slug, false ) ); ?>" method="post">
			<?php settings_fields( $this->module->options_group_name ); ?>
			<?php do_settings_sections( $this->module->options_group_name ); ?>
			<?php
				echo '<input id="vip_workflow_module_name" name="vip_workflow_module_name" type="hidden" value="' . esc_attr( $this->module->name ) . '" />';
			?>
				<p class="submit"><?php submit_button( null, 'primary', 'submit', false ); ?><a class="cancel-settings-link" href="<?php echo esc_url( VIP_WORKFLOW_SETTINGS_PAGE ); ?>"><?php _e( 'Back to VIP Workflow', 'vip-workflow' ); ?></a></p>
			</form>
			<?php
	}

	/**
	* Gets a simple phrase containing the formatted date and time that the post is scheduled for.
	*
	* @param  obj    $post               Post object
	* @return str    $scheduled_datetime The scheduled datetime in human-readable format
	*/
	private function get_scheduled_datetime( $post ) {

			$scheduled_ts = strtotime( $post->post_date );

			$date = date_i18n( get_option( 'date_format' ), $scheduled_ts );
			$time = date_i18n( get_option( 'time_format' ), $scheduled_ts );

			/* translators: 1: date, 2: time */
			return sprintf( __( '%1$s at %2$s', 'vip-workflow' ), $date, $time );
	}
}

