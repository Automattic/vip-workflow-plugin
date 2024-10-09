<?php

/**
 * Class RequiredMetadataIdHandlerTest
 *
 * @package vip-workflow-plugin
 */
namespace VIPWorkflow\Tests;

use VIPWorkflow\Modules\CustomStatus;
use VIPWorkflow\Modules\CustomStatus\Meta\RequiredMetadataIdHandler;

class RequiredMetadataIdHandlerTest extends WorkflowTestCase {
	public function test_remove_deleted_metadata_from_required_metadata() {
		$meta_id            = 1;
		$custom_status_term = CustomStatus::add_custom_status( [
			'name'                  => 'Test Custom Status',
			'slug'                  => 'test-custom-status',
			'description'           => 'Test Description.',
			'required_metadata_ids' => [ $meta_id ],
		] );
		$term_id            = $custom_status_term->term_id;

		RequiredMetadataIdHandler::remove_deleted_metadata_from_required_metadata( $meta_id );

		$updated_term = CustomStatus::get_custom_status_by( 'id', $term_id );

		$this->assertEquals( 'Test Custom Status', $updated_term->name );
		$this->assertEquals( 'Test Description.', $updated_term->description );
		$this->assertEmpty( $updated_term->meta['required_metadata_ids'] );
	}
}
