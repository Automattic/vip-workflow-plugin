<?php
/**
 * The token module for securing previews.
 *
 * @package vip-workflow
 */
namespace VIPWorkflow\Modules\Preview;

use WP_Error;

class Token {
	const META_KEY     = 'vw_preview_token';
	const TOKEN_ACTION = 'preview';

	/**
	 * Generate a one-time-use authentication token that can be returned with a
	 * subsequent request and validated. Upon verification, both the token and the
	 * expiration should be validated. (See validate_token)
	 *
	 * @param  int    $post_id            Post ID.
	 * @param  string $is_one_time_use    Whether this token should be deleted after one use.
	 * @param  string $expiration_seconds The number of seconds this token should be valid for.
	 * @return array|WP_Error
	 */
	public static function generate_token( $post_id, $is_one_time_use, $expiration_seconds ) {
		if ( ! current_user_can( 'edit_posts', $post_id ) ) {
			return new WP_Error( 'vip-workflow-token-permission-denied', __( 'You do not have sufficient permissions to access this page.', 'vip-workflow' ) );
		}

		$expiration = time() + $expiration_seconds;
		$secret     = wp_generate_password( 64, true, true );
		$token      = hash_hmac( 'sha256', self::TOKEN_ACTION, $secret );

		$meta_value = [
			'action'       => self::TOKEN_ACTION,
			'expiration'   => $expiration,
			'one_time_use' => $is_one_time_use,
			'user'         => get_current_user_id(), // not validated, just stored in case it's interesting.
			'token'        => $token,
			'version'      => 1,                     // not validated, but might be useful in future.
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
		$saved_tokens = get_post_meta( $post_id, $meta_key, /* single */ false );
		$current_time = time();
		$is_valid     = false;

		if ( ! is_array( $saved_tokens ) ) {
			return false;
		}

		foreach ( $saved_tokens as $saved ) {
			$is_expired = false;

			// Check for token expiration.
			if ( ! isset( $saved['expiration'] ) || $current_time > $saved['expiration'] ) {
				$is_expired = true;
			}

			// Check if token matches. If it does, mark as expired.
			if ( ! $is_expired && $token === $saved['token'] && self::TOKEN_ACTION === $saved['action'] ) {
				$expire_on_use = ! isset( $saved['one_time_use'] ) || true === $saved['one_time_use'];

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
}
