<?php

namespace VIPWorkflow\Modules\Telemetry;

require_once __DIR__ . '/events/status-events.php';
require_once __DIR__ . '/events/notification-events.php';
require_once __DIR__ . '/events/settings-events.php';

use Automattic\VIP\Telemetry\Tracks;
use VIPWorkflow\Modules\Telemetry\Events\Status_Events;
use VIPWorkflow\Modules\Telemetry\Events\Notification_Events;
use VIPWorkflow\Modules\Telemetry\Events\Settings_Events;

class Telemetry {
	/**
	 * Tracker instance
	 *
	 * @var Tracks
	 */
	protected Tracks $tracks;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->tracks = new Tracks( 'vip_workflow_' );

		// init the events classes

	}

	/**
	 * Initialize the module and register event callbacks
	 */
	public function init(): void {
		// Add custom status events
		$status_events = new Status_Events( $this->tracks );
		add_action(
			'transition_post_status',
			[ $status_events, 'record_custom_status_change' ],
			10,
			3
		);
		add_action(
			'vw_add_custom_status',
			[ $status_events, 'record_add_custom_status' ],
			10,
			3
		);
		add_action(
			'vw_delete_custom_status',
			[ $status_events, 'record_delete_custom_status' ],
			10,
			3
		);
		add_action(
			'vw_update_custom_status',
			[ $status_events, 'record_update_custom_status' ],
			10,
			2
		);

		// Add notification events
		$notification_events = new Notification_Events( $this->tracks );
		add_action(
			'vw_notification_status_change',
			[ $notification_events, 'record_notification_sent' ],
			10,
			3
		);

		// add settings events
		$settings_events = new Settings_Events( $this->tracks );
		add_action(
			'vw_upgrade_version',
			[ $settings_events, 'record_admin_update' ],
			10,
			2
		);
		add_action(
			'vw_save_settings',
			[ $settings_events, 'record_settings_update' ],
			10,
			2
		);
	}
}
