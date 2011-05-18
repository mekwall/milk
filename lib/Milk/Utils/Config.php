<?php
namespace Milk\Utils;

use \F3,
	StdClass;

class Config {

	static public $config;
	static public $mode_config;
	static private $file;
	static private $mode;
	static private $type;
	
	const PRODUCTION = 0;
	const STAGE = 1;
	const DEBUG = 2;
	
	static public function load($file, $mode=self::PRODUCTION) {
		if (!is_readable($file))
			return trigger_error("Config file is missing or can't be read");

		if ($mode > 0)
			F3::clear('MILK.CONFIG');
		
		if (F3::cached('MILK.CONFIG')) {
			$confobj = F3::get('MILK.CONFIG');
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
	
	static public function object2array($data){
		if(!is_object($data) && !is_array($data)) return $data;
		if(is_object($data)) $data = get_object_vars($data);
		return array_map('Milk\Utils\Config::object2array', $data);
	}
	
	static public function array2object($data){
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
	
	static public function get($var) {
		return eval("return self::\$mode_config->$var;");
	}
}