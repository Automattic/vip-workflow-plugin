<?php

namespace VIPWorkflow\Modules\Telemetry;

use Automattic\VIP\Telemetry\Tracks;

class Tracker {
	/**
	 * Tracks instance
	 *
	 * @var Tracks
	 */
	public Tracks $tracks;

	/**
	 * Constructor
	 *
	 * @param Tracks $tracks The Tracks instance
	 */
	public function __construct( Tracks $tracks ) {
		$this->tracks = $tracks;
	}

	/**
	 * Record an event
	 *
	 * @param String $event_name The event name
	 * @param array $event_data The event data
	 */
	public function record_event( String $event_name, array $event_data = [] ): void {
		$this->tracks->record_event( $event_name, $event_data );
	}

	/**
	 * Wrap event callbacks in closure to inject the Tracker class
	 *
	 * @param callable $callback The callback function
	 * @param Tracker $tracker The Tracker class
	 * @return callable|null The wrapped callback function
	 */
	public static function track_event( String $callback, Tracker $tracker ): callable|null {
		if ( is_callable( $callback ) ) {
			return function () use ( $callback, $tracker ) {
				// get the arguments passed to the callback
				$args = func_get_args();
				// add the tracker to the arguments
				$args[] = $tracker;

				return call_user_func_array( $callback, $args );
			};
		}
		return null;
	}
}
