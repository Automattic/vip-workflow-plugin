<?php

/**
 * Class RequiredMetadataIdHandlerTest
 *
 * @package vip-workflow-plugin
 */
namespace VIPWorkflow\Tests;

use VIPWorkflow\Modules\CustomStatus;
use VIPWorkflow\Modules\CustomStatus\Meta\RequiredMetadataIdHandler;
use VIPWorkflow\Modules\Shared\PHP\OptionsUtilities;
use WP_UnitTestCase;

class RequiredMetadataIdHandlerTest extends WP_UnitTestCase {

	/**
	 * Before each test, ensure default custom statuses are available and reset all module options.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Reset all module options
		OptionsUtilities::reset_all_module_options();

		// Normally custom statuses are installed on 'admin_init', which is only run when a page is accessed
		// in the admin web interface. Manually install them here. This avoid issues when a test creates or deletes
		// a status and it's the only status existing, which can cause errors due to status restrictions.
		CustomStatus::setup_install();
	}

	public function test_remove_deleted_metadata_from_required_metadata() {
		$meta_id = 1;
		$custom_status_term = CustomStatus::add_custom_status( [
			'name'               => 'Test Custom Status',
			'slug'               => 'test-custom-status',
			'description'        => 'Test Description.',
			'required_metadata_ids' => [ $meta_id ],
		] );
		$term_id     = $custom_status_term->term_id;

		RequiredMetadataIdHandler::remove_deleted_metadata_from_required_metadata( $meta_id );

		$updated_term = CustomStatus::get_custom_status_by( 'id', $term_id );

		$this->assertEquals( 'Test Custom Status', $updated_term->name );
		$this->assertEquals( 'Test Description.', $updated_term->description );
		$this->assertEmpty( $updated_term->meta['required_metadata_ids'] );

		CustomStatus::delete_custom_status( $term_id );
	}
}
