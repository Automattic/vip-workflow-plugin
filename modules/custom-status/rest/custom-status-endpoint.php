<?php
/**
 * class CustomStatusEndpoint
 * REST endpoint for managing custom statuses
 */

namespace VIPWorkflow\Modules\CustomStatus\REST;

use VIPWorkflow\Modules\CustomStatus;
use VIPWorkflow\Modules\EditorialMetadata;
use WP_Error;
use WP_REST_Request;
use WP_Term;

defined( 'ABSPATH' ) || exit;

class CustomStatusEndpoint {
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
		register_rest_route( VIP_WORKFLOW_REST_NAMESPACE, '/custom-status', [
			'methods'             => 'POST',
			'callback'            => [ __CLASS__, 'handle_create_status' ],
			'permission_callback' => [ __CLASS__, 'permission_callback' ],
			'args'                => [
				// Required parameters
				'name'              => [
					'required'          => true,
					'validate_callback' => function ( $param ) {
						return ! empty( trim( $param ) );
					},
					'sanitize_callback' => function ( $param ) {
						return trim( $param );
					},
				],

				// Optional parameters
				'description'       => [
					'default'           => '',
					'sanitize_callback' => function ( $param ) {
						return stripslashes( wp_filter_nohtml_kses( trim( $param ) ) );
					},
				],
				'required_metadata_ids' => [
					'default'           => [],
					'validate_callback' => function ( $param ) {
						$metadata_ids = array_map( 'absint', $param );
						return self::is_valid_editorial_metadata_ids( $metadata_ids );
					},
					'sanitize_callback' => function ( $param ) {
						return array_map( 'absint', $param );
					},
				],
				'required_user_ids' => [
					'default'           => [],
					'validate_callback' => function ( $param ) {
						$user_ids = array_map( 'absint', $param );
						return self::is_valid_user_ids( $user_ids );
					},
					'sanitize_callback' => function ( $param ) {
						return array_map( 'absint', $param );
					},
				],
			],
		] );

		register_rest_route( VIP_WORKFLOW_REST_NAMESPACE, '/custom-status/(?P<id>[0-9]+)', [
			'methods'             => 'PUT',
			'callback'            => [ __CLASS__, 'handle_update_status' ],
			'permission_callback' => [ __CLASS__, 'permission_callback' ],
			'args'                => [
				// Required parameters
				'name'              => [
					'required'          => true,
					'validate_callback' => function ( $param ) {
						return ! empty( trim( $param ) );
					},
					'sanitize_callback' => function ( $param ) {
						return trim( $param );
					},
				],
				'id'                => [
					'required'          => true,
					'validate_callback' => function ( $param ) {
						$term_id = absint( $param );
						$term    = get_term( $term_id, CustomStatus::TAXONOMY_KEY );
						return ( $term instanceof WP_Term );
					},
					'sanitize_callback' => function ( $param ) {
						return absint( $param );
					},
				],

				// Optional parameters
				'description'       => [
					'default'           => '',
					'sanitize_callback' => function ( $param ) {
						return stripslashes( wp_filter_nohtml_kses( trim( $param ) ) );
					},
				],
				'required_metadata_ids' => [
					'default'           => [],
					'validate_callback' => function ( $param ) {
						$metadata_ids = array_map( 'absint', $param );
						return self::is_valid_editorial_metadata_ids( $metadata_ids );
					},
					'sanitize_callback' => function ( $param ) {
						return array_map( 'absint', $param );
					},
				],
				'required_user_ids' => [
					'default'           => [],
					'validate_callback' => function ( $param ) {
						$user_ids = array_map( 'absint', $param );
						return self::is_valid_user_ids( $user_ids );
					},
					'sanitize_callback' => function ( $param ) {
						return array_map( 'absint', $param );
					},
				],
			],
		] );

		register_rest_route( VIP_WORKFLOW_REST_NAMESPACE, '/custom-status/(?P<id>[0-9]+)', [
			'methods'             => 'DELETE',
			'callback'            => [ __CLASS__, 'handle_delete_status' ],
			'permission_callback' => [ __CLASS__, 'permission_callback' ],
			'args'                => [
				// Required parameters
				'id' => [
					'required'          => true,
					'validate_callback' => function ( $param ) {
						$term_id = absint( $param );
						$term    = get_term( $term_id, CustomStatus::TAXONOMY_KEY );
						return ( $term instanceof WP_Term );
					},
					'sanitize_callback' => function ( $param ) {
						return absint( $param );
					},
				],
			],
		] );

