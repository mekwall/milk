<?php
namespace Milk\Utils;

use \F3;

class GeoNames {

	private $username;
	private $country;
	
	public function __construct($username, $country='se') {
		$this->username = $username;
		$this->country = $country;
	}
	
	private function execute($method, $fields) {
		$result = F3::http(
			"GET http://api.geonames.org/$method",
			http_build_query($fields)
		);
		return json_decode($result);
	}
	
	public function postalCodeSearch($code, $max_rows=10, $starts_with=false) {
		return $this->execute("postalCodeSearchJSON",
			array(
				$starts_with ? 'postalcode_startsWith' : 'postalcode' => $code,
				'maxRows' => $max_rows,
				'style' => 'full',
				'country' => $this->country,
				'username' => $this->username
			)
		);
	}
	
	public function placeNameSearch($place, $max_rows=10, $starts_with=false) {
		return $this->execute("postalCodeSearchJSON",
			array(
				$starts_with ? 'placename_startsWith' : 'placename' => $place,
				'maxRows' => $max_rows,
				'style' => 'full',
				'country' => $this->country,
				'username' => $this->username
			)
		);
	}
	
	public function nearbyPostalCodes($code, $radius=5, $max_rows=5) {
		return $this->execute("findNearbyPostalCodesJSON",
			array(
				'postalcode' => $code,
				'radius' => $radius,
				'maxRows' => $max_rows,
				'style' => 'full',
				'country' => $this->country,
				'username' => $this->username
			)
		);
	}
	
	public function searchName($name, $max_rows=10, $start_row=0) {
		return $this->execute("findNearbyPostalCodesJSON",
			array(
				'name' => $query,
				'maxRows' => $max_rows,
				'startRow' => $start_row,
				'style' => 'full',
				'country' => $this->country,
				'username' => $this->username
			)
		);
	}
	
	public function search($query, $max_rows=10, $start_row=0) {
		return $this->execute("findNearbyPostalCodesJSON",
			array(
				'q' => $query,
				'maxRows' => $max_rows,
				'startRow' => $start_row,
				'style' => 'full',
				'country' => $this->country,
				'username' => $this->username
			)
		);
	}
	
}