<?php
namespace Milk\Core;

// If not already loaded, load the loader ;)
if (!class_exists("Loader"))
	require_once __DIR__."/Loader.php";

// Dependencies
use Milk\Core\Module,
	Milk\Core\Exception,
	Milk\Core\Configuration,
	Milk\Core\Translation,
	Milk\Core\Dispatcher,
	Milk\Core\Cache,
	Milk\DB;

/**
	Core Application
	This is a singleton class
		@public
**/
class Application extends Module\Singleton {
	
	/**
		Shared variables
	**/
	private $_vars = array();

	/**
		Bootstrapper
			@protected
	**/
	protected function bootstrap() {
		// Define default path constants
		// @todo do we really want to use definitions?
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
		
		// Reference to loader instance
		$this->loader = Loader::getInstance();
		
		// Reference to config instance
		$this->config = Configuration::getInstance();

		// Reference to dispatcher instance
		$this->dispatcher = Dispatcher::getInstance();
		
		// Reference to config instance
		$this->cache = Cache::getInstance();
		
		// Set default config
		$this->config->setDefaults(array(
			'debug' => false,
			'default_charset' => 'utf-8',
			'default_content_type' => 'text/html',
			'default_from_email' => 'webmaster@'.$_SERVER['SERVER_NAME'],
			'languages' => array(
				'en' => array(
					'name' => _('English'),
					'locale' => array(
						'all' => 'en_US.UTF-8',
						'textdomain' => 'milk'
					)
				)
			),
			'language_cookie_name' => 'milk_language',
			'cookie' => array(
				'lifetime' => 1200,
				'domain' => $_SERVER['SERVER_NAME']
			),
			'session' => array(
				'lifetime' => 1200,
				'domain' => $_SERVER['SERVER_NAME']
			),
			'cache' => array(),
			'database' => array(),
			'middleware' => array()
		));
		
		// Load config from file if exist
		if (is_readable(APP_PATH.'/config.yaml'))
			$this->config->loadFile(APP_PATH.'/config.yaml');
		else if (is_readable(APP_PATH.'/config.json'))
			$this->config->loadFile(APP_PATH.'/config.json');
	}
	
	/**
		Run application
			@public
	**/
	public function run() {
		// Set default configuration group if not set
		if ($this->config->loaded === false)
			$this->config->setGroup('default');
			
		// Load cache backends
		if (isset($this->config->cache)) {
			$this->cache->loadBackends($this->config->cache);
			// Load routes from cache
			if ($routes = $this->cache->get('Application::$routes')) {
				$this->dispatcher->addRawRoutes($routes);
			} else {
				// Load routes from file if exist
				if (is_readable(APP_PATH.'/urls.yaml'))
					$this->dispatcher->addRoutes(APP_PATH.'/urls.yaml', Dispatcher::FILE);
				else if (is_readable(APP_PATH."/urls.json"))
					$this->dispatcher->addRoutes(APP_PATH.'/urls.json', Dispatcher::FILE);

				$this->cache->put('Application::$routes', $this->dispatcher->getRoutes());
			}
		} else {
			// Load routes from file if exist
			if (is_readable(APP_PATH.'/urls.yaml'))
				$this->dispatcher->addRoutes(APP_PATH.'/urls.yaml', Dispatcher::FILE);
			else if (is_readable(APP_PATH."/urls.json"))
				$this->dispatcher->addRoutes(APP_PATH.'/urls.json', Dispatcher::FILE);
		}
		
		// Make sure we have a default timezone set
		if (isset($this->config->timezone))
			$default_timezone = $this->config->timezone;
		elseif (ini_get('date.timezone') == '')
			$default_timezone = 'Europe/London';
		date_default_timezone_set($default_timezone);
		
		// Load database backends
		if (isset($this->config->database)) {
			$this->db = DB\Backend::getInstance();
			foreach ($this->config->database as $engine) {
				$this->db->load($engine);
			}
		}
	
		// Dispatch request
		$this->dispatcher->dispatch();
	}
	
	/**
		Constructor
			@protected
	**/
	protected function __construct($defaults=null) {
		$this->bootstrap();
	}
	
	/**
		Get variable
			@public
	**/
	public function __get($key) {
		if (isset($this->_vars[$key]))
			return $this->_vars[$key];
		else
			return;
	}
	
	/**
		Set variable
			@public
	**/
	public function __set($key, $value) {
		$this->_vars[$key] = $value;
	}
}

return Application::getInstance(true);