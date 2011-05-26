<?php
namespace Milk\Core;

class Cache {

	// Reference to current app
	private static $_app;
	
	// Instance
	private static $_instance;
	
	/**
		Get instance
			@public
	**/
	public static function getInstance($app=null) {
		if (!self::$_instance)
			self::$_instance = new self;
		self::$_app = &$app;
		return self::$_instance;
	}
	
	/**
		Return current app instance to allow chaining
	**/
	public function end() {
		return self::$_app;
	}

	public function clear($var) {
		return true;
	}
	
	public function cached($var) {
		return false;
	}


}