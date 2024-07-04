<?php
/**
 * Plugin Name: WordPress VIP Workflow
 * Plugin URI: https://github.com/Automattic/vip-workflow-plugin
 * Description: Adding additional editorial workflow capabilities to WordPress.
 * Author: WordPress VIP
 * Text Domain: vip-workflow
 * Version: 0.0.1
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * License: GPL-3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package vip-workflow
 */

 namespace VIPWorkflow;

 use VIPWorkflow\Modules\VW_Module;
 use stdClass;

 /**
  * Print admin notice regarding having an old version of PHP.
  */
function vip_workflow_print_incompatibility_notice() {
	?>
	<div class="notice notice-error">
			<p><?php esc_html_e( 'VIP Workflow requires PHP 8.0+.', 'vip-workflow' ); ?></p>
		</div>
	<?php
}

// ToDo: Add a check for the WP version as well.
if ( version_compare( phpversion(), '8.0', '<' ) ) {
	add_action( 'admin_notices', 'vip_workflow_print_incompatibility_notice' );
	return;
}

// Define contants
define( 'VIP_WORKFLOW_VERSION', '0.0.1' );
define( 'VIP_WORKFLOW_ROOT', __DIR__ );
define( 'VIP_WORKFLOW_URL', plugins_url( '/', __FILE__ ) );
define( 'VIP_WORKFLOW_SETTINGS_PAGE', add_query_arg( 'page', 'vw-settings', get_admin_url( null, 'admin.php' ) ) );

// ToDo: Let's revisit if we want to instantiate the class here or not. Thinking we should move it elsewhere and keep this class simple.

// Core class
#[\AllowDynamicProperties]
class VIP_Workflow {

	// Unique identified added as a prefix to all options
	public $options_group      = 'vip_workflow_';
	public $options_group_name = 'vip_workflow_options';

	/**
	 * @var VIP_Workflow The one true VIP_Workflow
	 */
	private static $instance;

	/**
	 * Active modules.
	 *
	 * @var \stdClass
	 */
	public $modules;

	/**
	 * Number of active modules.
	 *
	 * @var int
	 */
	public $modules_count;

	/**
	 * @var WPVIPW_Module
	 */
	public $helpers;

