<?php
/**
 * class EditorialMetadata
 * REST endpoint for updating an editorial metadata
 */

namespace VIPWorkflow\Modules\EditorialMetadata\REST;

use VIPWorkflow\Modules\Editorial_Metadata;
use VIPWorkflow\VIP_Workflow;
use WP_Error;
use WP_REST_Request;
use WP_Term;

defined( 'ABSPATH' ) || exit;

class EditEditorialMetadata {
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
		register_rest_route( VIP_WORKFLOW_REST_NAMESPACE, '/editorial-metadata', [
			'methods'             => 'POST',
			'callback'            => [ __CLASS__, 'handle_create_editorial_metadata' ],
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
				'type'        => [
					'required'          => true,
					'validate_callback' => function ( $param ) {
						$param = trim( $param );
						return ! empty( $param ) && in_array( $param, Editorial_Metadata::SUPPORTED_METADATA_TYPES );
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
		] );

		register_rest_route( VIP_WORKFLOW_REST_NAMESPACE, '/editorial-metadata/(?P<id>[0-9]+)', [
			'methods'             => 'PUT',
			'callback'            => [ __CLASS__, 'handle_update_editorial_metadata' ],
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
				'id'          => [
					'required'          => true,
					'validate_callback' => function ( $param ) {
						$term_id = absint( $param );
						$term    = get_term( $term_id, Editorial_Metadata::METADATA_TAXONOMY );
						return ( $term instanceof WP_Term );
					},
					'sanitize_callback' => function ( $param ) {
						return absint( $param );
					},
				],
				'type'        => [
					'required'          => true,
					'validate_callback' => function ( $param ) {
						$param = trim( $param );
						return ! empty( $param ) && in_array( $param, Editorial_Metadata::SUPPORTED_METADATA_TYPES );
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
		] );

		register_rest_route( VIP_WORKFLOW_REST_NAMESPACE, '/editorial-metadata/(?P<id>[0-9]+)', [
			'methods'             => 'DELETE',
			'callback'            => [ __CLASS__, 'handle_delete_editorial_metadata' ],
			'permission_callback' => [ __CLASS__, 'permission_callback' ],
			'args'                => [
				// Required parameters
				'id' => [
					'required'          => true,
					'validate_callback' => function ( $param ) {
						$term_id = absint( $param );
						$term    = get_term( $term_id, Editorial_Metadata::METADATA_TAXONOMY );
						return ( $term instanceof WP_Term );
					},
					'sanitize_callback' => function ( $param ) {
						return absint( $param );
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
	 * Handle a request to create a new editorial metadata
	 *
	 * @param WP_REST_Request $request
	 */
	public static function handle_create_editorial_metadata( WP_REST_Request $request ) {
		$editorial_metadata_name        = sanitize_text_field( $request->get_param( 'name' ) );
		$editorial_metadata_slug        = sanitize_title( $request->get_param( 'name' ) );
		$editorial_metadata_description = $request->get_param( 'description' );
		$editorial_metadata_type         = $request->get_param( 'type' );

		$editorial_metadata_module = VIP_Workflow::instance()->editorial_metadata;

		// Check that the name isn't numeric
		if ( is_numeric( $editorial_metadata_name ) ) {
			return new WP_Error( 'invalid', 'Please enter a valid, non-numeric name for the editorial metadata.' );
		}

		// Check to make sure the name isn't too long
		if ( strlen( $editorial_metadata_name ) > 200 ) {
			return new WP_Error( 'invalid', 'Editorial metadata name is too long. Please choose a name that is 200 characters or less.' );
		}

		// Check to make sure the editorial metadata doesn't already exist as another term because otherwise we'd get a fatal error
		$term_exists = term_exists( $editorial_metadata_slug, Editorial_Metadata::METADATA_TAXONOMY );

		if ( $term_exists ) {
			return new WP_Error( 'invalid', 'Editorial metadata name conflicts with existing term. Please choose another.' );
		}

		$args = [
			'description' => $editorial_metadata_description,
			'slug'        => $editorial_metadata_slug,
			'type'        => $editorial_metadata_type,
			'name'        => $editorial_metadata_name,
		];

		$add_editorial_metadata_result = $editorial_metadata_module->insert_editorial_metadata_term( $args );

		return rest_ensure_response( $add_editorial_metadata_result );
	}

	/**
	 * Handle a request to update the new editorial metadata
	 *
	 * @param WP_REST_Request $request
	 */
	public static function handle_update_editorial_metadata( WP_REST_Request $request ) {
		$term_id                        = $request->get_param( 'id' );
		$editorial_metadata_name        = sanitize_text_field( $request->get_param( 'name' ) );
		$editorial_metadata_slug        = sanitize_title( $request->get_param( 'name' ) );
		$editorial_metadata_description = $request->get_param( 'description' );
		$editorial_metadata_type         = $request->get_param( 'type' );

		$editorial_metadata_module = VIP_Workflow::instance()->editorial_metadata;

		// Check that the name isn't numeric
		if ( is_numeric( $editorial_metadata_name ) ) {
			return new WP_Error( 'invalid', 'Please enter a valid, non-numeric name for the editorial metadata.' );
		}

		// Check to make sure the name isn't too long
		if ( strlen( $editorial_metadata_name ) > 200 ) {
			return new WP_Error( 'invalid', 'Editorial metadata name is too long. Please choose a name that is 200 characters or less.' );
		}

		// Check to make sure the status doesn't already exist
		$editorial_metadata_by_id = $editorial_metadata_module->get_editorial_metadata_term_by( 'id', $term_id );

		$editorial_metadata_by_slug = $editorial_metadata_module->get_editorial_metadata_term_by( 'slug', $editorial_metadata_slug );

		if ( $editorial_metadata_by_slug && ( $editorial_metadata_by_id->slug !== $editorial_metadata_slug ) ) {
			return new WP_Error( 'invalid', 'Editorial Metadata already exists. Please choose another name.' );
		}

		// Check to make sure the editorial metadata doesn't already exist as another term because otherwise we'd get a fatal error
		$term_exists = term_exists( $editorial_metadata_slug, Editorial_Metadata::METADATA_TAXONOMY );

		// term_id from term_exists is a string, while term_id is an integer so not using strict comparison
		if ( $term_exists && isset( $term_exists['term_id'] ) && $term_exists['term_id'] != $term_id ) {
			return new WP_Error( 'invalid', 'Editorial metadata name conflicts with existing term. Please choose another.' );
		}

		// get status_name & status_description
		$args = [
			'description' => $editorial_metadata_description,
			'slug'        => $editorial_metadata_slug,
			'type'        => $editorial_metadata_type,
			'name'        => $editorial_metadata_name,
		];

		// ToDo: Ensure that we don't do an update when the name and description are the same as the current status
		$update_editorial_metadata_result = $editorial_metadata_module->update_editorial_metadata_term( $term_id, $args );

		// Regardless of an error being thrown, the result will be returned so the client can handle it.
		return rest_ensure_response( $update_editorial_metadata_result );
	}

	/**
	 * Handle a request to delete the status
	 *
	 * @param WP_REST_Request $request
	 */
	public static function handle_delete_editorial_metadata( WP_REST_Request $request ) {
		$term_id = $request->get_param( 'id' );

		$editorial_metadata_module = VIP_Workflow::instance()->editorial_metadata;

		// Check to make sure the status exists
		$editorial_metadata_by_id = $editorial_metadata_module->get_editorial_metadata_term_by( 'id', $term_id );
		if ( ! $editorial_metadata_by_id ) {
			return new WP_Error( 'invalid', 'Editorial Metadata does not exist.' );
		}

		$delete_editorial_metadata_result = $editorial_metadata_module->delete_editorial_metadata_term( $term_id );

		// Regardless of an error being thrown, the result will be returned so the client can handle it.
		return rest_ensure_response( $delete_editorial_metadata_result );
	}

	// Public API

	/**
	 * Get the URL for the editorial metadata CRUD endpoint
	 *
	 * @return string The CRUD URL
	 */
	public static function get_crud_url() {
		return rest_url( sprintf( '%s/%s', VIP_WORKFLOW_REST_NAMESPACE, 'editorial-metadata/' ) );
	}
}
