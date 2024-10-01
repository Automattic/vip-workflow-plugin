<?php
/**
 * class RequiredMetadataIdHandler
 *
 * Cleans up editorial metadata IDs that are required on a custom status, and should be cleaned up once they are deleted.
 */

namespace VIPWorkflow\Modules\CustomStatus\Meta;

use VIPWorkflow\Modules\Custom_Status;
use VIPWorkflow\VIP_Workflow;
use VIPWorkflow\Modules\Shared\PHP\MetaCleanupUtilities;

use WP_Term;

class RequiredMetadataIdHandler {

	public static function init(): void {
		// Add the required metadata IDs to the custom status
		add_filter( 'vw_register_custom_status_meta', [ __CLASS__, 'add_required_metadata_ids' ], 10, 2 );

		// Remove deleted metadata fields from required metadata fields
		add_action( 'vw_editorial_metadata_term_deleted', [ __CLASS__, 'remove_deleted_metadata_from_required_metadata' ], 10, 1 );
	}

	/**
	 * Add the required metadata IDs to the custom status
	 *
	 * @param array $term_meta The meta keys for the custom status
	 * @param WP_Term $custom_status The custom status term
	 * @return array The updated meta keys
	 */
	public static function add_required_metadata_ids( array $term_meta, WP_Term $custom_status ): array {
		$metadata_ids = get_term_meta( $custom_status->term_id, Custom_Status::METADATA_REQ_EDITORIAL_FIELDS_KEY, true );
		if ( ! is_array( $metadata_ids ) ) {
			$metadata_ids = [];
		}

		$term_meta[ Custom_Status::METADATA_REQ_EDITORIAL_FIELDS_KEY ] = $metadata_ids;

		return $term_meta;
	}

	/**
	 * Remove the delete metadata from the required metadata fields on a status
	 *
	 * @param integer $meta_id The meta ID that was deleted
	 * @return void
	 */
	public static function remove_deleted_metadata_from_required_metadata( int $deleted_meta_id ): void {
		$custom_statuses = VIP_Workflow::instance()->custom_status->get_custom_statuses();

		MetaCleanupUtilities::cleanup_id( $custom_statuses, $deleted_meta_id, /* id_to_replace */ null, Custom_Status::METADATA_REQ_EDITORIAL_FIELDS_KEY );
	}
}

RequiredMetadataIdHandler::init();
