<?php

namespace VIPWorkflow\Tests;

use VIPWorkflow\VIP_Workflow;
use PHPUnit\Framework\TestCase;

class ClassWorkflowTest extends TestCase {

	public function test_validate_vip_workflow_instance_exists() {
		$this->assertTrue( VIP_Workflow::instance() !== null );
	}
}
