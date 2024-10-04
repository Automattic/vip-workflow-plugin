<?php
/**
 * The preview module for sharing pre-published content.
 *
 * @package vip-workflow
 */

namespace VIPWorkflow\Modules;

require_once __DIR__ . '/token.php';
require_once __DIR__ . '/rest/preview-endpoint.php';

use VIPWorkflow\Modules\CustomStatus;
use VIPWorkflow\Modules\Preview\PreviewEndpoint;
use VIPWorkflow\VIP_Workflow;
use VIPWorkflow\Modules\Preview\Token;
use WP_Query;

class Preview {
	/**
	 * Initialize preview module
	 */
	public static function init(): void {
		// Load block editor JS
		add_action( 'enqueue_block_editor_assets', [ __CLASS__, 'load_block_editor_scripts' ], 9 /* Load before custom status module */ );

		// Load block editor CSS
		add_action( 'enqueue_block_editor_assets', [ __CLASS__, 'load_block_editor_styles' ] );

		// Preview rendering
		add_filter( 'query_vars', [ __CLASS__, 'add_preview_query_vars' ] );
		add_action( 'posts_results', [ __CLASS__, 'allow_preview_result' ], 10, 2 );
	}

	// Block editor asset filters

	public static function load_block_editor_scripts(): void {
		$asset_file = include VIP_WORKFLOW_ROOT . '/dist/modules/preview/preview.asset.php';
		wp_enqueue_script( 'vip-workflow-preview-script', VIP_WORKFLOW_URL . 'dist/modules/preview/preview.js', $asset_file['dependencies'], $asset_file['version'], true );

		$generate_preview_url = '';
		$post_id              = get_the_ID();

		if ( $post_id ) {
			$generate_preview_url = PreviewEndpoint::get_url( $post_id );
		}
		$custom_status_slugs  = wp_list_pluck( CustomStatus::get_custom_statuses(), 'slug' );
		$custom_post_types    = VIP_Workflow::instance()->get_supported_post_types();

		wp_localize_script( 'vip-workflow-preview-script', 'VW_PREVIEW', [
			'custom_post_types'    => $custom_post_types,
			'custom_status_slugs'  => $custom_status_slugs,
			'expiration_options'   => self::get_link_expiration_options(),
			'text_preview_error'   => __( 'There was an error generating a preview link:', 'vip-workflow' ),
			'url_generate_preview' => $generate_preview_url,
		] );
	}

	public static function load_block_editor_styles(): void {
		$asset_file = include VIP_WORKFLOW_ROOT . '/dist/modules/preview/preview.asset.php';

		wp_enqueue_style( 'vip-workflow-preview-styles', VIP_WORKFLOW_URL . 'dist/modules/preview/preview.css', [ 'wp-components' ], $asset_file['version'] );
	}

	// Preview rendering WP_Query filters

	public static function add_preview_query_vars( array $query_vars ) {
		// Allow 'vw-token' GET parameter to be detected in WP_Query parameters
		$query_vars[] = 'vw-token';

		return $query_vars;
	}

	public static function allow_preview_result( array $posts, WP_Query $query ) {
		$token = $query->query_vars['vw-token'] ?? false;

		// If there's no token, go back to result processing quickly
		if ( false === $token ) {
			return $posts;
		}

		// Only allow preview on individual post queries
		$is_preview = $query->is_preview() && 1 === count( $posts );

		if ( ! $is_preview ) {
			return $posts;
		}

		if ( Token::validate_token( $token, $posts[0]->ID ) ) {
			// Temporarily set post_status to 'publish' to stop WP_Query->get_posts() from clearing out
			// unpublished posts before render
			$saved_post_id         = $posts[0]->ID;
			$saved_post_status     = $posts[0]->post_status;
			$posts[0]->post_status = 'publish';

			// Change headers and ensure this page isn't cached
			nocache_headers();

			$undo_filter_function = function ( $posts ) use ( $saved_post_id, $saved_post_status, &$undo_filter_function ) {
				if ( 1 === count( $posts ) && $posts[0]->ID === $saved_post_id ) {
					// If this is the same post, reset the status and unregister this callback
					$posts[0]->post_status = $saved_post_status;
					remove_filter( 'the_posts', $undo_filter_function, /* priority */ 5 );
				}

				return $posts;
			};

			// 'the_posts' filter is called shortly after 'posts_results' in WP_Query::get_posts().
			// Call $undo_filter_function to reset the post_status to avoid possible side effects from other parts of
			// WordPress treating the post as published.
			add_filter( 'the_posts', $undo_filter_function, /* priority */ 5 );
		} elseif ( 'publish' === $posts[0]->post_status ) {
			// If this post is published, redirect to the public URL
			wp_safe_redirect( get_post_permalink( $posts[0]->ID ) );
			exit;
		} elseif ( current_user_can( 'edit_post', $posts[0]->ID ) ) {
			// If the user is already able to view this preview and the token is invalid, redirect to the preview URL.
			// This ensures that an expired token doesn't appear to work for logged-in users due to permissions.
			wp_safe_redirect( remove_query_arg( 'vw-token' ) );
			exit;
		}

		return $posts;
	}

	// Public API

	/**
	 * Returns the valid set of expiration options for preview links. See the
	 * vw_preview_expiration_options filter for customization.
	 *
	 * @access public
	 *
	 * @return array
	 */
	public static function get_link_expiration_options(): array {
		/**
		 * Filter the expiration options available in the preview modal dropdown.
		 *
		 * @param array $expiration_options Array of expiration options. Each option uses keys:
		 *     'label': The visible label for the option, e.g. "1 hour"
		 *     'value': The value to be sent to the API, e.g. "1h". This value should be unique.
		 *     'second_count': The number of seconds the this expiration should be valid for, e.g. 3600
		 *     'default': Optional. Whether this option should be selected by default.
		 */
		return apply_filters( 'vw_preview_expiration_options', [
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

Preview::init();
