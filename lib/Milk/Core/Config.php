<?php
namespace Milk\Core;

use \StdClass;

use Milk\Core\Exception,
	Milk\Core\Cache,
	Milk\Core\Translation;

class Config {

	static public $config;
	static public $mode_config;
	
	static private $file;
	static private $mode;
	static private $type;
	
	const PRODUCTION = 0;
	const STAGE = 1;
	const DEBUG = 2;
	
	public static function load($file, $mode=self::PRODUCTION) {
		if (!is_readable($file))
			throw Exception( _("Config file is missing or could not be read") );

		if ($mode > 0)
			Cache::clear('MILK.CONFIG');
		
		if (Cache::cached('MILK.CONFIG')) {
			$confobj = Cache::get('MILK.CONFIG');
		} else {
			self::$type = substr($file, strrpos($file, '.')+1);
			
			switch (self::$type) {
				case 'json':
					$data = file_get_contents($file);
					$confobj = json_decode($data);
				break;
				
				case 'yaml':
					$confobj = (object)yaml_parse_file($file);
				break;
				
				default: 
					return trigger_error("Unknown config file type");
			}
			
			if ($mode > 0)
				F3::set('MILK.CONFIG', $confobj, TRUE);
		}
		
		switch ($mode) {
			case self::PRODUCTION:
				$conf = self::array2object($confobj->production);
			break;
			
			case self::STAGE:
				$conf = self::array2object(array_merge((array)$confobj->production, (array)$confobj->stage));
			break;
			
			case self::DEBUG:
				$conf = self::array2object(array_merge((array)$confobj->production, (array)$confobj->stage, (array)$confobj->debug));
			break;
		}

		self::$file = $file;
		self::$mode = $mode;
		self::$config = $confobj;
		self::$mode_config = $conf;
	}
	
	public static function save($type='') {
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
	
	public static function object2array($data){
		if(!is_object($data) && !is_array($data)) return $data;
		if(is_object($data)) $data = get_object_vars($data);
		return array_map('Milk\Utils\Config::object2array', $data);
	}
	
	public static function array2object($data){
		if(!is_array($data)) return $data;
		$object = new StdClass();
		if (is_array($data) && count($data) > 0) {
			foreach ($data as $name=>$value) {
				$name = strtolower(trim($name));
				if (!empty($name)) {
					$object->$name = self::array2object($value);
				}
			}
		}
		return $object;
	}
	
	public static function get($var) {
		return eval("return self::\$mode_config->$var;");
	}
}