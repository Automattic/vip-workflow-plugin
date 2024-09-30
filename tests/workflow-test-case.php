<?php
/**
 * Class WorkflowTestCase
 *
 * @package vip-workflow
 */

namespace VIPWorkflow\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Extension of TestCase with helper methods
 */
class WorkflowTestCase extends TestCase {
	protected $user_ids_to_cleanup = [];

	/**
	 * Before each test, register REST endpoints.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Always ensure we're starting as an unauthenticated user unless specifically set
		wp_set_current_user( null );
	}

	/**
	 * After each test, reset REST endpoints.
	 */
	protected function tearDown(): void {
		// Remove any users created during this test
		foreach ( $this->user_ids_to_cleanup as $user_id ) {
			if ( is_multisite() ) {
				// Ensure user is fully deleted in multisite tests
				wpmu_delete_user( $user_id );
			} else {
				wp_delete_user( $user_id );
			}
		}

		$this->user_ids_to_cleanup = [];

		parent::tearDown();
	}

	protected function create_user( $username, $args = [] ) {
		$default_args = [
			'user_login'   => $username,
			'user_pass'    => 'password',
			'display_name' => $username,
			'user_email'   => sprintf( '%s@example.com', $username ),
			'role'         => 'editor',
		];

		$user_id = wp_insert_user( array_merge( $default_args, $args ) );

		if ( is_wp_error( $user_id ) ) {
			throw new \Exception( esc_html( $user_id->get_error_message() ) );
		}

		$this->user_ids_to_cleanup[] = $user_id;

		return $user_id;
	}
}
