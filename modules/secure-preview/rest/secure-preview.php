<?php
/**
 * class EditStatus
 * REST endpoint for updating a custom status
 */

namespace VIPWorkflow\Modules\SecurePreview;

use VIPWorkflow\VIP_Workflow;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

class SecurePreviewEndpoint {
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
		register_rest_route( VIP_WORKFLOW_REST_NAMESPACE, '/preview/(?P<post_id>[0-9]+)', [
			'methods'             => 'POST',
			'callback'            => [ __CLASS__, 'handle_generate_preview_token' ],
			'permission_callback' => [ __CLASS__, 'permission_callback' ],
			'args'                => [
				// Required parameters
				'post_id' => [
					'required'          => true,
					'validate_callback' => function ( $param ) {
						$post_id = absint( $param );
						return VIP_Workflow::instance()->custom_status->is_post_using_custom_status( $post_id );
					},
					'sanitize_callback' => function ( $param ) {
						return absint( $param );
					},
				],
			],
		] );
	}

	/**
	 * Check if the current user has permission to manage edit the post
	 */
	public static function permission_callback( WP_REST_Request $request ) {
		$post_id = $request->get_param( 'post_id' );
		return current_user_can( 'edit_posts', $post_id );
	}

	/**
	 * Handle a request to create a new status
	 *
	 * @param WP_REST_Request $request
	 */
	public static function handle_generate_preview_token( WP_REST_Request $request ) {
		$post_id = $request->get_param( 'post_id' );

		$token = Token::generate_token( $post_id, 'edit_posts' );

		if ( is_wp_error( $token ) ) {
			return $token;
		}

		return [
			'token' => $token,
		];
	}

	// Public API

	/**
	 * Given a post ID, returns the URL for the preview endpoint for that post.
	 *
	 * @param int $post_id The post to generate a preview URL for.
	 * @return string The URL for the preview endpoint.
	 */
	public static function get_url( $post_id ) {
		return rest_url( sprintf( '%s/preview/%d', VIP_WORKFLOW_REST_NAMESPACE, absint( $post_id ) ) );
	}
}
