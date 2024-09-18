<?php
/**
 * class Notifications
 * Email notifications for VIP Workflow and more
 */
namespace VIPWorkflow\Modules;

use VIPWorkflow\VIP_Workflow;
use VIPWorkflow\Modules\Shared\PHP\Module;
use function VIPWorkflow\Modules\Shared\PHP\vw_draft_or_post_title;

class Notifications extends Module {

	public $module;

	/**
	 * Register the module with VIP Workflow but don't do anything else
	 */
	public function __construct() {

		// Register the module with VIP Workflow
		$this->module_url = $this->get_module_url( __FILE__ );
		$args             = [
			'module_url'            => $this->module_url,
			'slug'                  => 'notifications',
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
		add_action( 'vw_send_scheduled_emails', [ $this, 'send_emails' ], 10, 4 );
		// Schedule webhook sending
		add_action( 'vw_send_scheduled_webhook', [ $this, 'send_to_webhook' ], 10, 4 );
	}

	/**
	 * Set up and send post status change notification email
	 */
	public function notification_status_change( $new_status, $old_status, $post ) {
		$supported_post_types = VIP_Workflow::instance()->get_supported_post_types();
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

			$blogname = get_option( 'blogname' );

			$body = '';

			$post_id    = $post->ID;
			$post_title = vw_draft_or_post_title( $post_id );
			$post_type  = $post->post_type;

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
				$body .= sprintf( __( 'A new %1$s (#%2$s "%3$s") was created by %4$s %5$s.', 'vip-workflow' ), $post_type, $post_id, $post_title, $current_user->display_name, $current_user->user_email ) . "\r\n";
			} elseif ( 'trash' == $new_status ) {
				/* translators: 1: site name, 2: post type, 3. post title */
				$subject = sprintf( __( '[%1$s] %2$s Trashed: "%3$s"', 'vip-workflow' ), $blogname, $post_type, $post_title );
				/* translators: 1: post type, 2: post id, 3. post title, 4. user name, 5. user email */
				$body .= sprintf( __( '%1$s #%2$s "%3$s" was moved to the trash by %4$s %5$s.', 'vip-workflow' ), $post_type, $post_id, $post_title, $current_user_display_name, $current_user_email ) . "\r\n";
			} elseif ( 'trash' == $old_status ) {
				/* translators: 1: site name, 2: post type, 3. post title */
				$subject = sprintf( __( '[%1$s] %2$s Restored (from Trash): "%3$s"', 'vip-workflow' ), $blogname, $post_type, $post_title );
				/* translators: 1: post type, 2: post id, 3. post title, 4. user name, 5. user email */
				$body .= sprintf( __( '%1$s #%2$s "%3$s" was restored from trash by %4$s %5$s.', 'vip-workflow' ), $post_type, $post_id, $post_title, $current_user_display_name, $current_user_email ) . "\r\n";
			} elseif ( 'future' == $new_status ) {
				/* translators: 1: site name, 2: post type, 3. post title */
				$subject = sprintf( __( '[%1$s] %2$s Scheduled: "%3$s"' ), $blogname, $post_type, $post_title );
				/* translators: 1: post type, 2: post id, 3. post title, 4. user name, 5. user email 6. scheduled date  */
				$body .= sprintf( __( '%1$s #%2$s "%3$s" was scheduled by %4$s %5$s.  It will be published on %6$s' ), $post_type, $post_id, $post_title, $current_user_display_name, $current_user_email, $this->get_scheduled_datetime( $post ) ) . "\r\n";
			} elseif ( 'publish' == $new_status ) {
				/* translators: 1: site name, 2: post type, 3. post title */
				$subject = sprintf( __( '[%1$s] %2$s Published: "%3$s"', 'vip-workflow' ), $blogname, $post_type, $post_title );
				/* translators: 1: post type, 2: post id, 3. post title, 4. user name, 5. user email */
				$body .= sprintf( __( '%1$s #%2$s "%3$s" was published by %4$s %5$s.', 'vip-workflow' ), $post_type, $post_id, $post_title, $current_user_display_name, $current_user_email ) . "\r\n";
			} elseif ( 'publish' == $old_status ) {
				/* translators: 1: site name, 2: post type, 3. post title */
				$subject = sprintf( __( '[%1$s] %2$s Unpublished: "%3$s"', 'vip-workflow' ), $blogname, $post_type, $post_title );
				/* translators: 1: post type, 2: post id, 3. post title, 4. user name, 5. user email */
				$body .= sprintf( __( '%1$s #%2$s "%3$s" was unpublished by %4$s %5$s.', 'vip-workflow' ), $post_type, $post_id, $post_title, $current_user_display_name, $current_user_email ) . "\r\n";
			} else {
				/* translators: 1: site name, 2: post type, 3. post title */
				$subject = sprintf( __( '[%1$s] %2$s Status Changed for "%3$s"', 'vip-workflow' ), $blogname, $post_type, $post_title );

				/* translators: 1: post type, 2: post id, 3. post title, 4. user name, 5. user email, 6. old status, 7. new status  */
				$body .= sprintf( __( 'Status was changed for %1$s #%2$s "%3$s" by %4$s %5$s from "%6$s" to "%7$s".', 'vip-workflow' ), $post_type, $post_id, $post_title, $current_user_display_name, $current_user_email, $old_status_friendly_name, $new_status_friendly_name ) . "\r\n";
			}

			$body .= "\r\n";

			$edit_link = htmlspecialchars_decode( get_edit_post_link( $post_id ) );
			if ( 'publish' != $new_status ) {
				$view_link = add_query_arg( [ 'preview' => 'true' ], wp_get_shortlink( $post_id ) );
			} else {
				$view_link = htmlspecialchars_decode( get_permalink( $post_id ) );
			}
			/* translators: 1: edit link */
			$body .= sprintf( __( 'Edit: %s', 'vip-workflow' ), $edit_link ) . "\r\n";
			/* translators: 1: view link */
			$body .= sprintf( __( 'View: %s', 'vip-workflow' ), $view_link ) . "\r\n";

			$body .= $this->get_notification_footer( $post );

			$this->schedule_emails( 'status-change', $post, $subject, $body );

			$this->schedule_webhook_notification( $current_user->display_name, $post_type, $post_id, $edit_link, $post_title, $old_status_friendly_name, $new_status_friendly_name, $post->post_modified_gmt );
		}
	}

	/**
	 * Get the footer for the email notification
	 *
	 * @return string Footer for the email notification
	 */
	public function get_notification_footer() {
		$body  = '';
		$body .= "\r\n--------------------\r\n";
		/* translators: 1: post title */
		$body .= __( 'You are receiving this email because a notification was configured via the VIP Workflow Plugin.', 'vip-workflow' );
		$body .= "\r\n";
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
	public function schedule_emails( $action, $post, $subject, $message, $message_headers = '' ) {
		// Ensure the email address is set from settings.
		if ( empty( VIP_Workflow::instance()->settings->module->options->email_address ) ) {
			return;
		}

		$email_recipients = [ VIP_Workflow::instance()->settings->module->options->email_address ];

		/**
		 * Filter the email recipients
		 *
		 * @param array $email_recipients Array of email recipients
		 * @param string $action Action being taken, eg. status-change
		 * @param WP_Post $post Post object
		 */
		$email_recipients = apply_filters( 'vw_notification_email_recipients', $email_recipients, $action, $post );

		/**
		 * Filter the email subject
		 *
		 * @param string $subject Subject of the email
		 * @param string $action Action being taken, eg. status-change
		 *
		 */
		$subject       = apply_filters( 'vw_notification_email_subject', $subject, $action, $post );

		/**
		 * Filter the email message
		 *
		 * @param string $message Body of the email
		 * @param string $action Action being taken, eg. status-change
		 * @param WP_Post $post Post object
		 */
		$message       = apply_filters( 'vw_notification_email_message', $message, $action, $post );

		/**
		 * Filter the email headers
		 *
		 * @param string $message_headers Message headers
		 * @param string $action Action being taken, eg. status-change
		 * @param WP_Post $post Post object
		 */
		$message_headers = apply_filters( 'vw_notification_email_headers', $message_headers, $action, $post );

		if ( ! empty( $email_recipients ) ) {
			wp_schedule_single_event( time(), 'vw_send_scheduled_emails', [ $email_recipients, $subject, $message, $message_headers ] );
		}
	}

	/**
	 * Sends emails
	 *
	 * @param mixed $to Emails to send to
	 * @param string $subject Subject of the email
	 * @param string $message Body of the email
	 * @param string $message_headers. (optional) Message headers
	 */
	public function send_emails( $recipients, $subject, $message, $message_headers = '' ) {
		$response = wp_mail( $recipients, $subject, $message, $message_headers );

		// ToDo: Switch to using log2logstash instead of error_log.
		if ( ! $response ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'Unable to send notification to email(s) provided.' );
		}
	}

	/**
	 * Schedule a webhook notification
	 *
	 * @param string $current_user Current user's name
	 * @param string $post_type Post type
	 * @param int $post_id Post ID
	 * @param string $edit_link Edit link for the post
	 * @param string $post_title Post title
	 * @param string $old_status Old status of the post
	 * @param string $new_status New status of the post
	 * @param string $post_timestamp Timestamp of the post's last update
	 */
	public function schedule_webhook_notification( $current_user, $post_type, $post_id, $edit_link, $post_title, $old_status, $new_status, $post_timestamp ) {
		// Ensure the webhook URL is set from settings.
		if ( empty( VIP_Workflow::instance()->settings->module->options->webhook_url ) ) {
			return;
		}

		/* translators: 1: user name, 2: post type, 3: post id, 4: edit link, 5: post title, 6: old status, 7: new status */
		$format = __( '*%1$s* changed the status of *%2$s #%3$s - <%4$s|%5$s>* from *%6$s* to *%7$s*', 'vip-workflow' );
		$message   = sprintf( $format, $current_user, $post_type, $post_id, $edit_link, $post_title, $old_status, $new_status );

		$message_type = 'plugin:vip-workflow:post-update';
		$timestamp    = $post_timestamp;

		wp_schedule_single_event( time(), 'vw_send_scheduled_webhook', [ $message, $message_type, $timestamp ] );
	}

	/**
	 * Send notifications to a webhook
	 *
	 * @param string $message Message to be sent to webhook
	 * @param string $message_type Type of message being sent
	 * @param string $timestamp Timestamp of the message that corresponds to the time at which the post was updated
	 */
	public function send_to_webhook( $message, $message_type, $timestamp ) {
		$webhook_url = VIP_Workflow::instance()->settings->module->options->webhook_url;

		// Set up the payload
		$payload = [
			'type'      => $message_type,
			'timestamp' => $timestamp,
			'data'      => $message,
		];

		/**
		 * Filter the payload before sending it to the webhook
		 *
		 * @param array $payload Payload to be sent to the webhook
		 */
		$payload = apply_filters( 'vw_notification_send_to_webhook_payload', $payload );

		// Send the notification
		$response = wp_remote_post(
			$webhook_url,
			[
				'body'    => wp_json_encode( $payload ),
				'headers' => [ 'Content-Type' => 'application/json' ],
			]
		);

		// ToDo: Switch to using log2logstash instead of error_log.
		if ( is_wp_error( $response ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'Unable to send notification to webhook provided.' );
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
