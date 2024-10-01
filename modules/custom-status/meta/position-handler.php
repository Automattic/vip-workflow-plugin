<?php
/**
 * class PositionHandler
 *
 * This class serves as a handler for the position of a custom status.
 */

namespace VIPWorkflow\Modules\CustomStatus\Meta;

use VIPWorkflow\Modules\Custom_Status;

use WP_Term;

class PositionHandler {

	public static function init(): void {
		// Add the position to the custom status
		add_filter( 'vw_register_custom_status_meta', [ __CLASS__, 'add_position' ], 10, 2 );
	}

	/**
	 * Add the position to the custom status
	 *
	 * @param array $term_meta The meta keys for the custom status
	 * @param WP_Term $custom_status The custom status term
	 * @return array The updated meta keys
	 */
	public static function add_position( array $term_meta, WP_Term $custom_status ): array {
		$position = get_term_meta( $custom_status->term_id, Custom_Status::METADATA_POSITION_KEY, true );

		// ToDo: Should this be done, or is it better to just return the value?
		if ( ! is_numeric( $position ) ) {
			$position = 0;
		}

		$term_meta[ Custom_Status::METADATA_POSITION_KEY ] = $position;

		return $term_meta;
	}
}

PositionHandler::init();
