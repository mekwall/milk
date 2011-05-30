<?php
namespace Milk\Core;

class Module {

}

namespace Milk\Core\Module;

use Milk\Core\Module,
	Milk\Core\Exception;

class Singleton extends Module {

	// Reference to current app
	private static $_app;
	
	// Instances
	private static $_instances = array();
	
	/**
		Constructor
			@protected
	**/
	protected function __construct() {}
	
	/**
		Get instance
			@public
	**/
	public static function getInstance($is_app=false, $args=null) {
		$class = get_called_class();
		if (!isset(self::$_instances[$class])) {
			if (!empty($args)) {
				$reflect  = new ReflectionClass($class);
				self::$_instances[$class] = $reflect->newInstanceArgs($args);
				unset($reflect);
			} else {
				self::$_instances[$class] = new $class;
			}
		}
		if ($is_app)
			self::$_app = self::$_instances[$class];
		return self::$_instances[$class];
	}
	
	/**
		Return current app instance
	**/
	public function app() {
		return self::$_app;
	}
	
	/**
		Prevent cloning of instance
	**/
    final public function __clone() {
        throw new Exception("Cloning of singleton object is not allowed");
    }
}