<?php
/**
 * class ReqMetadataFieldsCronCleaner
 * Cleans up fields that are required on a custom status, and should be cleaned up.
 */

namespace VIPWorkflow\Modules\CustomStatus\Meta;

use VIPWorkflow\Modules\Custom_Status;
use VIPWorkflow\VIP_Workflow;

class RequiredFieldsCronCleaner {

	public static function init(): void {
		// Remove deleted users from required users, and if a user is reassigned, update the required users
		add_action( 'delete_user', [ __CLASS__, 'remove_deleted_user_from_required_users' ], 10, 2 );

		// Remove deleted metadata fields from required metadata fields
		add_action( 'vw_editorial_metadata_term_deleted', [ __CLASS__, 'remove_deleted_metadata_from_required_metadata' ], 10, 1 );
	}

	/**
	 * Remove the delete metadata from the required metadata fields on a status
	 *
	 * @param integer $meta_id The meta ID that was deleted
	 * @return void
	 */
	public static function remove_deleted_metadata_from_required_metadata( int $meta_id ): void {
		self::cleanup_required_field( $meta_id, /* id_to_replace */ null, Custom_Status::METADATA_REQ_EDITORIAL_FIELDS_KEY );
	}

	/**
	 * Remove the deleted user from the required users on a status, and if a user is reassigned, update the required users
	 *
	 * @param integer $deleted_user_id The ID of the user that was deleted
	 * @param integer|null $reassigned_user_id The ID of the user that the deleted user's tasks were reassigned to
	 * @return void
	 */
	public static function remove_deleted_user_from_required_users( int $deleted_user_id, int|null $reassigned_user_id ): void {
		self::cleanup_required_field( $deleted_user_id, $reassigned_user_id, Custom_Status::METADATA_REQ_USER_IDS_KEY );
	}

	/**
	 * Cleans up a required field on a custom status
	 *
	 * @param integer $id_to_delete The ID of the item that was deleted
	 * @param integer|null $id_to_replace   The ID of the item that the deleted item was reassigned to
	 * @param string $taxonomy_key  The key of the taxonomy to clean up
	 *
	 * @return void
	 */
	private static function cleanup_required_field( int $id_to_delete, int|null $id_to_replace, string $taxonomy_key ): void {
		$custom_statuses = VIP_Workflow::instance()->custom_status->get_custom_statuses();

		foreach ( $custom_statuses as $custom_status ) {
			$required_ids  = self::get( $custom_status->term_id, $taxonomy_key );
			$deleted_id_index = array_search( $id_to_delete, $required_ids, /* strict */ true );

			if ( false === $deleted_id_index ) {
				// Ignore this status, the id isn't associated with it
				continue;
			}

			if ( null === $id_to_replace ) {
				// Remove the deleted id, and don't replace it
				unset( $required_ids[ $deleted_id_index ] );
				$required_ids = array_values( $required_ids );
			} else {
				// Replace the deleted id with the reassigned id
				$required_ids[ $deleted_id_index ] = $id_to_replace;
			}

			self::update( $custom_status->term_id, $required_ids, $taxonomy_key );
		}
	}

	// Public API

	/**
	 * Given a $term_id, return the required ids required to make changes to the status.
	 *
	 * @param integer $term_id The term ID of the custom status
	 * @param string $taxonomy_key The key of the taxonomy to get the required fields from
	 * @return array The required ids required to make changes to the status
	 */
	public static function get( int $term_id, string $taxonomy_key ): array {
		$result = get_term_meta( $term_id, $taxonomy_key, true );
		return is_array( $result ) ? $result : [];
	}

	/**
	 * Given a $term_id an an array of required ids, return the required ids required to make changes to the status.
	 *
	 * @param integer $term_id The term ID of the custom status
	 * @param array $required_ids The required ids required to make changes to the status
	 * @param string $taxonomy_key The key of the taxonomy to get the required fields from
	 * @return void
	 */
	public static function update( int $term_id, array $required_ids, string $taxonomy_key ): void {
		update_term_meta( $term_id, $taxonomy_key, $required_ids );
	}
}

RequiredFieldsCronCleaner::init();
