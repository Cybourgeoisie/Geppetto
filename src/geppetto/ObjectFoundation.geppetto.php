<?php

namespace Geppetto;

/**
 * ObjectFoundation
 */

abstract class ObjectFoundation extends Foundation
{
	/*
	 * Interfaces
	 */

	public function getTableName()
	{
		return $this->_table_name;
	}

	public function getTableSchema()
	{
		return $this->_table_schema;
	}

	public function getTablePrimaryKey()
	{
		return $this->_table_primary_key;
	}

	public function getTableReferences()
	{
		return $this->_table_references;
	}

	/*
	 * Handle Table References
	 */

	public function isTableReference($name)
	{
		// Parse the class name
		$parsed_table_name = $this->_deriveTableName($name);

		// Check that it is a reference
		return array_key_exists($parsed_table_name, $this->_table_references);
	}

	public function getTableReference($name)
	{
		// Parse the class name
		$parsed_table_name = $this->_deriveTableName($name);

		// Verify that we have a value
		if (array_key_exists($parsed_table_name, $this->_table_references))
		{
			// Get the references
			return $this->_table_references[$parsed_table_name];
		}

		Utility::throwException('Parameter (' . $name . ') is not a valid reference for table (' . $this->_table_name . ')');
	}

	private function _getClassNameFromReference($name)
	{
		// IAS-specific
		if (class_exists($name . 'Dao') && is_subclass_of($name . 'Dao', '\Geppetto\Object'))
		{
			// Create the class name
			$class_name = $name . 'Dao';
		}
		// Generic
		else if (class_exists($name) && is_subclass_of($name, '\Geppetto\Object'))
		{
			// Create the class name
			$class_name = $name;
		}
		else
		{
			Utility::throwException('Could not find the corresponding class for name (' . $name . ') or parsed name (' . $parsed_table_name . ')');
		}

		return $class_name;
	}

	public function getTableReferenceObject($name)
	{
		// Parse the class name
		$parsed_table_name = $this->_deriveTableName($name);

		// Verify that we have a value
		if (array_key_exists($parsed_table_name, $this->_table_references))
		{
			// Get the references
			$references = $this->_table_references[$parsed_table_name];

			// Determine which class name we're working with - either with or without Dao at the end
			$class_name = $this->_getClassNameFromReference($name);

			// Two cases: the current table is the reference table, or otherwise
			// First: Handle if the current table is the reference table
			if ($this->_table_name == $references[0]['reference_table'])
			{
				// Get the ID from this table
				$sql = 'SELECT ' . $references[0]['column_name'] . ' AS id FROM ' . $references[0]['reference_table'] .
					' WHERE ' . $references[0]['reference_table_primary_key'] . ' = $1';

				// This is the one-to-one case
				$one_or_many = 'one';

				// Get the ID from this object
				$class_id_name = $references[0]['reference_table_primary_key'];
			}
			// Second: Handle if the current table is being referenced
			else
			{
				// Get the ID from the reference table
				$sql = 'SELECT ' . $references[0]['reference_table_primary_key'] . ' AS id FROM ' . $references[0]['reference_table'] .
					' WHERE ' . $references[0]['column_name'] . ' = $1';

				// This is the one-to-many case
				$one_or_many = 'many';

				// Get the ID name from this object
				$class_id_name = $references[0]['column_name'];
			}

			// Validate the class ID
			$class_id = $this->$class_id_name;
			if (!$class_id) return null;

			// Get all rows
			$rows = Utility::pgQueryParams($sql, array($class_id));
			if (!$rows) return null;

			// For each ID, return the instance
			$objects = array();
			foreach ($rows as $row)
			{
				$objects[] = $class_name::find($row['id']);
			}

			// Return the instance(s)
			return $one_or_many == 'one' ? $objects[0] : $objects;
		}

		Utility::throwException('Parameter (' . $name . ') is not a valid reference for table (' . $this->_table_name . ')');
	}

/***************
 Private Methods
 ***************/
	public function __construct()
	{
		// Get the table name and columns
		$this->_table_name        = $this->_getTableNameFromClassName();
		$this->_table_schema      = $this->_getTableSchema($this->_table_name);
		$this->_table_primary_key = $this->_getTablePrimaryKey($this->_table_name);
		//$this->_table_references  = $this->_getTableReferences($this->_table_name);
	}

	private function _getTableNameFromClassName()
	{
		// Get the active class's name using late static bindings
		$class_name = get_called_class();

		// Parse the class name
		$table_name = $this->_deriveTableName($class_name);

		// Validate that the table exists
		if (!Validation::validateTableName($table_name))
		{
			Utility::throwException('Table (' . $table_name . ') could not be found.');
		}

		return $table_name;
	}

	private function _deriveTableName($name)
	{
		// Precede each capital letter with an underscore
		$parsed_name = preg_replace('/([A-Z])/', '_${1}', $name);

		// Remove the initial underscore if one exists
		$parsed_name = preg_replace('/^_/', '', $parsed_name);

		// Convert to all lowercase letters
		$table_name = strtolower($parsed_name);

		return $table_name;
	}
}
