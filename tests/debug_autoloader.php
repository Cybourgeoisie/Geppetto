<?php

$autoloader = require "../vendor/autoload.php";
$results = $autoloader->findFile("\Geppetto\Object");
print "Found file for class at: $results";
print "\r\n";
