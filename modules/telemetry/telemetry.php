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
	}

	/**
	 * Initialize the module and register event callbacks
	 */
	public function init(): void {
		// Add custom status events
		$status_events = new Status_Events( $this->tracks );
		$status_events->register_events();

		// Add notification events
		$notification_events = new Notification_Events( $this->tracks );
		$notification_events->register_events();

		// add settings events
		$settings_events = new Settings_Events( $this->tracks );
		$settings_events->register_events();
	}
}
