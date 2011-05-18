<?php
namespace Milk\WebService\Transports;

use \F3 as F3;

class JSON {

	private $responses = array();
	private $batch = false;
	private $invalid = false;
	
	static private $_instance = null;
	
	public function __construct() {
		set_error_handler("Milk\WebService\Transports\JSON\ErrorHandler");
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
		$requests = json_decode(file_get_contents('php://input'));
		if ($requests === null)
			return $this->error(400, null, null, null);
			
		if (!is_array($requests))
			$requests = array($requests);
		else
			$this->batch = true;

		return $requests;
	}

	public function output($output, $cache=false, $http_code=200){
		// Send correct http status header
		F3::httpStatus($http_code);
		// Send correct content type header
		header('Content-type: application/json');
		if ($cache === false) {
			header("Pragma: no-cache");
			header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
			header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
		} else {
			$sdata = serialize($output);
			$etag = md5($sdata);
			if (F3::cached($etag)) {
				$output = unserialize(F3::get($etag));
			} else {
				F3::set($etag, $sdata, true);
			}		
			header("Etag: $etag");
		}
		$this->responses[] = $output;
        return $this;
	}
	
	public function error($http_code, $message=null, $data=null, $id=null, $error_code=null) {
		if (!empty($data))
			$output = array('error' => $http_code, 'message' => $message, 'data' => $data);
		else
			$output = array('error' => $http_code, 'message' => $message);
			
		return self::output($output, false, $http_code);
	}
	
	public function success($status, $data=null, $cache=null, $http_code=200) {
		$output = $data ?
					array('success' => $http_code, 'message' => $status, 'data' => $data) : 
					array('success' =>$http_code, 'message' => $status);
		return self::output($output, $cache, $http_code);
	}
	
	public function result($result, $cache=null, $http_code=200) {
		$output = array('success' => $http_code, 'data' => $result);
		return self::output($output, $cache, $http_code);
	}
	
	public function conflict($message) {
		return self::error(409, $message);
	}
	
	public function denied($message) {
		return self::error(403, $message);
	}
    
    public function notFound($object) {
		return self::error(404, "$object not found");
	}
}

namespace Milk\WebService\Transports\JSON;

function ErrorHandler($error, $message, $file, $line) {
    if (!(error_reporting() & $errno)) {
        // This error code is not included in error_reporting
        return;
    }
	$json = JSON::getInstance();
	$json->error(500, $message, array("file" => $file, "line" => $line), $error);
	
    /* Don't execute PHP internal error handler */
    return true;
}