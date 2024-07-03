<?php

if ( ! class_exists( 'VW_Settings' ) ) {

	class VW_Settings extends VW_Module {

		public $module;

		/**
		 * Register the module with VIP Workflow but don't do anything else
		 */
		public function __construct() {
			// Register the module with VIP Workflow
			$this->module_url = $this->get_module_url( __FILE__ );
			$args = array(
				'title' => __( 'VIP Workflow', 'vip-workflow' ),
				'short_description' => __( 'VIP Workflow redefines your WordPress publishing workflow.', 'vip-workflow' ),
				'extended_description' => __( 'Enable any of the features below to take control of your workflow. Custom statuses, email notifications, and more help you and your team save time so everyone can focus on what matters most: the content.', 'vip-workflow' ),
				'module_url' => $this->module_url,
				'img_url' => $this->module_url . 'lib/eflogo_s128.png',
				'slug' => 'settings',
				'settings_slug' => 'vw-settings',
				'default_options' => array(
					'enabled' => 'on',
				),
				'configure_page_cb' => 'print_default_settings',
				'autoload' => true,
			);
			$this->module = vip_workflow()->register_module( 'settings', $args );
		}

		/**
		 * Initialize the rest of the stuff in the class if the module is active
		 */
		public function init() {
			add_action( 'admin_init', array( $this, 'helper_settings_validate_and_save' ), 100 );
			add_action( 'admin_init', array( $this, 'register_settings' ) );

			add_action( 'admin_print_styles', array( $this, 'action_admin_print_styles' ) );
			add_action( 'admin_print_scripts', array( $this, 'action_admin_print_scripts' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'action_admin_enqueue_scripts' ) );
			add_action( 'admin_menu', array( $this, 'action_admin_menu' ) );
		}

		/**
		 * Add necessary things to the admin menu
		 */
		public function action_admin_menu() {
			global $vip_workflow;

			$vw_logo = 'lib/eflogo_s32w.png';

			add_menu_page( $this->module->title, $this->module->title, 'manage_options', $this->module->settings_slug, array( $this, 'settings_page_controller' ), $this->module->module_url . $vw_logo );

			foreach ( $vip_workflow->modules as $mod_name => $mod_data ) {
				if ( isset( $mod_data->options->enabled ) && 'on' == $mod_data->options->enabled
				&& $mod_data->configure_page_cb && $mod_name != $this->module->name ) {
					add_submenu_page( $this->module->settings_slug, $mod_data->title, $mod_data->title, 'manage_options', $mod_data->settings_slug, array( $this, 'settings_page_controller' ) );
				}
			}
		}

		public function action_admin_enqueue_scripts() {
			if ( $this->is_whitelisted_settings_view() ) {
				wp_enqueue_script( 'vip-workflow-settings-js', $this->module_url . 'lib/settings.js', array( 'jquery' ), VIP_WORKFLOW_VERSION, true );
			}
		}

		/**
		 * Add settings styles to the settings page
		 */
		public function action_admin_print_styles() {
			if ( $this->is_whitelisted_settings_view() ) {
				wp_enqueue_style( 'vip_workflow-settings-css', $this->module_url . 'lib/settings.css', false, VIP_WORKFLOW_VERSION );
			}
		}

		/**
		 * Extra data we need on the page for transitions, etc.
		 *
		 * @since 0.7
		 */
		public function action_admin_print_scripts() {
			?>
		<script type="text/javascript">
			var vw_admin_url = '<?php echo esc_url( get_admin_url() ); ?>';
		</script>
			<?php
		}

		/**
		 * Handles all settings and configuration page requests. Required element for VIP Workflow
		 */
		public function settings_page_controller() {
			global $vip_workflow;

			$page_requested = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : 'settings';
			$requested_module = $vip_workflow->get_module_by( 'settings_slug', $page_requested );
			if ( ! $requested_module ) {
				wp_die( esc_html__( 'Not a registered VIP Workflow module', 'vip-workflow' ) );
			}

			$configure_callback = $requested_module->configure_page_cb;
			$requested_module_name = $requested_module->name;

			// Don't show the settings page for the module if the module isn't activated
			if ( ! $this->module_enabled( $requested_module_name ) ) {
				/* translators: 1: link to the settings page for VIP Workflow */
				echo '<div class="message error"><p>' . wp_kses( sprintf( __( 'Module not enabled. Please enable it from the <a href="%1$s">VIP Workflow settings page</a>.', 'vip-workflow' ), esc_url( VIP_WORKFLOW_SETTINGS_PAGE ) ), 'a' ) . '</p></div>';
				return;
			}

			$this->print_default_header( $requested_module );
			$vip_workflow->$requested_module_name->$configure_callback();
		}

		/**
		 * Disabling nonce verification because that is not available here, it's just rendering it. The actual save is done in helper_settings_validate_and_save and that's guarded well.
		 * phpcs:disable:WordPress.Security.NonceVerification.Missing
		 */
		public function print_default_header( $current_module ) {
			// If there's been a message, let's display it
			if ( isset( $_GET['message'] ) ) {
				$message = $_GET['message'];
			} else if ( isset( $_REQUEST['message'] ) ) {
				$message = $_REQUEST['message'];
			} else if ( isset( $_POST['message'] ) ) {
				$message = $_POST['message'];
			} else {
				$message = false;
			}
			if ( $message && isset( $current_module->messages[ $message ] ) ) {
				$display_text = '<span class="vip-workflow-updated-message vip-workflow-message">' . esc_html( $current_module->messages[ $message ] ) . '</span>';
			}

			// If there's been an error, let's display it
			if ( isset( $_GET['error'] ) ) {
				$error = $_GET['error'];
			} else if ( isset( $_REQUEST['error'] ) ) {
				$error = $_REQUEST['error'];
			} else if ( isset( $_POST['error'] ) ) {
				$error = $_POST['error'];
			} else {
				$error = false;
			}
			if ( $error && isset( $current_module->messages[ $error ] ) ) {
				$display_text = '<span class="vip-workflow-error-message vip-workflow-message">' . esc_html( $current_module->messages[ $error ] ) . '</span>';
			}

			if ( $current_module->img_url ) {
				$page_icon = '<img src="' . esc_url( $current_module->img_url ) . '" class="module-icon icon32" />';
			} else {
				$page_icon = '<div class="icon32" id="icon-options-general"><br/></div>';
			}
			?>
		<div class="wrap vip-workflow-admin">
			<?php if ( 'settings' != $current_module->name ) : ?>
				<?php echo wp_kses_post( $page_icon ); ?>
			<h2><a href="<?php echo esc_url( EDIT_FLOW_SETTINGS_PAGE ); ?>"><?php _e( 'VIP Workflow', 'vip-workflow' ); ?></a>:&nbsp;<?php echo esc_attr( $current_module->title ); ?><?php echo ( isset( $display_text ) ? wp_kses_post( $display_text ) : '' ); ?></h2>
			<?php else : ?>
				<?php echo wp_kses_post( $page_icon ); ?>
			<h2><?php _e( 'VIP Workflow', 'vip-workflow' ); ?><?php echo ( isset( $display_text ) ? wp_kses_post( $display_text ) : '' ); ?></h2>
			<?php endif; ?>

			<div class="explanation">
				<?php if ( $current_module->short_description ) : ?>
				<h3><?php echo wp_kses_post( $current_module->short_description ); ?></h3>
				<?php endif; ?>
				<?php if ( $current_module->extended_description ) : ?>
				<p><?php echo wp_kses_post( $current_module->extended_description ); ?></p>
				<?php endif; ?>
			</div>
			<?php
		}
		//phpcs:enable:WordPress.Security.NonceVerification.Missing

		/**
		 * Adds Settings page for VIP Workflow.
		 */
		public function print_default_settings() {
			?>
		<div class="vip-workflow-modules">
			<?php $this->print_modules(); ?>
		</div>
		<form class="basic-settings" action="<?php echo esc_url( menu_page_url( $this->module->settings_slug, false ) ); ?>" method="post">
			<?php settings_fields( $this->module->options_group_name ); ?>
			<?php do_settings_sections( $this->module->options_group_name ); ?>
			<?php
				echo '<input id="vip_workflow_module_name" name="vip_workflow_module_name" type="hidden" value="' . esc_attr( $this->module->name ) . '" />';
			?>
			<p class="submit"><?php submit_button( null, 'primary', 'submit', false ); ?></p>
		</form>
			<?php
		}

		public function print_modules() {
			global $vip_workflow;

			if ( ! $vip_workflow->modules_count ) {
				echo '<div class="message error">' . esc_html__( 'There are no VIP Workflow modules registered', 'vip-workflow' ) . '</div>';
			} else {

				foreach ( $vip_workflow->modules as $mod_name => $mod_data ) {
					if ( $mod_data->autoload ) {
						continue;
					}

					$classes = array(
						'vip-workflow-module',
					);
					if ( 'on' == $mod_data->options->enabled ) {
						$classes[] = 'module-enabled';
					} elseif ( 'off' == $mod_data->options->enabled ) {
						$classes[] = 'module-disabled';
					}
					if ( $mod_data->configure_page_cb ) {
						$classes[] = 'has-configure-link';
					}
					echo '<div class="' . esc_attr( implode( ' ', $classes ) ) . '" id="' . esc_attr( $mod_data->slug ) . '">';
					if ( $mod_data->img_url ) {
						echo '<img src="' . esc_url( $mod_data->img_url ) . '" height="24px" width="24px" class="float-right module-icon" />';
					}
					echo '<form method="get" action="' . esc_url( get_admin_url( null, 'options.php' ) ) . '">';
					echo '<h4>' . esc_html( $mod_data->title ) . '</h4>';
					if ( 'on' == $mod_data->options->enabled ) {
						echo '<p>' . wp_kses( $mod_data->short_description, 'a' ) . '</p>';
					} else {
						echo '<p>' . esc_html( $mod_data->short_description ) . '</p>';
					}
					echo '<p class="vip-workflow-module-actions">';
					if ( $mod_data->configure_page_cb ) {
						$configure_url = add_query_arg( 'page', $mod_data->settings_slug, get_admin_url( null, 'admin.php' ) );
						echo '<a href="' . esc_url( $configure_url ) . '" class="configure-vip-workflow-module button button-primary';
						if ( 'off' == $mod_data->options->enabled ) {
							echo ' hidden" style="display:none;';
						}
						// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
						echo '">' . esc_html__( $mod_data->configure_link_text ) . '</a>';
					}
					echo '</p>';
					wp_nonce_field( 'change-vip-workflow-module-nonce', 'change-module-nonce-' . $mod_data->slug, false );
					echo '</form>';
					echo '</div>';
				}
			}
		}

		/**
		 * Given a form field and a description, prints either the error associated with the field or the description.
		 *
		 * @since 0.7
		 *
		 * @param string $field The form field for which to check for an error
		 * @param string $description Unlocalized string to display if there was no error with the given field
		 */
		public function helper_print_error_or_description( $field, $description ) {
			if ( isset( $_REQUEST['form-errors'][ $field ] ) ) :
				?>
			<div class="form-error">
				<p><?php echo esc_html( $_REQUEST['form-errors'][ $field ] ); ?></p>
			</div>
			<?php else : ?>
			<p class="description"><?php echo esc_html( $description ); ?></p>
				<?php
		endif;
		}

		/**
		 * Generate an option field to turn post type support on/off for a given module
		 *
		 * @param object $module VIP Workflow module we're generating the option field for
		 * @param {missing}
		 *
		 * @since 0.7
		 */
		public function helper_option_custom_post_type( $module, $args = array() ) {

			$all_post_types = array(
				'post' => __( 'Posts' ),
				'page' => __( 'Pages' ),
			);
			$custom_post_types = $this->get_supported_post_types_for_module();
			if ( count( $custom_post_types ) ) {
				foreach ( $custom_post_types as $custom_post_type => $args ) {
					$all_post_types[ $custom_post_type ] = $args->label;
				}
			}

			foreach ( $all_post_types as $post_type => $title ) {
				echo '<label for="' . esc_attr( $post_type ) . '">';
				echo '<input id="' . esc_attr( $post_type ) . '" name="'
				. esc_attr( $module->options_group_name ) . '[post_types][' . esc_attr( $post_type ) . ']"';
				if ( isset( $module->options->post_types[ $post_type ] ) ) {
					checked( $module->options->post_types[ $post_type ], 'on' );
				}
				// Defining post_type_supports in the functions.php file or similar should disable the checkbox
				disabled( post_type_supports( $post_type, $module->post_type_support ), true );
				echo ' type="checkbox" />&nbsp;&nbsp;&nbsp;' . esc_html( $title ) . '</label>';
				// Leave a note to the admin as a reminder that add_post_type_support has been used somewhere in their code
				if ( post_type_supports( $post_type, $module->post_type_support ) ) {
					/* translators: 1: post type, 2: post type support */
					echo '&nbsp&nbsp;&nbsp;<span class="description">' . esc_html( sprintf( __( 'Disabled because add_post_type_support( \'%1$s\', \'%2$s\' ) is included in a loaded file.', 'vip-workflow' ), $post_type, $module->post_type_support ) ) . '</span>';
				}
				echo '<br />';
			}
		}

		/**
		 * Validation and sanitization on the settings field
		 * This method is called automatically/ doesn't need to be registered anywhere
		 *
		 * @since 0.7
		 */
		public function helper_settings_validate_and_save() {

			if ( ! isset( $_POST['action'], $_POST['_wpnonce'], $_POST['option_page'], $_POST['_wp_http_referer'], $_POST['vip_workflow_module_name'], $_POST['submit'] ) || ! is_admin() ) {
				return false;
			}

			global $vip_workflow;
			$module_name = sanitize_key( $_POST['vip_workflow_module_name'] );

			if ( 'update' != $_POST['action']
			|| $_POST['option_page'] != $vip_workflow->$module_name->module->options_group_name ) {
				return false;
			}

			if ( ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( $_POST['_wpnonce'], $vip_workflow->$module_name->module->options_group_name . '-options' ) ) {
				wp_die( esc_html__( 'Cheatin&#8217; uh?' ) );
			}

			$new_options = ( isset( $_POST[ $vip_workflow->$module_name->module->options_group_name ] ) ) ? $_POST[ $vip_workflow->$module_name->module->options_group_name ] : array();

			// Only call the validation callback if it exists?
			if ( method_exists( $vip_workflow->$module_name, 'settings_validate' ) ) {
				$new_options = $vip_workflow->$module_name->settings_validate( $new_options );
			}

			// Cast our object and save the data.
			$new_options = (object) array_merge( (array) $vip_workflow->$module_name->module->options, $new_options );
			$vip_workflow->update_all_module_options( $vip_workflow->$module_name->module->name, $new_options );

			// Redirect back to the settings page that was submitted without any previous messages
			$goback = add_query_arg( 'message', 'settings-updated', remove_query_arg( array( 'message' ), wp_get_referer() ) );
			wp_safe_redirect( $goback );
			exit;
		}
	}

}