<?php

use VIPWorkflow\Modules\CustomStatus\REST\EditStatus;

class Test_EditStatus extends WP_UnitTestCase {

	private $user_id;

	protected function setUp(): void {
		parent::setUp();

		$this->user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->user_id );
	}

	protected function tearDown(): void {
		parent::tearDown();
		wp_delete_user( $this->user_id );
	}

	public function test_post_custom_status() {
		$this->markTestSkipped( 'This is causing other tests to fail' );
		$request = new WP_REST_Request( 'POST', '/vip-workflow/v1/custom-status' );
		$request->set_body_params( array(
			'name' => 'Test Status',
			'slug' => 'test-status',
			'color' => '#ff0000',
			'position' => 1,
			'post_types' => array( 'post' ),
		) );

		$response = rest_get_server()->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'Test Status', $data->name );
		$this->assertEquals( 'test-status', $data->slug );
		$this->assertEquals( 1, $data->position );
	}
}
