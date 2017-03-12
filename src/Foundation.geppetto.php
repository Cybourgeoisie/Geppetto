<?php

namespace Geppetto;

/**
 * Class: Foundation
 * 
 * Public Methods:
 * 
 * 
 * Private Methods:
 * 
 */

abstract class Foundation
{
	// Generic cache
	protected static $_schema_cache;
	protected static $_primary_key_cache;
	protected static $_references_cache;

	// Class-specific
	protected $_table_name;
	protected $_table_schema;
	protected $_table_primary_key;
	protected $_table_references;

/**************
 Public Methods
 **************/

	/*
	 * Generic Query Wrappers
	 */

	public function select($columns, $primary_key_value)
	{
		return $this->_select($this->_table_name, $columns, $primary_key_value);
	}

	public function insert($keyed_array)
	{
		return $this->_insert($this->_table_name, $keyed_array);
	}

	public function update($keyed_array)
	{
		return $this->_update($this->_table_name, $keyed_array);
	}

	/*
	 * Special Case Query Wrappers
	 */

	public function selectAll($primary_key_value)
	{
		return $this->_select($this->_table_name, array_keys($this->_table_schema), $primary_key_value);
	}

/***************
 Private Methods
 ***************/
	public function __construct() { }

	/*********************
	 Get Table Information
	 *********************/

	protected function _getCleanTableName($table_name)
	{
		// Escape the table if it's a reserved name
		$reserved_table_names = array('user', 'order');
		if (in_array($table_name, $reserved_table_names))
		{
			$table_name = '"' . $table_name . '"';
		}

		return $table_name;
	}

	protected function _getTableSchema($table_name)
	{
		// Look for an existing schema
		if (isset(Foundation::$_schema_cache[$table_name]))
		{
			return Foundation::$_schema_cache[$table_name];
		}

		// Get all of the column names and schemas
		$sql = QueryRepository::getSql('schema');

		// Get all rows
		$rows = Utility::pgQueryParams($sql, array($table_name));

		// Validate result
		if (empty($rows))
		{
			Utility::throwException('Columns for table (' . $table_name . ') could not be found.');
		}

		// Construct an array of column => schema information
		$schema = self::_constructSchema($rows);

		// Cache the schema for later retrieval
		Foundation::$_schema_cache[$table_name] = $schema;

		// Return the schema
		return $schema;
	}

	protected function _getTablePrimaryKey($table_name)
	{
		// Primary key cache
		if (isset(Foundation::$_primary_key_cache[$table_name]))
		{
			return Foundation::$_primary_key_cache[$table_name];
		}

		// Get the primary key column name
		$sql = QueryRepository::getSql('primary_key');

		// Get all rows
		$rows = Utility::pgQueryParams($sql, array($table_name));

		// Validate result
		if (empty($rows) || !array_key_exists('0', $rows) || !array_key_exists('primary_key', $rows[0]))
		{
			Utility::throwException('Primary key column for table (' . $table_name . ') could not be found.');
		}

		// Cache the primary key for later retrieval
		Foundation::$_primary_key_cache[$table_name] = $rows[0]['primary_key'];

		return $rows[0]['primary_key'];
	}

	protected function _getTableReferences($table_name)
	{
		// Reference Cache
		if (isset(Foundation::$_references_cache[$table_name]))
		{
			return Foundation::$_references_cache[$table_name];
		}

		// Get all of the references, either this table's foriegn keys 
		// or other table foriegn keys referencing this table
		$sql = QueryRepository::getSql('references');

		// Get all rows
		$rows = Utility::pgQueryParams($sql, array($table_name));

		// Validate result
		if (empty($rows))
		{
			// Cache an empty array since there are no results
			Foundation::$_references_cache[$table_name] = array();

			// Return an empty array
			return array();
		}

		// Construct an array of all referenced tables and their columns
		$references = self::_constructReferences($table_name, $rows);

		// Cache the references for later retrieval
		Foundation::$_references_cache[$table_name] = $references;

		return $references;
	}

