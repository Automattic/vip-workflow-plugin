<?php

/**
 * Class Editorial_Metadata
 * Editorial Metadata for VIP Workflow
 */
namespace VIPWorkflow\Modules;

require_once __DIR__ . '/rest/editorial-metadata.php';

use VIPWorkflow\VIP_Workflow;
use VIPWorkflow\Modules\Shared\PHP\Module;
use VIPWorkflow\Modules\EditorialMetadata\REST\EditEditorialMetadata;

class Editorial_Metadata extends Module {

	// ToDo: Review the default metadata types we provide OOB
	const SUPPORTED_METADATA_TYPES = [
		'checkbox',
		'date',
		'number',
		'text',
	];
	const METADATA_TAXONOMY = 'vw_editorial_meta';
	const METADATA_POSTMETA_KEY = '_vw_editorial_meta';

	public $module;

	private $editorial_metadata_terms_cache = [];

	public function __construct() {
		// Register the module with VIP Workflow
		$this->module_url = $this->get_module_url( __FILE__ );
		$args             = [
			'title'                 => __( 'Editorial Metadata', 'vip-workflow' ),
			'short_description'     => __( 'Track details about your posts in progress.', 'vip-workflow' ),
			'extended_description'  => __( 'Log details on every assignment using configurable editorial metadata. It is completely customizable; create fields for everything from due date to location to contact information to role assignments.', 'vip-workflow' ),
			'module_url'            => $this->module_url,
			'slug'                  => 'editorial-metadata',
			'configure_page_cb'    => 'print_configure_view',
		];
		$this->module     = VIP_Workflow::instance()->register_module( 'editorial_metadata', $args );
	}

	public function init() {
		// Register the taxonomy we use for Editorial Metadata with WordPress core
		$this->register_taxonomy();

		// Load CSS and JS resources that we probably need in the admin page
		add_action( 'admin_enqueue_scripts', [ $this, 'action_admin_enqueue_scripts' ] );

		EditEditorialMetadata::init();
	}

	/**
	 * Register the post metadata taxonomy
	 */
	public function register_taxonomy() {
		// We need to make sure taxonomy is registered for all of the post types that support it
		$supported_post_types = $this->get_post_types_for_module();

		register_taxonomy( self::METADATA_TAXONOMY, $supported_post_types,
			array(
				'public' => false,
				'labels' => array(
					'name' => _x( 'Editorial Metadata', 'taxonomy general name', 'vip-workflow' ),
					'singular_name' => _x( 'Editorial Metadata', 'taxonomy singular name', 'vip-workflow' ),
					'search_items' => __( 'Search Editorial Metadata', 'vip-workflow' ),
					'popular_items' => __( 'Popular Editorial Metadata', 'vip-workflow' ),
					'all_items' => __( 'All Editorial Metadata', 'vip-workflow' ),
					'edit_item' => __( 'Edit Editorial Metadata', 'vip-workflow' ),
					'update_item' => __( 'Update Editorial Metadata', 'vip-workflow' ),
					'add_new_item' => __( 'Add New Editorial Metadata', 'vip-workflow' ),
					'new_item_name' => __( 'New Editorial Metadata', 'vip-workflow' ),
				),
				'rewrite' => false,
			)
		);
	}

	/**
	 * Load default editorial metadata the first time the module is loaded
	 */
	public function install() {
		// ToDo: Review the default metadata fields we provide OOB
		$default_terms = [
			[
				'name' => __( 'First Draft Date', 'vip-workflow' ),
				'slug' => 'first-draft-date',
				'type' => 'date',
				'description' => __( 'When the first draft needs to be ready.', 'vip-workflow' ),
			],
			[
				'name' => __( 'Assignment', 'vip-workflow' ),
				'slug' => 'assignment',
				'type' => 'text',
				'description' => __( 'What the post needs to cover.', 'vip-workflow' ),
			],
			[
				'name' => __( 'Needs Photo', 'vip-workflow' ),
				'slug' => 'needs-photo',
				'type' => 'checkbox',
				'description' => __( 'Checked if this post needs a photo.', 'vip-workflow' ),
			],
			[
				'name' => __( 'Word Count', 'vip-workflow' ),
				'slug' => 'word-count',
				'type' => 'number',
				'description' => __( 'Required post length in words.', 'vip-workflow' ),
			],
		];

		// Load the metadata fields if the slugs don't conflict
		foreach ( $default_terms as $term ) {
			if ( ! term_exists( $term['slug'], self::METADATA_TAXONOMY ) ) {
				$this->insert_editorial_metadata_term( $term );
			}
		}
	}

