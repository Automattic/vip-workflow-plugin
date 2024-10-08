<?php

/**
 * Class NotificationsTest
 *
 * @package vip-workflow-plugin
 */

namespace VIPWorkflow\Tests;

use VIPWorkflow\Modules\Notifications;
use VIPWorkflow\Modules\Settings;
use VIPWorkflow\Modules\Shared\PHP\OptionsUtilities;
use WP_Error;
use WP_UnitTestCase;

class NotificationsTest extends WP_UnitTestCase {

	protected function tearDown(): void {
		parent::tearDown();

		reset_phpmailer_instance();

		// Reset all module options
		OptionsUtilities::reset_all_module_options();
	}

	public function test_validate_get_notification_footer() {
		$expected_result = "\r\n--------------------\r\nYou are receiving this email because a notification was configured via the VIP Workflow Plugin.\r\n";
		$result = Notifications::get_notification_footer();

		$this->assertTrue( $result === $expected_result );
	}

	public function test_send_emails() {
		$recipients = [ 'test1@gmail.com', 'test2@gmail.com', 'test3@gmail.com' ];
		$subject = 'Test Subject';
		$body = 'Test Body';

		Notifications::send_emails( $recipients, $subject, $body );

		$email = tests_retrieve_phpmailer_instance()->get_sent();

		$this->assertNotFalse( $email );
		$this->assertNotEmpty( $email );
		$this->assertSame( 3, count( $email->to ) );
		$this->assertSame( $subject, $email->subject );
		$this->assertDiscardWhitespace( $body, $email->body );
	}

	public function test_send_to_webhook_happy_path() {
		// Hook in and return a known response
		add_filter( 'pre_http_request', function () {
			return array(
				'headers'     => array(),
				'cookies'     => array(),
				'filename'    => null,
				'response'    => 200,
				'status_code' => 200,
				'success'     => 1,
				'body'        => 'All Done',
			);
		}, 10, 3 );

		OptionsUtilities::update_module_option_key( Settings::SETTINGS_SLUG, 'webhook_url', 'https://webhook.site/this-url-doesnt-exist' );

		$response = Notifications::send_to_webhook( 'Test Message', 'status-change', '2024-09-19 00:26:50' );

		$this->assertTrue( $response );

		OptionsUtilities::update_module_option_key( Settings::SETTINGS_SLUG, 'webhook_url', '' );
	}

	public function test_send_to_webhook_error_path() {
		// Hook in and return a known response
		add_filter( 'pre_http_request', function () {
			return new WP_Error( 'http_request_failed', 'Error Message' );
		}, 10, 3 );

		OptionsUtilities::update_module_option_key( Settings::SETTINGS_SLUG, 'webhook_url', 'https://webhook.site/this-url-doesnt-exist' );

		$response = Notifications::send_to_webhook( 'Test Message', 'status-change', '2024-09-19 00:26:50' );

		$this->assertFalse( $response );

		OptionsUtilities::update_module_option_key( Settings::SETTINGS_SLUG, 'webhook_url', '' );
	}
}