	/************************
	 Format Table Information
	 ************************/

	private function _constructSchema($rows)
	{
		// Compile the schema in an array of column => info
		$schema = array();

		foreach($rows as $row)
		{
			$schema[$row['column_name']] = $row;
		}

		return $schema;
	}

	private function _constructReferences($table_name, $rows)
	{
		// Compile all of the references
		$references = array();

		foreach($rows as $row)
		{
			// This table holds the foreign key constraint
			if ($row['table_name'] == $table_name)
			{
				$foreign_table_name    = $row['foreign_table_name'];
				$column_name           = $row['column_name'];
				$foreign_column_name   = $row['foreign_column_name'];
			}
			// The foreign table holds the key constraint
			else
			{
				$foreign_table_name   = $row['table_name'];
				$column_name          = $row['foreign_column_name'];
				$foreign_column_name  = $row['column_name'];
			}

			// Make sure that this key exists and points to an array
			if (!array_key_exists($foreign_table_name, $references))
			{
				// Create a new element under this table
				$references[$foreign_table_name] = array();
			}

			// Add the reference
			$references[$foreign_table_name][] = array(
				'column_name'                 => $column_name,
				'foreign_column_name'         => $foreign_column_name,
				'reference_table'             => $row['table_name'],
				'reference_table_primary_key' => self::_getTablePrimaryKey($row['table_name'])
			);
		}

		return $references;
	}

	/**********************
	 Prepare SQL Statements
	 **********************/

	private function _calculateInputPlaceholders($columns)
	{
		// Make sure that we have at least one element
		if (!is_array($columns) || empty($columns) || count($columns) < 1)
		{
			Utility::throwException('Columns provided to calculate input placeholders are invalid.');
		}

		// Given the input array, just create an array that ranges from 1 to the number of items
		$numeric_array = range(1, count($columns));

		// For each value, precede the value with a dollar sign
		$numeric_array = array_map(
			function($num)
			{
				return '$' . $num;
			},
			$numeric_array
		);

		// Return the keyed array, with each parameter named by column
		return array_combine($columns, $numeric_array);
	}

	private function _escapeColumnNames($columns)
	{
		// Make sure that we have at least one element
		if (!is_array($columns) || empty($columns) || count($columns) < 1)
		{
			Utility::throwException('Columns provided are invalid.');
		}

		// For each value, wrap in double quotes
		$columns = array_map(
			function($column)
			{
				return '"' . $column . '"';
			},
			$columns
		);

		return $columns;
	}

	/***************
	 Parse SQL Input
	 ***************/

	private function _getPrimaryKeyFromInput($table_name, $keyed_array)
	{
		// Get the primary key
		$table_primary_key = self::_getTablePrimaryKey($table_name);

		// Find the primary key
		if (empty($keyed_array) || !array_key_exists($table_primary_key, $keyed_array))
		{
			Utility::throwException('Primary key value not found in input.');
		}

		return $keyed_array[$table_primary_key];
	}

	/******************
	 Run SQL Statements
	 ******************/

	/**
	 * (array) $columns - an array of columns
	 * (int) $primary_key_value - integer representing the primary key value
	 */
	private function _select($table_name, $columns, $primary_key_value)
	{
		// Get the schema and primary key
		$table_schema      = self::_getTableSchema($table_name);
		$table_primary_key = self::_getTablePrimaryKey($table_name);

		// Get the columns
		$parameter_columns = Validation::validateTableColumns($table_name, $table_schema, $columns);

		// Escape the column names
		$parameter_columns = self::_escapeColumnNames($parameter_columns);

		// Construct the primary key requirement
		$primary_key_value = intval($primary_key_value);

		// Clean the table name
		$table_name = self::_getCleanTableName($table_name);

		// Construct the SELECT sql
		$sql = '
			SELECT ' . implode(', ', $parameter_columns) . '
			FROM ' . $table_name . '
			WHERE ' . $table_primary_key . ' = $1;
		';

		// Sanitize and run the query
		$rows = Utility::pgQueryParams($sql, array($primary_key_value));

		// Validate result, check that primary key is set
		if (empty($rows) || !array_key_exists('0', $rows) || !array_key_exists($table_primary_key, $rows[0]))
		{
			Utility::throwException('Record not found for ' . $table_name . ' with a primary key value of ' . $primary_key_value);
		}

		// Return the only record
		return $rows[0];
	}

