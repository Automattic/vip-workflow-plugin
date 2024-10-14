<?php

namespace VIPWorkflow\Modules\Telemetry;

use Automattic\VIP\Telemetry\Tracks;
use VIPWorkflow\Modules\CustomStatus;
use VIPWorkflow\Modules\EditorialMetadata;
use VIPWorkflow\Modules\Shared\PHP\HelperUtilities;
use WP_Post;
use WP_Term;

class Telemetry {
	/**
	 * Tracker instance
	 *
	 * @var Tracks
	 */
	protected static Tracks $tracks;

	/**
	 * Initialize the module and register event callbacks
	 */
	public static function init(): void {
		self::$tracks = new Tracks( 'vip_workflow_', [ 'plugin_version' => VIP_WORKFLOW_VERSION ] );

		// Custom Status events
		add_action( 'transition_post_status', [ __CLASS__, 'record_custom_status_change' ], 10, 3 );
		add_action( 'vw_add_custom_status', [ __CLASS__, 'record_add_custom_status' ], 10, 3 );
		add_action( 'vw_delete_custom_status', [ __CLASS__, 'record_delete_custom_status' ], 10, 3 );
		add_action( 'vw_update_custom_status', [ __CLASS__, 'record_update_custom_status' ], 10, 2 );

		// Notification events
		add_action( 'vw_notification_status_change', [ __CLASS__, 'record_notification_sent' ], 10, 3 );

		// Settings events
		add_action( 'vw_upgrade_version', [ __CLASS__, 'record_admin_update' ], 10, 2 );
		add_action( 'vw_save_settings', [ __CLASS__, 'record_settings_update' ], 10, 2 );

		// Editorial Metadata events
		add_action( 'vw_add_editorial_metadata_field', [ __CLASS__, 'record_add_editorial_metadata_field' ], 10, 1 );
	}

	// Custom Status events

	/**
	 * Record an event when a post's custom status changes
	 *
	 * @param string $new_status The new status
	 * @param string $old_status The old status
	 * @param WP_Post $post The post object
	 */
	public static function record_custom_status_change( string $new_status, string $old_status, WP_Post $post ): void {
		if ( ! in_array( $post->post_type, HelperUtilities::get_supported_post_types() ) ) {
			// This isn't a supported post type
			return;
		} elseif ( $old_status === $new_status ) {
			// The status hasn't changed
			return;
		} elseif ( in_array( $new_status, [ 'inherit', 'auto-draft' ] ) ) {
			// The status hasn't changed, or it moved into an auto-generated status
			return;
		}

		$status_slugs = wp_list_pluck( CustomStatus::get_custom_statuses(), 'slug' );

		if ( ! in_array( $new_status, $status_slugs, true ) && ! in_array( $old_status, $status_slugs, true ) ) {
			// The status isn't moving to or from a custom status
			return;
		}

		self::$tracks->record_event( 'post_custom_status_changed', [
			'old_status' => $old_status,
			'new_status' => $new_status,
			'post_id'    => $post->ID,
		] );
	}

	/**
	 * Record an event when a custom status is created
	 *
	 * @param WP_Term $term The custom status term object
	 */

	public static function record_add_custom_status( WP_Term $term ): void {
		$required_user_count               = count( $updated_status->meta[ CustomStatus::METADATA_REQ_USER_IDS_KEY ] ?? [] );
		$required_editorial_metadata_count = count( $updated_status->meta[ CustomStatus::METADATA_REQ_EDITORIAL_IDS_KEY ] ?? [] );

		self::$tracks->record_event( 'custom_status_created', [
			'term_id'        => $term->term_id,
			'name'           => $term->name,
			'slug'           => $term->slug,
			'required_users' => $required_user_count,
			'required_em'    => $required_editorial_metadata_count,
		] );
	}

	/**
	 * Record an event when a custom status is deleted
	 *
	 * @param int $term_id The custom status term ID
	 * @param string $term_name The custom status term name
	 * @param string $slug The status slug
	 */
	public static function record_delete_custom_status( int $term_id, string $term_name, string $slug ): void {
		self::$tracks->record_event( 'custom_status_deleted', [
			'term_id' => $term_id,
			'name'    => $term_name,
			'slug'    => $slug,
		] );
	}

	/**
	 * Record an event when a custom status is updated
	 *
	 * @param WP_Term $updated_status The updated status WP_Term object.
	 * @param array $update_args The arguments used to update the status.
	 */
	public static function record_update_custom_status( WP_Term $updated_status, array $update_args ): void {
		$is_position_update = 1 === count( $update_args ) && isset( $update_args['position'] );
		if ( $is_position_update ) {
			// Ignore position changes, as they fire for every custom status when statuses are reordered
			return;
		}

		$required_user_count               = count( $updated_status->meta[ CustomStatus::METADATA_REQ_USER_IDS_KEY ] ?? [] );
		$required_editorial_metadata_count = count( $updated_status->meta[ CustomStatus::METADATA_REQ_EDITORIAL_IDS_KEY ] ?? [] );

		self::$tracks->record_event( 'custom_status_changed', [
			'term_id'        => $updated_status->term_id,
			'name'           => $updated_status->name,
			'slug'           => $updated_status->slug,
			'required_users' => $required_user_count,
			'required_em'    => $required_editorial_metadata_count,
		] );
	}

