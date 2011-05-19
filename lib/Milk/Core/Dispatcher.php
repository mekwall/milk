<?php
namespace Milk\Core;

use Milk\Core\Exception,
	Milk\Core\Dispatcher\Route;
	
use Milk\Utils\HTTP;

class Dispatcher {
	
	// Constants
	const ARR	= 0;
	const URL	= 1;
	const FILE	= 2;
	const JSON	= 3;
	const YAML	= 4;
	const IMPORT = 5;
	
	// Routes
	private static $routes = array();
	
	// Hotlink reroute
	private static $hotlink = "/";
	
	// Parameters passed
	private static $params = array();
	
	/**
		Add routes to dispatcher
			@param $routes mixed
			@param $type const
			@param $base string
			@public
			@static
	**/
	public static function addRoutes($routes, $type=self::ARR, $base="/") {

		switch($type) {
			case self::URL:
				$routes = parse_url($routes, PHP_URL_PATH);
			case self::FILE:
				if (is_readable($routes)) {
					$ext = strtolower(pathinfo($routes, PATHINFO_EXTENSION));
					switch($ext) {
						case 'json':
							$type = self::JSON;
						break;
						case 'yaml':
							$type = self::YAML;
						break;
						default: 
							throw new Exception('Unknown file type');
						break;
					}
					$routes = file_get_contents($routes);
				} else {
					throw new Exception("$routes is not readable");
				}
			break;
		}

		switch($type) {
			case self::JSON:
				$routes = (array)json_decode($routes);
				switch(json_last_error()) {
					case JSON_ERROR_DEPTH:
						$error =  'Maximum stack depth exceeded';
					break;
					case JSON_ERROR_CTRL_CHAR:
						$error = 'Unexpected control character found';
					break;
					case JSON_ERROR_SYNTAX:
						$error = 'Syntax error, malformed JSON';
					break;
				}
				if (!empty($error))
					throw new Exception('JSON error: '.$error);
			break;
			
			case self::YAML:
				if (!function_exists('yaml_parse'))
					throw new Exception('YAML parser is not installed');
				if (!$yaml = @yaml_parse($routes)) {
					$error = (object)error_get_last();
					throw new Exception('YAML error: '.$error->message);
				}
					
				$routes = (array)$yaml["routes"];
			break;
		}

		if (is_array($routes)) {
			foreach ($routes as $pattern => $route) {
				if ($route instanceof Route) {
					self::$routes[$route->pattern] = $route;
				} else {
					foreach ($routes as $pattern => $route) {
						if (!is_array($route) && !is_object($route) || is_callable($route))
							$route = array(
								"pattern" => $pattern, 
								"target" => $route
							);
						
						if (!is_object($route))
							$route = (object)$route;
					
						if (is_string($route->target)) {
						
							// Look for import shortcut
							if (substr($route->target, 0, 1) === "@") {
								//self::import(substr($route->target, 1));
							}
						
							if (strpos($route->target, "function(") !== false)
								eval('$route->target='.$route->target.';');
						}
						
						self::$routes[$route->pattern] = new Route(
							$route->pattern, 
							$route->target, 
							isset($route->ttl) ? $route->ttl : 0,
							isset($route->hotlink) ? $route->hotlink : TRUE
						);
					}
				}
			}
			return true;
		} else {
			throw new Exception('Param is not an array');
			return false;
		}
	}
	
	/**
		Get current routes
			@return array
			@public
	**/
	public static function getRoutes() {
		return self::$routes;
	}
	
	/**
		Reroute to specified URI
			@param $uri string
			@public
	**/
	public static function reroute($uri) {
		if (PHP_SAPI != 'cli' && !headers_sent()) {
			// HTTP redirect
			HTTP::redirect($uri);
		}
		self::mock('GET '.$uri);
		self::dispatch();
	}
	
	/**
		Mock request to dispatch
			@public
			@static
	**/
	static function mock($pattern, array $params=NULL) {
		@list($method, $uri) = preg_split('/\s+/', $pattern, 2, PREG_SPLIT_NO_EMPTY);
		$query = explode('&', parse_url($uri, PHP_URL_QUERY));
		
		$_SERVER['REQUEST_METHOD'] = $method;
		$_SERVER['REQUEST_URI'] = $uri;
	}
	
