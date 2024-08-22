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
