<?php

namespace VIPWorkflow\Modules\Telemetry;

use Automattic\VIP\Telemetry\Tracks;
use VIPWorkflow\Modules\CustomStatus;
use VIPWorkflow\Modules\Shared\PHP\HelperUtilities;
use WP_Post;
use WP_User;

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
		add_action( 'vw_add_custom_status', [ __CLASS__, 'record_add_custom_status' ], 10, 2 );
		add_action( 'vw_delete_custom_status', [ __CLASS__, 'record_delete_custom_status' ], 10, 2 );
		add_action( 'vw_update_custom_status', [ __CLASS__, 'record_update_custom_status' ], 10, 3 );

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

		if ( in_array( $new_status, [ $old_status, 'inherit', 'auto-draft', 'publish', 'draft' ] ) ) {
			return;
		}

		self::$tracks->record_event( 'post_custom_status_changed', [
			'new_status' => $new_status,
			'old_status' => $old_status,
			'post_id'    => $post->ID,
		] );
	}

	/**
	 * Record an event when a custom status is created
	 *
	 * @param string $term The term name
	 * @param string $slug The term slug
	 */
	public static function record_add_custom_status( string $term, string $slug ): void {
		self::$tracks->record_event( 'custom_status_created', [
			'term' => $term,
			'slug' => $slug,
		] );
	}

	/**
	 * Record an event when a custom status is deleted
	 *
	 * @param int $status_id The status ID
	 * @param string $slug The status slug
	 */
	public static function record_delete_custom_status( int $status_id, string $slug ): void {
		self::$tracks->record_event( 'custom_status_deleted', [
			'status_id' => $status_id,
			'slug'      => $slug,
		] );
	}

	/**
	 * Record an event when a custom status is updated
	 *
	 * @param int $status_id The status ID
	 * @param string $slug The status slug
	 * @param int $position The status position
	 */
	public static function record_update_custom_status( int $status_id, string $slug, int $position ): void {
		self::$tracks->record_event( 'custom_status_changed', [
			'status_id' => $status_id,
			'slug'      => $slug,
			'position'  => $position,
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
	public function record_admin_update( string $previous_version, string $new_version ): void {
		// Get all custom statuses
		$custom_statuses = CustomStatus::get_custom_statuses();
		// Get supported post types
		$supported_post_types = HelperUtilities::get_supported_post_types();

		$published_posts     = 0;
		$custom_status_posts = 0;
		foreach ( $supported_post_types as $post_type ) {
			// Get all posts count for each post type
			$posts_count = wp_count_posts( $post_type );

			// Only care about published and posts with custom status
			$published_posts += (int) $posts_count->publish;
			foreach ( $custom_statuses as $status ) {
				$custom_status_posts += (int) $posts_count->{ $status->slug };
			}
		}

		self::$tracks->record_event( 'administration_update', [
			'previous_version'    => $previous_version,
			'new_version'         => $new_version,
			'custom_status_posts' => $custom_status_posts,
			'published_posts'     => $published_posts,
		] );
	}

	/**
	 * Record an event when the settings are updated
	 *
	 * @param array $new_options The new options
	 * @param array $old_options The old options
	 */
	public function record_settings_update( array $new_options, array $old_options ): void {
		if ( $new_options['publish_guard'] !== $old_options['publish_guard'] ) {
			$this->record_publish_guard_toggle( $new_options['publish_guard'] );
		}

		if ( $new_options['send_to_webhook'] !== $old_options['send_to_webhook'] ) {
			$this->record_send_to_webhook_toggle( $new_options['send_to_webhook'] );
		}
	}

	/**
	 * Record an event when the publish guard is toggled
	 *
	 * @param bool $enabled Whether the publish guard is enabled
	 */
	protected function record_publish_guard_toggle( bool $enabled ): void {
		if ( $enabled ) {
			self::$tracks->record_event( 'publish_guard_enabled', [
				'enabled' => $enabled,
			] );
			return;
		}

		self::$tracks->record_event( 'publish_guard_disabled', [
			'enabled' => $enabled,
		] );
	}

	/**
	 * Record an event when the send to webhook is toggled
	 *
	 * @param bool $enabled Whether the send to webhook is enabled
	 */
	protected function record_send_to_webhook_toggle( bool $enabled ): void {
		if ( $enabled ) {
			self::$tracks->record_event( 'send_to_webhook_enabled', [
				'enabled' => $enabled,
			] );
			return;
		}

		self::$tracks->record_event( 'send_to_webhook_disabled', [
			'enabled' => $enabled,
		] );
	}
}

Telemetry::init();
