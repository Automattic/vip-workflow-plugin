<?php
/**
 * Class EditorialMetadataRestApiTest
 *
 * @package vip-workflow-plugin
 */

namespace VIPWorkflow\Tests;

use VIPWorkflow\Modules\EditorialMetadata;
use WP_REST_Request;

/**
 * e2e tests to ensure that the Editorial Metadata REST API endpoint is available.
 */
class EditorialMetadataRestApiTest extends RestTestCase {

	public function test_create_editorial_metadata() {
		$request = new WP_REST_Request( 'POST', sprintf( '/%s/%s', VIP_WORKFLOW_REST_NAMESPACE, 'editorial-metadata' ) );
		$request->set_body_params( [
			'name'               => 'test-metadata',
			'description'        => 'A test metadata for testing',
			'type'               => 'text',
		] );

		wp_set_current_user( self::$administrator_user_id );
		$this->add_rest_nonce( $request );
		$response = $this->server->dispatch( $request );
		wp_set_current_user( null );

		$this->assertEquals( 200, $response->get_status(), sprintf( 'Unexpected REST output: %s', wp_json_encode( $response ) ) );

		$result = $response->get_data();

		$this->assertObjectHasProperty( 'term_id', $result, sprintf( 'Unexpected REST output: %s', wp_json_encode( $result ) ) );
		$term_id = $result->term_id;

		$created_term = EditorialMetadata::get_editorial_metadata_term_by( 'id', $term_id );

		$this->assertEquals( 'test-metadata', $created_term->name );
		$this->assertEquals( 'A test metadata for testing', $created_term->description );
		$this->assertEquals( 'text', $created_term->meta['type'] );
		$this->assertEquals( 'vw_editorial_meta_text_' . $term_id, $created_term->meta['postmeta_key'] );

		EditorialMetadata::delete_editorial_metadata_term( $term_id );
	}

	public function test_update_editorial_metadata() {
		$editorial_metadata_term = EditorialMetadata::insert_editorial_metadata_term( [
			'name'        => 'Test Metadata 1',
			'description' => 'A test metadata for testing',
			'type'        => 'text',
		] );

		$term_id = $editorial_metadata_term->term_id;

		$request = new WP_REST_Request( 'PUT', sprintf( '/%s/%s/%d', VIP_WORKFLOW_REST_NAMESPACE, 'editorial-metadata', $term_id ) );
		$request->set_body_params( [
			'id'                 => $term_id,
			'name'               => 'Test Metadata 2',
			'description'        => 'Test Description 2!',
		] );

		wp_set_current_user( self::$administrator_user_id );
		$this->add_rest_nonce( $request );
		$response = $this->server->dispatch( $request );
		wp_set_current_user( null );

		$this->assertEquals( 200, $response->get_status() );

		$updated_term = EditorialMetadata::get_editorial_metadata_term_by( 'id', $term_id );

		$this->assertEquals( 'Test Metadata 2', $updated_term->name );
		$this->assertEquals( 'Test Description 2!', $updated_term->description );
		$this->assertEquals( 'text', $updated_term->meta['type'] );
		$this->assertEquals( 'vw_editorial_meta_text_' . $term_id, $updated_term->meta['postmeta_key'] );

		EditorialMetadata::delete_editorial_metadata_term( $term_id );
	}

	public function test_delete_editorial_metadata() {
		$editorial_metadata_term = EditorialMetadata::insert_editorial_metadata_term( [
			'name'        => 'metadata to delete',
			'description' => 'Delete this metadata',
			'type'        => 'text',
		] );

		$term_to_delete_id = $editorial_metadata_term->term_id;

		$request = new WP_REST_Request( 'DELETE', sprintf( '/%s/%s/%d', VIP_WORKFLOW_REST_NAMESPACE, 'editorial-metadata', $term_to_delete_id ) );

		wp_set_current_user( self::$administrator_user_id );
		$this->add_rest_nonce( $request );
		$response = $this->server->dispatch( $request );
		wp_set_current_user( null );

		$this->assertEquals( 200, $response->get_status() );

		$all_terms = EditorialMetadata::get_editorial_metadata_terms();
		$all_term_slugs = wp_list_pluck( $all_terms, 'slug' );
		$this->assertNotContains( 'metadata-to-delete', $all_term_slugs );
	}
}
