<?php

namespace Geppetto;

/**
 * Object
 */

abstract class Object extends ObjectFoundation
{
	protected $table       = null;    // Table name
	protected $primary_key = null;    // Primary key column name
	protected $schema      = array(); // Table schema
	protected $references  = array(); // Table references
	protected $record      = array(); // Row data
	protected $dirty       = array(); // Dirty columns

/**************
 Public Methods
 **************/

	public function save()
	{
		if (empty($this->dirty))
		{
			return;	// Nothing to do
		}

		// Now insert or update depending on $this->primary_key.
		if (isset($this->record[$this->primary_key]))
		{
			// Update this record
			$new_record = $this->update($this->record);
		}
		else
		{
			// Create this record
			$new_record = $this->insert($this->record);
		}

		// Replace the record
		$this->hydrate($new_record);
	}

	public function delete($hard_delete = false)
	{
		// Make sure that the record is currently saved
		if (!isset($this->record[$this->primary_key]))
		{
			return;	// Nothing to do
		}

		if ($hard_delete)
		{
			// Do a hard delete.. eventually
		}
		else if (array_key_exists('status', $this->schema) && array_key_exists('status', $this->record))
		{
			$this->__set('status', false);
			$this->save();
		}
	}

	public function saved()
	{
		return isset($this->record[$this->primary_key]) && $this->record[$this->primary_key];
	}

	public function toArray()
	{
		return $this->record;
	}

	public function load($id)
	{
		// Get the record		
		$record = $this->selectAll($id);

		// Ensure we have data
		if (empty($record) || !array_key_exists($this->primary_key, $record))
		{
			Utility::throwException('Could not find the requested record.');
		}

		// Fill in the data
		$this->hydrate($record);
	}

/*********************
 Public Static Methods
 *********************/

	public static function find($id)
	{
		// Create a new object of the child, load it, and ship it off
		$object = new static();
		$object->load($id);
		return $object;
	}

/*************
 Magic Methods
 *************/

	public function __construct()
	{
		// Get the parent information
		parent::__construct();

		$this->table       = $this->getTableName();
		$this->schema      = $this->getTableSchema();
		$this->primary_key = $this->getTablePrimaryKey();
		//$this->references  = $this->getTableReferences();
	}

	public function __get($column)
	{
		// Get properties directly from the schema
		if (isset($this->schema[$column]))
		{
			if (isset($this->record[$column]))
			{
				return $this->record[$column];
			}
			else
			{
				return null;
			}
		}

		/*if ($this->isTableReference($column))
		{
			return $this->getTableReferenceObject($column);
		}*/

		Utility::throwException('No column named: ' . $column);
	}

	public function __set($column, $value)
	{
		// Check that this column is either a column in the schema, or is an object referred to by this table
		if (!isset($this->schema[$column]))
		{
			Utility::throwException('No column named: ' . $column);
		}

		// Don't allow editing the primary key
		if ($column == $this->primary_key)
		{
			Utility::throwException('Cannot edit primary key column.');
		}

		if (isset($this->record[$column]) && $this->record[$column] === $value)
		{
			// No reason to update
			return;
		}

		// Flag column as being dirty and update the value
		$this->dirty[$column]  = true;
		$this->record[$column] = $value;
	}

	/*public function __set($column, $value)
	{
		// Check that this column is either a column in the schema, or is an object referred to by this table
		if (!isset($this->schema[$column]) && !is_object($value) && !is_subclass_of($value, '\Geppetto\Object'))
		{
			Utility::throwException('No column named: ' . $column);
		}
		else if (is_object($value) && is_subclass_of($value, '\Geppetto\Object') && !$this->isTableReference($column))
		{
			Utility::throwException('No reference to parameter named: ' . $column);
		}

		// Don't allow editing the primary key
		if ($column == $this->primary_key)
		{
			Utility::throwException('Cannot edit primary key column.');
		}

		// If this is a reference to another object, set the correct value
		if ($this->isTableReference($column) && $value->saved())
		{
			// Get the reference information
			$reference = $this->getTableReference($column);

			// Update column and value to the foreign key column and value
			$column = $reference[0]['column_name'];
			$value  = $value->$reference[0]['foreign_column_name'];
		}
		// If this wasn't saved, warn the user
		else if ($this->isTableReference($column) && !$value->saved())
		{
			Utility::throwException('Cannot set nonexistent DAO record.');
		}

		if (isset($this->record[$column]) && $this->record[$column] === $value)
		{
			// No reason to update
			return;
		}

		// Flag column as being dirty and update the value
		$this->dirty[$column]  = true;
		$this->record[$column] = $value;
	}*/

/***************
 Private Methods
 ***************/

	protected function hydrate($row)
	{
		$this->record = $row;
		$this->dirty = array();
	}
}