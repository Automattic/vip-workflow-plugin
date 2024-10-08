<?php
/**
 * Class CustomStatusRestApiTest
 *
 * @package vip-workflow-plugin
 */

namespace VIPWorkflow\Tests;

use VIPWorkflow\Modules\CustomStatus;
use VIPWorkflow\Modules\EditorialMetadata;
use VIPWorkflow\Modules\Shared\PHP\OptionsUtilities;
use WP_REST_Request;

/**
 * e2e tests to ensure that the Custom Status REST API endpoint is available.
 */
class CustomStatusRestApiTest extends RestTestCase {

	/**
	 * Before each test, ensure default custom statuses are available.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Normally custom statuses are installed on 'admin_init', which is only run when a page is accessed
		// in the admin web interface. Manually install them here. This avoid issues when a test creates or deletes
		// a status and it's the only status existing, which can cause errors due to status restrictions.
		CustomStatus::setup_install();
	}

	// tear down after class
	protected function tearDown(): void {
		parent::tearDown();

		// Reset all module options
		OptionsUtilities::reset_all_module_options();
	}

	public function test_create_custom_status_with_optional_fields() {
		$editorial_metadata_term = EditorialMetadata::insert_editorial_metadata_term( [
			'name'        => 'Test Metadata 1',
			'description' => 'A test metadata for testing',
			'type'        => 'text',
		] );
		$admin_user_id = self::create_user( 'test-admin', [ 'role' => 'administrator' ] );

		$request = new WP_REST_Request( 'POST', sprintf( '/%s/%s', VIP_WORKFLOW_REST_NAMESPACE, 'custom-status' ) );
		$request->set_body_params( [
			'name'              => 'test-status',
			'description'       => 'A test status for testing',
			'required_user_ids' => [ $admin_user_id ],
			'required_metadata_ids' => [ $editorial_metadata_term->term_id ],
		] );

		wp_set_current_user( $this->administrator_user_id );
		$this->add_rest_nonce( $request );
		$response = $this->server->dispatch( $request );
		wp_set_current_user( null );

		$this->assertEquals( 200, $response->get_status() );

		$result = $response->get_data();

		$this->assertObjectHasProperty( 'term_id', $result, sprintf( 'Unexpected REST output: %s', wp_json_encode( $result ) ) );
		$term_id = $result->term_id;

		$created_term = CustomStatus::get_custom_status_by( 'id', $term_id );

		$this->assertEquals( 'test-status', $created_term->name );
		$this->assertEquals( 'A test status for testing', $created_term->description );
		$this->assertCount( 1, $created_term->meta['required_user_ids'] );
		$this->assertEquals( $admin_user_id, $created_term->meta['required_user_ids'][0] );
		$this->assertCount( 1, $created_term->meta['required_metadata_ids'] );
		$this->assertEquals( $editorial_metadata_term->term_id, $created_term->meta['required_metadata_ids'][0] );

		CustomStatus::delete_custom_status( $term_id );
		EditorialMetadata::delete_editorial_metadata_term( $editorial_metadata_term->term_id );
	}

	public function test_create_custom_status() {
		$request = new WP_REST_Request( 'POST', sprintf( '/%s/%s', VIP_WORKFLOW_REST_NAMESPACE, 'custom-status' ) );
		$request->set_body_params( [
			'name' => 'test-status',
		] );

		wp_set_current_user( $this->administrator_user_id );
		$this->add_rest_nonce( $request );
		$response = $this->server->dispatch( $request );
		wp_set_current_user( null );

		$this->assertEquals( 200, $response->get_status() );

		$result = $response->get_data();

		$this->assertObjectHasProperty( 'term_id', $result, sprintf( 'Unexpected REST output: %s', wp_json_encode( $result ) ) );
		$term_id = $result->term_id;

		$created_term = CustomStatus::get_custom_status_by( 'id', $term_id );

		$this->assertEquals( 'test-status', $created_term->name );

		CustomStatus::delete_custom_status( $term_id );
	}

	public function test_update_custom_status() {
		$custom_status_term = CustomStatus::add_custom_status( [
			'name'               => 'Test Custom Status',
			'slug'               => 'test-custom-status',
			'description'        => 'Test Description.',
		] );

		$term_id        = $custom_status_term->term_id;
		$editor_user_id = self::create_user( 'test-editor', [ 'role' => 'editor' ] );

		$request = new WP_REST_Request( 'PUT', sprintf( '/%s/%s/%d', VIP_WORKFLOW_REST_NAMESPACE, 'custom-status', $term_id ) );
		$request->set_body_params( [
			'id'                => $term_id,
			'name'              => 'Test Custom Status 2',
			'description'       => 'Test Description 2!',
			'required_user_ids' => [ $editor_user_id ],
		] );

		wp_set_current_user( $this->administrator_user_id );
		$this->add_rest_nonce( $request );
		$response = $this->server->dispatch( $request );
		wp_set_current_user( null );

		$this->assertEquals( 200, $response->get_status() );

		$updated_term = CustomStatus::get_custom_status_by( 'id', $term_id );

		$this->assertEquals( 'Test Custom Status 2', $updated_term->name );
		$this->assertEquals( 'Test Description 2!', $updated_term->description );
		$this->assertCount( 1, $updated_term->meta['required_user_ids'] );
		$this->assertEquals( $editor_user_id, $updated_term->meta['required_user_ids'][0] );

		CustomStatus::delete_custom_status( $term_id );
	}

	public function test_delete_custom_status() {
		$term_to_delete = CustomStatus::add_custom_status( [
			'name' => 'Custom Status To Delete',
			'slug' => 'custom-status-to-delete',
		] );

		$term_to_delete_id = $term_to_delete->term_id;

		$request = new WP_REST_Request( 'DELETE', sprintf( '/%s/%s/%d', VIP_WORKFLOW_REST_NAMESPACE, 'custom-status', $term_to_delete_id ) );

		wp_set_current_user( $this->administrator_user_id );
		$this->add_rest_nonce( $request );
		$response = $this->server->dispatch( $request );
		wp_set_current_user( null );

		$this->assertEquals( 200, $response->get_status() );

		$all_terms      = CustomStatus::get_custom_statuses();
		$all_term_slugs = wp_list_pluck( $all_terms, 'slug' );
		$this->assertNotContains( 'custom-status-to-delete', $all_term_slugs );
	}

	public function test_reorder_custom_status() {
		$existing_custom_statuses   = CustomStatus::get_custom_statuses();
		$existing_custom_status_ids = wp_list_pluck( $existing_custom_statuses, 'term_id' );

		$term1 = CustomStatus::add_custom_status( [
			'name' => 'Custom Status 1',
			'slug' => 'custom-status-1',
		] );
		$term2 = CustomStatus::add_custom_status( [
			'name' => 'Custom Status 2',
			'slug' => 'custom-status-2',
		] );
		$term3 = CustomStatus::add_custom_status( [
			'name' => 'Custom Status 3',
			'slug' => 'custom-status-3',
		] );

		$request = new WP_REST_Request( 'POST', sprintf( '/%s/%s/%s', VIP_WORKFLOW_REST_NAMESPACE, 'custom-status', 'reorder' ) );
		$request->set_body_params( [
			'status_positions' => [
				$term3->term_id,
				$term1->term_id,
				$term2->term_id,
				...$existing_custom_status_ids,
			],
		] );

		wp_set_current_user( $this->administrator_user_id );
		$this->add_rest_nonce( $request );
		$response = $this->server->dispatch( $request );
		wp_set_current_user( null );

		$this->assertEquals( 200, $response->get_status() );

		$reordered_custom_statuses = CustomStatus::get_custom_statuses();
		$this->assertEquals( $term3->term_id, $reordered_custom_statuses[0]->term_id );
		$this->assertEquals( $term1->term_id, $reordered_custom_statuses[1]->term_id );
		$this->assertEquals( $term2->term_id, $reordered_custom_statuses[2]->term_id );

		CustomStatus::delete_custom_status( $term1->term_id );
		CustomStatus::delete_custom_status( $term2->term_id );
		CustomStatus::delete_custom_status( $term3->term_id );
	}
}
