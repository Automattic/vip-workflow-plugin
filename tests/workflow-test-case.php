<?php
/**
 * Class WorkflowTestCase
 *
 * @package vip-workflow
 */

namespace VIPWorkflow\Tests;

use VIPWorkflow\Modules\CustomStatus;
use VIPWorkflow\Modules\Shared\PHP\OptionsUtilities;
use WP_UnitTestCase;

/**
 * Extension of TestCase with helper methods
 */
class WorkflowTestCase extends WP_UnitTestCase {
	/**
	 * Before each test, register REST endpoints.
	 */
	protected function setUp(): void {
		// Reset module options at the start of each test, which allows setup_install() to install a default
		// set of custom statuses. Tested code expects some default statuses to always be available.
		OptionsUtilities::reset_all_module_options();
		CustomStatus::setup_install();

		parent::setUp();
	}
}
