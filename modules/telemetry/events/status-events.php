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
	if ( 'post' !== $post->post_type ) {
		return;
	}

	if ( in_array( $new_status, [ $old_status, 'inherit', 'auto-draft', 'publish', 'draft' ] ) ) {
		return;
	}

	$tracker->record_event( 'post_custom_status_changed', [
		'new_status' => $new_status,
		'old_status' => $old_status,
		'post_id'    => $post->ID,
	] );
}

function record_add_custom_status(
	string $term,
	string $slug,
	array $args,
	Tracker $tracker
): void {
	$tracker->record_event( 'custom_status_created', [
		'term' => $term,
		'slug' => $slug,
	] );
}

function record_delete_custom_status(
	int $status_id,
	string $slug,
	array $args,
	Tracker $tracker
): void {
	$tracker->record_event( 'custom_status_deleted', [
		'status_id' => $status_id,
		'slug'      => $slug,
	] );
}

function record_update_custom_status(
	int $status_id,
	array $args,
	Tracker $tracker
): void {
	$tracker->record_event( 'custom_status_changed', [
		'status_id' => $status_id,
		'slug'      => $args['slug'],
	] );
}
