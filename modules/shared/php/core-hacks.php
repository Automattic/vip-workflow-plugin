<?php
/**
 * class CoreHacks
 * Core hacks for the VIP Workflow plugin, that are necessary in the custom status module.
 */
namespace VIPWorkflow\Modules\Shared\PHP;

use VIPWorkflow\Modules\CustomStatus;
use VIPWorkflow\Modules\Shared\PHP\HelperUtilities;
use WP_Post;

use function VIPWorkflow\Modules\Shared\PHP\_vw_wp_link_page;

class CoreHacks {

	private const PUBLISHED_STATUSES = [ 'publish', 'future', 'private' ];

	public static function init(): void {
		// These seven-ish methods are hacks for fixing bugs in WordPress core
		add_filter( 'wp_insert_post_data', [ __CLASS__, 'maybe_keep_post_name_empty' ], 10, 2 );
		add_filter( 'pre_wp_unique_post_slug', [ __CLASS__, 'fix_unique_post_slug' ], 10, 6 );
		add_filter( 'preview_post_link', [ __CLASS__, 'fix_preview_link_part_one' ] );
		add_filter( 'post_link', [ __CLASS__, 'fix_preview_link_part_two' ], 10, 3 );
		add_filter( 'page_link', [ __CLASS__, 'fix_preview_link_part_two' ], 10, 3 );
		add_filter( 'post_type_link', [ __CLASS__, 'fix_preview_link_part_two' ], 10, 3 );
		add_filter( 'preview_post_link', [ __CLASS__, 'fix_preview_link_part_three' ], 11, 2 );
		add_filter( 'get_sample_permalink', [ __CLASS__, 'fix_get_sample_permalink' ], 10, 5 );
		add_filter( 'get_sample_permalink_html', [ __CLASS__, 'fix_get_sample_permalink_html' ], 10, 5 );
		add_filter( 'post_row_actions', [ __CLASS__, 'fix_post_row_actions' ], 10, 2 );
		add_filter( 'page_row_actions', [ __CLASS__, 'fix_post_row_actions' ], 10, 2 );

		// Pagination for custom post statuses when previewing posts
		add_filter( 'wp_link_pages_link', [ __CLASS__, 'modify_preview_link_pagination_url' ], 10, 2 );
	}

	/**
	 * A new hack! hack! hack! until core better supports custom statuses`
	 *
	 * If the post_name is set, set it, otherwise keep it empty
	 *
	 * @see https://github.com/Automattic/Edit-Flow/issues/523
	 * @see https://github.com/Automattic/Edit-Flow/issues/633
	 *
	 * @param array $data The post data
	 * @param array $postarr The post array
	 * @return array $data The post data
	 */
	public static function maybe_keep_post_name_empty( array $data, array $postarr ): array {
		$status_slugs = wp_list_pluck( CustomStatus::get_custom_statuses(), 'slug' );

		// Ignore if it's not a post status and post type we support
		if ( ! in_array( $data['post_status'], $status_slugs )
		|| ! in_array( $data['post_type'], HelperUtilities::get_supported_post_types() ) ) {
			return $data;
		}

		// If the post_name was intentionally set, set the post_name
		if ( ! empty( $postarr['post_name'] ) ) {
			$data['post_name'] = sanitize_title( $postarr['post_name'] );
			return $data;
		}

		// Otherwise, keep the post_name empty
		$data['post_name'] = '';

		return $data;
	}

