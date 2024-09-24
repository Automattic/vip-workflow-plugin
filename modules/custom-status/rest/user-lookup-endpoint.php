<?php
/**
 * class UserLookupEndpoint
 * REST endpoint for searching users
 */

namespace VIPWorkflow\Modules\CustomStatus\REST;

use WP_REST_Request;
use WP_User_Query;

defined( 'ABSPATH' ) || exit;

class UserLookupEndpoint {
	/**
	 * Initialize the class
	 */
	public static function init() {
		add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
	}

	/**
	 * Register the REST routes
	 */
	public static function register_routes() {
		register_rest_route( VIP_WORKFLOW_REST_NAMESPACE, '/user-lookup/(?P<user_search>[^/]+)', [
			'methods'             => 'GET',
			'callback'            => [ __CLASS__, 'handle_user_lookup' ],
			'permission_callback' => [ __CLASS__, 'permission_callback' ],
			'args'                => [
				// Required parameters
				'user_search' => [
					'required'          => true,
					'validate_callback' => function ( $param ) {
						return ! empty( trim( $param ) );
					},
					'sanitize_callback' => function ( $param ) {
						return trim( $param );
					},
				],
			],
		] );
	}

	/**
	 * Check if the current user has permission to manage options
	 */
	public static function permission_callback() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Handle a request to create a new status
	 *
	 * @param WP_REST_Request $request
	 */
	public static function handle_user_lookup( WP_REST_Request $request ) {
		$user_search = sanitize_text_field( $request->get_param( 'user_search' ) );

		$user_query = new WP_User_Query( [
			'fields'         => [ 'ID', 'display_name', 'user_login', 'user_email' ],
			'search'         => sprintf( '*%s*', $user_search ),
			'search_columns' => [ 'ID', 'display_name', 'user_login', 'user_email' ],
			'number'         => 20,
		] );

		return $user_query->get_results();
	}

	// Public API

	/**
	 * Get the URL for the custom status CRUD endpoint
	 *
	 * @return string The CRUD URL
	 */
	public static function get_url() {
		return rest_url( sprintf( '%s/%s', VIP_WORKFLOW_REST_NAMESPACE, 'user-lookup/' ) );
	}
}

UserLookupEndpoint::init();
