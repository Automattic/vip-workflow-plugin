<?php
/**
 * class RequiredMetadataIdHandler
 *
 * Cleans up editorial metadata IDs that are required on a custom status, and should be cleaned up once they are deleted.
 */

namespace VIPWorkflow\Modules\CustomStatus\Meta;

use VIPWorkflow\Modules\CustomStatus;
use VIPWorkflow\Modules\EditorialMetadata;
use VIPWorkflow\Modules\Shared\PHP\MetaCleanupUtilities;

use WP_Term;

class RequiredMetadataIdHandler {

	public static function init(): void {
		// Add the required metadata IDs to the custom status
		add_filter( 'vw_register_custom_status_meta', [ __CLASS__, 'add_required_metadata_ids' ], 10, 2 );

		// Remove the required metadata fields on a status
		add_action( 'vw_delete_custom_status_meta', [ __CLASS__, 'delete_required_metadata' ], 10, 1 );

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
		$metadata_ids = MetaCleanupUtilities::get_array( $custom_status->term_id, CustomStatus::METADATA_REQ_EDITORIAL_IDS_KEY );

		$term_meta[ CustomStatus::METADATA_REQ_EDITORIAL_IDS_KEY ] = $metadata_ids;

		// Reset the metadata array to be empty, so that it can be filled with the actual metadata only.
		$term_meta[ CustomStatus::METADATA_REQ_EDITORIALS_KEY ] = [];

		foreach ( $metadata_ids as $metadata_id ) {
			$editorial_metadata = EditorialMetadata::get_editorial_metadata_term_by( 'id', $metadata_id );
			if ( $editorial_metadata ) {
				$term_meta[ CustomStatus::METADATA_REQ_EDITORIALS_KEY ][] = $editorial_metadata;
			}
		}

		return $term_meta;
	}

	/**
	 * Delete the required metadata fields on a status
	 *
	 * @param integer $term_id The term ID of the status
	 * @return void
	 */
	public static function delete_required_metadata( int $term_id ): void {
		delete_term_meta( $term_id, CustomStatus::METADATA_REQ_EDITORIAL_IDS_KEY );
	}

	/**
	 * Remove the delete metadata from the required metadata fields on a status
	 *
	 * @param integer $meta_id The meta ID that was deleted
	 * @return void
	 */
	public static function remove_deleted_metadata_from_required_metadata( int $deleted_meta_id ): void {
		$custom_statuses = CustomStatus::get_custom_statuses();

		MetaCleanupUtilities::cleanup_id( $custom_statuses, $deleted_meta_id, /* id_to_replace */ null, CustomStatus::METADATA_REQ_EDITORIAL_IDS_KEY );
	}
}

RequiredMetadataIdHandler::init();