		register_rest_route( VIP_WORKFLOW_REST_NAMESPACE, '/custom-status/reorder', [
			'methods'             => 'POST',
			'callback'            => [ __CLASS__, 'handle_reorder_status' ],
			'permission_callback' => [ __CLASS__, 'permission_callback' ],
			'args'                => [
				// Required parameters
				'status_positions' => [
					'required'          => true,
					'validate_callback' => function ( $param ) {
						if ( ! is_array( $param ) ) {
							return false;
						}

						// validate each item in the array.
						foreach ( $param as $position => $term_id ) {
							$term_id = absint( $term_id );
							$term    = get_term( $term_id, CustomStatus::TAXONOMY_KEY );
							if ( ! $term instanceof WP_Term ) {
								return false;
							}
						}

						return true;
					},
					'sanitize_callback' => function ( $param ) {
						// Sanitize each item in the array.
						foreach ( $param as $position => $term_id ) {
							$param[ $position ] = absint( $term_id );
						}
						return $param;
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
	public static function handle_create_status( WP_REST_Request $request ) {
		$status_name               = sanitize_text_field( $request->get_param( 'name' ) );
		$status_slug               = sanitize_title( $request->get_param( 'name' ) );
		$status_description        = $request->get_param( 'description' );
		$status_required_metadata_ids = $request->get_param( 'required_metadata_ids' );
		$status_required_user_ids = $request->get_param( 'required_user_ids' );

		// Check that the name isn't numeric
		if ( is_numeric( $status_name ) ) {
			return new WP_Error( 'invalid', 'Please enter a valid, non-numeric name for the status.' );
		}

		// Check to make sure the name is not restricted
		if ( CustomStatus::is_restricted_status( strtolower( $status_name ) ) ) {
			return new WP_Error( 'invalid', 'Status name is restricted. Please chose another name.' );
		}

		// Check to make sure the name isn't too long
		if ( strlen( $status_name ) > 20 ) {
			return new WP_Error( 'invalid', 'Status name is too long. Please choose a name that is 20 characters or less.' );
		}

		// Check to make sure the status doesn't already exist as another term because otherwise we'd get a fatal error
		$term_exists = term_exists( $status_slug, CustomStatus::TAXONOMY_KEY );

		if ( $term_exists ) {
			return new WP_Error( 'invalid', 'Status name conflicts with existing term. Please choose another.' );
		}

		$args = [
			'name'               => $status_name,
			'description'        => $status_description,
			'slug'               => $status_slug,
			'required_metadata_ids' => $status_required_metadata_ids,
			'required_user_ids' => $status_required_user_ids,
		];

		$add_status_result = CustomStatus::add_custom_status( $args );

		// Regardless of an error being thrown, the result will be returned so the client can handle it.
		return rest_ensure_response( $add_status_result );
	}

	/**
	 * Handle a request to update the status
	 *
	 * @param WP_REST_Request $request
	 */
	public static function handle_update_status( WP_REST_Request $request ) {
		$term_id                   = $request->get_param( 'id' );
		$status_name               = sanitize_text_field( $request->get_param( 'name' ) );
		$status_slug               = sanitize_title( $request->get_param( 'name' ) );
		$status_description        = $request->get_param( 'description' );
		$status_required_metadata_ids = $request->get_param( 'required_metadata_ids' );
		$status_required_user_ids = $request->get_param( 'required_user_ids' );

		// Check that the name isn't numeric
		if ( is_numeric( $status_name ) ) {
			return new WP_Error( 'invalid', 'Please enter a valid, non-numeric name for the status.' );
		}

		// Check to make sure the name is not restricted
		if ( CustomStatus::is_restricted_status( strtolower( $status_name ) ) ) {
			return new WP_Error( 'invalid', 'Status name is restricted. Please chose another name.' );
		}

		// Check to make sure the name isn't too long
		if ( strlen( $status_name ) > 20 ) {
			return new WP_Error( 'invalid', 'Status name is too long. Please choose a name that is 20 characters or less.' );
		}

		// Check to make sure the status doesn't already exist
		$custom_status_by_id   = CustomStatus::get_custom_status_by( 'id', $term_id );
		$custom_status_by_slug = CustomStatus::get_custom_status_by( 'slug', $status_slug );

		if ( $custom_status_by_slug && $custom_status_by_id && $custom_status_by_id->slug !== $status_slug ) {
			return new WP_Error( 'invalid', 'Status already exists. Please choose another name.' );
		}

		// Check to make sure the status doesn't already exist as another term because otherwise we'd get a fatal error
		$term_exists = term_exists( $status_slug, CustomStatus::TAXONOMY_KEY );

		// term_id from term_exists is a string, while term_id is an integer so not using strict comparison
		if ( $term_exists && isset( $term_exists['term_id'] ) && $term_exists['term_id'] != $term_id ) {
			return new WP_Error( 'invalid', 'Status name conflicts with existing term. Please choose another.' );
		}

		// get status_name & status_description
		$args = [
			'name'               => $status_name,
			'description'        => $status_description,
			'slug'               => $status_slug,
			'required_metadata_ids' => $status_required_metadata_ids,
			'required_user_ids' => $status_required_user_ids,
		];

		$updated_status = CustomStatus::update_custom_status( $term_id, $args );

		// Regardless of an error being thrown, the result will be returned so the client can handle it.
		return rest_ensure_response( $updated_status );
	}

	/**
	 * Handle a request to delete the status
	 *
	 * @param WP_REST_Request $request
	 */
	public static function handle_delete_status( WP_REST_Request $request ) {
		$term_id = $request->get_param( 'id' );

		// Check to make sure the status exists
		$custom_status_by_id = CustomStatus::get_custom_status_by( 'id', $term_id );
		if ( ! $custom_status_by_id ) {
			return new WP_Error( 'invalid', 'Status does not exist.' );
		}

		$delete_status_result = CustomStatus::delete_custom_status( $term_id );

		// Regardless of an error being thrown, the result will be returned so the client can handle it.
		return rest_ensure_response( $delete_status_result );
	}

	/**
	 * Handle a request to reorder the statuses
	 *
	 * @param WP_REST_Request $request
	 */
	public static function handle_reorder_status( WP_REST_Request $request ) {
		$status_order = $request->get_param( 'status_positions' );

		if ( ! is_array( $status_order ) ) {
			return new WP_Error( 'invalid', 'Status order must be an array.' );
		}

		foreach ( $status_order as $position => $term_id ) {

			// Have to add 1 to the position because the index started with zero
			$args = [
				'position' => absint( $position ) + 1,
			];

			$update_status_result = CustomStatus::update_custom_status( (int) $term_id, $args );

			// Stop the operation immediately if something has gone wrong, rather than silently continuing.
			if ( is_wp_error( $update_status_result ) ) {
				return $update_status_result;
			}
		}

		// Regardless of an error being thrown, the result will be returned so the client can handle it.
		return rest_ensure_response( CustomStatus::get_custom_statuses() );
	}

	// Utility functions

	private static function is_valid_editorial_metadata_ids( $metadata_ids ) {
		// If the array is empty, it's valid
		if ( [] === $metadata_ids ) {
			return true;
		}

		foreach ( $metadata_ids as $metadata_id ) {
			$editorial_metadata = EditorialMetadata::get_editorial_metadata_term_by( 'id', $metadata_id );
			if ( is_wp_error( $editorial_metadata ) || ! $editorial_metadata ) {
				return false;
			}
		}

		return true;
	}

	private static function is_valid_user_ids( $user_ids ) {
		// If the array is empty, it's valid
		if ( [] === $user_ids ) {
			return true;
		}

		$args = [
			'include' => $user_ids,
			'fields'  => [ 'ID' ],
		];

		$users_found = get_users( $args );

		if ( count( $users_found ) !== count( $user_ids ) ) {
			return false;
		}

		return true;
	}

	// Public API

	/**
	 * Get the URL for the custom status CRUD endpoint
	 *
	 * @return string The CRUD URL
	 */
	public static function get_crud_url() {
		return rest_url( sprintf( '%s/%s', VIP_WORKFLOW_REST_NAMESPACE, 'custom-status/' ) );
	}

	/**
	 * Get the URL for the custom status reorder endpoint
	 *
	 * @return string The reorder URL
	 */
	public static function get_reorder_url() {
		return rest_url( sprintf( '%s/%s', VIP_WORKFLOW_REST_NAMESPACE, 'custom-status/reorder' ) );
	}
}

CustomStatusEndpoint::init();
