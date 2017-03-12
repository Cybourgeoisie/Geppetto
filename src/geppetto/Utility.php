<?php

namespace Geppetto;

/**
 * Class: Utility
 */

class Utility
{
	/**
	 * Throw exception
	 */
	public static function throwException($msg = null)
	{
		// Get the backtrace from the previous function call
		$backtrace = debug_backtrace();

		// Get the error line
		$backtrace_info = array_shift($backtrace);
		$line = $backtrace_info['line'];

		// Get the class / function info
		$backtrace_info = array_shift($backtrace);
		$class  = $backtrace_info['class'];
		$method = $backtrace_info['function'];

		throw new Exception($class . '::' . $method . '@' . $line . ' - ' . $msg);
	}
	
	/**
	 * Run a PostgreSQL statement with given parameters
	 */
	public static function pgQueryParams($sql, $params, $suppress = false)
	{
		$db_handler = DatabaseHandler::init();
		return $db_handler->query($sql, $params, $suppress);
	}

	/**
	 * Return a properly formatted time for postgres
	 */
	public static function pgTimeNow()
	{
		return date('Y-m-d G:i:s');
	}

	/**
	 * Convert a postgres timestamp to unix time
	 */
	public static function pgTimeToUnix($timestamp)
	{
		return strtotime($timestamp);
	}
}