	/**
	 * A new hack! hack! hack! until core better supports custom statuses`
	 *
	 * `wp_unique_post_slug` is used to set the `post_name`. When a custom status is used, WordPress will try
	 * really hard to set `post_name`, and we leverage `wp_unique_post_slug` to prevent it being set
	 *
	 * @see: https://github.com/WordPress/WordPress/blob/396647666faebb109d9cd4aada7bb0c7d0fb8aca/wp-includes/post.php#L3932
	 *
	 * @param string|null $override_slug The override slug
	 * @param string $slug The slug
	 * @param int $post_id The post ID
	 * @param string $post_status The post status
	 * @param string $post_type The post type
	 * @param int $post_parent The post parent
	 * @return string|null $override_slug The override slug
	*/
	public static function fix_unique_post_slug( string|null $override_slug, string $slug, int $post_id, string $post_status, string $post_type, int $post_parent ): string|null {
		$status_slugs = wp_list_pluck( CustomStatus::get_custom_statuses(), 'slug' );

		if ( ! in_array( $post_status, $status_slugs )
		|| ! in_array( $post_type, HelperUtilities::get_supported_post_types() ) ) {
			return null;
		}

		$post = get_post( $post_id );

		if ( empty( $post ) ) {
			return null;
		}

		if ( $post->post_name ) {
			return $slug;
		}

		return '';
	}


	/**
	 * Another hack! hack! hack! until core better supports custom statuses
	 *
	 * The preview link for an unpublished post should always be ?p=
	 *
	 * @param string $preview_link The preview link
	 * @return string $preview_link The preview link
	 */
	public static function fix_preview_link_part_one( string $preview_link ): string {
		global $pagenow;

		$post = get_post( get_the_ID() );

		// Optimization: preview_post_link is called for each visible post on the Posts -> All Posts page.
		// Temporarily cache slugs to avoid calling get_custom_statuses() for each post.
		static $status_slugs = false;
		if ( false === $status_slugs ) {
			$status_slugs = wp_list_pluck( CustomStatus::get_custom_statuses(), 'slug' );
		}

		if ( ! $post
		|| ! is_admin()
		|| 'post.php' != $pagenow
		|| ! in_array( $post->post_status, $status_slugs )
		|| ! in_array( $post->post_type, HelperUtilities::get_supported_post_types() )
		|| strpos( $preview_link, 'preview_id' ) !== false
		|| 'sample' === $post->filter ) {
			return $preview_link;
		}

		return self::get_preview_link( $post );
	}

	/**
	 * Another hack! hack! hack! until core better supports custom statuses
	 *
	 * The preview link for an unpublished post should always be ?p=
	 * The code used to trigger a post preview doesn't also apply the 'preview_post_link' filter
	 * So we can't do a targeted filter. Instead, we can even more hackily filter get_permalink
	 * @see http://core.trac.wordpress.org/ticket/19378
	 *
	 * @param string $permalink The permalink
	 * @param int|WP_Post $post The post object
	 * @param bool $sample Is this a sample permalink?
	 * @return string $permalink The permalink
	 */
	public static function fix_preview_link_part_two( string $permalink, int|WP_Post $post, bool $sample ): string {
		global $pagenow;

		if ( is_int( $post ) ) {
			$post = get_post( $post );
		}

		//Should we be doing anything at all?
		if ( ! in_array( $post->post_type, HelperUtilities::get_supported_post_types() ) ) {
			return $permalink;
		}

		//Is this published?
		if ( in_array( $post->post_status, self::PUBLISHED_STATUSES ) ) {
			return $permalink;
		}

		//Are we overriding the permalink? Don't do anything
		// phpcs:ignore:WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['action'] ) && 'sample-permalink' === $_POST['action'] ) {
			return $permalink;
		}

		//Are we previewing the post from the normal post screen?
		if ( ( 'post.php' === $pagenow || 'post-new.php' === $pagenow )
		// phpcs:ignore:WordPress.Security.NonceVerification.Missing
		&& ! isset( $_POST['wp-preview'] ) ) {
			return $permalink;
		}

		//If it's a sample permalink, not a preview
		if ( $sample ) {
			return $permalink;
		}

