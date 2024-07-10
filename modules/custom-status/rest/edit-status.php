<?php
/**
 * class EditStatus
 * REST endpoint for updating a custom status
 */

namespace VIPWorkflow\Modules\CustomStatus\REST;

use VIPWorkflow\Modules\Custom_Status;
use VIPWorkflow\VIP_Workflow;
use WP_Error;
use WP_REST_Request;
use WP_Term;

defined( 'ABSPATH' ) || exit;

class EditStatus {
	public static function init() {
		add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
	}

	public static function register_routes() {
		register_rest_route( VIP_WORKFLOW_REST_NAMESPACE, '/custom-status/(?P<id>[0-9]+)', [
			'methods'             => 'PUT',
			'callback'            => [ __CLASS__, 'handle_put_custom_status_request' ],
			'permission_callback' => [ __CLASS__, 'permission_callback' ],
			'args'                => [
				// Required parameters
				'name'        => [
					'required'          => true,
					'validate_callback' => function ( $param ) {
						return ! empty( trim( $param ) );
					},
					'sanitize_callback' => function ( $param ) {
						return trim( $param );
					},
				],
				'id'   => [
					'required'          => true,
					'validate_callback' => function ( $param ) {
						$term_id = absint( $param );
						$term    = get_term( $term_id, Custom_Status::TAXONOMY_KEY );
						return ( $term instanceof WP_Term );
					},
					'sanitize_callback' => function ( $param ) {
						return absint( $param );
					},
				],

				// Optional parameters
				'description' => [
					'default'           => '',
					'sanitize_callback' => function ( $param ) {
						return stripslashes( wp_filter_nohtml_kses( trim( $param ) ) );
					},
				],
			],
		],
		[
			'methods'             => 'DELETE',
			'callback'            => [ __CLASS__, 'handle_post_custom_status_request' ],
			'permission_callback' => [ __CLASS__, 'permission_callback' ],
			'args'                => [
				// Required parameters
				'name'        => [
					'required'          => true,
					'validate_callback' => function ( $param ) {
						return ! empty( trim( $param ) );
					},
					'sanitize_callback' => function ( $param ) {
						return trim( $param );
					},
				],

				// Optional parameters
				'description' => [
					'default'           => '',
					'sanitize_callback' => function ( $param ) {
						return stripslashes( wp_filter_nohtml_kses( trim( $param ) ) );
					},
				],
			],
		]
	);

		register_rest_route( VIP_WORKFLOW_REST_NAMESPACE, '/custom-status', [
			'methods'             => 'POST',
			'callback'            => [ __CLASS__, 'handle_post_custom_status_request' ],
			'permission_callback' => [ __CLASS__, 'permission_callback' ],
			'args'                => [
				// Required parameters
				'name'        => [
					'required'          => true,
					'validate_callback' => function ( $param ) {
						return ! empty( trim( $param ) );
					},
					'sanitize_callback' => function ( $param ) {
						return trim( $param );
					},
				],

				// Optional parameters
				'description' => [
					'default'           => '',
					'sanitize_callback' => function ( $param ) {
						return stripslashes( wp_filter_nohtml_kses( trim( $param ) ) );
					},
				],
			],
		]);
	}

	public static function permission_callback() {
		return current_user_can( 'manage_options' );
	}

	public static function handle_delete_custom_status_request( WP_REST_Request $request ) {
		$term_id = $request->get_param( 'id' );

		$custom_status_module = VIP_Workflow::instance()->custom_status;

		// Check to make sure the status doesn't already exist
		$custom_status_by_id   = $custom_status_module->get_custom_status_by( 'id', $term_id );
		if ( ! $custom_status_by_id ) {
			return new WP_Error( 'invalid', 'Status does not exist.' );
		}

		// Don't allow deletion of default statuses
		if ( $custom_status_by_id->slug === $custom_status_module->get_default_custom_status()->slug ) {
			return new WP_Error( 'invalid', 'Cannot delete default status.' );
		}

		$delete_status_result = $custom_status_module->delete_custom_status( $term_id );

		if ( is_wp_error( $delete_status_result ) ) {
			return $delete_status_result;
		} else {
			return [
				'updated_statuses' => array_values( $custom_status_module->get_custom_statuses() ),
			];
		}
	}

