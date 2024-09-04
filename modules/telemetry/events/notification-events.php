<?php

namespace VIPWorkflow\Modules\Telemetry\Events;

use Automattic\VIP\Telemetry\Tracks;
use WP_Post;
use WP_User;

class Notification_Events {
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
			'vw_notification_status_change',
			[ $this, 'record_notification_sent' ],
			10,
			3
		);
	}

	/**
	 * Record an event when a notification is sent
	 *
	 * @param WP_Post $post The post object
	 * @param string $subject The notification subject
	 * @param WP_User $user The user object
	 * @param Tracker $tracker The Tracker instance
	 */
	public function record_notification_sent(
		WP_Post $post,
		string $subject,
		WP_User $user,
	): void {
		$this->tracks->record_event( 'notification_sent', [
			'subject' => $subject,
			'post_id' => $post->ID,
		] );
	}
}
