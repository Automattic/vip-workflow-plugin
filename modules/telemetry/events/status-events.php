<?php

namespace VIPWorkflow\Modules\Telemetry\Events;

use Automattic\VIP\Telemetry\Tracks;
use VIPWorkflow\VIP_Workflow;
use WP_Post;

class Status_Events {
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
	 * Register the event callbacks
	 */
	public function register_events(): void {
		add_action(
			'transition_post_status',
			[ $this, 'record_custom_status_change' ],
			10,
			3
		);
		add_action(
			'vw_add_custom_status',
			[ $this, 'record_add_custom_status' ],
			10,
			3
		);
		add_action(
			'vw_delete_custom_status',
			[ $this, 'record_delete_custom_status' ],
			10,
			3
		);
		add_action(
			'vw_update_custom_status',
			[ $this, 'record_update_custom_status' ],
			10,
			2
		);
	}

	/**
	 * Record an event when a post's custom status changes
	 *
	 * @param string $new_status The new status
	 * @param string $old_status The old status
	 * @param WP_Post $post The post object
	 */
	public function record_custom_status_change(
		string $new_status,
		string $old_status,
		WP_Post $post,
	): void {
		if ( ! in_array( $post->post_type, VIP_Workflow::instance()->custom_status->get_supported_post_types() ) ) {
			return;
		}

		if ( in_array( $new_status, [ $old_status, 'inherit', 'auto-draft', 'publish', 'draft' ] ) ) {
			return;
		}

		$this->tracks->record_event( 'post_custom_status_changed', [
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
	 * @param array $args The term arguments
	 */
	public function record_add_custom_status(
		string $term,
		string $slug,
		array $args,
	): void {
		$this->tracks->record_event( 'custom_status_created', [
			'term' => $term,
			'slug' => $slug,
		] );
	}

	/**
	 * Record an event when a custom status is deleted
	 *
	 * @param int $status_id The status ID
	 * @param string $slug The status slug
	 * @param array $args The status arguments
	 */
	public function record_delete_custom_status(
		int $status_id,
		string $slug,
		array $args,
	): void {
		$this->tracks->record_event( 'custom_status_deleted', [
			'status_id' => $status_id,
			'slug'      => $slug,
		] );
	}

	/**
	 * Record an event when a custom status is updated
	 *
	 * @param int $status_id The status ID
	 * @param array $args The status arguments
	 */
	public function record_update_custom_status(
		int $status_id,
		array $args,
	): void {
		$this->tracks->record_event( 'custom_status_changed', [
			'status_id' => $status_id,
			'slug'      => $args['slug'],
		] );
	}
}
