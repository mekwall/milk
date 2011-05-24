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

use Milk\Utils\Strings,
	Milk\Utils\Strings\Slug,
	Milk\Utils\Strings\Inflector;
	
class StringsTest extends PHPUnit_Extensions_OutputTestCase {

	public function testStringsCapitalize() {
		echo Strings::capitalize('hello world!');
		$this->expectOutputString('Hello World!');
	}
	
	public function testStringsRandom() {
		echo Strings::random(10);
	}
	
	public function testStringsUnderscore() {
		echo Strings::underscore('HelloWorld');
		$this->expectOutputString('hello_world');
	}

	public function testStringsCamelize() {
		echo Strings::camelize('hello_world');
		$this->expectOutputString('HelloWorld');
	}
	
	public function testSlugMake() {
		echo Slug::make("Hëllö Wörld! I höpe you feel good? &_ ßü");
		$this->expectOutputString('helloe-woerld-i-hoepe-you-feel-good-ssue');
	}
}