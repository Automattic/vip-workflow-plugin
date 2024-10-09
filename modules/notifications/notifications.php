<?php
/**
 * The notifications module for sending email/webhook notifications.
 *
 * @package vip-workflow
 */
namespace VIPWorkflow\Modules;

use WP_Post;

use VIPWorkflow\Modules\Shared\PHP\HelperUtilities;
use VIPWorkflow\Modules\Settings;
use VIPWorkflow\Modules\Shared\PHP\OptionsUtilities;
use function VIPWorkflow\Modules\Shared\PHP\vw_draft_or_post_title;

class Notifications {

	/**
	 * Initialize the notifications module
	 */
	public static function init(): void {
		// Send notifications on post status change
		add_action( 'transition_post_status', [ __CLASS__, 'notification_status_change' ], 10, 3 );

		// Schedule email sending
		add_action( 'vw_send_scheduled_emails', [ __CLASS__, 'send_emails' ], 10, 4 );

		// Schedule webhook sending
		add_action( 'vw_send_scheduled_webhook', [ __CLASS__, 'send_to_webhook' ], 10, 4 );
	}

	/**
	 * Set up and send post status change notification email
	 */
	public static function notification_status_change( string $new_status, string $old_status, WP_Post $post ): void {
		$supported_post_types = HelperUtilities::get_supported_post_types();
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

			$post_id           = $post->ID;
			$post_title        = vw_draft_or_post_title( $post_id );
			$post_type         = $post->post_type;
			$subject_post_type = ucfirst( $post_type );

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
			if ( 'new' === $old_status || 'auto-draft' === $old_status ) {
				$old_status_friendly_name = 'New';
				/* translators: 1: site name, 2: post type, 3. post title */
				$subject = sprintf( __( '[%1$s] New %2$s Created: "%3$s"', 'vip-workflow' ), $blogname, $subject_post_type, $post_title );
				/* translators: 1: post type, 2: post id, 3. post title, 4. user name, 5. user email */
				$body .= sprintf( __( 'A new %1$s (#%2$s "%3$s") was created by %4$s %5$s.', 'vip-workflow' ), $post_type, $post_id, $post_title, $current_user->display_name, $current_user->user_email ) . "\r\n";
			} elseif ( 'trash' === $new_status ) {
				/* translators: 1: site name, 2: post type, 3. post title */
				$subject = sprintf( __( '[%1$s] %2$s Trashed: "%3$s"', 'vip-workflow' ), $blogname, $subject_post_type, $post_title );
				/* translators: 1: post type, 2: post id, 3. post title, 4. user name, 5. user email */
				$body .= sprintf( __( '%1$s #%2$s "%3$s" was moved to the trash by %4$s %5$s.', 'vip-workflow' ), $post_type, $post_id, $post_title, $current_user_display_name, $current_user_email ) . "\r\n";
			} elseif ( 'trash' === $old_status ) {
				/* translators: 1: site name, 2: post type, 3. post title */
				$subject = sprintf( __( '[%1$s] %2$s Restored (from Trash): "%3$s"', 'vip-workflow' ), $blogname, $subject_post_type, $post_title );
				/* translators: 1: post type, 2: post id, 3. post title, 4. user name, 5. user email */
				$body .= sprintf( __( '%1$s #%2$s "%3$s" was restored from trash by %4$s %5$s.', 'vip-workflow' ), $post_type, $post_id, $post_title, $current_user_display_name, $current_user_email ) . "\r\n";
			} elseif ( 'future' === $new_status ) {
				/* translators: 1: site name, 2: post type, 3. post title */
				$subject = sprintf( __( '[%1$s] %2$s Scheduled: "%3$s"' ), $blogname, $subject_post_type, $post_title );
				/* translators: 1: post type, 2: post id, 3. post title, 4. user name, 5. user email 6. scheduled date  */
				$body .= sprintf( __( '%1$s #%2$s "%3$s" was scheduled by %4$s %5$s.  It will be published on %6$s' ), $post_type, $post_id, $post_title, $current_user_display_name, $current_user_email, self::get_scheduled_datetime( $post ) ) . "\r\n";
			} elseif ( 'publish' === $new_status ) {
				/* translators: 1: site name, 2: post type, 3. post title */
				$subject = sprintf( __( '[%1$s] %2$s Published: "%3$s"', 'vip-workflow' ), $blogname, $subject_post_type, $post_title );
				/* translators: 1: post type, 2: post id, 3. post title, 4. user name, 5. user email */
				$body .= sprintf( __( '%1$s #%2$s "%3$s" was published by %4$s %5$s.', 'vip-workflow' ), $post_type, $post_id, $post_title, $current_user_display_name, $current_user_email ) . "\r\n";
			} elseif ( 'publish' === $old_status ) {
				/* translators: 1: site name, 2: post type, 3. post title */
				$subject = sprintf( __( '[%1$s] %2$s Unpublished: "%3$s"', 'vip-workflow' ), $blogname, $subject_post_type, $post_title );
				/* translators: 1: post type, 2: post id, 3. post title, 4. user name, 5. user email */
				$body .= sprintf( __( '%1$s #%2$s "%3$s" was unpublished by %4$s %5$s.', 'vip-workflow' ), $post_type, $post_id, $post_title, $current_user_display_name, $current_user_email ) . "\r\n";
			} else {
				/* translators: 1: site name, 2: post title, 3: post type, 4: new status */
				$subject = sprintf( __( '[%1$s] "%2$s" %3$s moved to "%4$s"', 'vip-workflow' ), $blogname, $post_title, $subject_post_type, $new_status_friendly_name );

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

			$body .= self::get_notification_footer( $post );

			$action = 'status-change';

			self::schedule_emails( $action, $post, $subject, $body );

			/* translators: 1: user name, 2: post type, 3: post id, 4: edit link, 5: post title, 6: old status, 7: new status */
			$webhook_format  = __( '*%1$s* changed the status of *%2$s #%3$s - <%4$s|%5$s>* from *%6$s* to *%7$s*', 'vip-workflow' );
			$webhook_message = sprintf( $webhook_format, $current_user_display_name, $post_type, $post_id, $edit_link, $post_title, $old_status, $new_status );

			self::schedule_webhook_notification( $webhook_message, $action, $post->post_modified_gmt );

			/**
			 * Fires after a notification is sent
			 *
			 * @param int $post_id The post ID of the post that was updated.
			 */
			do_action( 'vw_notification_status_change', $post->ID );
		}
	}

