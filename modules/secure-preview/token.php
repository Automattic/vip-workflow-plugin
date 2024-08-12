<?php
/**
 * The token module for securing previews.
 *
 * @package vip-workflow
 */
namespace VIPWorkflow\Modules\SecurePreview;

use WP_Error;

class Token {
	const META_KEY     = 'vip_decoupled_token';
	const TOKEN_ACTION = 'preview';

	/**
	 * Generate a one-time-use authentication token that can be returned with a
	 * subsequent request and validated. Upon verification, both the token and the
	 * expiration should be validated. (See validate_token.)
	 *
	 * @param  int    $post_id Post ID.
	 * @param  string $cap     Capability required to generate this token.
	 * @return array|WP_Error
	 */
	public static function generate_token( $post_id, $cap ) {
		if ( ! current_user_can( $cap, $post_id ) ) {
			return new WP_Error( 'vw-token-permission-denied', __( 'You do not have sufficient permissions to access this page.', 'vip-workflow' ) );
		}

		$token_lifetime = self::get_token_lifetime_in_seconds( $post_id );
		$expiration     = time() + $token_lifetime;
		$secret         = wp_generate_password( 64, true, true );
		$token          = hash_hmac( 'sha256', self::TOKEN_ACTION, $secret );

		$meta_value = [
			'action'     => self::TOKEN_ACTION,
			'expiration' => $expiration,
			'user'       => get_current_user(), // not validated, just stored in case it's interesting.
			'token'      => $token,
			'version'    => 1,                  // not validated, but might be useful in future.
		];

		add_post_meta( $post_id, self::META_KEY, $meta_value );

		return $token;
	}

	/**
	 * Validate a token that has been sent with a request.
	 *
	 * @param  string $token   Token.
	 * @param  int    $post_id Post ID.
	 * @return bool
	 */
	public static function validate_token( $token, $post_id ) {
		$meta_key     = self::META_KEY;
		$saved_tokens = get_post_meta( $post_id, $meta_key, false );
		$current_time = time();
		$is_valid     = false;

		foreach ( $saved_tokens as $saved ) {
			$is_expired = false;

			// Check for token expiration.
			if ( ! isset( $saved['expiration'] ) || $current_time > $saved['expiration'] ) {
				$is_expired = true;
			}

			// Check if token matches. If it does, mark as expired.
			if ( ! $is_expired && $token === $saved['token'] && self::TOKEN_ACTION === $saved['action'] ) {
				/**
				 * Filter whether to expire the token on use. By default, tokens are
				 * "one-time use" and we mark them as expired as soon as they are used.
				 * If you want to allow tokens to be used more than once, filter this
				 * value to `false`. Understand the security implications of this change:
				 * Within the expiration window, tokens / preview URLs become bearer
				 * tokens for viewing the associated draft post preview. Anyone who
				 * possesses them will be able to view and share the preview, even if they
				 * are not an authorized WordPress user, and could share them with anyone
				 * else.
				 *
				 * @param bool   $expire_on_use Whether the token should expire on use.
				 * @param int    $post_id       Post ID.
				 */
				$expire_on_use = (bool) apply_filters( 'vw_secure_preview_token_expire_on_use', true, $post_id );

				if ( $expire_on_use ) {
					$is_expired = true;
				}

				$is_valid = true;
			}

			// Delete expired tokens.
			if ( $is_expired ) {
				delete_post_meta( $post_id, $meta_key, $saved );
			}
		}

		return $is_valid;
	}

	/**
	 * Get token lifetime (expiration period) in seconds.
	 *
	 * @param int    $post_id Post ID.
	 * @return int
	 */
	private static function get_token_lifetime_in_seconds( $post_id ) {
		$default_lifetime     = HOUR_IN_SECONDS;
		$default_max_lifetime = 3 * HOUR_IN_SECONDS;

		/**
		 * Filter the maximum allowed token lifetime.
		 *
		 * This filter allows external plugins or custom code to modify the maximum
		 * lifetime of a token. It provides a way to enforce an upper limit on the
		 * duration for which a token remains valid.
		 *
		 * Understand the security implications of this change:
		 * the longer the token lifetime, the more security risk you bear with a compromised token.
		 * Extend the maximum lifetime with caution.
		 *
		 * @param int    $default_max_lifetime The default maximum token lifetime in seconds.
		 * @param int    $post_id              Post ID.
		 */
		$max_lifetime = apply_filters( 'vw_secure_preview_max_token_lifetime', $default_max_lifetime, $post_id );

		/**
		 * Filter the allowed token lifetime.
		 *
		 * @param int    $default_lifetime Token lifetime in seconds.
		 * @param int    $post_id          Post ID.
		 */
		$token_lifetime = (int) apply_filters( 'vw_secure_preview_token_lifetime', $default_lifetime, $post_id );

		// Enforce a maximum token lifetime.
		if ( $token_lifetime > $max_lifetime ) {
			return $max_lifetime;
		}

		return $token_lifetime;
	}
}