	/**
	 * Enqueue resources that we need in the admin settings page
	 */
	public function action_admin_enqueue_scripts() {
		// Load Javascript we need to use on the configuration views
		if ( $this->is_whitelisted_settings_view() ) {
			$asset_file = include VIP_WORKFLOW_ROOT . '/dist/modules/editorial-metadata/editorial-metadata-configure.asset.php';
			wp_enqueue_script( 'vip-workflow-editorial-metadata-configure', VIP_WORKFLOW_URL . 'dist/modules/editorial-metadata/editorial-metadata-configure.js', $asset_file['dependencies'], $asset_file['version'], true );
			wp_enqueue_style( 'vip-workflow-editorial-metadata-styles', VIP_WORKFLOW_URL . 'dist/modules/editorial-metadata/editorial-metadata-configure.css', [ 'wp-components' ], $asset_file['version'] );

			wp_localize_script( 'vip-workflow-editorial-metadata-configure', 'VW_EDITORIAL_METADATA_CONFIGURE', [
				'supported_metadata_types' => self::SUPPORTED_METADATA_TYPES,
				'editorial_metadata_terms' => $this->get_editorial_metadata_terms(),
				'url_edit_editorial_metadata'    => EditEditorialMetadata::get_crud_url(),
			] );
		}
	}

	/**
	 * Get all of the editorial metadata terms as objects and sort by position
	 *
	 * @param array $filter_args Filter to specific arguments
	 * @return array $ordered_terms The terms as they should be ordered
	 */
	public function get_editorial_metadata_terms() {
		// Internal object cache for repeat requests
		if ( ! empty( $this->editorial_metadata_terms_cache ) ) {
			return $this->editorial_metadata_terms_cache;
		}

		$terms = get_terms( [
			'taxonomy'   => self::METADATA_TAXONOMY,
			'orderby'    => 'name',
			'hide_empty' => false,
		]);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			$terms = [];
		}

		$ordered_terms = [];
		$hold_to_end = [];

		// Order the terms
		foreach ( $terms as $key => $term ) {
			// Unencode and set all of our psuedo term meta because we need the position if it exists
			$unencoded_description = $this->get_unencoded_description( $term->description );
			if ( is_array( $unencoded_description ) ) {
				foreach ( $unencoded_description as $key => $value ) {
					$term->$key = $value;
				}
			}

			// We require the position key later on
			if ( ! isset( $term->position ) ) {
				$term->position = false;
			}

			// Only add the term to the ordered array if it has a set position and doesn't conflict with another key
			// Otherwise, hold it for later
			if ( $term->position && ! array_key_exists( $term->position, $ordered_terms ) ) {
				$ordered_terms[ (int) $term->position ] = $term;
			} else {
				$hold_to_end[] = $term;
			}
		}
		// Sort the items numerically by key
		ksort( $ordered_terms, SORT_NUMERIC );
		// Append all of the terms that didn't have an existing position
		foreach ( $hold_to_end as $unpositioned_term ) {
			$ordered_terms[] = $unpositioned_term;
		}

		$ordered_terms = array_values( $ordered_terms );

		// Set the internal object cache
		$this->editorial_metadata_terms_cache = $ordered_terms;

