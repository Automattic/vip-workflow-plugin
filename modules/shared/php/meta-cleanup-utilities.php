<?php
/**
 * class MetaCleanupUtilities
 *
 * @desc Utility methods for cleaning up `term_meta` data.
 */

namespace VIPWorkflow\Modules\Shared\PHP;

class MetaCleanupUtilities {

	/**
	 * Cleans up a required ID on a list of IDs on a term_meta.
	 *
	 * Example: If a user is deleted, we need to remove the user from the list of required users on a status.
	 *
	 * @param array $objects_to_clean The list of objects to clean up, assumed to be WP_Term objects
	 * @param integer $id_to_delete The ID of the item that was deleted
	 * @param integer|null $id_to_replace   The ID of the item that the deleted item was reassigned to
	 * @param string $taxonomy_key  The key of the taxonomy to clean up
	 *
	 * @return void
	 */
	public static function cleanup_id( array $objects_to_clean, int $id_to_delete, int|null $id_to_replace, string $taxonomy_key ): void {
		foreach ( $objects_to_clean as $object_to_clean ) {
			$required_ids  = self::get_array( $object_to_clean->term_id, $taxonomy_key );

			if ( [] === $required_ids ) {
				// Ignore this, it doesn't have any required ids
				continue;
			}

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

			self::update( $object_to_clean->term_id, $required_ids, $taxonomy_key );
		}
	}

	/**
	 * Given a $term_id, return the required ids.
	 *
	 * @param integer $term_id The term ID of the custom status
	 * @param string $taxonomy_key The key of the taxonomy to get the required fields from
	 * @return array The required ids required to make changes to the status
	 */
	public static function get_array( int $term_id, string $taxonomy_key ): array {
		$result = get_term_meta( $term_id, $taxonomy_key, true );
		return is_array( $result ) ? $result : [];
	}

	/**
	 * Given a $term_id, return the required id.
	 *
	 * @param integer $term_id
	 * @param string $taxonomy_key
	 * @return integer The required id required to make changes to the status
	 */
	public static function get_int( int $term_id, string $taxonomy_key ): int {
		$result = get_term_meta( $term_id, $taxonomy_key, true );
		return is_numeric( $result ) ? $result : 0;
	}

	/**
	 * Update the required ids, given a $term_id and an array of required ids.
	 *
	 * @param integer $term_id The term ID of the custom status
	 * @param array $required_ids The required ids required to make changes to the status
	 * @param string $taxonomy_key The key of the taxonomy to get the required fields from
	 * @return void
	 */
	private static function update( int $term_id, array $required_ids, string $taxonomy_key ): void {
		update_term_meta( $term_id, $taxonomy_key, $required_ids );
	}
}
