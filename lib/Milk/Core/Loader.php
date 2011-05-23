<?php
namespace Milk\Core;

use Milk\Core\Exception,
	Milk\Utils\Translation;

class Loader {

	private static $loaded 		= array();
	private static $paths 		= array();
	private static $namespaces 	= array();

	/**
		Load a class/interface
			@param $class string
			@public
			@static
	**/
	public static function autoload($class) {
		$list = get_included_files();
		
		// Check if the class/interface requested is in a namespace
		if (__NAMESPACE__ || (strpos($class, '\\') !== false)) {
			$nslevels = substr_count($class, '\\');
			if ($nslevels == 0 && __NAMESPACE__) {
				$namespace = __NAMESPACE__;
			} else {
				$namespace = $class;
				// We need to check each level to see if it's defined
				while ($namespace = strstr($namespace, '\\', true)) {
					if (isset(self::$namespaces[$namespace])) {
						break;
					}
				}
			}
			if (isset(self::$namespaces[$namespace])) {
				$path_start = self::$namespaces[$namespace];
				$nsclass = str_replace($namespace.'\\', '', $class);
				$line = $path_start;
				$path = explode('\\', $nsclass);
				foreach ($path as $sign) {
					$line .= '/'.$sign;
					$file = $line.'.php';
					if (is_file($file) && is_readable($file)) {
						// Include the file
						include_once $file;
						// Verify that class/interface exists
						foreach (array($class, str_replace('\\', '_', $class)) as $style) {
							if (class_exists($class, FALSE) || interface_exists($class, FALSE)) {
								self::$loaded[$class] = TRUE;
								return;
							}
						}
					}
					if (!is_dir($line))
						break;
				}
			}
		}
		
		$paths = array_merge( self::$paths, explode(PATH_SEPARATOR, get_include_path()) );
		foreach (
				array(
					str_replace('_', DIRECTORY_SEPARATOR, $class), 
					str_replace(DIRECTORY_SEPARATOR, '_', $class)
				) as $style
			) {
			foreach ($paths as $path) {
				$path = realpath($path);
				if (!$path)
					continue;
				$file = $path . '/' . $style . '.php';
				$glob = glob(dirname($file).'/*.php', GLOB_NOSORT);
				if ($glob) {
					// Case-insensitive check for file presence
					$fkey = array_search(
						strtolower($file),
						array_map('strtolower',$glob)
					);
					if (is_int($fkey) && !in_array($glob[$fkey], $list)) {
						// Include the file
						include_once $glob[$fkey];
						// Verify that class/interface exists
						if (class_exists($class, FALSE) || interface_exists($class, FALSE)) {
							self::$loaded[$class] = TRUE;
							return;
						}
					}
				}
			}
		}

		// Trigger error if there are no other autoloaders registered
		if (count(spl_autoload_functions()) == 1) {
		
			if (!class_exists("Exception"))
				self::autoload("Milk\Core\Exception");
		
			if (class_exists("Exception")) {
				throw new Exception(
					sprintf( _("Could not load %s"), $class )
				);
			} else {
				trigger_error( _("Could not load %s"), $class );
			}
		}
	}
	
	/**
		Register autoloader to SPL
			@public
			@static
	**/
	public static function register() {
		spl_autoload_register(__CLASS__.'::autoload');
	}
	
	/**
		Add path to autoloader
			@param $path string
			@public
			@static
	**/
	public static function addPath($path) {
		self::$paths[] = $path;
	}
	
	/**
		Add paths to autoloader
			@param $paths array
			@public
			@static
	**/
	public static function addPaths($paths) {
		foreach ($paths as $$path) {
			self::addPath($path);
		}
	}
	
	/**
		Add namespace to autoloader
			@param $ns string
			@param $path string
			@public
			@static
	**/
	public static function addNamespace($ns, $path) {
		self::$namespaces[$ns] = $path;
	}
	
	/**
		Add namespaces to autoloader
			@param $namespaces array
			@public
			@static
	**/
	public static function addNamespaces($namespaces) {
		foreach ($namespaces as $ns => $path) {
			self::addNamespace($ns, $path);
		}
	}
	
}

// Register autoloader
Loader::register();