<?php

namespace VIPWorkflow\Modules\Telemetry;

use Automattic\VIP\Telemetry\Tracks;

require_once __DIR__ . '/class-vip-workflow-tracker.php';
require_once __DIR__ . '/events/status-events.php';

class Telemetry {
	/**
	 * Tracker instance
	 *
	 * @var Tracker
	 */
	protected Tracker $tracker;

	/**
	 * Constructor
	 */
	public function __construct() {
		$telemetry = new Tracks( 'vip_workflow_' );
		$this->tracker = new Tracker( $telemetry );
	}

	/**
	 * Initialize the module and register event callbacks
	 */
	public function init(): void {
		add_action(
			'transition_post_status',
			Tracker::track_event( 'VIPWorkflow\Modules\Telemetry\Events\record_custom_status_change', $this->tracker ),
			10,
			3
		);
		add_action(
			'vw_add_custom_status',
			Tracker::track_event( 'VIPWorkflow\Modules\Telemetry\Events\record_add_custom_status', $this->tracker ),
			10,
			2
		);
		add_action(
			'vw_delete_custom_status',
			Tracker::track_event( 'VIPWorkflow\Modules\Telemetry\Events\record_delete_custom_status', $this->tracker ),
			10,
			3
		);
		add_action(
			'vw_update_custom_status',
			Tracker::track_event( 'VIPWorkflow\Modules\Telemetry\Events\record_update_custom_status', $this->tracker ),
			10,
			2
		);
	}
}
