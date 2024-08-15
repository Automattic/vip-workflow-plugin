<?php
/**
 * The secure preview module, for sharing pre-published content.
 *
 * @package vip-bundle-decoupled
 */

namespace VIPWorkflow\Modules;

require_once __DIR__ . '/token.php';
require_once __DIR__ . '/rest/secure-preview.php';

use VIPWorkflow\Modules\SecurePreview\SecurePreviewEndpoint;
use VIPWorkflow\VIP_Workflow;
use VIPWorkflow\Common\PHP\Module;
use VIPWorkflow\Modules\SecurePreview\Token;

class Secure_Preview extends Module {
	public $module;

	public function __construct() {
		// Register the module with VIP Workflow
		$this->module_url = $this->get_module_url( __FILE__ );

		$this->module = VIP_Workflow::instance()->register_module( 'secure_preview', [
			'title'      => __( 'Secure preview', 'vip-workflow' ),
			'module_url' => $this->module_url,
			'slug'       => 'secure_preview',
			'autoload'   => true,
		] );
	}

	/**
	 * Initialize secure preview module
	 */
	public function init() {
		SecurePreviewEndpoint::init();

		// Load block editor JS
		add_action( 'enqueue_block_editor_assets', [ $this, 'load_block_editor_scripts' ], 9 /* Load before custom status module */ );

		// Load block editor CSS
		add_action( 'enqueue_block_editor_assets', [ $this, 'load_block_editor_styles' ] );

		// Preview rendering
		add_filter( 'query_vars', [ $this, 'add_preview_query_vars' ] );
		add_action( 'posts_results', [ $this, 'allow_secure_preview_results' ], 10, 2 );
	}

	public function load_block_editor_scripts() {
		$asset_file = include VIP_WORKFLOW_ROOT . '/dist/modules/secure-preview/secure-preview.asset.php';
		wp_enqueue_script( 'vip-workflow-secure-preview-script', VIP_WORKFLOW_URL . 'dist/modules/secure-preview/secure-preview.js', $asset_file['dependencies'], $asset_file['version'], true );

		$generate_preview_url = '';
		$post_id              = get_the_ID();

		if ( $post_id ) {
			$generate_preview_url = SecurePreviewEndpoint::get_url( $post_id );
		}

		$custom_status_module = VIP_Workflow::instance()->custom_status;
		$custom_status_slugs  = wp_list_pluck( $custom_status_module->get_custom_statuses(), 'slug' );
		$custom_post_types    = $custom_status_module->get_post_types_for_module();

		wp_localize_script( 'vip-workflow-secure-preview-script', 'VW_SECURE_PREVIEW', [
			'custom_post_types'    => $custom_post_types,
			'custom_status_slugs'  => $custom_status_slugs,
			'expiration_options'   => $this->get_link_expiration_options(),
			'text_preview_error'   => __( 'There was an error generating a preview link:', 'vip-workflow' ),
			'url_generate_preview' => $generate_preview_url,
		] );
	}

	public function load_block_editor_styles() {
		$asset_file = include VIP_WORKFLOW_ROOT . '/dist/modules/secure-preview/secure-preview.asset.php';

		wp_enqueue_style( 'vip-workflow-secure-preview-styles', VIP_WORKFLOW_URL . 'dist/modules/secure-preview/secure-preview.css', [ 'wp-components' ], $asset_file['version'] );
	}

	public function add_preview_query_vars( $query_vars ) {
		$query_vars[] = 'vw-token';

		return $query_vars;
	}

	public function allow_secure_preview_results( $posts, &$query ) {
		$token = $query->query_vars['vw-token'] ?? false;

		// If there's no token, go back to result processing quickly
		if ( false === $token ) {
			return $posts;
		}

		// Only allow secure preview on individual post queries
		$is_preview = $query->is_preview() && 1 === count( $posts );

		if ( ! $is_preview ) {
			return $posts;
		}

		if ( Token::validate_token( $token, $posts[0]->ID ) ) {
			// Temporarily set post_status to 'publish' to stop WP_Query->get_posts() from clearing out
			// unpublished posts before render
			$posts[0]->post_status = 'publish';
		}

		return $posts;
	}

	/**
	 * Get the expiration options available in the secure preview modal dropdown.
	 */
	public function get_link_expiration_options() {
		/**
		 * Filter the expiration options available in the secure preview modal dropdown.
		 *
		 * @param array $expiration_options Array of expiration options. Each option uses keys:
		 *     'label': The visible label for the option, e.g. "1 hour"
		 *     'value': The value to be sent to the API, e.g. "1h"
		 *     'second_count': The number of seconds the this expiration should be valid for, e.g. 3600
		 *     'default': Optional. Whether this option should be selected by default.
		 */
		return apply_filters( 'vw_secure_preview_expiration_options', [
			[
				'label'        => __( '1 hour', 'vip-workflow' ),
				'value'        => '1h',
				'second_count' => HOUR_IN_SECONDS,
			],
			[
				'label'        => __( '8 hours', 'vip-workflow' ),
				'value'        => '8h',
				'second_count' => 8 * HOUR_IN_SECONDS,
				'default'      => true,
			],
			[
				'label'        => __( '1 day', 'vip-workflow' ),
				'value'        => '1d',
				'second_count' => DAY_IN_SECONDS,
			],
		]);
	}
}
