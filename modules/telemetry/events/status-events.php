<?php

namespace VIPWorkflow\Telemetry\Events;

use VIPWorkflow\Telemetry\Tracker;
use WP_Post;

function record_custom_status_change(
	string $new_status,
	string $old_status,
	WP_Post $post,
	Tracker $tracker
	): void {
	if ( $post->post_type !== 'post' ) {
		return;
	}

	if ( in_array( $new_status, [ $old_status, 'inherit', 'auto-draft', 'publish', 'draft' ] ) ) {
		return;
	}

	$tracker->record_event( 'custom_status_change', [
		'new_status' => $new_status,
		'old_status' => $old_status,
		'post_id'    => $post->ID,
	] );
}

function record_add_custom_status(
	string $term,
	array $args,
	Tracker $tracker
	): void {
	$tracker->record_event( 'add_custom_status', [
		'term' => $term,
		'args' => $args,
	] );
}
