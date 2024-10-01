<?php
/**
 * class RequiredMetadataIdCleanup
 *
 * Cleans up editorial metadata IDs that are required on a custom status, and should be cleaned up once they are deleted.
 */

namespace VIPWorkflow\Modules\CustomStatus\Meta;

use VIPWorkflow\Modules\Custom_Status;
use VIPWorkflow\VIP_Workflow;
use VIPWorkflow\Modules\Shared\PHP\MetaCleanupUtilities;

class RequiredMetadataIdCleanup {

	public static function init(): void {
		// Remove deleted metadata fields from required metadata fields
		add_action( 'vw_editorial_metadata_term_deleted', [ __CLASS__, 'remove_deleted_metadata_from_required_metadata' ], 10, 1 );
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

RequiredMetadataIdCleanup::init();
