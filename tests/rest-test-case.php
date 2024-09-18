<?php
/**
 * Class RestApiTest
 *
 * @package vip-block-data-api
 */

namespace VIPWorkflow\Tests;

use PHPUnit\Framework\TestCase;
use WP_REST_Request;
use WP_REST_Server;

/**
 * e2e tests to ensure that the REST API endpoint is available.
 */
class RestTestCase extends TestCase {
	protected static $administrator_user_id;
	protected $server;

	/**
	 * Create an administrator user for REST testing. Runs once.
	 */
	public static function setUpBeforeClass(): void {
		self::$administrator_user_id = wp_insert_user([
			'user_login' => 'admin-rest-user',
			'user_pass'  => wp_generate_password(),
			'user_email' => 'admin-rest-user@example.com',
			'role'       => 'administrator',
		]);
	}

	/**
	 * Remove test user. Runs once.
	 */
	public static function tearDownAfterClass(): void {
		wp_delete_user( self::$administrator_user_id );
	}

	/**
	 * Before each test, register REST endpoints.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Create a new REST server
		$this->server = new WP_REST_Server();

		// Replace the global REST server with ours, and then run 'rest_api_init' hooks to register endpoints
		global $wp_rest_server;
		$wp_rest_server = $this->server;
		do_action( 'rest_api_init', $wp_rest_server );
	}

	/**
	 * After each test, reset REST endpoints.
	 */
	protected function tearDown(): void {
		global $wp_rest_server;
		$wp_rest_server = null;

		parent::tearDown();
	}

	/**
	 * Given a WP_REST_Request, add a nonce header to the request. Required for authenticated requests.
	 */
	protected function add_rest_nonce( WP_REST_Request $request ): void {
		$request->set_header( 'X-Wp-Nonce', wp_create_nonce( 'wp_rest' ) );
	}
}
