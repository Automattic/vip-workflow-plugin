<?php
/**
 * class RequiredUserIdCleanup
 *
 * Cleans up user IDs that are required on a custom status, and should be cleaned up once they are deleted.
 */

namespace VIPWorkflow\Modules\CustomStatus\Meta;

use VIPWorkflow\Modules\Custom_Status;
use VIPWorkflow\VIP_Workflow;
use VIPWorkflow\Modules\Shared\PHP\MetaCleanupUtilities;

class RequiredUserIdCleanup {

	public static function init(): void {
		// Remove deleted users from required users, and if a user is reassigned, update the required users
		add_action( 'delete_user', [ __CLASS__, 'remove_deleted_user_from_required_users' ], 10, 2 );
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

RequiredUserIdCleanup::init();