	/**
	 * Get the footer for the email notification
	 *
	 * @return string Footer for the email notification
	 */
	public static function get_notification_footer(): string {
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
	public static function schedule_emails( string $action, WP_Post $post, string $subject, string $message, string $message_headers = '' ): void {
		// Ensure the email address is set from settings.
		if ( empty( OptionsUtilities::get_options_by_key( 'email_address' ) ) ) {
			return;
		}

		$email_recipients = [ OptionsUtilities::get_options_by_key( 'email_address' ) ];

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
		$subject = apply_filters( 'vw_notification_email_subject', $subject, $action, $post );

		/**
		 * Filter the email message
		 *
		 * @param string $message Body of the email
		 * @param string $action Action being taken, eg. status-change
		 * @param WP_Post $post Post object
		 */
		$message = apply_filters( 'vw_notification_email_message', $message, $action, $post );

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
	 * @param array $recipients Emails to send to
	 * @param string $subject Subject of the email
	 * @param string $message Body of the email
	 * @param string $message_headers. (optional) Message headers
	 */
	public static function send_emails( array $recipients, string $subject, string $message, string $message_headers = '' ): void {
		$response = wp_mail( $recipients, $subject, $message, $message_headers );

		if ( ! $response ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'Unable to send notification to email(s) provided.' );
		}
	}

	/**
	 * Schedule a webhook notification
	 *
	 * @param string $webhook_message Message to be sent to webhook
	 * @param string $action Action being taken, eg. status-change
	 * @param string $timestamp Timestamp of the message, eg. the time at which the post was updated
	 */
	public static function schedule_webhook_notification( string $webhook_message, string $action, string $timestamp ): void {
		// Ensure the webhook URL is set from settings.
		if ( empty( OptionsUtilities::get_options_by_key( 'webhook_url' ) ) ) {
			return;
		}

		$message_type = 'plugin:vip-workflow:' . $action;

		wp_schedule_single_event( time(), 'vw_send_scheduled_webhook', [ $webhook_message, $message_type, $timestamp ] );
	}

	/**
	 * Send notifications to a webhook
	 *
	 * @param string $message Message to be sent to webhook
	 * @param string $message_type Type of message being sent
	 * @param string $timestamp Timestamp of the message that corresponds to the time at which the post was updated
	 * @return bool True if the notification was sent successfully, false otherwise
	 */
	public static function send_to_webhook( string $message, string $message_type, string $timestamp ): bool {
		$webhook_url = OptionsUtilities::get_options_by_key( 'webhook_url' );

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

		if ( is_wp_error( $response ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'Unable to send notification to webhook provided.' );
			return false;
		}

		return true;
	}

	/**
	* Gets a simple phrase containing the formatted date and time that the post is scheduled for.
	*
	* @param WP_Post $post The post object
	* @return string The formatted date and time that the post is scheduled for
	*/
	private static function get_scheduled_datetime( WP_Post $post ): string {
		$scheduled_ts = strtotime( $post->post_date );

		$date = date_i18n( get_option( 'date_format' ), $scheduled_ts );
		$time = date_i18n( get_option( 'time_format' ), $scheduled_ts );

		/* translators: 1: date, 2: time */
		return sprintf( __( '%1$s at %2$s', 'vip-workflow' ), $date, $time );
	}
}

Notifications::init();
