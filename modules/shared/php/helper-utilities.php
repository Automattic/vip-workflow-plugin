<?php
/**
 * class HelperUtilities
 *
 * @desc Utility methods for common tasks used throughout the modules
 */

namespace VIPWorkflow\Modules\Shared\PHP;

use VIPWorkflow\Modules\Settings;
use VIPWorkflow\Modules\Shared\PHP\OptionsUtilities;

class HelperUtilities {

	/**
	 * Collect all of the active post types
	 *
	 * @return array $post_types All of the post types that are 'on'
	 */
	public static function get_supported_post_types(): array {
		$post_types         = [];
		$post_types_options = OptionsUtilities::get_options_by_key( 'post_types' );

		foreach ( $post_types_options as $post_type => $value ) {
			if ( 'on' === $value ) {
				$post_types[] = $post_type;
			}
		}

		return $post_types;
	}

	/**
	 * Whether the current post type is upported or not, based on the supported post types that have been configured.
	 * The check is only performed on the allowed pages, which by default is the edit.php, post.php, and post-new.php pages.
	 *
	 * @param array $allowed_pages The pages on which we should check for unsupported post types
	 *
	 * @return bool
	 */
	public static function is_current_post_type_unsupported( $allowed_pages = [ 'edit.php', 'post.php', 'post-new.php' ] ): bool {
		global $pagenow;

		// If we're not on a page that we care about, return false
		if ( ! in_array( $pagenow, $allowed_pages ) ) {
			return false;
		}

		$post_type = self::get_current_post_type();

		$supported_post_types = self::get_supported_post_types();

		if ( $post_type && ! in_array( $post_type, $supported_post_types ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Checks for the current post type
	 *
	 * @return string|null $post_type The post type we've found, or null if no post type
	 */
	public static function get_current_post_type(): ?string {
		global $post, $typenow, $pagenow, $current_screen;
		//get_post() needs a variable
		$post_id = isset( $_REQUEST['post'] ) ? (int) $_REQUEST['post'] : false;

		if ( $post && $post->post_type ) {
			$post_type = $post->post_type;
		} elseif ( $typenow ) {
			$post_type = $typenow;
		} elseif ( $current_screen && ! empty( $current_screen->post_type ) ) {
			$post_type = $current_screen->post_type;
		} elseif ( isset( $_REQUEST['post_type'] ) ) {
			$post_type = sanitize_key( $_REQUEST['post_type'] );
		} elseif ( 'post.php' === $pagenow
		&& $post_id
		&& ! empty( get_post( $post_id )->post_type ) ) {
			$post_type = get_post( $post_id )->post_type;
		} elseif ( 'edit.php' === $pagenow && empty( $_REQUEST['post_type'] ) ) {
			$post_type = 'post';
		} else {
			$post_type = null;
		}

		return $post_type;
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