		return $ordered_terms;
	}

	/**
	 * Returns a term for single metadata field
	 *
	 * @param int|string $field The slug or ID for the metadata field term to return
	 * @return object $term Term's object representation
	 */
	public function get_editorial_metadata_term_by( $field, $value ) {
		if ( ! in_array( $field, [ 'id', 'slug', 'name' ] ) ) {
			return false;
		}

		if ( 'id' === $field ) {
			$field = 'term_id';
		}

		$terms = $this->get_editorial_metadata_terms();
		$term = wp_filter_object_list( $terms, [ $field => $value ] );

		if ( ! empty( $term ) ) {
			return array_shift( $term );
		} else {
			return false;
		}
	}

	/**
	 * Insert a new editorial metadata term
	 * @todo Handle conflicts with existing terms at that position (if relevant)
	 */
	public function insert_editorial_metadata_term( $args ) {
		// Term is always added to the end of the list
		$default_position = count( $this->get_editorial_metadata_terms() ) + 1;

		$defaults = [
			'position' => $default_position,
			'name' => '',
			'slug' => '',
			'description' => '',
			'type' => '',
		];
		$args = array_merge( $defaults, $args );
		$term_name = $args['name'];
		unset( $args['name'] );

		// We're encoding metadata that isn't supported by default in the term's description field
		$args_to_encode = [
			'description' => $args['description'],
			'position' => $args['position'],
			'type' => $args['type'],
		];
		$encoded_description = $this->get_encoded_description( $args_to_encode );
		$args['description'] = $encoded_description;

		unset( $args['position'] );
		unset( $args['type'] );

		$inserted_term = wp_insert_term( $term_name, self::METADATA_TAXONOMY, $args );

		// Reset the internal object cache
		$this->editorial_metadata_terms_cache = [];

		// Populate the inserted term with the new values, or else only the term_taxonomy_id and term_id are returned.
		if ( is_wp_error( $inserted_term ) ) {
			return $inserted_term;
		} else {
			$inserted_term = $this->get_editorial_metadata_term_by( 'id', $inserted_term['term_id'] );
		}

		return $inserted_term;
	}

	/**
	 * Update an existing editorial metadata term if the term_id exists
	 *
	 * @param int $term_id The term's unique ID
	 * @param array $args Any values that need to be updated for the term
	 * @return object|WP_Error $updated_term The updated term or a WP_Error object if something disastrous happened
	*/
	public function update_editorial_metadata_term( $term_id, $args ) {
		$old_term = $this->get_editorial_metadata_term_by( 'id', $term_id );
		if ( ! $old_term || is_wp_error( $old_term ) ) {
			return new WP_Error( 'invalid', __( "Editorial metadata term doesn't exist.", 'vip-workflow' ) );
		}

		// Reset the internal object cache
		$this->editorial_metadata_terms_cache = [];

		$new_args = [];

		$old_args = array(
			'position' => $old_term->position,
			'name' => $old_term->name,
			'slug' => $old_term->slug,
			'description' => $old_term->description,
			'type' => $old_term->type,
		);

		$new_args = array_merge( $old_args, $args );

		// We're encoding metadata that isn't supported by default in the term's description field
		$args_to_encode = array(
			'description' => $new_args['description'],
			'position' => $new_args['position'],
			'type' => $new_args['type'],
		);
		$encoded_description = $this->get_encoded_description( $args_to_encode );
		$new_args['description'] = $encoded_description;

		unset( $new_args['position'] );
		unset( $new_args['type'] );

		$updated_term = wp_update_term( $term_id, self::METADATA_TAXONOMY, $new_args );

		// Reset the internal object cache
		$this->editorial_metadata_terms_cache = [];

		// Populate the updated term with the new values, or else only the term_taxonomy_id and term_id are returned.
		if ( is_wp_error( $updated_term ) ) {
			return $updated_term;
		} else {
			$updated_term = $this->get_editorial_metadata_term_by( 'id', $term_id );
		}

		return $updated_term;
	}

	/**
	 * Delete an existing editorial metadata term
	 *
	 * @param int $term_id The term we want deleted
	 * @return bool $result Whether or not the term was deleted
	 */
	public function delete_editorial_metadata_term( $term_id ) {
		$result = wp_delete_term( $term_id, self::METADATA_TAXONOMY );

		if ( ! $result || is_wp_error( $result ) ) {
			return new WP_Error( 'invalid', __( 'Unable to delete editorial metadata term.', 'vip-workflow' ) );
		}

		// Reset the internal object cache
		$this->editorial_metadata_terms_cache = array();

		return $result;
	}

	public function print_configure_view() {
		include_once __DIR__ . '/views/manage-editorial-metadata.php';
	}
}