	// Notification events

	/**
	 * Record an event when a notification is sent
	 *
	 * @param int $post_id The post ID of the post that was updated.
	 * @param bool $is_email_scheduled True if an email was scheduled as part of the notification, false otherwise.
	 * @param bool $is_webhook_scheduled True if a webhook was scheduled as part of the notification, false otherwise.
	 */
	public static function record_notification_sent( int $post_id, $is_email_scheduled, $is_webhook_scheduled ): void {
		self::$tracks->record_event( 'notification_sent', [
			'post_id' => $post_id,
			'email'   => $is_email_scheduled,
			'webhook' => $is_webhook_scheduled,
		] );
	}

	// Settings events

	/**
	 * Record an event when the plugin is upgraded
	 *
	 * @param string $previous_version The previous version
	 * @param string $new_version The new version
	 */
	public static function record_admin_update( string $previous_version, string $new_version ): void {
		$custom_statuses      = CustomStatus::get_custom_statuses();
		$supported_post_types = HelperUtilities::get_supported_post_types();

		$published_post_count     = 0;
		$custom_status_post_count = 0;

		foreach ( $supported_post_types as $post_type ) {
			// Get all posts count for this post type
			$posts_count = wp_count_posts( $post_type );

			$published_post_count += (int) $posts_count->publish;

			foreach ( $custom_statuses as $status ) {
				if ( isset( $posts_count->{ $status->slug } ) ) {
					$custom_status_post_count += (int) $posts_count->{ $status->slug };
				}
			}
		}

		self::$tracks->record_event( 'plugin_update', [
			'previous_version'    => $previous_version,
			'new_version'         => $new_version,
			'published_posts'     => $published_post_count,
			'custom_status_posts' => $custom_status_post_count,
		] );
	}

	/**
	 * Record presence of publish guard and webhook settings if toggled
	 *
	 * @param array $new_options The new options
	 * @param array $old_options The old options
	 */
	public static function record_settings_update( array $new_options, array $old_options ): void {
		if ( $new_options['publish_guard'] !== $old_options['publish_guard'] ) {
			self::record_publish_guard_toggle( $new_options['publish_guard'] );
		}

		if ( $new_options['webhook_url'] !== $old_options['webhook_url'] ) {
			self::record_send_to_webhook_toggle( $new_options['webhook_url'] );
		}

		if ( $new_options['email_address'] !== $old_options['email_address'] ) {
			self::record_send_to_email_toggle( $new_options['email_address'] );
		}
	}

	/**
	 * Record an event when the publish guard is toggled
	 *
	 * @param string $publish_guard_value Either 'on' or 'off'.
	 */
	protected static function record_publish_guard_toggle( string $publish_guard_value ): void {
		if ( 'on' === $publish_guard_value ) {
			self::$tracks->record_event( 'publish_guard_enabled' );
		} else {
			self::$tracks->record_event( 'publish_guard_disabled' );
		}
	}

	/**
	 * Record an event when send to webhook is toggled
	 *
	 * @param string $webhook_url A string indicating a webhook URL, or an empty string if none is set.
	 */
	protected static function record_send_to_webhook_toggle( string $webhook_url ): void {
		if ( '' === $webhook_url ) {
			self::$tracks->record_event( 'send_to_webhook_disabled' );
		} else {
			self::$tracks->record_event( 'send_to_webhook_enabled' );
		}
	}

	/**
	 * Record an event when send to email is changed
	 *
	 * @param string $email_address A string indicating a webhook URL, or an empty string if none is set.
	 */
	protected static function record_send_to_email_toggle( string $email_address ): void {
		if ( '' === $email_address ) {
			self::$tracks->record_event( 'send_to_email_disabled' );
		} else {
			self::$tracks->record_event( 'send_to_email_enabled' );
		}
	}

	// Editorial Metadata events

	/**
	 * Record an event when an editorial metadata field is added
	 *
	 * @param WP_Term $editorial_metadata The name of the field
	 */
	public static function record_add_editorial_metadata_field( WP_Term $editorial_metadata ): void {
		self::$tracks->record_event( 'em_field_created', [
			'term_id' => $editorial_metadata->term_id,
			'name'    => $editorial_metadata->name,
			'slug'    => $editorial_metadata->slug,
			'type'    => $editorial_metadata->meta[ EditorialMetadata::METADATA_TYPE_KEY ],
		] );
	}
}

Telemetry::init();
