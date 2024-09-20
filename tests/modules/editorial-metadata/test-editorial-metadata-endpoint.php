<?php
/**
 * Class EditorialMetadataRestApiTest
 *
 * @package vip-workflow
 */

namespace VIPWorkflow\Tests;

use VIPWorkflow\Modules\EditorialMetadata;
use WP_REST_Request;

/**
 * e2e tests to ensure that the Editorial Metadata REST API endpoint is available.
 */
class EditorialMetadataRestApiTest extends RestTestCase {

	/**
	 * Before each test, ensure default editorial metadatas are available.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Normally editorial metadatas are installed on 'admin_init', which is only run when a page is accessed
		// in the admin web interface. Manually install them here. This avoid issues when running tests.
		EditorialMetadata::setup_install();
	}

	public function test_create_editorial_metadata() {
		$request = new WP_REST_Request( 'POST', sprintf( '/%s/%s', VIP_WORKFLOW_REST_NAMESPACE, 'editorial-metadata' ) );
		$request->set_body_params( [
			'name'               => 'Test Metadata',
			'description'        => 'A test metadata for testing',
			'type'               => 'checkbox',
		] );

		wp_set_current_user( self::$administrator_user_id );
		$this->add_rest_nonce( $request );
		$response = $this->server->dispatch( $request );
		wp_set_current_user( null );

		$this->assertEquals( 200, $response->get_status(), sprintf( 'Unexpected REST output: %s', wp_json_encode( $response ) )  );

		$result = $response->get_data();

		$this->assertObjectHasProperty( 'term_id', $result, sprintf( 'Unexpected REST output: %s', wp_json_encode( $result ) ) );
		$term_id = $result->term_id;

		$created_term = EditorialMetadata::get_editorial_metadata_term_by( 'id', $term_id );

		$this->assertEquals( 'Test Metadata', $created_term->name );
		$this->assertEquals( 'A test metadata for testing', $created_term->description );
		$this->assertEquals( 'checkbox', $created_term->type );

		EditorialMetadata::delete_editorial_metadata_term( $term_id );
	}
}
