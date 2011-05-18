<?php
namespace Milk\Utils;

use \F3;

use \Milk\Utils\Compress\ClosureCompiler,
	\Milk\Utils\Compress\CSSTidy;
	
use \Milk\WebService\Consumers\Amazon;
	
require_once(LIB_PATH.'/CSSTidy/class.csstidy.php');

class Compress {

	static $js,
		   $css,
		   $cli = false,
	       $js_include = array(),
		   $css_include = array(),
		   $uploadFiles = array();
		   
	protected $method,
			  $request,
			  $query;
			  

	public function __construct() {
		$compress = F3::get('COMPRESS');
		self::$js = $compress['JS'];
		self::$css = $compress['CSS'];
		F3::set('compress_js', new Compress_Links('js'));
		F3::set('compress_css', new Compress_Links('css'));
		
		if (PHP_SAPI == 'cli')
			self::$cli = true;
		
		if (!self::$cli) {
			$this->method = $_SERVER['REQUEST_METHOD'];
			$uri = explode('/', $_SERVER['REQUEST_URI']);
			
			$file = $uri[count($uri)-1];
			if ($file == "") {
				$file = $uri[count($uri)-2];
			}
			$this->group = substr($file, 0, strpos($file, '.'));
		} else {
			$argv = (array)F3::get('argv');
			$this->group = $argv[0];
		}
	}
	
	public function getJS() {
		self::outputJS($this->group);
	}
	
	public function getCSS() {
		self::outputCSS($this->group);
	}
	