	public static function handle_post_custom_status_request( WP_REST_Request $request ) {
		$status_name        = sanitize_text_field( $request->get_param( 'name' ) );
		$status_slug        = sanitize_title( $request->get_param( 'name' ) );
		$status_description = $request->get_param( 'description' );

		$custom_status_module = VIP_Workflow::instance()->custom_status;

		// ToDo: Ensure we have a similar error shown when the name is empty when running validate_callback above

		// // Check if name field was filled in
		// if ( empty( $status_name ) ) {
		//  $change_error = new WP_Error( 'invalid', esc_html__( 'Please enter a name for the status.', 'vip-workflow' ) );
		//  die( esc_html( $change_error->get_error_message() ) );
		// }

		// Check that the name isn't numeric
		if ( is_numeric( $status_name ) ) {
			return new WP_Error( 'invalid', 'Please enter a valid, non-numeric name for the status.' );
		}

		// Check to make sure the name is not restricted
		if ( $custom_status_module->is_restricted_status( strtolower( $status_name ) ) ) {
			return new WP_Error( 'invalid', 'Status name is restricted. Please chose another name.' );
		}

		// Check to make sure the name isn't too long
		if ( strlen( $status_name ) > 20 ) {
			return new WP_Error( 'invalid', 'Status name is too long. Please choose a name that is 20 characters or less.' );
		}

		// Check to make sure the status doesn't already exist as another term because otherwise we'd get a fatal error
		$term_exists = term_exists( $status_slug, Custom_Status::TAXONOMY_KEY );

		// term_id from term_exists is a string, while term_id is an integer so not using strict comparison
		if ( $term_exists ) {
			return new WP_Error( 'invalid', 'Status name conflicts with existing term. Please choose another.' );
		}

		// get status_slug & status_description
		$args = [
			'description' => $status_description,
			'slug'        => $status_slug,
		];

		$add_status_result = $custom_status_module->add_custom_status( $status_name, $args );

		if ( is_wp_error( $add_status_result ) ) {
			return $add_status_result;
		} else {
			return [
				'updated_statuses' => array_values( $custom_status_module->get_custom_statuses() ),
			];
		}
	}

	/**
	 * Handle a PUT request to update the status
	 */
	public static function handle_put_custom_status_request( WP_REST_Request $request ) {
		$term_id            = $request->get_param( 'id' );
		$status_name        = sanitize_text_field( $request->get_param( 'name' ) );
		$status_slug        = sanitize_title( $request->get_param( 'name' ) );
		$status_description = $request->get_param( 'description' );

		$custom_status_module = VIP_Workflow::instance()->custom_status;

		// ToDo: Ensure we have a similar error shown when the name is empty when running validate_callback above

		// // Check if name field was filled in
		// if ( empty( $status_name ) ) {
		//  $change_error = new WP_Error( 'invalid', esc_html__( 'Please enter a name for the status.', 'vip-workflow' ) );
		//  die( esc_html( $change_error->get_error_message() ) );
		// }

		// Check that the name isn't numeric
		if ( is_numeric( $status_name ) ) {
			return new WP_Error( 'invalid', 'Please enter a valid, non-numeric name for the status.' );
		}

		// Check to make sure the name is not restricted
		if ( $custom_status_module->is_restricted_status( strtolower( $status_name ) ) ) {
			return new WP_Error( 'invalid', 'Status name is restricted. Please chose another name.' );
		}

		// Check to make sure the name isn't too long
		if ( strlen( $status_name ) > 20 ) {
			return new WP_Error( 'invalid', 'Status name is too long. Please choose a name that is 20 characters or less.' );
		}

		// Check to make sure the status doesn't already exist
		$custom_status_by_id   = $custom_status_module->get_custom_status_by( 'id', $term_id );
		// if ( $custom_status_by_id->name === $status_name ) {
		// 	return new WP_Error( 'invalid', 'Status already exists. Please choose another name.' );
		// }

		$custom_status_by_slug = $custom_status_module->get_custom_status_by( 'slug', $status_slug );

		if ( $custom_status_by_slug && ( $custom_status_by_id->slug !== $status_slug ) ) {
			return new WP_Error( 'invalid', 'Status already exists. Please choose another name.' );
		}

		// Check to make sure the status doesn't already exist as another term because otherwise we'd get a fatal error
		$term_exists = term_exists( $status_slug, Custom_Status::TAXONOMY_KEY );

		// term_id from term_exists is a string, while term_id is an integer so not using strict comparison
		if ( $term_exists && isset( $term_exists['term_id'] ) && $term_exists['term_id'] != $term_id ) {
			return new WP_Error( 'invalid', 'Status name conflicts with existing term. Please choose another.' );
		}

		// get status_name & status_description
		$args = [
			'name'        => $status_name,
			'description' => $status_description,
			'slug'        => $status_slug,
		];

		// ToDo: Ensure that we don't do an update when the name and description are the same as the current status
		$update_status_result = $custom_status_module->update_custom_status( $term_id, $args );

		if ( is_wp_error( $update_status_result ) ) {
			return $update_status_result;
		} else {
			return [
				'updated_statuses' => array_values( $custom_status_module->get_custom_statuses() ),
			];
		}
	}

	// Public API

	public static function get_url() {
		return rest_url( sprintf( '%s/%s', VIP_WORKFLOW_REST_NAMESPACE, 'custom-status/' ) );
	}
}
