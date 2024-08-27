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

use WP_Error;

class Editorial_Metadata extends Module {

	// ToDo: Review the default metadata types we provide OOB
	const SUPPORTED_METADATA_TYPES = [
		'checkbox',
		'text',
	];
	const METADATA_TAXONOMY = 'vw_editorial_meta';
	const METADATA_POSTMETA_KEY = 'vw_editorial_meta';

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

		EditEditorialMetadata::init();

		// Load CSS and JS resources that we probably need in the admin page
		add_action( 'admin_enqueue_scripts', [ $this, 'action_admin_enqueue_scripts' ] );

		// Load block editor JS
		add_action( 'enqueue_block_editor_assets', [ $this, 'load_scripts_for_block_editor' ], 9 /* Load before custom status module */ );

		// Load block editor CSS
		add_action( 'enqueue_block_editor_assets', [ $this, 'load_styles_for_block_editor' ] );

		add_action( 'init', [ $this, 'register_editorial_metadata_terms_as_post_meta' ] );
	}

	/**
	 * Register the post meta for each editorial metadata term
	 */
	public function register_editorial_metadata_terms_as_post_meta() {
		$editorial_metadata_terms = $this->get_editorial_metadata_terms();

		foreach ( $editorial_metadata_terms as $term ) {
			$post_meta_key = $this->get_postmeta_key( $term );
			$post_meta_args = $this->get_postmeta_args( $term );

			foreach ( $this->get_supported_post_types() as $post_type ) {
				register_post_meta( $post_type, $post_meta_key, $post_meta_args );
			}
		}
	}

	/**
	 * Register the post metadata taxonomy
	 */
	public function register_taxonomy() {
		// We need to make sure taxonomy is registered for all of the post types that support it
		$supported_post_types = $this->get_supported_post_types();

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
				'type' => 'text',
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

	public function load_scripts_for_block_editor() {
		$asset_file = include VIP_WORKFLOW_ROOT . '/dist/modules/editorial-metadata/editorial-metadata-block.asset.php';
		wp_enqueue_script( 'vip-workflow-block-editorial-metadata-script', VIP_WORKFLOW_URL . 'dist/modules/editorial-metadata/editorial-metadata-block.js', $asset_file['dependencies'], $asset_file['version'], true );

		$editorial_metadata_terms = $this->get_editorial_metadata_terms();
		wp_localize_script( 'vip-workflow-block-editorial-metadata-script', 'VipWorkflowEditorialMetadatas', $editorial_metadata_terms );
	}

	public function load_styles_for_block_editor() {
		$asset_file = include VIP_WORKFLOW_ROOT . '/dist/modules/editorial-metadata/editorial-metadata-block.asset.php';

		wp_enqueue_style( 'vip-workflow-manager-styles', VIP_WORKFLOW_URL . 'dist/modules/editorial-metadata/editorial-metadata-block.css', [ 'wp-components' ], $asset_file['version'] );
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

			// Set the post meta key for the term, this is not set when the term is first created due to a lack of term_id.
			if ( ! isset( $term->meta_key ) ) {
				$term->meta_key = $this->get_postmeta_key( $term );
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
	 * @return WP_Term|false $term Term's object representation
	 */
	public function get_editorial_metadata_term_by( $field, $value ) {
		// We only support id, slug and name for lookup.
		if ( ! in_array( $field, [ 'id', 'slug', 'name' ] ) ) {
			return false;
		}

		if ( 'id' === $field ) {
			$field = 'term_id';
		}

		// ToDo: This is inefficient as we are fetching all the terms, and then finding the one that matches.
		$terms = $this->get_editorial_metadata_terms();
		$term = wp_filter_object_list( $terms, [ $field => $value ] );

		$term = array_shift( $term );

		return null !== $term ? $term : false;
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
			// Update the term with the meta_key, as we use the term_id to generate it
			$this->update_editorial_metadata_term( $inserted_term['term_id'] );
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
	public function update_editorial_metadata_term( $term_id, $args = [] ) {
		$old_term = $this->get_editorial_metadata_term_by( 'id', $term_id );
		if ( ! $old_term ) {
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
			'meta_key' => isset( $old_term->meta_key ) ? $old_term->meta_key : $this->get_postmeta_key( $old_term ),
		);

		$new_args = array_merge( $old_args, $args );

		// We're encoding metadata that isn't supported by default in the term's description field
		$args_to_encode = array(
			'description' => $new_args['description'],
			'position' => $new_args['position'],
			'type' => $new_args['type'],
			'meta_key' => $new_args['meta_key'],
		);
		$encoded_description = $this->get_encoded_description( $args_to_encode );
		$new_args['description'] = $encoded_description;

		unset( $new_args['position'] );
		unset( $new_args['type'] );
		unset( $new_args['meta_key'] );

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
		$term = $this->get_editorial_metadata_term_by( 'id', $term_id );
		$post_meta_key = $this->get_postmeta_key( $term_id, $term->type );
		delete_post_meta_by_key( $post_meta_key );

		$result = wp_delete_term( $term_id, self::METADATA_TAXONOMY );

		if ( ! $result ) {
			return new WP_Error( 'invalid', __( 'Unable to delete editorial metadata term.', 'vip-workflow' ) );
		}

		// Reset the internal object cache
		$this->editorial_metadata_terms_cache = array();

		return $result;
	}

	 /** Generate the args for registering post meta
	 *
	 * @param WP_Term $term The term object
	 * @return array $args Post meta args
	 */
	public function get_postmeta_args( $term ) {
		$arg_type = '';
		switch ( $term->type ) {
			case 'checkbox':
				$arg_type = 'boolean';
				break;
			case 'text':
				$arg_type = 'string';
				break;
		}
		$args = [
			'type' => $arg_type,
			'description' => $term->description,
			'single' => true,
			'show_in_rest' => true,
			'sanitize_callback' => function ( $value ) use ( $arg_type ) {
				switch ( $arg_type ) {
					case 'boolean':
						return boolval( $value );
					case 'string':
						return stripslashes( wp_filter_nohtml_kses( trim( $value ) ) );
				}
			},
			'auth_callback' => function () {
				// ToDo: Review the permissions required to edit metadata
				return current_user_can( 'edit_posts' ) || current_user_can( 'edit_pages' );
			},
		];
		return $args;
	}

	/**
	 * Generate a unique key based on the term id and type
	 *
	 * Key is in the form of _vw_editorial_meta_{type}_{term_id}
	 *
	 * @param WP_Term $term The term object
	 * @return string $postmeta_key Unique key
	 */
	public function get_postmeta_key( $term ) {
		$key = self::METADATA_POSTMETA_KEY;
		$prefix = "{$key}_{$term->type}";
		$postmeta_key = "{$prefix}_" . $term->term_id;
		return $postmeta_key;
	}

	public function print_configure_view() {
		include_once __DIR__ . '/views/manage-editorial-metadata.php';
	}
}