	/**
	 * Main VIP Workflow Instance
	 *
	 * Insures that only one instance of VIP Workflow exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @staticvar array $instance
	 * @uses VIP_Workflow::setup_globals() Setup the globals needed
	 * @uses VIP_Workflow::includes() Include the required files
	 * @uses VIP_Workflow::setup_actions() Setup the hooks and actions
	 * @see VIP_Workflow()
	 * @return The one true VIP_Workflow
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new VIP_Workflow();
			self::$instance->setup_globals();
			self::$instance->setup_actions();
			// ToDo: Take this away, along with any backwards compat code.
			// Backwards compat for when we promoted use of the $edit_flow global
			global $vip_workflow;
			$vip_workflow = self::$instance;
		}
		return self::$instance;
	}

	private function __construct() {
		/** Do nothing **/
	}

	private function setup_globals() {
		$this->modules       = new stdClass();
		$this->modules_count = 0;
	}

	/**
	 * Include the common resources to VIP Workflow and dynamically load the modules
	 */
	private function load_modules() {

		// We use the WP_List_Table API for some of the table gen
		if ( ! class_exists( 'WP_List_Table' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		}

		// VIP Workflow base module
		require_once VIP_WORKFLOW_ROOT . '/common/php/class-module.php';

		// Scan the modules directory and include any modules that exist there
		$module_dirs = scandir( VIP_WORKFLOW_ROOT . '/modules/' );
		$class_names = array();
		foreach ( $module_dirs as $module_dir ) {
			if ( file_exists( VIP_WORKFLOW_ROOT . "/modules/{$module_dir}/$module_dir.php" ) ) {
				include_once VIP_WORKFLOW_ROOT . "/modules/{$module_dir}/$module_dir.php";

				// Prepare the class name because it should be standardized
				$tmp        = explode( '-', $module_dir );
				$class_name = '';
				$slug_name  = '';
				foreach ( $tmp as $word ) {
					$class_name .= ucfirst( $word ) . '_';
					$slug_name  .= $word . '_';
				}
				$slug_name                 = rtrim( $slug_name, '_' );
				$class_names[ $slug_name ] = 'VW_' . rtrim( $class_name, '_' );
			}
		}

		// Instantiate VW_Module as $helpers for back compat and so we can
		// use it in this class
		$this->helpers = new VW_Module();

		// Other utils
		require_once VIP_WORKFLOW_ROOT . '/common/php/util.php';

		// Instantiate all of our classes onto the VIP Workflow object
		// but make sure they exist too
		foreach ( $class_names as $slug => $class_name ) {
			if ( class_exists( $class_name ) ) {
				$this->$slug = new $class_name();
			}
		}
	}

	/**
	 * Setup the default hooks and actions
	 *
	 * @since EditFlow 0.7.4
	 * @access private
	 * @uses add_action() To add various actions
	 */
	private function setup_actions() {
		add_action( 'init', array( $this, 'action_init' ) );
		add_action( 'init', array( $this, 'action_init_after' ), 1000 );

		add_action( 'admin_init', array( $this, 'action_admin_init' ) );
	}

	/**
	 * Inititalizes the Edit Flows!
	 * Loads options for each registered module and then initializes it if it's active
	 */
	public function action_init() {
		$this->load_modules();

		// Load all of the module options
		$this->load_module_options();

		// Load all of the modules that are enabled.
		// Modules won't have an options value if they aren't enabled
		foreach ( $this->modules as $mod_name => $mod_data ) {
			if ( isset( $mod_data->options->enabled ) && 'on' == $mod_data->options->enabled ) {
				$this->$mod_name->init();
			}
		}
	}

	/**
	 * Initialize the plugin for the admin
	 */
	public function action_admin_init() {
		// Upgrade if need be but don't run the upgrade if the plugin has never been used
		$previous_version = get_option( $this->options_group . 'version' );
		if ( $previous_version && version_compare( $previous_version, VIP_WORKFLOW_VERSION, '<' ) ) {
			foreach ( $this->modules as $mod_name => $mod_data ) {
				if ( method_exists( $this->$mod_name, 'upgrade' ) ) {
						$this->$mod_name->upgrade( $previous_version );
				}
			}
			update_option( $this->options_group . 'version', VIP_WORKFLOW_VERSION );
		} elseif ( ! $previous_version ) {
			update_option( $this->options_group . 'version', VIP_WORKFLOW_VERSION );
		}

		// For each module that's been loaded, auto-load data if it's never been run before
		foreach ( $this->modules as $mod_name => $mod_data ) {
			// If the module has never been loaded before, run the install method if there is one
			if ( ! isset( $mod_data->options->loaded_once ) || ! $mod_data->options->loaded_once ) {
				if ( method_exists( $this->$mod_name, 'install' ) ) {
					$this->$mod_name->install();
				}
				$this->update_module_option( $mod_name, 'loaded_once', true );
			}
		}

		$this->register_scripts_and_styles();
	}

	/**
	 * Register a new module
	 */
	public function register_module( $name, $args = array() ) {

		// A title and name is required for every module
		if ( ! isset( $args['title'], $name ) ) {
			return false;
		}

		$defaults = array(
			'title'                => '',
			'short_description'    => '',
			'extended_description' => '',
			'img_url'              => false,
			'slug'                 => '',
			'post_type_support'    => '',
			'default_options'      => array(),
			'options'              => false,
			'configure_page_cb'    => false,
			'configure_link_text'  => __( 'Configure', 'vip-workflow' ),
			// These messages are applied to modules and can be overridden if custom messages are needed
			'messages'             => array(
				'settings-updated'    => __( 'Settings updated.', 'vip-workflow' ),
				'form-error'          => __( 'Please correct your form errors below and try again.', 'vip-workflow' ),
				'nonce-failed'        => __( 'Cheatin&#8217; uh?', 'vip-workflow' ),
				'invalid-permissions' => __( 'You do not have necessary permissions to complete this action.', 'vip-workflow' ),
				'missing-post'        => __( 'Post does not exist', 'vip-workflow' ),
			),
			'autoload'             => false, // autoloading a module will remove the ability to enable or disable it
		);
		if ( isset( $args['messages'] ) ) {
			$args['messages'] = array_merge( (array) $args['messages'], $defaults['messages'] );
		}
		$args                       = array_merge( $defaults, $args );
		$args['name']               = $name;
		$args['options_group_name'] = $this->options_group . $name . '_options';
		if ( ! isset( $args['settings_slug'] ) ) {
			$args['settings_slug'] = 'vw-' . $args['slug'] . '-settings';
		}
		if ( empty( $args['post_type_support'] ) ) {
			$args['post_type_support'] = 'vw_' . $name;
		}
		// If there's a Help Screen registered for the module, make sure we
		// auto-load it
		if ( ! empty( $args['settings_help_tab'] ) ) {
			add_action( 'load-vip-workflow_page_' . $args['settings_slug'], array( &$this->$name, 'action_settings_help_menu' ) );
		}

		$this->modules->$name = (object) $args;

		++$this->modules_count;

		return $this->modules->$name;
	}

	/**
	 * Load all of the module options from the database
	 * If a given option isn't yet set, then set it to the module's default (upgrades, etc.)
	 */
	public function load_module_options() {

		foreach ( $this->modules as $mod_name => $mod_data ) {

			$this->modules->$mod_name->options = get_option( $this->options_group . $mod_name . '_options', new stdClass() );
			foreach ( $mod_data->default_options as $default_key => $default_value ) {
				if ( ! isset( $this->modules->$mod_name->options->$default_key ) ) {
					$this->modules->$mod_name->options->$default_key = $default_value;
				}
			}

			$this->$mod_name->module = $this->modules->$mod_name;
		}
	}

	/**
	 * Load the post type options again so we give add_post_type_support() a chance to work
	 *
	 * @see http://dev.editflow.org/2011/11/17/edit-flow-v0-7-alpha2-notes/#comment-232
	 */
	public function action_init_after() {
		foreach ( $this->modules as $mod_name => $mod_data ) {

			if ( isset( $this->modules->$mod_name->options->post_types ) ) {
				$this->modules->$mod_name->options->post_types = $this->helpers->clean_post_type_options( $this->modules->$mod_name->options->post_types, $mod_data->post_type_support );
			}

			$this->$mod_name->module = $this->modules->$mod_name;
		}
	}

	/**
	 * Get a module by one of its descriptive values
	 *
	 * @param string $key The property to use for searching a module (ex: 'name')
	 * @param string|int|array $value The value to compare (using ==)
	 */
	public function get_module_by( $key, $value ) {
		$module = false;
		foreach ( $this->modules as $mod_name => $mod_data ) {

			if ( 'name' === $key && $value === $mod_name ) {
				$module = $this->modules->$mod_name;
			} else {
				foreach ( $mod_data as $mod_data_key => $mod_data_value ) {
					if ( $mod_data_key === $key && $mod_data_value === $value ) {
						$module = $this->modules->$mod_name;
					}
				}
			}
		}
		return $module;
	}

	/**
	 * Update the $edit_flow object with new value and save to the database
	 */
	public function update_module_option( $mod_name, $key, $value ) {
		$this->modules->$mod_name->options->$key = $value;
		$this->$mod_name->module                 = $this->modules->$mod_name;
		return update_option( $this->options_group . $mod_name . '_options', $this->modules->$mod_name->options );
	}

	public function update_all_module_options( $mod_name, $new_options ) {
		if ( is_array( $new_options ) ) {
			$new_options = (object) $new_options;
		}
		$this->modules->$mod_name->options = $new_options;
		$this->$mod_name->module           = $this->modules->$mod_name;
		return update_option( $this->options_group . $mod_name . '_options', $this->modules->$mod_name->options );
	}

	/**
	 * Registers commonly used scripts + styles for easy enqueueing
	 */
	public function register_scripts_and_styles() {
		wp_enqueue_style( 'vw-admin-css', VIP_WORKFLOW_URL . 'common/css/vip-workflow-admin.css', false, VIP_WORKFLOW_VERSION, 'all' );

		wp_register_script( 'jquery-listfilterizer', VIP_WORKFLOW_URL . 'common/js/jquery.listfilterizer.js', array( 'jquery' ), VIP_WORKFLOW_VERSION, true );
		wp_register_style( 'jquery-listfilterizer', VIP_WORKFLOW_URL . 'common/css/jquery.listfilterizer.css', false, VIP_WORKFLOW_VERSION, 'all' );


		wp_localize_script(
			'jquery-listfilterizer',
			'__i18n_jquery_filterizer',
			array(
				'all'      => esc_html__( 'All', 'vip-workflow' ),
				'selected' => esc_html__( 'Selected', 'vip-workflow' ),
			)
		);
	}
}

function vip_workflow() {
	return VIP_Workflow::instance();
}
add_action( 'plugins_loaded', 'vip_workflow' );
