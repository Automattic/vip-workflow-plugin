<?php
/**
 * class RequiredUserIdHandler
 *
 * This class serves as a handler for required user IDs on a custom status. It will set them on a custom status, and remove them when they are deleted.
 */

namespace VIPWorkflow\Modules\CustomStatus\Meta;

use VIPWorkflow\Modules\Custom_Status;
use VIPWorkflow\VIP_Workflow;
use VIPWorkflow\Modules\Shared\PHP\MetaCleanupUtilities;

use WP_Term;
use WP_User;

class RequiredUserIdHandler {

	public static function init(): void {
		// Add the required user IDs to the custom status
		add_filter( 'vw_register_custom_status_meta', [ __CLASS__, 'add_required_user_ids' ], 10, 2 );

		// Remove deleted users from required users, and if a user is reassigned, update the required users
		add_action( 'delete_user', [ __CLASS__, 'remove_deleted_user_from_required_users' ], 10, 2 );
	}

	/**
	 * Add the required user IDs to the custom status
	 *
	 * @param array $term_meta The meta keys for the custom status
	 * @param WP_Term $custom_status The custom status term
	 * @return array The updated meta keys
	 */
	public static function add_required_user_ids( array $term_meta, WP_Term $custom_status ): array {
		$user_ids = get_term_meta( $custom_status->term_id, Custom_Status::METADATA_REQ_USER_IDS_KEY, true );
		if ( ! is_array( $user_ids ) ) {
			$user_ids = [];
		}

		$term_meta[ Custom_Status::METADATA_REQ_USER_IDS_KEY ] = $user_ids;

		// For UI purposes, add 'required_users' to the term meta as well.
		$term_meta[ Custom_Status::METADATA_REQ_USERS_KEY ] = array_filter( array_map( function ( $user_id ) {
			$user = get_user_by( 'ID', $user_id );
			if ( $user instanceof WP_User ) {
				return [
					'id'   => $user->ID,
					'slug' => $user->user_login,
				];
			} else {
				return false;
			}
		}, $term_meta[ Custom_Status::METADATA_REQ_USER_IDS_KEY ] ) );

		return $term_meta;
	}

	/**
	 * Remove the deleted user from the required users on a status, and if a user is reassigned, update the required users
	 *
	 * @param integer $deleted_user_id The ID of the user that was deleted
	 * @param integer|null $reassigned_user_id The ID of the user that the deleted user's tasks were reassigned to
	 * @return void
	 */
	public static function remove_deleted_user_from_required_users( int $deleted_user_id, int|null $reassigned_user_id ): void {
		$custom_statuses = VIP_Workflow::instance()->custom_status->get_custom_statuses();

		MetaCleanupUtilities::cleanup_id( $custom_statuses, $deleted_user_id, $reassigned_user_id, Custom_Status::METADATA_REQ_USER_IDS_KEY );
	}
}

RequiredUserIdHandler::init();
