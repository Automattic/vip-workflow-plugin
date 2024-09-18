<?php
/**
 * class TaxonomyUtilities
 *
 * @desc Utility methods for working with WP_Terms
 */

namespace VIPWorkflow\Modules\Shared\PHP;

class TaxonomyUtilities {
	/**
	 * This is a hack, Hack, HACK!!!
	 * Encode all of the given arguments as a serialized array, and then base64_encode
	 * Used to store extra data in a term's description field
	 *
	 * @param array $args The arguments to encode
	 * @return string Arguments encoded in base64
	 */
	public static function get_encoded_description( $args = array() ) {
		return base64_encode( maybe_serialize( $args ) );
	}

	/**
	 * If given an encoded string from a term's description field,
	 * return an array of values. Otherwise, return the original string
	 *
	 * @param string $string_to_unencode Possibly encoded string
	 * @return array Array if string was encoded, otherwise the string as the 'description' field
	 */
	public static function get_unencoded_description( $string_to_unencode ) {
		return maybe_unserialize( base64_decode( $string_to_unencode ) );
	}
}
