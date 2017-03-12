<?php

namespace Geppetto;

/**
 * Class: QueryRepository
 *
 * Available Databases:
 *  postgresql
 *
 * Available Queries:
 *  table_exists - Check for an existing table
 *  schema       - Get a table's schema
 *  primary_key  - Get the primary key of a table
 *  references   - Get all of this table's references and any references to this table
 *
 */

abstract class QueryRepository
{
	public static function getSql($command, $database = 'postgresql')
	{
		// Return the SQL for the database and command requests
		if (array_key_exists($database, self::$sql) && 
			array_key_exists($command, self::$sql[$database]))
		{
			return self::$sql[$database][$command];
		}
		else if (array_key_exists('all', self::$sql) && 
			array_key_exists($command, self::$sql['all']))
		{
			return self::$sql['all'][$command];
		}

		Utility::throwException('Requested SQL command not found');
	}

	private static $sql = array(
		'postgresql' => array(

			'primary_key' => '
				SELECT
					pg_attribute.attname AS primary_key
				FROM
					pg_index,
					pg_class,
					pg_attribute
				WHERE
					pg_class.oid = $1::regclass                AND
					indrelid = pg_class.oid                    AND
					pg_attribute.attrelid = pg_class.oid       AND
					pg_attribute.attnum = any(pg_index.indkey) AND
					indisprimary;
			'
		),

		'all' => array(

			'table_exists' => '
				SELECT EXISTS(
					SELECT
						table_name
					FROM
						information_schema.tables 
					WHERE
						table_name = $1
				) AS exists;
			',

			'schema' => '
				SELECT
					column_name,
					udt_name
				FROM
					information_schema.columns
				WHERE
					table_schema = \'public\' AND
					table_name = $1
				ORDER BY
					table_catalog, table_name, ordinal_position
			',

			'primary_key' => '
				SELECT
					c.column_name, c.data_type
				FROM
					information_schema.table_constraints tc 
				JOIN
					information_schema.constraint_column_usage AS ccu USING (constraint_schema, constraint_name)
				JOIN
					information_schema.columns AS c ON
						c.table_schema = tc.constraint_schema AND 
						tc.table_name = c.table_name AND ccu.column_name = c.column_name
				WHERE
					constraint_type = \'PRIMARY KEY\' AND
					tc.table_name = $1;
			',

			'references' => '
				SELECT
					DISTINCT tc.constraint_name,
					tc.table_name,
					ccu.table_name AS foreign_table_name,
					kcu.column_name,
					ccu.column_name AS foreign_column_name
				FROM
					information_schema.table_constraints tc
				JOIN 
					information_schema.key_column_usage kcu ON tc.constraint_name = kcu.constraint_name
				JOIN
					information_schema.constraint_column_usage ccu ON ccu.constraint_name = tc.constraint_name
				WHERE
					constraint_type = \'FOREIGN KEY\' AND 
					(ccu.table_name = $1 OR tc.table_name = $1)
				GROUP BY 
					tc.constraint_name, 
					tc.table_name,
					foreign_table_name,
					kcu.column_name,
					foreign_column_name;
			'
		)
	);
}
