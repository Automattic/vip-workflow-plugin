<?php
/**
 * class RequiredUserIds
 * Stores user IDs requried to make changes to a custom status.
 */

namespace VIPWorkflow\Modules\CustomStatus\Meta;

use VIPWorkflow\VIP_Workflow;

class RequiredUserIds {
	const TERM_META_KEY = 'required_user_ids';

	public static function init(): void {
		add_action( 'delete_user', [ __CLASS__, 'remove_deleted_user_from_required_users' ], 10, 2 );
	}

	public static function remove_deleted_user_from_required_users( int $deleted_user_id, int|null $reassigned_user_id ): void {
		$custom_statuses = VIP_Workflow::instance()->custom_status->get_custom_statuses();

		foreach ( $custom_statuses as $custom_status ) {
			$required_user_ids  = self::get( $custom_status->term_id );
			$deleted_user_index = array_search( $deleted_user_id, $required_user_ids, /* strict */ true );

			if ( false === $deleted_user_index ) {
				// Ignore this status, the user isn't associated with it
				continue;
			}

			if ( null === $reassigned_user_id ) {
				// Remove the deleted user
				unset( $required_user_ids[ $deleted_user_index ] );
				$required_user_ids = array_values( $required_user_ids );
			} else {
				// Replace the deleted user with the reassigned user
				$required_user_ids[ $deleted_user_index ] = $reassigned_user_id;
			}

			// ToDo: Clear the custom status cache
			self::update( $custom_status->term_id, $required_user_ids );
		}
	}

	// Public API

	/**
	 * Given a $term_id, return the user IDs required to make changes to the status.
	 */
	public static function get( int $term_id ): array {
		$result = get_term_meta( $term_id, self::TERM_META_KEY, true );
		return is_array( $result ) ? $result : [];
	}

	/**
	 * Given a $term_id an an array of user IDs, return the user IDs required to make changes to the status.
	 */
	public static function update( int $term_id, array $user_ids ): void {
		update_term_meta( $term_id, self::TERM_META_KEY, $user_ids );
	}
}

RequiredUserIds::init();