	/**
		Dispatch request to route
			@public
			@static
	**/
	public static function dispatch() {
	
		if (count(self::$routes) === 0) {
			throw new Exception( _("No routes exist") );
			return;
		}
		
		// Detailed routes get matched first
		ksort(self::$routes);
		
		// Save the current time
		$time = time();
			
		// Process routes
		foreach (self::$routes as $route) {
			// Check if request method matches current route
			if (!in_array($_SERVER['REQUEST_METHOD'], $route->methods))
				continue;
			
			// Get base path
			$base = substr(rawurldecode($_SERVER['REQUEST_URI']), strlen($route->base)-1);
		
			// Build pattern
			$pattern = 
				'|^'.
				preg_replace(
					'/(?:{{)?@(\w+\b)(?:}})?/i',
					// Valid URL characters (RFC 1738)
					'(?P<\1>[\w\-\.!~\*\'"(),\h]+)',
					// Wildcard character in URI
					str_replace( '\*','(.*)', str_replace("|", "\|", $route->pattern) )
				).'\/?(?:\?.*)?$'.
				'|i';

			// Check if request uri matches route pattern			
			if (!$found = 
					(bool)
					preg_match(
						$pattern,
						$base,
						$args
					)
				)
				continue;
			
			if (!$route->hotlink &&
				isset($_SERVER['HTTP_REFERER']) &&
				parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) != $_SERVER['SERVER_NAME'])
				// Hot link detected; Redirect page
				self::reroute(self::$hotlink);
				
			// Save named uri captures
			foreach ($args as $key => $arg)
				// Remove non-zero indexed elements
				if (is_numeric($key) && $key)
					 unset($args[$key]);
					 
			self::$params = $args;
			
			// Dispatch to target
			$classes = array();
			$targets = is_string($route->target) ? explode('|', $route->target) : array($route->target);
			foreach ($targets as $target) {
				if (is_string($target)) {
					// Replace tokens in route handler, if any
					$diff = FALSE;
					if (preg_match('/{{.+}}/', $target)) {
						$target = self::resolve($target);
						$diff = TRUE;
					}
					
					if (preg_match('/(.+)(->|::)(.+)/', $target, $match)) {
						if ($diff && (!class_exists($match[1]) ||
							!method_exists($match[1],$match[3]))) {
							// Method not found
							throw new Exception( _("Method not found") );
							return;
						}
						$target = array($match[2]=='->' ?
							new $match[1] : $match[1], $match[3]);
						
					} elseif ($diff && !function_exists($target)) {
						// Method not found
						throw new Exception( _("Method not found") );
						return;
					}
				}
					
				if (!is_callable($target)) {
					// Method not callable
					throw new Exception( _("Method not callable") );
				}
				
				$oop = is_array($target) && (is_object($target[0]) || is_string($target[0]));
				
				if ($oop && method_exists($target[0], $before='beforeRoute') &&
					!in_array($target[0], $classes)) {
					// Execute beforeRoute() once per class
					call_user_func( array($target[0], $before) );
					$classes[] = is_object($target[0]) ? get_class($target[0]) : $target[0];
				}
				
				// Call target method
				$out = call_user_func_array(
					$target,
					array_splice($args, 1)
				);
				
				if ($oop && method_exists($target[0], $after='afterRoute') &&
					!in_array($target[0], $classes)) {
					// Execute afterRoute() once per class
					call_user_func( array($target[0],$after) );
					$classes[] = is_object($target[0]) ? get_class($target[0]) : $target[0];
				}

				echo $out;
			}
			return;
		}
		
		// No route matching current request
		HTTP::notFound();
		throw new Exception( sprintf(_("Route '%s' does not exist"), $_SERVER['REQUEST_URI']) );
	}
}

namespace Milk\Core\Dispatcher;

use Milk\Core\Dispatcher,
	Milk\Utils\HTTP;

class Route {

	// Base path of route
	public $base = "/";
	
	// Pattern to match
	public $pattern = '';
	
	// Time to live
	public $ttl = 0;
	
	// Allow hotlink
	public $hotlink = TRUE;
	
	// Methods valid for this route
	public $methods = array();
	
	public function __construct($pattern, $target, $ttl=0, $hotlink=TRUE) {
		$request = preg_split('/\s+/', $pattern, 2, PREG_SPLIT_NO_EMPTY);
		
		if (count($request) > 1)
			$this->methods = explode('|', strtoupper($request[0]));
		else
			$this->methods = &HTTP::$methods;

		$this->pattern = $pattern;
		$this->ttl = $ttl;
		$this->hotlink = $hotlink;
		$this->target = $target;
	}
	
	public function setBase($base) {
		if (substr($base, 0, 1) !== "/")
			$base = "/$base";
		$this->base = $base;
		return $this;
	}
}