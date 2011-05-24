<?php
// Namespace of our app
namespace Blog;

// Include Milk
require '/home/oddy/milk/lib/Milk/Core/Application.php';

// Shorthand access to core modules
use Milk\Core\Application,
	Milk\Core\Loader,
	Milk\Core\Dispatcher;

// Define base path
define('BASE_PATH', realpath(__DIR__.'/..'));

// Setup path to current namespace
Loader::addNamespace(__NAMESPACE__,
	realpath(BASE_PATH.'/app'));

// Instantiate our app
$app = new Application;

Dispatcher::addRoutes(
	array('/article' => 'Blog\Views\Article::read')
);

$app->run();