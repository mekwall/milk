<?php
namespace Milk\Utils;

class HTTP {

	// HTTP methods
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
		
	public static function notFound() {
		self::status(404);
	}
	
	public static function redirect($uri) {
		self::status($_SERVER['REQUEST_METHOD'] == 'GET' ? 301 : 303);
		header("Location: $uri");
	}
		
	public static function status($status) {
		header("Status: $status");
	}
	
	public static function isAjax() {
		return (
			!empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
			&& strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
		);
	}
}