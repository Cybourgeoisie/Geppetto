<?php

namespace Geppetto;

/**
 * Class: Validation
 */

abstract class Validation
{
	public static function validateTableName($table_name)
	{
		// See if a table name exists
		$sql = QueryRepository::getSql('table_exists');

		// Get all rows
		$rows = Utility::pgQueryParams($sql, array($table_name));

		// Return result
		return !empty($rows)                         &&
				array_key_exists('0', $rows)         &&
				array_key_exists('exists', $rows[0]) &&
				$rows[0]['exists'] == 't';
	}

	public static function validateTableColumns($table_name, $table_schema, $columns)
	{
		// Prepare to return the column names
		$parameter_columns = array();

		// Ensure that each column exists in the schema
		foreach ($columns as $key)
		{
			foreach ($table_schema as $column => $schema)
			{
				if ($column == $key)
				{
					$parameter_columns[] = $column;
					continue 2; // Move on to the next key
				}
			}

			// If we get here, the table column does not exist
			Utility::throwException('Column (' . $key . ') for table (' . $table_name . ') does not exist.');
		}

		return $parameter_columns;
	}

	public static function validateTableInput($table_name, $table_schema, $table_primary_key, $keyed_array)
	{
		// Prepare to return two arrays - column names and values
		$parameter_columns = array();
		$parameter_values  = array();

		// Ensure that each column exists in the schema
		foreach ($keyed_array as $key => $value)
		{
			// If the key is the primary key column, back down
			if ($key == $table_primary_key)
			{
				continue; // We don't want to set this value
			}

			foreach ($table_schema as $column => $schema)
			{
				if ($column == $key)
				{
					$parameter_columns[]       = $column;
					$parameter_values[$column] = $value;
					continue 2; // Move on to the next key
				}
			}

			// If we get here, the table column does not exist
			Utility::throwException('Column (' . $key . ') for table (' . $table_name . ') does not exist.');
		}

		return array($parameter_columns, $parameter_values);
	}
}