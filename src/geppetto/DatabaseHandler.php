<?php

declare(strict_types=1);

namespace Geppetto;

class DatabaseHandler
{
	private static $db_obj  = NULL;
	private $db_conn = NULL;

	protected function __construct()
	{
		$this->createDBConn();
	}

	private function __clone()
	{
		return self::$db_obj;
	}

	private function __wakeup():void {}

	public static function init()
	{
		if (NULL == self::$db_obj)
		{
			self::$db_obj = new DatabaseHandler();
		}

		return self::$db_obj;
	}

	private function createDBConn()
	{
		$db_conn_string = 'host=' . DB_HOST . 
			' port=' . DB_PORT . 
			' dbname=' . DB_NAME . 
			' user=' . DB_USER . 
			' password=' . DB_PASS;

		$this->db_conn = pg_connect($db_conn_string);
	}

	public function query($sql, $params)
	{
		if (!$this->db_conn)
		{
			Utility::throwException('Invalid DatabaseHandler object - DB conn is not set');
		}

		// Execute query
		pg_send_query_params($this->db_conn, $sql, $params);
		$sqh = pg_get_result($this->db_conn);

		if ($sqh)
		{
			$state = pg_result_error_field($sqh, PGSQL_DIAG_SQLSTATE);

			if ($state==0)
			{
				$rows = pg_fetch_all($sqh);
				return ($rows ? $rows : array());
			}
			else
			{
				$error = pg_result_error($sqh);
				Utility::throwException("Encountered error while executing SQL ($sql): $error");
			}
		}
		else
		{
			Utility::throwException("Did not receive response while executing SQL");
		}
	}
}