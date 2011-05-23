<?php
// Include universal autoloader
include "/home/oddy/milk/lib/Milk/Core/Loader.php";
use Milk\Core\Loader;

// Setup paths to namespaces
Loader::addNamespaces(
	array(
		"Milk"	=> "/var/sites/milk/lib/Milk"
	)
);

use Milk\Core\Dispatcher,
	Milk\Core\Dispatcher\Route;

class DispatcherTestSimple extends PHPUnit_Extensions_OutputTestCase {

	public function testSimpleRoutesObject() {
		Dispatcher::addRoutes(
			array(
				new Route(
					"/hello",
					function(){ return "Hello world!"; }
				)
			)
		);
		Dispatcher::mock("GET /hello");
		Dispatcher::dispatch();
		$this->expectOutputString('Hello world!');
	}
	
	public function testSimpleRoutesArray() {
		$routes = array(
			"/hello"
				=> function(){ return "Hello world!"; }
		);
		Dispatcher::addRoutes($routes);
		Dispatcher::mock("GET /hello");
		Dispatcher::dispatch();
		$this->expectOutputString('Hello world!');
	}
	
	public function testSimpleRoutesYAML() {
		$yaml = <<<'YAML'
routes:
- pattern: /hello
  target: function(){ return "Hello world!"; } 
YAML;
		Dispatcher::addRoutes($yaml, Dispatcher::YAML);
		Dispatcher::mock("GET /hello");
		Dispatcher::dispatch();
		$this->expectOutputString('Hello world!');
	}
	
	public function testSimpleRoutesYAMLFile() {
		Dispatcher::addRoutes("simple.yaml", Dispatcher::FILE);
		Dispatcher::mock("GET /hello");
		Dispatcher::dispatch();
		$this->expectOutputString('Hello world!');
	}
	
	public function testSimpleRoutesJSON() {
		$json = json_encode(array(
			new Route(
				"/hello",
				'function(){ return _("Hello world!"); }'
			)
		));
		Dispatcher::addRoutes($json, Dispatcher::JSON);
		Dispatcher::mock("GET /hello");
		Dispatcher::dispatch();
		$this->expectOutputString('Hello world!');
	}
	
	public function testSimpleRoutesJSONFile() {
		Dispatcher::addRoutes("simple.json", Dispatcher::FILE);
		Dispatcher::mock("GET /hello");
		Dispatcher::dispatch();
		$this->expectOutputString('Hello world!');
	}
}
