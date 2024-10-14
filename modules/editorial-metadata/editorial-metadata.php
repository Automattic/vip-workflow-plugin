<?php

/**
 * Class EditorialMetadata
 * Editorial Metadata for VIP Workflow
 */
namespace VIPWorkflow\Modules;

require_once __DIR__ . '/rest/editorial-metadata-endpoint.php';

use VIPWorkflow\Modules\EditorialMetadata\REST\EditorialMetadataEndpoint;
use VIPWorkflow\Modules\Shared\PHP\HelperUtilities;
use VIPWorkflow\Modules\Shared\PHP\InstallUtilities;
use WP_Error;
use WP_Term;

class EditorialMetadata {

	const SUPPORTED_METADATA_TYPES = [
		'checkbox',
		'text',
		'date',
	];
	const METADATA_TAXONOMY        = 'vw_editorial_meta';
	const SETTINGS_SLUG            = 'vw-editorial-metadata';
	const METADATA_TYPE_KEY        = 'type';
	const METADATA_POSTMETA_KEY    = 'postmeta_key';

	public static function init(): void {
		// Register the taxonomy we use for Editorial Metadata with WordPress core, and ensure its registered before custom status
		add_action( 'init', [ __CLASS__, 'register_editorial_metadata_taxonomy' ] );

		// Register the post meta for each editorial metadata term
		add_action( 'init', [ __CLASS__, 'register_editorial_metadata_terms_as_post_meta' ] );

		// Setup editorial metadata on first install
		add_action( 'init', [ __CLASS__, 'setup_install' ] );

		// Add menu sidebar item
		add_action( 'admin_menu', [ __CLASS__, 'add_admin_menu' ] );

		// Load CSS and JS resources for the admin page
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'action_admin_enqueue_scripts' ] );

		// Load block editor JS
		add_action( 'enqueue_block_editor_assets', [ __CLASS__, 'load_scripts_for_block_editor' ] );

		// Load block editor CSS
		add_action( 'enqueue_block_editor_assets', [ __CLASS__, 'load_styles_for_block_editor' ] );
	}

	/**
	 * Register the post meta for each editorial metadata term
	 *
	 * @access private
	 */
	public static function register_editorial_metadata_terms_as_post_meta(): void {
		$editorial_metadata_terms = self::get_editorial_metadata_terms();

		foreach ( $editorial_metadata_terms as $term ) {
			$post_meta_key  = $term->meta[ self::METADATA_POSTMETA_KEY ];
			$post_meta_args = self::get_postmeta_args( $term );

			foreach ( HelperUtilities::get_supported_post_types() as $post_type ) {
				register_post_meta( $post_type, $post_meta_key, $post_meta_args );
			}
		}
	}

	/**
	 * Register the post metadata taxonomy
	 *
	 * @access private
	 */
	public static function register_editorial_metadata_taxonomy(): void {
		// We need to make sure taxonomy is registered for all of the post types that support it
		$supported_post_types = HelperUtilities::get_supported_post_types();

		register_taxonomy( self::METADATA_TAXONOMY, $supported_post_types,
			[
				'public'  => false,
				'labels'  => [
					'name'          => _x( 'Editorial Metadata', 'taxonomy general name', 'vip-workflow' ),
					'singular_name' => _x( 'Editorial Metadata', 'taxonomy singular name', 'vip-workflow' ),
					'search_items'  => __( 'Search Editorial Metadata', 'vip-workflow' ),
					'popular_items' => __( 'Popular Editorial Metadata', 'vip-workflow' ),
					'all_items'     => __( 'All Editorial Metadata', 'vip-workflow' ),
					'edit_item'     => __( 'Edit Editorial Metadata', 'vip-workflow' ),
					'update_item'   => __( 'Update Editorial Metadata', 'vip-workflow' ),
					'add_new_item'  => __( 'Add New Editorial Metadata', 'vip-workflow' ),
					'new_item_name' => __( 'New Editorial Metadata', 'vip-workflow' ),
				],
				'rewrite' => false,
			]
		);
	}

	/**
	 * Load default editorial metadata the first time the module is loaded
	 *
	 * @access private
	 */
	public static function setup_install(): void {
		InstallUtilities::install_if_first_run( self::SETTINGS_SLUG, function () {
			$default_terms = [
				[
					'name'        => __( 'Embargo Date', 'vip-workflow' ),
					'slug'        => 'embargo-date',
					'type'        => 'date',
					'description' => __( 'The date a post can be published.', 'vip-workflow' ),
				],
				[
					'name'        => __( 'Expiry Date', 'vip-workflow' ),
					'slug'        => 'expiry-date',
					'type'        => 'date',
					'description' => __( 'The date the content of a post would be marked as expired.', 'vip-workflow' ),
				],
				[
					'name'        => __( 'Legal Review', 'vip-workflow' ),
					'slug'        => 'legal-review',
					'type'        => 'checkbox',
					'description' => __( 'This is to be checked once legal has reviewed this post.', 'vip-workflow' ),
				],
			];

			// Load the metadata fields if the slugs don't conflict
			foreach ( $default_terms as $term ) {
				if ( ! term_exists( $term['slug'], self::METADATA_TAXONOMY ) ) {
					self::insert_editorial_metadata_term( $term );
				}
			}
		});
	}

	/**
	 * Register admin sidebar menu
	 *
	 * @access private
	 */
	public static function add_admin_menu(): void {
		$menu_title = __( 'Editorial Metadata', 'vip-workflow' );

		add_submenu_page( CustomStatus::SETTINGS_SLUG, $menu_title, $menu_title, 'manage_options', self::SETTINGS_SLUG, [ __CLASS__, 'render_settings_view' ] );
	}

	/**
	 * Print settings page for the Editorial Metadata module
	 *
	 * @access private
	 */
	public static function render_settings_view(): void {
		include_once __DIR__ . '/views/manage-editorial-metadata.php';
	}

	/**
	 * Enqueue resources that we need in the admin settings page
	 *
	 * @access private
	 */
	public static function action_admin_enqueue_scripts(): void {
		// Load Javascript we need to use on the configuration views
		if ( HelperUtilities::is_settings_view_loaded( self::SETTINGS_SLUG ) ) {
			$asset_file = include VIP_WORKFLOW_ROOT . '/dist/modules/editorial-metadata/editorial-metadata-configure.asset.php';
			wp_enqueue_script( 'vip-workflow-editorial-metadata-configure', VIP_WORKFLOW_URL . 'dist/modules/editorial-metadata/editorial-metadata-configure.js', $asset_file['dependencies'], $asset_file['version'], true );
			wp_enqueue_style( 'vip-workflow-editorial-metadata-styles', VIP_WORKFLOW_URL . 'dist/modules/editorial-metadata/editorial-metadata-configure.css', [ 'wp-components' ], $asset_file['version'] );

			wp_localize_script( 'vip-workflow-editorial-metadata-configure', 'VW_EDITORIAL_METADATA_CONFIGURE', [
				'supported_metadata_types'    => self::SUPPORTED_METADATA_TYPES,
				'editorial_metadata_terms'    => self::get_editorial_metadata_terms(),
				'url_edit_editorial_metadata' => EditorialMetadataEndpoint::get_url(),
			] );
		}
	}

	/**
	 * Enqueue resources that we need in the admin settings page
	 *
	 * @access private
	 */
	public static function load_scripts_for_block_editor(): void {
		$asset_file   = include VIP_WORKFLOW_ROOT . '/dist/modules/editorial-metadata/editorial-metadata-block.asset.php';
		$dependencies = array_merge( $asset_file['dependencies'], [ 'vip-workflow-block-custom-status-script' ] );

		wp_enqueue_script( 'vip-workflow-block-editorial-metadata-script', VIP_WORKFLOW_URL . 'dist/modules/editorial-metadata/editorial-metadata-block.js', $dependencies, $asset_file['version'], true );

		wp_localize_script( 'vip-workflow-block-editorial-metadata-script', 'VW_EDITORIAL_METADATA', [
			'editorial_metadata_terms' => self::get_editorial_metadata_terms(),
		] );
	}

	/**
	 * Enqueue resources that we need in the block editor
	 *
	 * @access private
	 */
	public static function load_styles_for_block_editor(): void {
		$asset_file = include VIP_WORKFLOW_ROOT . '/dist/modules/editorial-metadata/editorial-metadata-block.asset.php';

		wp_enqueue_style( 'vip-workflow-editorial-metadata-styles', VIP_WORKFLOW_URL . 'dist/modules/editorial-metadata/editorial-metadata-block.css', [ 'wp-components' ], $asset_file['version'] );
	}

	// Public API

	/**
	 * Get all of the editorial metadata terms as objects
	 *
	 * @param array $filter_args Filter to specific arguments
	 * @return array $ordered_terms The terms as they should be ordered
	 */
	public static function get_editorial_metadata_terms(): array {

		$terms = get_terms( [
			'taxonomy'   => self::METADATA_TAXONOMY,
			'orderby'    => 'name',
			'hide_empty' => false,
		]);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			$terms = [];
		}

		$terms = array_map( [ __CLASS__, 'add_metadata_to_term' ], $terms );

		return $terms;
	}

	/**
	 * Returns a term for single metadata field
	 *
	 * @param string $field The field to search by
	 * @param int|string $value The value to search for
	 * @return WP_Term|false $term Term's object representation
	 */
	public static function get_editorial_metadata_term_by( string $field, int|string $value ): WP_Term|false {
		// We only support id, slug and name for lookup.
		if ( ! in_array( $field, [ 'id', 'slug', 'name' ] ) ) {
			return false;
		}

		$term = false;

		if ( 'id' === $field ) {
			$term = get_term( $value, self::METADATA_TAXONOMY );
		} else {
			$term = get_term_by( $field, $value, self::METADATA_TAXONOMY );
		}

		if ( is_wp_error( $term ) || ! $term ) {
			$term = false;
		} else {
			$term = self::add_metadata_to_term( $term );
		}

		return $term;
	}

	/**
	 * Add all the metadata fields to a term
	 *
	 * @param WP_Term $term The term to add metadata to
	 * @return WP_Term $term The term with metadata added
	 */
	public static function add_metadata_to_term( WP_Term $term ): WP_Term {
		if ( ! isset( $term->taxonomy ) || self::METADATA_TAXONOMY !== $term->taxonomy ) {
			return $term;
		}

		// if metadata is already set, don't overwrite it
		if ( isset( $term->meta ) && isset( $term->meta[ self::METADATA_TYPE_KEY ] ) && isset( $term->meta[ self::METADATA_POSTMETA_KEY ] ) ) {
			return $term;
		}

		$term_meta                                = [];
		$term_meta[ self::METADATA_TYPE_KEY ]     = get_term_meta( $term->term_id, self::METADATA_TYPE_KEY, true );
		$term_meta[ self::METADATA_POSTMETA_KEY ] = get_term_meta( $term->term_id, self::METADATA_POSTMETA_KEY, true );

		if ( '' === $term_meta[ self::METADATA_TYPE_KEY ] || '' === $term_meta[ self::METADATA_POSTMETA_KEY ] ) {
			return $term;
		}

		$term->meta = $term_meta;

		return $term;
	}

	/**
	 * Insert a new editorial metadata term
	 *
	 * @param array $args The arguments for the new term
	 * @return WP_Term|WP_Error $term The new term or a WP_Error object if something disastrous happened
	 */
	public static function insert_editorial_metadata_term( array $args ): WP_Term|WP_Error {
		$term_to_save = [
			'slug'        => $args['slug'] ?? sanitize_title( $args['name'] ),
			'description' => $args['description'] ?? '',
		];
		$term_name    = $args['name'];

		$inserted_term = wp_insert_term( $term_name, self::METADATA_TAXONOMY, $term_to_save );

		if ( is_wp_error( $inserted_term ) ) {
			return $inserted_term;
		}

		$term_id = $inserted_term['term_id'];

		$metadata_type         = $args['type'];
		$metadata_postmeta_key = self::get_postmeta_key( $metadata_type, $term_id );

		$type_meta_result = add_term_meta( $term_id, self::METADATA_TYPE_KEY, $metadata_type );
		if ( is_wp_error( $type_meta_result ) ) {
			return $type_meta_result;
		} elseif ( ! $type_meta_result ) {
			// If we can't save the type, we should delete the term
			wp_delete_term( $term_id, self::METADATA_TAXONOMY );
			return new WP_Error( 'invalid', __( 'Unable to create editorial metadata.', 'vip-workflow' ) );
		}

		$postmeta_meta_result = add_term_meta( $term_id, self::METADATA_POSTMETA_KEY, $metadata_postmeta_key );
		if ( is_wp_error( $postmeta_meta_result ) ) {
			return $postmeta_meta_result;
		} elseif ( ! $postmeta_meta_result ) {
			// If we can't save the postmeta key, we should delete the term
			delete_term_meta( $term_id, self::METADATA_TYPE_KEY );
			wp_delete_term( $term_id, self::METADATA_TAXONOMY );
			return new WP_Error( 'invalid', __( 'Unable to create editorial metadata.', 'vip-workflow' ) );
		}

		$term_result = self::get_editorial_metadata_term_by( 'id', $term_id );

		if ( ! is_wp_error( $term_result ) ) {
			/**
			 * Fires after a new editorial metadata field is added to the database.
			 *
			 * @param WP_Term $term The editorial metadata term object.
			 */
			do_action( 'vw_add_editorial_metadata_field', $term_result );
		}

		return $term_result;
	}

	/**
	 * Update an existing editorial metadata term if the term_id exists
	 *
	 * @param int $term_id The term's unique ID
	 * @param array $args Any values that need to be updated for the term
	 * @return WP_Term|WP_Error $updated_term The updated WP_Term or a WP_Error object if something disastrous happened
	*/
	public static function update_editorial_metadata_term( int $term_id, array $args = [] ): WP_Term|WP_Error {
		$old_term = self::get_editorial_metadata_term_by( 'id', $term_id );
		if ( is_wp_error( $old_term ) ) {
			return $old_term;
		} elseif ( ! $old_term ) {
			return new WP_Error( 'invalid', __( "Editorial metadata doesn't exist.", 'vip-workflow' ), array( 'status' => 400 ) );
		}

		$term_fields_to_update = [
			'name'        => isset( $args['name'] ) ? $args['name'] : $old_term->name,
			'slug'        => isset( $args['slug'] ) ? $args['slug'] : $old_term->slug,
			'description' => isset( $args['description'] ) ? $args['description'] : $old_term->description,
		];

		$updated_term = wp_update_term( $term_id, self::METADATA_TAXONOMY, $term_fields_to_update );

		if ( is_wp_error( $updated_term ) ) {
			return $updated_term;
		}

		// No need to update the metadata as type can't be changed, and so neither can the postmeta key.

		$term_result = self::get_editorial_metadata_term_by( 'id', $term_id );

		return $term_result;
	}

	/**
	 * Delete an existing editorial metadata term
	 *
	 * @param int $term_id The term we want deleted
	 * @return bool $result Whether or not the term was deleted
	 */
	public static function delete_editorial_metadata_term( int $term_id ): bool|WP_Error {
		$term = self::get_editorial_metadata_term_by( 'id', $term_id );
		if ( is_wp_error( $term ) ) {
			return $term;
		} elseif ( ! $term ) {
			return new WP_Error( 'invalid', __( "Editorial metadata term doesn't exist.", 'vip-workflow' ), array( 'status' => 400 ) );
		}

		// Delete the post meta for the term
		$post_meta_key = self::get_postmeta_key( $term->meta[ self::METADATA_TYPE_KEY ], $term_id );
		delete_post_meta_by_key( $post_meta_key );

		delete_term_meta( $term_id, self::METADATA_TYPE_KEY );

		delete_term_meta( $term_id, self::METADATA_POSTMETA_KEY );

		$result = wp_delete_term( $term_id, self::METADATA_TAXONOMY );
		if ( ! $result ) {
			return new WP_Error( 'invalid', __( 'Unable to delete editorial metadata term.', 'vip-workflow' ) );
		}

		do_action( 'vw_editorial_metadata_term_deleted', $term_id );

		return $result;
	}

	/** Generate the args for registering post meta
	 *
	 * @param WP_Term $term The term object
	 * @return array $args Post meta args
	 */
	public static function get_postmeta_args( WP_Term $term ): array {
		$arg_type = '';
		switch ( $term->meta[ self::METADATA_TYPE_KEY ] ) {
			case 'checkbox':
				$arg_type = 'boolean';
				break;
			case 'date':
			case 'text':
				$arg_type = 'string';
				break;
		}

		$args = [
			'type'              => $arg_type,
			'description'       => $term->description,
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => function ( $value ) use ( $arg_type ) {
				switch ( $arg_type ) {
					case 'boolean':
						return boolval( $value );
					case 'string':
						return stripslashes( wp_filter_nohtml_kses( trim( $value ) ) );
				}
			},
		];

		return $args;
	}

	/**
	 * Generate a unique key based on the term id and type
	 *
	 * Key is in the form of vw_editorial_meta_{type}_{term_id}
	 *
	 * @param string $term_type The type of term
	 * @param int $term_id The term's unique ID
	 * @return string $postmeta_key Unique key
	 */
	public static function get_postmeta_key( string $term_type, int $term_id ): string {
		$key          = self::METADATA_TAXONOMY;
		$prefix       = "{$key}_{$term_type}";
		$postmeta_key = "{$prefix}_" . $term_id;
		return $postmeta_key;
	}
}

EditorialMetadata::init();
