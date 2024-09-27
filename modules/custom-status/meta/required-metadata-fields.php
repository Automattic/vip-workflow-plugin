<?php
/**
 * class RequiredMetadataFields
 * Stores metadata fields required to make changes to a custom status.
 */

namespace VIPWorkflow\Modules\CustomStatus\Meta;

use VIPWorkflow\Modules\Custom_Status;
use VIPWorkflow\VIP_Workflow;

class RequiredMetadataFields {

	public static function init(): void {
		add_action( 'delete_metadata', [ __CLASS__, 'remove_deleted_metadata_from_required_metadata' ], 10, 1 );
	}

	public static function remove_deleted_metadata_from_required_metadata( int $meta_id ): void {
		$custom_statuses = VIP_Workflow::instance()->custom_status->get_custom_statuses();

		foreach ( $custom_statuses as $custom_status ) {
			$required_metadata_fields  = self::get( $custom_status->term_id );
			$deleted_metadata_index = array_search( $meta_id, $required_metadata_fields, /* strict */ true );

			if ( false === $deleted_metadata_index ) {
				// Ignore this status, the metadata field isn't associated with it
				continue;
			}

			// Remove the deleted metadata field
			unset( $required_metadata_fields[ $deleted_metadata_index ] );
			$required_metadata_fields = array_values( $required_metadata_fields );

			self::update( $custom_status->term_id, $required_metadata_fields );
		}
	}

	// Public API

	/**
	 * Given a $term_id, return the metadata fields required to make changes to the status.
	 */
	public static function get( int $term_id ): array {
		$result = get_term_meta( $term_id, Custom_Status::METADATA_REQ_EDITORIAL_FIELDS_KEY, true );
		return is_array( $result ) ? $result : [];
	}

	/**
	 * Given a $term_id an an array of metadata fields, return the metadata fields required to make changes to the status.
	 */
	public static function update( int $term_id, array $metadata_fields ): void {
		update_term_meta( $term_id, Custom_Status::METADATA_REQ_EDITORIAL_FIELDS_KEY, $metadata_fields );
	}
}

RequiredMetadataFields::init();
