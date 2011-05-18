<?php
namespace Milk\WebService\Transports;

use \F3;

class JSONRPC {

	static private $version = "2.0";
		
	public $errors = array(
		-32700 => "Parse error",
		-32600 => "Invalid request",
		-32601 => "Method not found",
		-32602 => "Invalid params",
		-32603 => "Internal error",
		-32000 => "Not found",
		-32001 => "Conflicting data",
		-32099 => "Unknown error"
	);
	
	static private $_instance = null;
	private $responses = array();
	private $batch = false;
	private $invalid = false;
	
	public function __construct(){
		set_error_handler("Milk\WebService\Transports\JSONRPC\ErrorHandler");
		self::$_instance = $this;
	}
	
	static public function getInstance(){
		if (!self::$_instance) return new JSONRPC();
		return self::$_instance();
	}
	
	public function respond(){
		if (count($this->responses) == 1) {
			echo json_encode($this->batch ? $this->responses : $this->responses[0]);
		} elseif (count($this->responses) > 1) {
			echo json_encode($this->responses);
		}
	}
	
	public function parse(){
		$input = file_get_contents('php://input');
		$requests = json_decode($input);
		
		switch(json_last_error()) {
			case JSON_ERROR_DEPTH:
				return $this->error(400, 'Maximum stack depth exceeded', null, null, -32700);
			break;
			case JSON_ERROR_CTRL_CHAR:
				return $this->error(400, 'Unexpected control character, possibly incorrectly encoded', null, null, -32700);
			break;
			case JSON_ERROR_STATE_MISMATCH:
				return $this->error(400, 'Invalid or malformed JSON', null, null, -32700);
			break;
			case JSON_ERROR_SYNTAX:
				return $this->error(400, 'JSON syntax error', null, null, -32700);
			break;
			/* 
			case JSON_ERROR_UTF8:
				return $this->error(400, 'Malformed UTF-8 characters, possibly incorrectly encoded', null, null, -32700);
			break;
			*/
		}
		
		/*
		if ($requests === null)
			return $this->error(400, null, null, null, -32700);
		*/
			
		if (!is_array($requests))
			$requests = array($requests);
		else
			$this->batch = true;

		return $requests;
	}

	public function output($output, $cache=false, $http_code=200, $id){
		//! Send correct http status header
		F3::httpStatus($http_code);
		//! Send correct content type header
		header('Content-type: application/json');
		if ($cache === false) {
			header("Pragma: no-cache");
			header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
			header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
		} else {
			$soutput = serialize($output);
			$etag = md5($soutput);
			if (F3::cached($etag)) {
				$output = unserialize(F3::get($etag));
			} else {
				F3::set($etag, $soutput, true);
			}		
			header("Etag: $etag");
		}
		$this->responses[] = array("jsonrpc" => self::$version, "result"=>$output, "id"=>$id);
	}
	
	public function error($http_code, $message=null, $data=null, $id=null, $error_code=null) {
		$this->invalid = true;
		if ($error_code) {
			$error = $this->errors[$error_code];
		} else {
			switch ($http_code) {
				case 400:
					$error_code = -32600;
				break;
				
				case 404:
					$error_code = -32000;
				break;
				
				case 409:
					$error_code = -32001;
				break;
				
				case 401:
					$error_code = -32602;
				break;
				
				case 500:
					$error_code = -32603;
				break;
				
				case 501:
					$error_code = -32601;
				break;
				
				default:
					$error_code = -32099;
				break;
			}
		}
		
		if (!$message)
			$message = $this->errors[$error_code];
	
		if (is_array($data)) {
			$output = array('code' => $error_code, 'message' => $message, 'data' => $data);
		} else {
			$output = $data ?
					  array('code' => $error_code, 'message' => $message, 'data' => $data) :
					  array('code' => $error_code, 'message' => $message);
		}
		//! Send correct http status header
		F3::httpStatus($http_code);
		//! Send correct content type header
		header('Content-type: application/json');
		
		$response = array(
			"jsonrpc" => self::$version,
			"error"=>$output
		);
		if ($id) {
			$response['id'] = $id;
		}
		$this->responses[] = $response;
		
		//! return false so we can use this function as return elsewhere
		return false;
	}
	
	public function success($status, $data=null, $cache=null, $http_code=200, $id) {
		if ($data) {
			$output = array("message" => $status, "data" => $data);
		} else {
			$output = $status;
		}
		self::output($output, $cache, $http_code, $id);
	}
	
	public function result($result, $cache=null, $http_code=200, $id) {
		self::output($result, $cache, $http_code, $id);
	}
	
	public function denied($message, $id) {
		self::error(403, $message, $id);
	}

}

namespace Milk\WebService\Transports\JSONRPC;

function ErrorHandler($error, $message, $file, $line) {
    if (!(error_reporting() & $errno)) {
        // This error code is not included in error_reporting
        return;
    }	
	$jsonrpc = JSONRPC::getInstance();
	$jsonrpc->error(500, $message, array("file" => $file, "line" => $line), $error);
	
    /* Don't execute PHP internal error handler */
    return true;
}