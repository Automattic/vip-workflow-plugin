<?php

namespace VIPWorkflow\Modules\Telemetry\Events;

use VIPWorkflow\Modules\Telemetry\Tracker;
use VIPWorkflow\VIP_Workflow;

function record_admin_update( string $previous_version, string $new_version, Tracker $tracker ): void {
	// Get all custom statuses
	$custom_statuses = VIP_Workflow::instance()->custom_status->get_custom_statuses();

	// Get all posts count
	$posts_count = wp_count_posts();
	// Only care about published and posts with custom status
	$total_posts = (int) $posts_count->publish;
	foreach ( $custom_statuses as $status ) {
		$total_posts += (int) $posts_count->{ $status->slug };
	}

	$tracker->record_event( 'administration_update', [
		'previous_version' => $previous_version,
		'new_version'      => $new_version,
		'custom_statuses'  => count( $custom_statuses ),
		'total_posts'      => $total_posts,
	] );
}

function record_settings_update( array $new_options, array $old_options, Tracker $tracker ): void {
	if ( $new_options['publish_guard'] !== $old_options['publish_guard'] ) {
		record_publish_guard_toggle( $new_options['publish_guard'], $tracker );
	}

	if ( $new_options['send_to_webhook'] !== $old_options['send_to_webhook'] ) {
		record_send_to_webhook_toggle( $new_options['send_to_webhook'], $tracker );
	}
}

function record_publish_guard_toggle( bool $enabled, Tracker $tracker ): void {
	if ( $enabled ) {
		$tracker->record_event( 'publish_guard_enabled', [
			'enabled' => $enabled,
		] );
		return;
	}

	$tracker->record_event( 'publish_guard_disabled', [
		'enabled' => $enabled,
	] );
}

function record_send_to_webhook_toggle( bool $enabled, Tracker $tracker ): void {
	if ( $enabled ) {
		$tracker->record_event( 'send_to_webhook_enabled', [
			'enabled' => $enabled,
		] );
		return;
	}

	$tracker->record_event( 'send_to_webhook_disabled', [
		'enabled' => $enabled,
	] );
}
