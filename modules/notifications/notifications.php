<?php
/**
 * class Notifications
 * Email notifications for VIP Workflow and more
 */
namespace VIPWorkflow\Modules;

use VIPWorkflow\VIP_Workflow;
use VIPWorkflow\Common\PHP\Module;
use function VIPWorkflow\Common\PHP\vw_draft_or_post_title;

class Notifications extends Module {

	public $module;

	/**
	 * Register the module with VIP Workflow but don't do anything else
	 */
	public function __construct() {

		// Register the module with VIP Workflow
		$this->module_url = $this->get_module_url( __FILE__ );
		$args             = [
			'title'                => __( 'Notifications', 'vip-workflow' ),
			'short_description'    => __( 'Update your team of important changes to your content.', 'vip-workflow' ),
			'extended_description' => __( 'You can keep everyone updated about what is happening with a given content. This is possible through webhook notifications, and emails to admins. Each status change sends out a notification to the specified webhook URL(i.e.: Slack incoming webhooks) and/or email notifications to the admin.', 'vip-workflow' ),
			'module_url'           => $this->module_url,
			'img_url'              => $this->module_url . 'lib/notifications_s128.png',
			'slug'                 => 'notifications',
			'autoload'             => true,
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
	 * Set up and send post status change notification email
	 */
	public function notification_status_change( $new_status, $old_status, $post ) {
		$supported_post_types = $this->get_supported_post_types();
		if ( ! in_array( $post->post_type, $supported_post_types ) ) {
			return;
		}

		/**
		 * Filter the statuses that should be ignored when sending notifications
		 *
		 * @param array $ignored_statuses Array of statuses that should be ignored when sending notifications
		 * @param string $post_type The post type of the post
		 */
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

			// ToDo: See how we can optimize this, using batching as well as async processing.
			if ( 'on' === VIP_Workflow::instance()->settings->module->options->send_to_webhook ) {
				/* translators: 1: user name, 2: post type, 3: post id, 4: edit link, 5: post title, 6: old status, 7: new status */
				$format = __( '*%1$s* changed the status of *%2$s #%3$s - <%4$s|%5$s>* from *%6$s* to *%7$s*', 'vip-workflow' );
				$text   = sprintf( $format, $current_user->display_name, $post_type, $post_id, $edit_link, $post_title, $old_status_friendly_name, $new_status_friendly_name );

				$this->send_to_webhook( $text, 'status-change', $current_user, $post );
			}
		}
	}

	/**
	 * Get the footer for the email notification
	 *
	 * @param WP_Post $post
	 * @return string Footer for the email notification
	 */
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
	 * Send email notifications
	 *
	 * @param string $action (status-change)
	 * @param string $subject Subject of the email
	 * @param string $message Body of the email
	 * @param string $message_headers. (optional) Message headers
	 */
	public function send_email( $action, $post, $subject, $message, $message_headers = '' ) {

		// Get list of email recipients -- set them CC
		$recipients = $this->get_notification_recipients( $post, true );

		if ( $recipients && ! is_array( $recipients ) ) {
			$recipients = explode( ',', $recipients );
		}

		// ToDo: Figure out best filters to add for the email.

		if ( ! empty( $recipients ) ) {
			// ToDo: Let's batch these emails, and send collate the updates so we don't schedule too many emails.
			$this->schedule_emails( $recipients, $subject, $message, $message_headers );
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
		$webhook_url = VIP_Workflow::instance()->settings->module->options->webhook_url;

		// Bail if the webhook URL is not set
		if ( empty( $webhook_url ) ) {
			return;
		}

		// Set up the payload
		$payload = [
			'type'      => 'plugin:vip-workflow:post-update',
			'timestamp' => $post->post_modified_gmt,
			'data'      => $message,
		];

		/**
		 * Filter the payload before sending it to the webhook
		 *
		 * @param array $payload Payload to be sent to the webhook
		 * @param string $action Action being taken
		 * @param WP_User $user User who is taking the action
		 * @param WP_Post $post Post that the action is being taken on
		 */
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
	private function get_notification_recipients( $post, $string = false ) {
		$post_id = $post->ID;
		if ( ! $post_id ) {
			return $string ? '' : [];
		}

		// Email all admins if enabled.
		$admins = [];
		if ( 'on' === VIP_Workflow::instance()->settings->module->options->always_notify_admin ) {
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
