<?php
namespace Milk\Core;

if (!class_exists("Loader"))
	require_once __DIR__."/Loader.php";

use Milk\Core\Dispatcher,
	Milk\Core\Config,
	Milk\Core\Exception;

/**
	Core Application
	This is a singleton class
		@public
**/
class Application {

	/**
		Variable to hold instance
	**/
	protected static $_instance = null;
	
	/**
		Shared variables
	**/
	private $_vars = array();
	
	/**
		Get instance
			@public
	**/
	public static function getInstance() {
		if (!self::$_instance)
			self::$_instance = new self;
		return self::$_instance;
	}

	/**
		Bootstrapper
			@protected
	**/
	protected function bootstrap() {
		// Enable error reporting
		error_reporting(E_ALL);
		ini_set("display_errors", 1);
	
		// Define default path constants
		if (!defined('APP_PATH') && defined('BASE_PATH')) {
			define('APP_PATH', realpath(BASE_PATH.'/app'));
			if (!defined('CACHE_PATH'))
				define('CACHE_PATH', realpath(APP_PATH.'/Cache'));
			
			if (!defined('LOG_PATH'))	
				define('LOG_PATH', realpath(APP_PATH.'/Logs'));
				
			if (!defined('MODEL_PATH'))
				define('MODEL_PATH', realpath(APP_PATH.'/Models'));
			
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
		
		// Load config if exist
		
		
		// Load routes from files if they exist
		if (is_readable(APP_PATH."/urls.yaml"))
			Dispatcher::addRoutes(APP_PATH."/urls.yaml", Dispatcher::FILE);
		else if (is_readable(APP_PATH."/urls.json"))
			Dispatcher::addRoutes(APP_PATH."/urls.json", Dispatcher::FILE);
	}

	/**
		Run application
			@public
	**/
	public function run() {
		// Dispatch request
		Dispatcher::dispatch();
	}
	
	/**
		Constructor
	**/
	public function __construct() {
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