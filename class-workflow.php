<?php

namespace VIPWorkflow;

use VIPWorkflow\Modules\Shared\PHP\Module;
use stdClass;

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
		// VIP Workflow base module
		require_once VIP_WORKFLOW_ROOT . '/modules/shared/php/class-module.php';

		$skip_module_dirs = [ 'shared', 'preview', 'notifications', 'editorial-metadata', 'custom-status' ];

		// Scan the modules directory and include any modules that exist there
		$module_dirs = scandir( VIP_WORKFLOW_ROOT . '/modules/' );
		$class_names = [];
		foreach ( $module_dirs as $module_dir ) {
			// Skip the . and .. directories, as well as the shared folder
			if ( file_exists( VIP_WORKFLOW_ROOT . "/modules/{$module_dir}/$module_dir.php" ) && ! in_array( $module_dir, $skip_module_dirs ) ) {
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
				$class_names[ $slug_name ] = 'VIPWorkflow\Modules\\' . rtrim( $class_name, '_' );
			}
		}

		// Instantiate VW_Module as $helpers for back compat and so we can
		// use it in this class
		$this->helpers = new Module();

		// Other utils
		require_once VIP_WORKFLOW_ROOT . '/modules/shared/php/util.php';

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
	 * @uses add_action() To add various actions
	 */
	private function setup_actions() {
		add_action( 'init', [ $this, 'action_init' ], 8 );
		add_action( 'init', [ $this, 'action_init_after' ], 1000 );

		add_action( 'admin_init', [ $this, 'action_admin_init' ] );
	}

	/**
	 * Inititalizes the entire plugin
	 * Loads options for each registered module and then initializes it if it's active
	 */
	public function action_init() {
		$this->load_modules();

		// Load all of the module options
		$this->load_module_options();

		// Load all of the modules that are enabled.
		// Modules won't have an options value if they aren't enabled
		foreach ( $this->modules as $mod_name => $mod_data ) {
			$this->$mod_name->init();
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
	}

	/**
	 * Register a new module
	 */
	public function register_module( $name, $args = [] ) {
		$defaults = [
			'slug'                 => '',
			'post_type_support'    => '',
			'default_options'      => [],
			'options'              => false,
			'configure_page_cb'    => false,
		];
		if ( isset( $args['messages'] ) ) {
			$args['messages'] = array_merge( (array) $args['messages'], $defaults['messages'] );
		}
		$args                       = array_merge( $defaults, $args );
		$args['name']               = $name;
		$args['options_group_name'] = $this->options_group . $name . '_options';
		if ( ! isset( $args['settings_slug'] ) ) {
			$args['settings_slug'] = 'vw-' . $args['slug'];
		}
		if ( empty( $args['post_type_support'] ) ) {
			$args['post_type_support'] = 'vw_' . $name;
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
	 * Update a module option, using the module's name and the key
	 *
	 * @param string $mod_name The module name
	 * @param string $key The option key
	 * @param mixed $value The new value
	 */
	public function update_module_option( $mod_name, $key, $value ) {
		$this->modules->$mod_name->options->$key = $value;
		$this->$mod_name->module                 = $this->modules->$mod_name;
		return update_option( $this->options_group . $mod_name . '_options', $this->modules->$mod_name->options );
	}

	/**
	 * Update all module options
	 *
	 * @param Sttring $mod_name The module name
	 * @param stdClass $new_options The new options to save
	 */
	public function update_all_module_options( $mod_name, $new_options ) {
		if ( is_array( $new_options ) ) {
			$new_options = (object) $new_options;
		}
		$this->modules->$mod_name->options = $new_options;
		$this->$mod_name->module           = $this->modules->$mod_name;
		return update_option( $this->options_group . $mod_name . '_options', $this->modules->$mod_name->options );
	}


	/**
	 * Collect all of the active post types
	 *
	 * @return array $post_types All of the post types that are 'on'
	 */
	public function get_supported_post_types(): array {
		$post_types         = [];
		$post_types_options = $this->settings->module->options->post_types;

		foreach ( $post_types_options as $post_type => $value ) {
			if ( 'on' === $value ) {
				$post_types[] = $post_type;
			}
		}

		return $post_types;
	}

	/**
	 * Whether or not the current page is our settings view. Determination is based on $pagenow, $_GET['page'], and if it's settings module or not.
	 *
	 * @return bool $is_settings_view Return true if it is
	 */
	public static function is_settings_view_loaded( string $slug ): bool {
		global $pagenow;

		// All of the settings views are based on admin.php and a $_GET['page'] parameter
		if ( 'admin.php' != $pagenow || ! isset( $_GET['page'] ) ) {
			return false;
		}

		// The current page better be in the array of registered settings view slugs
		return $_GET['page'] === $slug;
	}
}

VIP_Workflow::instance();
