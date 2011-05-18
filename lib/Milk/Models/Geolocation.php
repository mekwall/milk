<?php
namespace Milk\Models\Geolocation;

use \F3;

use \Milk\Models\BaseModel;

/**

-- 
-- Structure for table `geo_continents`
-- 

DROP TABLE IF EXISTS `geo_continents`;
CREATE TABLE IF NOT EXISTS `geo_continents` (
  `code` char(2) NOT NULL COMMENT 'Continent code',
  `name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


**/

class Continents extends BaseModel {

	protected static $table = "geo_continents";
	
	public function getCountries() {}
	
}

/**

-- 
-- Structure for table `geo_countries`
-- 

DROP TABLE IF EXISTS `geo_countries`;
CREATE TABLE IF NOT EXISTS `geo_countries` (
  `code` char(2) NOT NULL COMMENT 'Two-letter country code (ISO 3166-1 alpha-2)',
  `name` varchar(255) NOT NULL COMMENT 'English country name',
  `full_name` varchar(255) NOT NULL COMMENT 'Full English country name',
  `iso3` char(3) NOT NULL COMMENT 'Three-letter country code (ISO 3166-1 alpha-3)',
  `number` smallint(3) unsigned zerofill NOT NULL COMMENT 'Three-digit country number (ISO 3166-1 numeric)',
  `continent_code` char(2) NOT NULL,
  PRIMARY KEY (`code`),
  KEY `continent_code` (`continent_code`),
  CONSTRAINT `fk_countries_continents` FOREIGN KEY (`continent_code`) REFERENCES `geo_continents` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

**/

class Country extends BaseModel {

	protected static $table = "geo_countries";
	
	static function byContinent() {}
	
	public function getPlaces() {}

}

/**

-- 
-- Structure for table `geo_admin1`
-- 

DROP TABLE IF EXISTS `geo_admin1`;
CREATE TABLE IF NOT EXISTS `geo_admin1` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` char(8) NOT NULL,
  `name` varchar(64) NOT NULL,
  `country_code` char(2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `country_code` (`country_code`),
  KEY `code` (`code`),
  CONSTRAINT `fk_admin1_countries` FOREIGN KEY (`country_code`) REFERENCES `geo_countries` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

**/

class Admin1 extends BaseModel {

	protected static $table = "geo_admin1";
	
	public static function byId($id) {
		$obj = new self;
		$obj->load("`id`=$id");
		if ($obj->id > 0)
			return $obj;
		return false;
	}

	public static function byName($name, $country) {
		$obj = new self;
		$obj->load("`name`='$name' AND `country_code`='$country'");
		if ($obj->id > 0)
			return $obj;
		return false;
	}
	
	public static function byCode($code, $country) {
		$obj = new self;
		$code = (int)$code;
		$obj->load("`code`=$code AND `country_code`='$country'");
		if ($obj->id > 0)
			return $obj;
		return false;
	}
	
	public static function byCodeAlpha($code, $country) {
		$obj = new self;
		$obj->load("`code_alpha`='$code' AND `country_code`='$country'");
		if ($obj->id > 0)
			return $obj;
		return false;
	}
	
	public static function getList($id_list=false) {
		/*
		if ($id_list && F3::cached('MILK.GEOLOCATION.ADMIN1_ID'))
			$cached = F3::get('MILK.GEOLOCATION.ADMIN1_ID');
		elseif (F3::cached('MILK.GEOLOCATION.ADMIN1'))
			$cached = F3::get('MILK.GEOLOCATION.ADMIN1');
		*/
			
		if (!empty($cached) && (count($cached) > 0))
			return $cached;

		$obj = new self;
		$result = $obj->lookup('*', null, null, 'name');
		
		if ($id_list) {
			$list = array();
			foreach ($result as $row) {
				$row = (object)$row;
				$id = $row->id;
				unset($row->id);
				$list[$id] = (object)$row;
			}
			F3::set('MILK.GEOLOCATION.ADMIN1_ID', $list, 3200);
		}
		F3::set('MILK.GEOLOCATION.ADMIN1', $result, 3200);
		return $id_list ? $list : $result;
	}
}

/**

-- 
-- Structure for table `geo_admin2`
-- 

DROP TABLE IF EXISTS `geo_admin2`;
CREATE TABLE IF NOT EXISTS `geo_admin2` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` char(8) NOT NULL,
  `name` varchar(64) NOT NULL,
  `country_code` char(2) NOT NULL,
  `admin1_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `country_code` (`country_code`),
  KEY `admin1_id` (`admin1_id`),
  KEY `code` (`code`),
  CONSTRAINT `fk_admin2_countries` FOREIGN KEY (`country_code`) REFERENCES `geo_countries` (`code`),
  CONSTRAINT `fk_admin2_admin1` FOREIGN KEY (`admin1_id`) REFERENCES `geo_admin1` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

**/

