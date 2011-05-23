<?php
namespace Milk\Utils;

class HTTP {

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