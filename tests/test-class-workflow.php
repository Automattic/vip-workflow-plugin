<?php

/**
 * Class ClassWorkflowTest
 *
 * @package vip-workflow-plugin
 */

namespace VIPWorkflow\Tests;

use VIPWorkflow\VIP_Workflow;
use WP_UnitTestCase;

class ClassWorkflowTest extends WP_UnitTestCase {

	public function test_validate_vip_workflow_instance_exists() {
		$this->assertTrue( VIP_Workflow::instance() !== null );
	}
}
