<?php
/**
 * class PositionHandler
 *
 * This class serves as a handler for the position of a custom status.
 */

namespace VIPWorkflow\Modules\CustomStatus\Meta;

use VIPWorkflow\Modules\Custom_Status;
use VIPWorkflow\Modules\Shared\PHP\MetaCleanupUtilities;
use WP_Term;

class PositionHandler {

	public static function init(): void {
		// Add the position to the custom status
		add_filter( 'vw_register_custom_status_meta', [ __CLASS__, 'add_position' ], 10, 2 );

		// Remove the position on a status
		add_action( 'vw_delete_custom_status_meta', [ __CLASS__, 'delete_position' ], 10, 1 );
	}

	/**
	 * Add the position to the custom status
	 *
	 * @param array $term_meta The meta keys for the custom status
	 * @param WP_Term $custom_status The custom status term
	 * @return array The updated meta keys
	 */
	public static function add_position( array $term_meta, WP_Term $custom_status ): array {
		$position = MetaCleanupUtilities::get_int( $custom_status->term_id, Custom_Status::METADATA_POSITION_KEY );

		$term_meta[ Custom_Status::METADATA_POSITION_KEY ] = $position;

		return $term_meta;
	}

	/**
	 * Delete the position on a status
	 *
	 * @param integer $term_id The term ID of the status
	 * @return void
	 */
	public static function delete_position( int $term_id ): void {
		delete_term_meta( $term_id, Custom_Status::METADATA_POSITION_KEY );
	}
}

PositionHandler::init();
