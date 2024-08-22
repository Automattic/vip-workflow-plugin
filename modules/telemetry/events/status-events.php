<?php

namespace VIPWorkflow\Modules\Telemetry\Events;

use VIPWorkflow\Modules\Telemetry\Tracker;
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

	$tracker->record_event( 'post_custom_status_change', [
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

function record_delete_custom_status(
	int $status_id,
	string $slug,
	array $args,
	Tracker $tracker
	): void {
	$tracker->record_event( 'delete_custom_status', [
		'status_id' => $status_id,
		'slug'      => $slug,
		'args'      => $args,
	] );
}

function record_update_custom_status(
	int $status_id,
	array $args,
	Tracker $tracker
	): void {
	$tracker->record_event( 'update_custom_status', [
		'status_id' => $status_id,
		'args'      => $args,
	] );
}
