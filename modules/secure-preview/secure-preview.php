<?php
/**
 * The secure preview module, for sharing pre-published content.
 *
 * @package vip-bundle-decoupled
 */

namespace VIPWorkflow\Modules;

require_once __DIR__ . '/rest/secure-preview.php';

use VIPWorkflow\Modules\SecurePreview\SecurePreviewEndpoint;
use VIPWorkflow\VIP_Workflow;
use VIPWorkflow\Common\PHP\Module;

class Secure_Preview extends Module {
	public $module;

	/**
	 * Register the module with VIP Workflow but don't do anything else
	 */
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
		add_action( 'enqueue_block_editor_assets', [ $this, 'load_block_editor_scripts' ] );

		// Load block editor CSS
		add_action( 'enqueue_block_editor_assets', [ $this, 'load_block_editor_styles' ] );
	}

	public function load_block_editor_scripts() {
		$asset_file = include VIP_WORKFLOW_ROOT . '/dist/modules/secure-preview/secure-preview.asset.php';
		wp_enqueue_script( 'vip-workflow-secure-preview-script', VIP_WORKFLOW_URL . 'dist/modules/secure-preview/secure-preview.js', $asset_file['dependencies'], $asset_file['version'], true );

		wp_localize_script( 'vip-workflow-secure-preview-script', 'VipWorkflowSecurePreview', [
			'secure_preview_url' => rest_url( 'vip-workflow/v1/secure-preview' ),
		] );
	}

	public function load_block_editor_styles() {
		$asset_file = include VIP_WORKFLOW_ROOT . '/dist/modules/secure-preview/secure-preview.asset.php';

		wp_enqueue_style( 'vip-workflow-secure-preview-styles', VIP_WORKFLOW_URL . 'dist/modules/secure-preview/secure-preview.css', [ 'wp-components' ], $asset_file['version'] );
	}
}