	public static function generateLinks($type, $group) {
		$links = '';
		$urls = F3::get('urls');
		$gzip = strstr($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') ? '.gz' : '';
		
		if ($type == 'js') {
			if (!isset(self::$js[$group]))
				return false;
			
			$remote = false;
			$release = F3::get('RELEASE');
			foreach (self::$js[$group] as $file) {
				// check for url
				if (strpos($file, '//') > -1) {
					$remote = true;
					$links .= "<script src=\"$file\"></script>\n\t";
				} else if (!$release) {
					$links .= "<script src=\"{$urls->static}/js/$file\"></script>\n\t";
				}
			}
			if ($release && !$remote) {
				$output = STATIC_PATH.'/js/'.$group.'.min.js';
				if (is_readable($output)) {
					//$crc = md5_file($output);
					$links .= "<script src=\"{$urls->cdn}/js/$group.min$gzip.js\"></script>\n\t";
				} else {
					self::generateJS($group);
				}
			}
			return $links;
			
		} elseif ($type == 'css') {
			if (!isset(self::$css[$group]))
				return false;

			$media = "all";
			$remote = false;
			$release = F3::get('RELEASE');
			foreach (self::$css[$group] as $file) {
				// check for url
				if (strpos($file, '//') > -1) {
					$remote = true;
					$m2 = substr($file, strpos($file, '@'));
					if (empty($m2))
						$media = $m2;
					$links .= "<link href=\"$file\" rel=\"stylesheet\" type=\"text/css\" media=\"$media\" />\n\t";
				}
				if (!$release) {
					$links .= "<link href=\"{$urls->static}/css/$file\" rel=\"stylesheet\" type=\"text/css\" media=\"$media\" />\n\t";
				}
			}

			if ($release && !$remote) {
				$output = STATIC_PATH.'/css/'.$group.'.min.css';
				if (is_readable($output)) {
					//$crc = md5(filemtime($output));
					$links .= "<link href=\"{$urls->cdn}/css/$group.min$gzip.css\" rel=\"stylesheet\" type=\"text/css\" media=\"$media\" />\n\t";
				} else {
					self::generateCSS($group);
				}
			}

			return $links;
		}
	}

	public static function outputJS($group) {
		$js = self::$js;
		$recheck = F3::get('PARAMS[crc]') == 'recheck' ? true : false;
		$output = STATIC_PATH.'/js/'.$group.'.min.js';
		if (is_readable($output) && !$recheck) {
			if(array_key_exists('HTTP_IF_MODIFIED_SINCE', $_SERVER)) {
				$modified_since = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
				$last_modified = filemtime($output);
				if($modified_since >= $last_modified) {
					header("HTTP/1.0 304 Not Modified");
					exit;
				}
			}
		} else {
			self::generateJS($group);
		}
		header('Last-Modified: '.gmdate("D, d M Y H:i:s", filemtime($output)).' GMT');
		header('Content-Type: application/x-javascript; charset: UTF-8');
		readfile($output);
		ob_end_flush();
	}
	
	public static function outputCSS($group) {
		$css = self::$css;
		$recheck = F3::get('PARAMS[crc]') == 'recheck' ? true : false;
		$output = STATIC_PATH.'/css/'.$group.'.min.css';
		if (is_readable($output) && !$recheck) {
			if(array_key_exists('HTTP_IF_MODIFIED_SINCE', $_SERVER)) {
				$modified_since = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
				$last_modified = filemtime($output);
				if($modified_since >= $last_modified) {
					header("HTTP/1.0 304 Not Modified");
					exit;
				}
			}
		} else {
			self::generateCSS($group);
		}
		header('Last-Modified: '.gmdate("D, d M Y H:i:s", filemtime($output)).' GMT');
		header('Content-Type: text/css; charset: UTF-8');
		readfile($output);
		ob_end_flush();
	}
	
	public static function generateJS($group) {
		set_time_limit(480);
		$js = self::$js;
		$output = STATIC_PATH.'/js/'.$group.'.min.js';
		if (self::$cli)
			echo "Target: $output\n";
		if ($packed = self::js($js[$group]))
			file_put_contents($output, $packed);
			
		// save packed file
		self::uploadToS3($output, 'js/'.$group.'.min.js', 'text/javascript');
		
		// create gzipped file as well
		$output_gz = STATIC_PATH.'/js/'.$group.'.min.gz.js';
		shell_exec("gzip -c $output > $output_gz");
		self::uploadToS3($output_gz, 'js/'.$group.'.min.gz.js', 'text/javascript');
	}
	
	public static function generateCSS($group) {
		set_time_limit(480);
		$css = self::$css;
		$output = STATIC_PATH.'/css/'.$group.'.min.css';
		if (self::$cli)
			echo "Target: $output\n";
		if ($packed = self::css($css[$group])) {
			$urls = F3::get('urls');
			preg_match_all("/static(?:-stage)?.maklarpaket.se\/(.[^)]+)/", $packed, $matches);
			$images = array();
			foreach ($matches[1] as $m) {
				if (isset($images[$m]) || !file_exists(STATIC_PATH."/$m"))
					continue;
				$images[$m] = true;
				if (self::$cli)
					echo "Processing image: $m\n";
				self::uploadToS3(STATIC_PATH."/$m", $m);
			}
			
			// save packed file
			$packed = str_replace(array('static.maklarpaket.se', 'static-stage.maklarpaket.se'), 'cdn.maklarpaket.se', $packed);
			file_put_contents($output, $packed);
			self::uploadToS3($output, 'css/'.$group.'.min.css', 'text/css');
			
			// create gzipped file as well
			$output_gz = STATIC_PATH.'/css/'.$group.'.min.gz.css';
			shell_exec("gzip -c $output > $output_gz");
			self::uploadToS3($output_gz, 'css/'.$group.'.min.gz.css', 'text/css');
		}
	}
	
	public static function executeUpload(){
		$ibatch = array();
		foreach (self::$uploadFiles as $file) {
			$upload = false;
			$source = $file['source'];
			$target = $file['target'];
			$mime = $file['mime'];
			if (empty($mime))
				$mime = Files::getMimeType($source);

			// Lets check if it's on S3 and compare with local file
			if (self::$cli)
				echo "Checking remote file: $target";
				
			if (($info = S3::getObjectInfo(F3::get('MILK.S3.BUCKET'), $target)) !== false) {
				if (md5_file($source) === $info['hash']) {
					if (self::$cli)
						echo " [IDENTICAL]\n";
				} else {
					if (self::$cli)
						echo " [UPDATING]\n";
					$upload = true;
					$ibatch[] = $target;
				}
			} else {
				if (self::$cli)
					echo " [NOT FOUND]\n";
				$upload = true;
			}

			// Upload file to S3
			if ($upload) {
				$expires = strtotime("+3 month");
				$max_age = $expires-time();
				$requestHeaders = array(
					F3::HTTP_Content => $mime,
					"Cache-Control" => "max-age=$max_age, no-transform, public",
					"Expires" => gmdate("D, d M Y H:i:s T", $expires)
				);
				
				// Check if target is a gzip file
				if (strpos($target, ".gz")) {
					$requestHeaders['Content-Encoding'] = 'gzip';
					$requestHeaders['Vary'] = 'Accept-Encoding';
				}
			
				if (self::$cli)
					echo "Uploading: $source => $target";
				if (!S3::putObject(
					S3::inputFile($source),
					F3::get('MILK.S3.BUCKET'), 
					$target, 
					S3::ACL_PUBLIC_READ,
					array(),
					$requestHeaders
				)) {			
					//! Upload failed
					if (self::$cli)
						echo " [FAILED]\n";
					else
						trigger_error("Upload to S3 failed!");
					return false;
				}
				if (self::$cli)
					echo " [OK]\n";
			}
		}
		
		if (count($ibatch) > 0) {
			if (self::$cli)
				echo "Invalidating updated files...";
			if ($dist = S3::getDistribution(F3::get('MILK.CLOUDFRONT.DIST_ID')))
				S3::invalidatePaths($dist, $ibatch);
			if (self::$cli)
				echo " Done!\n";
		} else {
			echo "No files to invalidate.\n";
		}
	}
	
	public static function uploadToS3($source, $target, $mime=null) {
		self::$uploadFiles[] = array(
			'source' => $source,
			'target' => $target,
			'mime' => $mime
		);
	}

    public static function js($files) {
        if (!is_array($files))
            $files = array($files);
        
        $compiler = new ClosureCompiler();
		foreach ($files as $file) {
			if (self::$cli)
				echo "Adding file $file\n";
			$file = STATIC_PATH."/js/$file";
			$compiler->add($file);
		}
		$compiler->simpleMode()
                 ->useClosureLibrary();
		
		if (F3::get('RELEASE'))
			$compiler->hideDebugInfo();
		
		if (self::$cli)
			echo "Compiling and merging...";
		$compiled = $compiler->_compile();
		if (self::$cli)
			echo " Done!\n";
		return $compiled;
    }
	
	public static function css($files) {
        if (!is_array($files))
            $files = array($files);
        
        $packed = '';
		$css_code = '';
        foreach ($files as $file) {
			if (strpos($file, '://'))
				continue;
			if (self::$cli)
				echo "Adding file $file\n";
			$filename = STATIC_PATH."/css/$file";
			$content = file_get_contents($filename);
			$css_code .= $content;
        }
		$css = new \csstidy();
		$css->set_cfg('preserve_css', FALSE);
		$css->set_cfg('remove_bslash', TRUE);
		$css->set_cfg('remove_last_;', TRUE);
		$css->set_cfg('lowercase_s', TRUE);
		$css->set_cfg('merge_selectors', 2);
		$css->set_cfg('optimise_shorthands', 1);
		$css->set_cfg('replace_colors', TRUE);
		$css->set_cfg('case_properties', 1);
		$css->set_cfg('compress_font-weight', TRUE);
		$css->load_template(LIB_PATH.'/CSSTidy/maklarpaket.tpl');
		if (self::$cli)
			echo "Compiling and merging...";
		$css->parse($css_code);
		if (self::$cli)
			echo " Done!\n";
		$packed = $css->print->plain();
        return $packed;
    }

}

class Compress_Links {
	private $type;
	private $links = array();
	
	public function __construct($type) {
		$this->type = $type;
	}

	public function __get($name) {
		return $this->links[$name];
	}

	public function __isset($name) {
		// Does not work :(
		if (F3::cached('_compress_'.$this->type.$name)) {
			return F3::get('_compress_'.$this->type.$name);	
		}
		if (!isset($this->links[$name])) {
			$this->links[$name] = Compress::generateLinks($this->type, $name);
			//F3::set('_compress_'.$this->type.$name, $this->links[$name], true);
			return $this->links[$name];
		}
		return (bool)$this->links[$name];
	}
}