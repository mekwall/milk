<?php
namespace Milk\Core;

use Milk\Core\Dispatcher;

/**
	Core Application
	This is a singleton class
		@public
**/
class Application {

	protected static $_instance = null;
	
	private $_vars = array();
	
	/**
		Get instance
			@public
	**/
	public static function getInstance() {
		if (!self::$_instance)
			self::$_instance = new __CLASS__;
		return self::$_instance;
	}

	/**
		Bootstrapper
			@protected
	**/
	protected function bootstrap() {
		// Define default path constants
		if (!defined('APP_PATH') && defined('BASE_PATH')) {
			define('APP_PATH', realpath(BASE_PATH.'/app'));
			if (!defined('CACHE_PATH'))
				define('CACHE_PATH', realpath(APP_PATH.'/Cache'));
			
			if (!defined('LOG_PATH'))	
				define('LOG_PATH', realpath(APP_PATH.'/Logs'));
			
			if (!defined('TPL_PATH'))
				define('TPL_PATH', realpath(APP_PATH.'/Templates'));
			
			if (!defined('TMP_PATH'))
				define('TMP_PATH', realpath(BASE_PATH.'/tmp'));
				
			if (!defined('BIN_PATH'))
				define('BIN_PATH', realpath(BASE_PATH.'/bin'));
				
			if (!defined('LIB_PATH'))
				define('LIB_PATH', realpath(BASE_PATH.'/lib'));
				
			if (!defined('STATIC_PATH'))	
				define('STATIC_PATH', realpath(BASE_PATH.'/static'));
		}
	}

	/**
		Run application
			@public
	**/
	public function run() {
		$this->bootstrap();
	}
	
	/**
		Run application
	**/
	public function __get($key) {
		if (isset($this->_vars[$key]))
			return $this->_vars[$key];
		else
			return;
	}
	
	public function __set($key, $value) {
		$this->_vars[$key] = $value;
	}
}

return Application::getInstance();