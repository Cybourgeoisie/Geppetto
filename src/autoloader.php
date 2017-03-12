<?php

/**
 * Program Autoloader - find and load in the Geppetto classes
 */

// Define the autoloader
spl_autoload_register(function($class_name) {
	if (!function_exists('findFileRecursively'))
	{
		function findFileRecursively(DirectoryIterator $dir, string $class_name, string $namespace)
		{
			foreach ($dir as $fileinfo)
			{
				// Skip over parent and current directory
				if ($fileinfo->isDot()) continue;

				// If a directory, iterate through
				if ($fileinfo->isDir())
				{
					// Iterate through the subfolders
					$dirpath = $fileinfo->getPathname();

					// Inception
					$result = findFileRecursively(new DirectoryIterator($dirpath), $class_name, $namespace);

					if (!empty($result))
						return $result;
				}
				// If a file, compare to the class we're looking for
				else if ($fileinfo->isFile())
				{
					$filename = strtolower($fileinfo->getFilename());
					
					if (!empty($namespace))
					{
						if ($filename == $class_name . '.' . $namespace . '.php')
						{
							return $fileinfo->getPathname();
						}
					}
					else
					{
						if ($filename == $class_name . '.php')
						{
							return $fileinfo->getPathname();
						}
					}
				}
			}

			return NULL;
		}
	}

	// Break down namespaces to get the class name
	$parts      = explode('\\', $class_name);
	$namespace  = (count($parts) > 1) ? strtolower(reset($parts)) : '';
	$class_name = strtolower(end($parts));

	$file = findFileRecursively(new DirectoryIterator(dirname(__FILE__)), $class_name, $namespace);
	if (!empty($file))
	{
		require_once($file);
	}
});
