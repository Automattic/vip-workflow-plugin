<?php
/**
 * class EditStatus
 * REST endpoint for updating a custom status
 */

namespace VIPWorkflow\Modules\SecurePreview;

use VIPWorkflow\VIP_Workflow;
use WP_Error;
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
				// URL Parameters
				'post_id'         => [
					'required'          => true,
					'validate_callback' => function ( $param ) {
						$post_id = absint( $param );
						return get_post( $post_id ) instanceof \WP_Post;
					},
					'sanitize_callback' => function ( $param ) {
						return absint( $param );
					},
				],

				// POST data parameters
				'expiration'      => [
					'required'          => true,
					'validate_callback' => function ( $param ) {
						$expiration_options = VIP_Workflow::instance()->secure_preview->get_link_expiration_options();
						$expiration_values  = wp_list_pluck( $expiration_options, 'value' );

						return in_array( $param, $expiration_values );
					},
					'sanitize_callback' => function ( $param ) {
						return strval( $param );
					},
				],
				'is_one_time_use' => [
					'required'          => true,
					'validate_callback' => function ( $param ) {
						return true === $param || false === $param;
					},
					'sanitize_callback' => function ( $param ) {
						return boolval( $param );
					},
				],

			],
		] );
	}

	/**
	 * Users allowed to edit the post can generate preview links
	 */
	public static function permission_callback( WP_REST_Request $request ) {
		$post_id = $request->get_param( 'post_id' );
		return current_user_can( 'edit_posts', $post_id );
	}

	/**
	 * Handle a request to create a preview token
	 *
	 * @param WP_REST_Request $request
	 */
	public static function handle_generate_preview_token( WP_REST_Request $request ) {
		$post_id          = $request->get_param( 'post_id' );
		$expiration_value = $request->get_param( 'expiration' );
		$is_one_time_use  = $request->get_param( 'is_one_time_use' );

		if ( ! VIP_Workflow::instance()->custom_status->is_post_using_custom_status( $post_id ) ) {
			$post_status = get_post_status( $post_id );

			if ( 'publish' === $post_status ) {
				return new WP_Error( 'vip-workflow-published-post', __( 'Secure links can not be generated for published posts.', 'vip-workflow' ) );
			} elseif ( 'auto-draft' === $post_status ) {
				return new WP_Error( 'vip-workflow-not-custom-status', __( 'Posts must be saved before a secure link can be generated.', 'vip-workflow' ) );
			} else {
				return new WP_Error( 'vip-workflow-not-custom-status', __( 'Secure links can only be generated for pre-published posts with a custom status.', 'vip-workflow' ) );
			}
		}

		$expiration_seconds = self::get_expiration_seconds( $expiration_value );
		if ( is_wp_error( $expiration_seconds ) ) {
			return $expiration_seconds;
		}

		$token = Token::generate_token( $post_id, $is_one_time_use, $expiration_seconds );

		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$preview_url = get_preview_post_link( $post_id, [ 'vw-token' => $token ] );

		// Remove unused 'preview_id' query param
		$preview_url = remove_query_arg( 'preview_id', $preview_url );

		return [
			'url' => $preview_url,
		];
	}

	// Public API

	/**
	 * Given a post ID, returns the URL for a preview link generation endpoint.
	 *
	 * @param int $post_id The post to generate a preview URL for.
	 * @return string The URL for the preview endpoint.
	 */
	public static function get_url( $post_id ) {
		return rest_url( sprintf( '%s/preview/%d', VIP_WORKFLOW_REST_NAMESPACE, absint( $post_id ) ) );
	}

	// Utility methods

	/**
	 * Given an expiration value (e.g. "1h"), returns the number of seconds that the expiration represents
	 * or a WP_Error if the value is invalid. Expiration definitions are provided in the secure_preview module.
	 *
	 * @param int $expiration_value A value defined in get_link_expiration_options(), e.g. "1h".
	 * @return int|WP_Error The number of seconds that the expiration represents or a WP_Error if the value is invalid.
	 */
	private static function get_expiration_seconds( $expiration_value ) {
		$expiration_options         = VIP_Workflow::instance()->secure_preview->get_link_expiration_options();
		$selected_expiration_option = array_values( array_filter( $expiration_options, function ( $option ) use ( $expiration_value ) {
			return $option['value'] === $expiration_value;
		} ) );

		if ( empty( $selected_expiration_option ) ) {
			// Translators: %s: the invalid expiration value selected, e.g. "1h"
			return new WP_Error( 'vip-workflow-invalid-expiration', sprintf( __( 'Invalid expiration selection: "%s".', 'vip-workflow' ), $expiration_value ) );
		} elseif ( count( $selected_expiration_option ) !== 1 || ! isset( $selected_expiration_option[0]['second_count'] ) ) {
			// Translators: %s: the invalid expiration value selected, e.g. "1h"
			return new WP_Error( 'vip-workflow-invalid-expiration', sprintf( __( 'Unable to determine the expiration for selection: "%s".', 'vip-workflow' ), $expiration_value ) );
		}

		$expiration_seconds = $selected_expiration_option[0]['second_count'];
		return $expiration_seconds;
	}
}
