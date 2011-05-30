<?php
namespace Milk\IO;

class Http {

	// HTTP methods according to RFC 2616
	public static $methods = 
		array(
			'GET',
			'POST',
			'HEAD',
			'PUT',
			'DELETE',
			'OPTIONS',
			'TRACE',
			'CONNECT'
		);
	
	/**
		Shortcut to set 404 status header
			@public
	**/
	public static function notFound() {
		self::status(404);
	}
	
	/**
		Redirect client to another location
			@param $uri string
			@public
	**/
	public static function redirect($uri) {
		self::status($_SERVER['REQUEST_METHOD'] == 'GET' ? 301 : 303);
		if (PHP_SAPI != 'cli' && !headers_sent())
			header("Location: $uri");
	}
		
	public static function status($status) {
		if (PHP_SAPI != 'cli' && !headers_sent())
			header("Status: $status");
	}
	
	public static function isAjax() {
		return (
			!empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
			&& strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
		);
	}
}

namespace Milk\IO\Http;

class Response {

	protected $headers;
	protected $content;
	protected $content_type = 'text/html';
	protected $status_code = 200;
	
	/**
		Send response to client
			@public
	**/
	public function send() {
	
	}
	
	/**
		Add multiple headers
	**/
	public function addHeaders(array $headers) {
		foreach ($headers as $name => $values)
			$this->headers[] = new Header($name, $values);
	}
	
	/**
		Add header
	**/
	public function addHeader($name, $values) {
		$this->headers[] = new Header($name, $values);
	}
	
	/**
		Set status code of reponse
	**/
	public function setStatusCode($code) {
		$this->status_code = (int)$code;
	}
}

class Request {

	protected $path;
	protected $path_info;
	
	protected $method;
	protected $encoding = 'utf-8';
	
	protected $GET,
			  $POST,
	          $REQUEST,
	          $COOKIES,
	          $FILES,
	          $META;

	protected $session;
	protected $raw_post_data;

	public function __construct() {
	
		$this->method = $_SERVER['REQUEST_METHOD'];
		
		if (!empty($_SERVER['CONTENT_TYPE']) &&
			strpos(strtolower($_SERVER['CONTENT_TYPE']), 'charset')) {
			$ct = explode(',', strtolower($_SERVER['CONTENT_TYPE']));
			foreach ($ct as $data) {
				list($key, $val) = explode('=', $data);
				if ($key === 'charset');
					$this->encoding = $val;
			}
		}
		
		$this->path = rawurldecode($_SERVER['REQUEST_URI']);
		$this->path_info = isset($_SERVER['PATH_INFO']) ?
			rawurldecode($_SERVER['PATH_INFO']) : $this->path;
		
		$this->META		= &$_SERVER;
		$this->GET 		= &$_GET;
		$this->POST 	= &$_POST;
		$this->REQUEST 	= &$_REQUEST;
		$this->COOKIES	= &$_COOKIES;
		$this->FILES	= &$_FILES;
		$this->session  = &$_SESSION;
		
		$this->raw_post_data = &$HTTP_RAW_POST_DATA;
	}
	
	public function getHost() {
		return isset($this->META['HTTP_X_FORWARDED_HOST']) ?
			$this->META['HTTP_X_FORWARDED_HOST'] :
			$this->META['HTTP_HOST'];
	}
	
	public function getPath() {
		return $this->path;
	}
	
	public function getFullPath() {
		return $this->path.$this->META['QUERY_STRING'];
	}
	
	public function buildAbsolutePath($location=null) {
		if ($location) {
			// @todo Implement relative path
		} else {
			return $this->getFullPath();
		}
	}
	
	public function isAjax() {
		return (!empty($this->META['HTTP_X_REQUESTED_WITH']) 
			&& strtolower($this->META['HTTP_X_REQUESTED_WITH'])
			== 'xmlhttprequest')
			? true : false;
	}
}

class Header {
	protected $name;
	protected $values;
}