	/**
	 * (array) $keyed_array - a dictionary of columns => values
	 */
	private function _insert($table_name, $keyed_array)
	{
		// Get the schema and primary key
		$table_schema      = self::_getTableSchema($table_name);
		$table_primary_key = self::_getTablePrimaryKey($table_name);

		// Get the columns and values
		list($parameter_columns, $parameter_values) = Validation::validateTableInput($table_name, $table_schema, $table_primary_key, $keyed_array);

		// Escape the column names
		$parameter_columns = self::_escapeColumnNames($parameter_columns);

		// List the number of parameters - Function creates an array of range 1..n and prepends '$' to each number
		$parameter_placeholders = self::_calculateInputPlaceholders($parameter_columns);

		// Clean the table name
		$table_name = self::_getCleanTableName($table_name);

		// Construct the INSERT statement
		$sql = '
			INSERT INTO ' . $table_name . ' (' . implode(', ', $parameter_columns) . ')
			VALUES (' . implode(', ', $parameter_placeholders) . ')
			RETURNING *;
		';

		// Sanitize and run the query
		$rows = Utility::pgQueryParams($sql, $parameter_values);

		// Validate result, check that primary key is set
		if (empty($rows) || !array_key_exists('0', $rows) || !array_key_exists($table_primary_key, $rows[0]))
		{
			Utility::throwException('Record not found for ' . $table_name . ' with a primary key value of ' . $primary_key_value);
		}

		// Return the only record
		return $rows[0];
	}

	/**
	 * (array) $keyed_array - a dictionary of columns => values
	 */
	private function _update($table_name, $keyed_array)
	{
		// Get the schema and primary key
		$table_schema      = self::_getTableSchema($table_name);
		$table_primary_key = self::_getTablePrimaryKey($table_name);

		// Get the columns and values
		list($parameter_columns, $parameter_values) = Validation::validateTableInput($table_name, $table_schema, $table_primary_key, $keyed_array);

		// Escape the column names
		$parameter_columns = self::_escapeColumnNames($parameter_columns);

		// List the number of parameters - Function creates an array of range 1..n and prepends '$' to each number
		$parameter_placeholders = self::_calculateInputPlaceholders($parameter_columns);

		// Construct the proper UPDATE parameters statement
		array_walk($parameter_placeholders,
			function(&$value, $key)
			{ 
				$value = $key . ' = ' . $value;
			}
		);

		// Construct the primary key requirement
		$primary_key_placeholder = '$' . (count($parameter_placeholders) + 1);
		$primary_key_value       = self::_getPrimaryKeyFromInput($table_name, $keyed_array);

		// Clean the table name
		$table_name = self::_getCleanTableName($table_name);

		// Construct the UPDATE statement
		$sql = '
			UPDATE ' . $table_name . ' SET
			' . implode(', ', $parameter_placeholders) . '
			WHERE ' . $table_primary_key . ' = ' . $primary_key_placeholder . '
			RETURNING *;
		';

		// Append the primary key value to the parameter values
		$parameter_values[] = $primary_key_value;

		// Sanitize and run the query
		$rows = Utility::pgQueryParams($sql, $parameter_values);

		// Validate result, check that primary key is set
		if (empty($rows) || !array_key_exists('0', $rows) || !array_key_exists($table_primary_key, $rows[0]))
		{
			Utility::throwException('Record not found for ' . $table_name . ' with a primary key value of ' . $primary_key_value);
		}

		// Return the only record
		return $rows[0];
	}
}
