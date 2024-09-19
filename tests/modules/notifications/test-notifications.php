<?php

/**
 * Class NotificationsTest
 *
 * @package vip-workflow-plugin
 */

namespace VIPWorkflow\Tests;

use VIPWorkflow\VIP_Workflow;
use VIPWorkflow\Modules\Notifications;
use WP_Error;
use WP_UnitTestCase;

class NotificationsTest extends WP_UnitTestCase {

	protected function tearDown(): void {
    	parent::tearDown();

    	reset_phpmailer_instance();
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
		$this->assertSame($subject, $email->subject );
		$this->assertDiscardWhitespace( $body, $email->body );
	}

	public function test_send_to_webhook_happy_path() {
		// Hook in and return a known response
		add_filter( 'pre_http_request', function() {
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

		VIP_Workflow::instance()->settings->module->options->webhook_url = 'https://webhook.site/this-url-doesnt-exist';

		$response = Notifications::send_to_webhook( 'Test Message', 'status-change', '2024-09-19 00:26:50' );

		$this->assertTrue( $response );

		VIP_Workflow::instance()->settings->module->options->webhook_url = '';
	}

	public function test_send_to_webhook_error_path() {
		// Hook in and return a known response
		add_filter( 'pre_http_request', function() {
			return new WP_Error( 'http_request_failed', 'Error Message' );
		}, 10, 3 );

		VIP_Workflow::instance()->settings->module->options->webhook_url = 'https://webhook.site/this-url-doesnt-exist';

		$response = Notifications::send_to_webhook( 'Test Message', 'status-change', '2024-09-19 00:26:50' );

		$this->assertFalse( $response );

		VIP_Workflow::instance()->settings->module->options->webhook_url = '';
	}

	public function test_status_change_triggers_notification_events() {
		// Hook in and return a known response
		add_filter( 'pre_http_request', function() {
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

		VIP_Workflow::instance()->settings->module->options->webhook_url = 'https://webhook.site/this-url-doesnt-exist';
		VIP_Workflow::instance()->settings->module->options->email_address = [ 'test@gmail.com' ];

		$post = array(
			'post_content' => rand_str(),
			'post_title' => rand_str(),
			'post_date_gmt' => '2024-01-01 12:00:00',
		);

		wp_insert_post( $post );

		$cron_events = reset(_get_cron_array());

		$this->assertArrayHasKey( 'vw_send_scheduled_emails', $cron_events );
		$this->assertArrayHasKey( 'vw_send_scheduled_webhook', $cron_events );

		VIP_Workflow::instance()->settings->module->options->webhook_url = '';
		VIP_Workflow::instance()->settings->module->options->email_address = [ ];
	}
}
