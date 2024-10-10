<?php

namespace VIPWorkflow\Modules\Telemetry;

use Automattic\VIP\Telemetry\Tracks;
use VIPWorkflow\Modules\CustomStatus;
use VIPWorkflow\Modules\Shared\PHP\HelperUtilities;
use WP_Post;

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
		add_action( 'vw_delete_custom_status', [ __CLASS__, 'record_delete_custom_status' ], 10, 2 );
		add_action( 'vw_update_custom_status', [ __CLASS__, 'record_update_custom_status' ], 10, 2 );

		// Notification events
		add_action( 'vw_notification_status_change', [ __CLASS__, 'record_notification_sent' ], 10, 1 );

		// Settings events
		add_action( 'vw_upgrade_version', [ __CLASS__, 'record_admin_update' ], 10, 2 );
		add_action( 'vw_save_settings', [ __CLASS__, 'record_settings_update' ], 10, 2 );
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
			return;
		}

		if ( in_array( $new_status, [ $old_status, 'inherit', 'auto-draft', 'publish' ] ) ) {
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
	 * @param int $term_id The term's ID
	 * @param string $term_name The term's name
	 * @param string $slug The term's slug
	 */

	public static function record_add_custom_status( int $term_id, string $term_name, string $term_slug ): void {
		self::$tracks->record_event( 'custom_status_created', [
			'term_id' => $term_id,
			'name'    => $term_name,
			'slug'    => $term_slug,
		] );
	}

	/**
	 * Record an event when a custom status is deleted
	 *
	 * @param int $term_id The custom status term ID
	 * @param string $slug The status slug
	 */
	public static function record_delete_custom_status( int $term_id, string $slug ): void {
		self::$tracks->record_event( 'custom_status_deleted', [
			'term_id' => $term_id,
			'slug'    => $slug,
		] );
	}

	/**
	 * Record an event when a custom status is updated
	 *
	 * @param int $term_id The custom status term ID
	 * @param string $slug The status slug
	 */
	public static function record_update_custom_status( int $status_id, string $slug ): void {
		self::$tracks->record_event( 'custom_status_changed', [
			'status_id' => $status_id,
			'slug'      => $slug,
		] );
	}

	// Notification events

	/**
	 * Record an event when a notification is sent
	 *
	 * @param int $post_id The post ID that was updated.
	 */
	public static function record_notification_sent( int $post_id ): void {
		self::$tracks->record_event( 'notification_sent', [
			'post_id' => $post_id,
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

		$custom_status_posts = 0;

		foreach ( $supported_post_types as $post_type ) {
			// Get all posts count for this post type
			$posts_count = wp_count_posts( $post_type );

			foreach ( $custom_statuses as $status ) {
				if ( isset( $posts_count->{ $status->slug } ) ) {
					$custom_status_posts += (int) $posts_count->{ $status->slug };
				}
			}
		}

		self::$tracks->record_event( 'plugin_update', [
			'previous_version'    => $previous_version,
			'new_version'         => $new_version,
			'custom_status_posts' => $custom_status_posts,
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
	 * Record an event when the send to webhook is toggled
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
}

Telemetry::init();
