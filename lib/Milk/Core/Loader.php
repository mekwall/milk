<?php
namespace Milk\Core;

// We have to include Core\Module if it doesn't exist
if (!class_exists('Milk\Core\Module'))
	require_once 'Module.php';
	
use Milk\Core\Module,
	Milk\Core\Loader\Exception,
	Milk\Utils\Translation;

class Loader extends Module\Singleton {

	private static $loaded 		= array();
	private static $paths 		= array();
	private static $namespaces 	= array();
	
	/**
		Load a class/interface
			@param $class string
			@public
			@static
	**/
	public function autoload($class) {
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
	public function register() {
		spl_autoload_register(
			array(self::getInstance(), 'autoload')
		);
		return self::getInstance();
	}
	
	/**
		Add path to autoloader
			@param $path string
			@public
			@static
	**/
	public function addPath($path) {
		self::$paths[] = $path;
		return self::$_instance;
	}
	
	/**
		Add paths to autoloader
			@param $paths array
			@public
			@static
	**/
	public function addPaths($paths) {
		foreach ($paths as $$path) {
			self::addPath($path);
		}
		return self::$_instance;
	}
	
	/**
		Add namespace to autoloader
			@param $ns string
			@param $path string
			@public
			@static
	**/
	public function addNamespace($ns, $path) {
		self::$namespaces[$ns] = $path;
		return self::getInstance();
	}
	
	/**
		Add namespaces to autoloader
			@param $namespaces array
			@public
			@static
	**/
	public function addNamespaces($namespaces) {
		foreach ($namespaces as $ns => $path) {
			self::addNamespace($ns, $path);
		}
		return self::$_instance;
	}
	
}

// Register autoloader
Loader::register();

// Add Milk namespace to loader
Loader::addNamespace("Milk", realpath(__DIR__.'/..'));

namespace Milk\Core\Loader;

use Milk\Core\Exception as BaseException;

class Exception extends BaseException {
	
	public function __construct($message=null, $code=0) {
		$trace = $this->getTrace();
		$this->file = $trace[1]['file'];
		$this->line = $trace[1]['line'];
		parent::__construct($message, $code);
	}
}