		return self::get_preview_link( $post );
	}

	/**
	 * Another hack! hack! hack! until core better supports custom statuses
	 *
	 * The preview link for a saved unpublished post with a custom status returns a 'preview_nonce'
	 * in it and needs to be removed when previewing it to return a viewable preview link.
	 * @see https://github.com/Automattic/Edit-Flow/issues/513
	 *
	 * @param string $preview_link The preview link
	 * @param WP_Post $query_args The post object
	 * @return string $preview_link The preview link
	 */
	public static function fix_preview_link_part_three( string $preview_link, WP_Post $query_args ) {
		$autosave = wp_get_post_autosave( $query_args->ID, get_current_user_id() );
		if ( $autosave ) {
			foreach ( array_intersect( array_keys( _wp_post_revision_fields( $query_args ) ), array_keys( _wp_post_revision_fields( $autosave ) ) ) as $field ) {
				if ( normalize_whitespace( $query_args->$field ) != normalize_whitespace( $autosave->$field ) ) {
					// Pass through, it's a personal preview.
					return $preview_link;
				}
			}
		}
		return remove_query_arg( [ 'preview_nonce' ], $preview_link );
	}

	/**
	 * Fix get_sample_permalink. Previously the 'editable_slug' filter was leveraged
	 * to correct the sample permalink a user could edit on post.php. Since 4.4.40
	 * the `get_sample_permalink` filter was added which allows greater flexibility in
	 * manipulating the slug. Critical for cases like editing the sample permalink on
	 * hierarchical post types.
	 *
	 * @param array  $permalink Sample permalink
	 * @param int     $post_id   Post ID
	 * @param string  $title     Post title
	 * @param string  $name      Post name (slug)
	 * @param WP_Post $post      Post object
	 * @return array $link Direct link to complete the action
	 */
	public static function fix_get_sample_permalink( array $permalink, int $post_id, string|null $title, string|null $name, WP_Post $post ): array {

		$status_slugs = wp_list_pluck( CustomStatus::get_custom_statuses(), 'slug' );

		if ( ! in_array( $post->post_status, $status_slugs )
		|| ! in_array( $post->post_type, HelperUtilities::get_supported_post_types() ) ) {
			return $permalink;
		}

		remove_filter( 'get_sample_permalink', [ __CLASS__, 'fix_get_sample_permalink' ], 10, 5 );

		$new_name  = ! is_null( $name ) ? $name : $post->post_name;
		$new_title = ! is_null( $title ) ? $title : $post->post_title;

		$post              = get_post( $post_id );
		$status_before     = $post->post_status;
		$post->post_status = 'draft';

		$permalink = get_sample_permalink( $post, $title, sanitize_title( $new_name ? $new_name : $new_title, $post->ID ) );

		$post->post_status = $status_before;

		add_filter( 'get_sample_permalink', [ __CLASS__, 'fix_get_sample_permalink' ], 10, 5 );

		return $permalink;
	}

	/**
	 * Hack to work around post status check in get_sample_permalink_html
	 *
	 *
	 * The get_sample_permalink_html checks the status of the post and if it's
	 * a draft generates a certain permalink structure.
	 * We need to do the same work it's doing for custom statuses in order
	 * to support this link
	 * @see https://core.trac.wordpress.org/browser/tags/4.5.2/src/wp-admin/includes/post.php#L1296
	 *
	 * @param array  $return    Sample permalink HTML markup.
	 * @param int     $post_id   Post ID.
	 * @param string  $new_title New sample permalink title.
	 * @param string  $new_slug  New sample permalink slug.
	 * @param WP_Post $post      Post object.
	 * @return array $sample_permalink_html
	 */
	public static function fix_get_sample_permalink_html( array $permalink, int $post_id, string|null $new_title, string|null $new_slug, WP_Post $post ): array {
		$status_slugs = wp_list_pluck( CustomStatus::get_custom_statuses(), 'slug' );

		if ( ! in_array( $post->post_status, $status_slugs )
		|| ! in_array( $post->post_type, HelperUtilities::get_supported_post_types() ) ) {
			return $permalink;
		}

		remove_filter( 'get_sample_permalink_html', [ __CLASS__, 'fix_get_sample_permalink_html' ], 10, 5 );

		$post->post_status     = 'draft';
		$sample_permalink_html = get_sample_permalink_html( $post, $new_title, $new_slug );

		add_filter( 'get_sample_permalink_html', [ __CLASS__, 'fix_get_sample_permalink_html' ], 10, 5 );

		return $sample_permalink_html;
	}


	/**
	 * Fixes a bug where post-pagination doesn't work when previewing a post with a custom status
	 * @link https://github.com/Automattic/Edit-Flow/issues/192
	 *
	 * This filter only modifies output if `is_preview()` is true
	 *
	 * Used by `wp_link_pages_link` filter
	 *
	 * @param string $link The link
	 * @param string $i The page number
	 *
	 * @return string $link The modified link
	 */
	public static function modify_preview_link_pagination_url( string $link, string $i ) {

		// Use the original $link when not in preview mode
		if ( ! is_preview() ) {
			return $link;
		}

		// Get an array of valid custom status slugs
		$custom_statuses = wp_list_pluck( CustomStatus::get_custom_statuses(), 'slug' );

		// Apply original link filters from core `wp_link_pages()`
		$r = apply_filters( 'wp_link_pages_args', [
			'link_before' => '',
			'link_after'  => '',
			'pagelink'    => '%',
		]);

		// _wp_link_page() && _vw_wp_link_page() produce an opening link tag ( <a href=".."> )
		// This is necessary to replicate core behavior:
		$link = $r['link_before'] . str_replace( '%', $i, $r['pagelink'] ) . $r['link_after'];
		$link = _vw_wp_link_page( $i, $custom_statuses ) . $link . '</a>';


		return $link;
	}

	/**
	 * Get the proper preview link for a post
	 *
	 * @param WP_Post $post The post object
	 * @return string $preview_link The preview link
	 */
	private static function get_preview_link( WP_Post $post ): string {

		if ( 'page' === $post->post_type ) {
			$args = [
				'page_id' => $post->ID,
			];
		} elseif ( 'post' === $post->post_type ) {
			$args = [
				'p'       => $post->ID,
				'preview' => 'true',
			];
		} else {
			$args = [
				'p'         => $post->ID,
				'post_type' => $post->post_type,
			];
		}

		$args['preview_id'] = $post->ID;
		return add_query_arg( $args, home_url( '/' ) );
	}

	/**
	 * Another hack! hack! hack! until core better supports custom statuses
	 *
	 * The preview link for an unpublished post should always be ?p=, even in the list table
	 * @see http://core.trac.wordpress.org/ticket/19378
	 */
	public static function fix_post_row_actions( array $actions, WP_Post $post ): array {
		global $pagenow;

		// Optimization: fix_post_row_actions is called for each visible post on the Posts -> All Posts page.
		// Temporarily cache slugs to avoid calling get_custom_statuses() for each post.
		static $status_slugs = false;
		if ( false === $status_slugs ) {
			$status_slugs = wp_list_pluck( CustomStatus::get_custom_statuses(), 'slug' );
		}

		// Only modify if we're using a pre-publish status on a supported custom post type
		if ( 'edit.php' != $pagenow
		|| ! in_array( $post->post_status, $status_slugs )
		|| ! in_array( $post->post_type, HelperUtilities::get_supported_post_types() ) ) {
			return $actions;
		}

		// 'view' is only set if the user has permission to post
		if ( empty( $actions['view'] ) ) {
			return $actions;
		}

		if ( 'page' === $post->post_type ) {
			$args = [
				'page_id' => $post->ID,
			];
		} elseif ( 'post' === $post->post_type ) {
			$args = [
				'p' => $post->ID,
			];
		} else {
			$args = [
				'p'         => $post->ID,
				'post_type' => $post->post_type,
			];
		}
		$args['preview'] = 'true';
		$preview_link    = add_query_arg( $args, home_url( '/' ) );

		/* translators: %s: post title */
		$actions['view'] = '<a href="' . esc_url( $preview_link ) . '" title="' . esc_attr( sprintf( __( 'Preview &#8220;%s&#8221;' ), $post->post_title ) ) . '" rel="permalink">' . __( 'Preview' ) . '</a>';
		return $actions;
	}
}

CoreHacks::init();
