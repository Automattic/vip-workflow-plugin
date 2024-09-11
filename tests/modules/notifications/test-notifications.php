<?php

use VIPWorkflow\Modules\Notifications;
use VIPWorkflow\VIP_Workflow;

class Test_Notifications extends WP_UnitTestCase {

	protected static $admin_user_id;
	protected static $notifications;

	public static function wpSetUpBeforeClass( $factory ) {
		/**
		 * `install` is hooked to `admin_init` and `init` is hooked to `init`.
		 * This means when running these tests, you can encounter a situation
		 * where the custom post type taxonomy has not been loaded into the database
		 * since the tests don't trigger `admin_init` and the `install` function is where
		 * the custom post type taxonomy is loaded into the DB.
		 *
		 * So make sure we do one cycle of `install` followed by `init` to ensure
		 * custom post type taxonomy has been loaded.
		 */
		VIP_Workflow::instance()->custom_status->install();
		VIP_Workflow::instance()->custom_status->init();

		self::$admin_user_id = $factory->user->create( array( 'role' => 'administrator' ) );

		self::$notifications = new Notifications();
		// self::$notifications->install();
		self::$notifications->init();
	}

	public static function wpTearDownAfterClass() {
		self::delete_user( self::$admin_user_id );
		self::$notifications = null;
	}

	protected function tearDown(): void {
		reset_phpmailer_instance();
		parent::tearDown();
	}

	/**
	 * Test that a notification status change text is accurate when status changed
	 */
	function test_send_post_notification_status_changed() {
		global $edit_flow;

		VIP_Workflow::instance()->settings->module->options->always_notify_admin = 'on';

		$post = $this->factory->post->create_and_get( array(
			'post_author' => self::$admin_user_id,
			'post_content' => rand_str(),
			'post_title' => rand_str(),
			'post_date_gmt' => '2016-04-29 12:00:00',
		) );

		wp_insert_post( [
			'ID' => $post->ID,
			'status' => 'assigned',
		] );

		$mailer = tests_retrieve_phpmailer_instance();

		$this->assertTrue( strpos( $mailer->get_sent()->body, 'New => Draft' ) > 0 );
	}
}
