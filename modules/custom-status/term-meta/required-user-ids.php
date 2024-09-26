<?php
/**
 * class RequiredUserIds
 * Stores user IDs requried to make changes to a custom status.
 */

namespace VIPWorkflow\Modules\CustomStatus;

class RequiredUserIds {
	const TERM_META_KEY = 'required_user_ids';

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
