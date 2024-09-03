<?php

namespace VIPWorkflow\Modules\Telemetry\Events;

use Automattic\VIP\Telemetry\Tracks;
use VIPWorkflow\VIP_Workflow;

class Settings_Events {
	/**
	 * Tracks instance
	 *
	 * @var Tracks
	 */
	protected Tracks $tracks;

	/**
	 * Constructor
	 *
	 * @param Tracks $tracks The Tracks instance
	 */
	public function __construct( Tracks $tracks ) {
		$this->tracks = $tracks;
	}

	/**
	 * Record an event when the plugin is upgraded
	 *
	 * @param string $previous_version The previous version
	 * @param string $new_version The new version
	 */
	public function record_admin_update( string $previous_version, string $new_version ): void {
		// Get all custom statuses
		$custom_statuses = VIP_Workflow::instance()->custom_status->get_custom_statuses();
		// Get supported post types
		$supported_post_types = VIP_Workflow::instance()->custom_status->get_supported_post_types();

		$total_posts = 0;
		foreach ( $supported_post_types as $post_type ) {
			// Get all posts count for each post type
			$posts_count = wp_count_posts( $post_type );

			// Only care about published and posts with custom status
			$total_posts += (int) $posts_count->publish;
			foreach ( $custom_statuses as $status ) {
				$total_posts += (int) $posts_count->{ $status->slug };
			}
		}

		$this->tracks->record_event( 'administration_update', [
			'previous_version' => $previous_version,
			'new_version'      => $new_version,
			'custom_statuses'  => count( $custom_statuses ),
			'total_posts'      => $total_posts,
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
			$this->tracks->record_event( 'publish_guard_enabled', [
				'enabled' => $enabled,
			] );
			return;
		}

		$this->tracks->record_event( 'publish_guard_disabled', [
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
			$this->tracks->record_event( 'send_to_webhook_enabled', [
				'enabled' => $enabled,
			] );
			return;
		}

		$this->tracks->record_event( 'send_to_webhook_disabled', [
			'enabled' => $enabled,
		] );
	}
}
