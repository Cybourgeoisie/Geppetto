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

	/**
	 * Return a formatted result
	 */
	public static function result($success, $message, $data = null)
	{
		return array(
			'success' => !!$success,
			'message' => $message,
			'data'    => $data
		);
	}

	/**
	 * Return a securely obfuscated string
	 */
	public static function createObfuscatedString(int $hex_length = 16, bool $b_crypto_strong = false)
	{
		if ($hex_length < 4 || $hex_length > 64)
		{
			throw new \Exception('Utility::createObfuscatedString - Inappropriate hex length');
		}

		$crypto_strong = false;
		$bytes = openssl_random_pseudo_bytes($hex_length, $crypto_strong);

		if ($b_crypto_strong && !$crypto_strong)
		{
			// Not necessarily a bad thing if it isn't.. as long as it's long and randomish.
			trigger_error("Utility::createObfuscatedString - Not cryptographically strong", E_USER_NOTICE);
		}

		$hex = bin2hex($bytes);
		return $hex;
	}
}
