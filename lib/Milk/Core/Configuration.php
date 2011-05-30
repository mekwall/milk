<?php
namespace Milk\Core;

use \StdClass,
	\ArrayAccess,
	\Iterator,
	\IteratorAggregate,
	\ArrayIterator;

// Dependencies
use Milk\Core\Module,
	Milk\Core\Exception,
	Milk\Core\Cache,
	Milk\Core\Translation;

class Configuration extends Module\Singleton {

	public static $current;
	public static $settings = array(
		'default' => array()
	);
	
	private static $group = "default";
	private static $file;
	private static $type;
	
	public $loaded = false;
	
	/**
		Set default configuration settings
			@param $defaults array
	**/
	public function setDefaults($defaults) {
		self::$settings['default'] = array_merge(
			self::$settings['default'], $defaults);
			
		return $this;
	}
	
	/**
		Set current configuration group
			@param $group string
	**/
	public function setGroup($group) {
		if ($group !== 'default')
			self::$current = new Settings(array_merge(self::$settings['default'], self::$settings[$group]));
		else
			self::$current = new Settings(self::$settings['default']);
			
		$this->loaded = true;
		return $this;
	}
	
	/**
		Load configuration settings from file
			@param $file string
			@param $mode
	**/
	public function loadFile($file) {
		if (!is_readable($file))
			throw Exception( _("Config file is missing or could not be read") );

		self::$type = substr($file, strrpos($file, '.')+1);
		
		switch (self::$type) {
			case 'yaml':
				$confobj = yaml_parse_file($file);
			break;
		
			case 'json':
				$data = file_get_contents($file);
				$confobj = json_decode($data, true);
			break;
			
			default: 
				return trigger_error("Unknown config file type");
		}
	
		self::$file = $file;
		self::$settings = array_merge(self::$settings, $confobj);
	}

	public function saveFile($type='') {
		if (empty($type))
			$type = self::$type;
		switch ($type) {
			case 'json':
				$json = json_encode(self::$config);
				file_put_contents(APP_PATH.'/config.json', $json);
			break;
			
			case 'yaml':
				$yaml = yaml_emit(self::object2array(self::$config));
				file_put_contents(APP_PATH.'/config.yaml', $yaml);
			break;
			
			default: 
				return trigger_error("Unknown config file type");
		}
		return true;
	}
	
	public function __get($var) {
		if (isset(self::$current->$var))
			return self::$current->$var;
		else if (isset($this->$var))
			return $this->$var;
	}
	
	public function __isset($var) {
		if (isset(self::$current->$var))
			return true;
		else if (isset($this->$var))
			return true;
		return false;
	}
	
	public static function __callStatic($func, $arguments) {
		$obj = self::getInstance();
		return call_user_func_array($obj->$func, $arguments);
	}
	
	/**
		Static access to the configuration
			@todo We all hate eval() so let's find a better solution
	**/
	public static function get($var) {
		return eval("return self::\$current->$var;");
	}
}

class Settings implements ArrayAccess, Iterator {

	private $position = 0;
	private $settings = array();

    public function __construct(array $settings) {
		$this->position = 0;
		$this->settings = $settings;
	}
	
	public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->settings[] = $value;
        } else {
            $this->settings[$offset] = $value;
        }
    }
	
    public function offsetExists($offset) {
        return isset($this->settings[$offset]);
    }
	
    public function offsetUnset($offset) {
        unset($this->settings[$offset]);
    }
	
    public function offsetGet($offset) {
		if (!isset($this->settings[$offset]))
			return null;
			
		if (is_array($this->settings[$offset]))
			$this->settings[$offset] = new self($this->settings[$offset]);
	
        return $this->settings[$offset];
    }

    public function rewind() {
       return reset($this->settings);
    }

    public function current() {
		return current($this->settings);
    }

    public function key() {
		return key($this->settings);
    }

    public function next() {
        return next($this->settings);
    }

    public function valid() {
        return key($this->settings) !== null;
    }
	
	public function __get($var) {
		if (isset($this->settings[$var])) {
			if (is_array($this->settings[$var]))
				$this->settings[$var] = new self($this->settings[$var]);
			return $this->settings[$var];
		}
	}
	
	public function __isset($var) {
		return isset($this->settings[$var]);
	}
}