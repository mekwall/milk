<?php
/**
    Setup base path
**/
define('BASE_PATH', 	realpath(__DIR__.'/../'));

error_reporting(E_ALL);
ini_set("display_errors", 1);

// Include universal autoloader
include "/home/oddy/milk/lib/Milk/Core/Loader.php";
use Milk\Core\Loader;

// Setup paths to namespaces
Loader::addNamespaces(
	array(
		"Milk"	=> "/var/sites/milk/lib/Milk",
		"App"	=> "/var/sites/milk/examples/blog/app"
	)
);

use Milk\Core\Dispatcher;

// Add routes with file/url
Dispatcher::addRoutes(APP_PATH."/urls.yaml", Dispatcher::FILE);

// Dispatch request
Dispatcher::dispatch();