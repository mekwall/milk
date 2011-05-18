<?php
namespace Milk\WebService\Transports;

use \F3;

class Plain {

	//! HTTP status codes
	private $http_codes = array(
		//! Success
		200 => 'OK',
		201 => 'Created Successfully',
		202 => 'Accepted',
		204 => 'No Content',
		//! Error
		400 => 'Syntax Error',
		401 => 'Not Authorized',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		409 => 'Record Conflict',
		500 => 'Unknown Error',
		501 => 'Method Not Implemented'
	);
	
	private $responses = array();
	
	public function respond(){
		foreach ($this->responses as $response) {
			echo $response;
		}
	}

	public function output($output, $cache=false, $http_code=200, $id=null){
		F3::httpStatus($http_code);
		$response = "$http_code {$this->http_codes[$http_code]}\n".var_export($output, true);
		if ($id)
			$response .= "\nID: $id";
		$this->responses[] = $response;
	}
	
	public function error($http_code, $error=null, $data=null, $id=null) {
		$this->output($data, false, $http_code, $id);
	}
	
	public function success($result, $data=null, $code, $cache=null, $id) {
		$this->output(array('status' => $result, 'data' => $data), $cache, $code, $id);
	}
	
	public function result($result, $cache=null, $code=200, $id) {
		$this->output($result, $cache, $code, $id);
	}
	
	public function denied($error, $id=null) {
		$this->error(403, $error, null, $id);
	}

}