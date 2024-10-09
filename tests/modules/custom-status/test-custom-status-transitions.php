<?php
/**
 * Class CustomStatusTransitionsTest
 *
 * @package vip-workflow-plugin
 */

namespace VIPWorkflow\Tests;

use VIPWorkflow\Modules\CustomStatus;

/**
 * Ensure restricted posts block updates from unauthorized users.
 */
class CustomStatusTransitionsTest extends WorkflowTestCase {

	public function test_transition_restrictions_as_privileged_user() {
		$admin_user_id = $this->factory()->user->create( [
			'role' => 'administrator',
		] );

		wp_set_current_user( $admin_user_id );

		// Setup statuses, with the second status requiring admin user permissions
		$status_1 = CustomStatus::add_custom_status( [
			'name'     => 'Status 1',
			'position' => -3,
			'slug'     => 'status-1',
		] );

		$status_2_restricted = CustomStatus::add_custom_status( [
			'name'              => 'Status 2 (restricted)',
			'position'          => -2,
			'slug'              => 'status-2-restricted',
			'required_user_ids' => [ $admin_user_id ],
		] );

		$status_3 = CustomStatus::add_custom_status( [
			'name'     => 'Status 3',
			'position' => -1,
			'slug'     => 'status-3',
		] );

		// Create a new post
		$post_id = wp_insert_post( [
			'post_title'   => 'Test Post',
			'post_content' => 'Test content',
			'post_status'  => 'status-1',
		] );
		$this->assertEquals( 'status-1', get_post_status( $post_id ) );

		// Transition to the restricted status
		$transtion_to_status_2_result = wp_update_post( [
			'ID'          => $post_id,
			'post_status' => 'status-2-restricted',
		], /* return WP_Error */ true );
		$this->assertEquals( $post_id, $transtion_to_status_2_result );
		$this->assertEquals( 'status-2-restricted', get_post_status( $post_id ) );

		// Transition out of the restricted status as a privileged user
		$transtion_to_status_3_result = wp_update_post( [
			'ID'          => $post_id,
			'post_status' => 'status-3',
		], /* return WP_Error */ true );

		$this->assertEquals( $post_id, $transtion_to_status_3_result );
		$this->assertEquals( 'status-3', get_post_status( $post_id ) );
	}

	public function test_transition_restrictions_as_unprivileged_user() {
		$author_user_id = $this->factory()->user->create( [
			'role' => 'author',
		] );

		wp_set_current_user( $author_user_id );

		// Setup statuses, with the second status requiring admin user permissions
		$status_1 = CustomStatus::add_custom_status( [
			'name'     => 'Status 1',
			'position' => -3,
			'slug'     => 'status-1',
		] );

		$admin_user_id = $this->factory()->user->create( [
			'role' => 'administrator',
		] );

		$status_2_restricted = CustomStatus::add_custom_status( [
			'name'              => 'Status 2 (restricted)',
			'position'          => -2,
			'slug'              => 'status-2-restricted',
			'required_user_ids' => [ $admin_user_id ],
		] );

		$status_3 = CustomStatus::add_custom_status( [
			'name'     => 'Status 3',
			'position' => -1,
			'slug'     => 'status-3',
		] );

		// Create a new post
		$post_id = wp_insert_post( [
			'post_title'   => 'Test Post',
			'post_content' => 'Test content',
			'post_status'  => 'status-1',
		] );
		$this->assertEquals( 'status-1', get_post_status( $post_id ) );

		// Transition to the restricted status
		$transtion_to_status_2_result = wp_update_post( [
			'ID'          => $post_id,
			'post_status' => 'status-2-restricted',
		], /* return WP_Error */ true );
		$this->assertEquals( $post_id, $transtion_to_status_2_result );
		$this->assertEquals( 'status-2-restricted', get_post_status( $post_id ) );

		// Transition out of the restricted status as an unprivileged user
		$transtion_to_status_3_result = wp_update_post( [
			'ID'          => $post_id,
			'post_status' => 'status-3',
		], /* return WP_Error */ true );

		$this->assertInstanceOf( 'WP_Error', $transtion_to_status_3_result );
		$this->assertEquals( 'status-2-restricted', get_post_status( $post_id ) );
	}
}
