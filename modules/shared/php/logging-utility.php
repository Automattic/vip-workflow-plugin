<?php
/**
 * Class LoggingUtility
 *
 * This class is used to log messages.
 */

namespace VIPWorkflow\Modules\Shared\PHP;

use VIPWorkflow\Modules\Shared\PHP\LogLevel;

class LoggingUtility {

	/**
	 * Log an info message
	 * Currently this only logs to logstash on WPVIP sites.
	 *
	 * @param string $message The message to log
	 */
	public static function log_info( string $message ): void {
		self::log_message( $message, LogLevel::INFO );
	}

	/**
	 * Log a warning message
	 * Currently this only logs to logstash on WPVIP sites.
	 *
	 * @param string $message The message to log
	 */
	public static function log_warning( string $message ): void {
		self::log_message( $message, LogLevel::WARNING );
	}

	/**
	 * Log an error message
	 * Currently this only logs to logstash on WPVIP sites.
	 *
	 * @param string $message The message to log
	 */
	public static function log_error( string $message ): void {
		self::log_message( $message, LogLevel::ERROR );
	}

	/**
	 * Log a message
	 * Currently this only logs to logstash on WPVIP sites.
	 *
	 * @param string $message The message to log
	 * @param LogLevel $logLevel The level of the log message
	 */
	private static function log_message( string $message, LogLevel $log_level ): void {
		if ( self::is_wpvip_site() ) {
			\Automattic\VIP\Logstash\log2logstash( [
				'severity' => $log_level->value,
				'feature'  => 'vip-workflow-plugin',
				'message'  => $message,
			] );
		}
	}

	/**
	 * Check if the site is a WPVIP site.
	 *
	 * @return bool true if it is a WPVIP site, false otherwise
	 */
	private static function is_wpvip_site() {
		return defined( 'WPCOM_IS_VIP_ENV' ) && constant( 'WPCOM_IS_VIP_ENV' ) === true
			&& defined( 'WPCOM_SANDBOXED' ) && constant( 'WPCOM_SANDBOXED' ) === false
			&& defined( 'FILES_CLIENT_SITE_ID' )
			&& function_exists( '\Automattic\VIP\Logstash\log2logstash' );
	}
}
