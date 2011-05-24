<?php
// Namespace of our app
namespace Blog;

// Define base path
define('BASE_PATH', realpath(__DIR__.'/..'));

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set("display_errors", 1);

// Include and instantiate our app
$app = require_once('/home/oddy/milk/lib/Milk/Core/Application.php');

// Let's do some magic chaining
$app
	// Add current namespace to loader
	->loader
		->addNamespace(
			__NAMESPACE__,
			realpath(BASE_PATH.'/app')
		)
		->end() // Return to app instance
	// Add a test route to dispatcher
	->dispatcher
		->addRoutes(array(
			"/foo" => function() { return "Foo"; }
		))
		->end() // Return to app instance
	// Run our app
	->run();