class Admin2 extends BaseModel {

	protected static $table = "geo_admin2";
	
	public static function byId($id) {
		$obj = new self;
		$obj->load("`id`=$id");
		if ($obj->id > 0)
			return $obj;
		return false;
	}

	public static function byName($name, $country) {
		$obj = new self;
		$obj->load("`name`='$name' AND `country_code`='$country'");
		if ($obj->id > 0)
			return $obj;
		return false;
	}
	
	public static function byCode($code, $country) {
		$obj = new self;
		$obj->load("`code`='$code' AND `country_code`='$country'");
		if ($obj->id > 0)
			return $obj;
		return false;
	}
	
	public static function getList($id_list=false) {
	
		if ($id_list && F3::cached('MILK.GEOLOCATION.ADMIN2_ID'))
			$cached = F3::get('MILK.GEOLOCATION.ADMIN2_ID');
		elseif (F3::cached('MILK.GEOLOCATION.ADMIN2'))
			$cached = F3::get('MILK.GEOLOCATION.ADMIN2');

		if (!empty($cached) && (count($cached) > 0))
			return $cached;
			
		$obj = new self;
		$result = $obj->lookup('*', null, null, 'name');
		
		if ($id_list) {
			$list = array();
			foreach ($result as $row) {
				$row = (object)$row;
				$id = $row->id;
				unset($row->id);
				$list[$id] = $row;
			}
			F3::set('MILK.GEOLOCATION.ADMIN2_ID', $list, 3200);
		}
		
		F3::set('MILK.ADMIN2', $result, 3200);
		return $id_list ? $list : $result;
	}
}

/**

-- 
-- Structure for table `geo_places`
-- 

DROP TABLE IF EXISTS `geo_places`;
CREATE TABLE IF NOT EXISTS `geo_places` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `country_code` char(2) NOT NULL,
  `admin1_id` int(11) unsigned NOT NULL,
  `admin2_id` int(11) unsigned NOT NULL,
  `name` varchar(64) NOT NULL,
  `longitude` float(16,14) NOT NULL,
  `latitude` float(16,14) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `country_code` (`country_code`),
  KEY `admin1_id` (`admin1_id`),
  KEY `admin2_id` (`admin2_id`),
  CONSTRAINT `fk_places_admin1` FOREIGN KEY (`admin1_id`) REFERENCES `geo_admin1` (`id`),
  CONSTRAINT `fk_places_admin2` FOREIGN KEY (`admin2_id`) REFERENCES `geo_admin2` (`id`),
  CONSTRAINT `fk_places_countries` FOREIGN KEY (`country_code`) REFERENCES `geo_countries` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

**/

class Place extends BaseModel {
	
	protected static $table = "geo_places";
	
	public static function byCountry() {}
	public static function byAdmin1() {}
	public static function byAdmin2() {}
	public static function byPostCode() {}
	
	public static function byName($name, $admin1, $admin2) {
		$obj = new Place;
		$obj->load("`name`='$name' AND `admin1_id`=$admin1 AND `admin2_id`=$admin2");
		if ($obj->id > 0)
			return $obj;
		return false;
	}
	
	public function getPostCodes() {}
}


/**

-- 
-- Structure for table `geo_postal_codes`
-- 

DROP TABLE IF EXISTS `geo_postal_codes`;
CREATE TABLE IF NOT EXISTS `geo_postal_codes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` char(8) NOT NULL,
  `country_code` char(2) NOT NULL,
  `admin1_id` int(11) unsigned NOT NULL,
  `admin2_id` int(11) unsigned NOT NULL,
  `place_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `code` (`code`),
  KEY `country_code` (`country_code`),
  KEY `admin1_id` (`admin1_id`),
  KEY `admin2_id` (`admin2_id`),
  KEY `place_id` (`place_id`),
  CONSTRAINT `fk_postal_codes_countries` FOREIGN KEY (`country_code`) REFERENCES `geo_countries` (`code`),
  CONSTRAINT `fk_postal_codes_admin1` FOREIGN KEY (`admin1_id`) REFERENCES `geo_admin1` (`id`),
  CONSTRAINT `fk_postal_codes_admin2` FOREIGN KEY (`admin2_id`) REFERENCES `geo_admin2` (`id`),
  CONSTRAINT `fk_postal_codes_places` FOREIGN KEY (`place_id`) REFERENCES `geo_places` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

**/

class PostalCode extends BaseModel {
	
	protected static $table = "geo_postal_codes";
	
	public static function byCode($code, $place) {
		$obj = new PostalCode;
		$obj->load("`code`='$code' AND `place_id`=$place");
		if ($obj->id > 0)
			return $obj;
		return false;
	}
	
}