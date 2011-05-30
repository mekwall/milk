<?php
namespace Milk\Core;

use Milk\Core\Module,
	Milk\Core\Exception,
	Milk\Core\Cache\Backend;

class Cache extends Module\Singleton {
	
	// Backends
	private static $_backends = array();
	
	public static function loadBackends($backends) {
		foreach ($backends as $name => $backend) {
			$class = "Milk\\Core\\Cache\\Backend\\$backend";
			self::$_backends[$name] = new $class;
		}
	}

	public static function clear($key, $backend='default') {
		if (isset(self::$_backends[$backend]))
			return self::$_backends[$backend]->clear($key);
	}
	
	public static function exists($key, $backend='default') {
		if (isset(self::$_backends[$backend]))
			return self::$_backends[$backend]->exists($key);
	}
	
	public static function put($key, $val, $ttl=0, $backend='default') {
		if (isset(self::$_backends[$backend]))
			return self::$_backends[$backend]->put($key, $val, $ttl);
	}
	
	public static function get($key, $backend='default') {
		if (isset(self::$_backends[$backend]))
			return self::$_backends[$backend]->get($key);
	}
	
	public static function __callStatic($func, $arguments) {
		$obj = self::getInstance();
		return call_user_func_array($obj->$func, $arguments);
	}

}