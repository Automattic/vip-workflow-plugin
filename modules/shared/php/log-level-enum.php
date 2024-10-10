<?php
/**
 * Class Loglevel
 *
 * This class is used to store the enums for the different log levels,
 */

namespace VIPWorkflow\Modules\Shared\PHP;

// LogLevel is an enum that represents the different levels of logging.
enum LogLevel: string {
	case INFO = 'info';
	case WARNING = 'warning';
	case ERROR = 'error';
};
