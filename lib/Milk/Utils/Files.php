<?php
namespace Milk\Utils;

class Files {

	/**
		Get MIME type for file
	
			@param string &$file File path
			@return string
	**/
	public static function getMimeType($file) {
		$type = false;
		//! Fileinfo documentation says fileinfo_open() will use the
		//! MAGIC env var for the magic file
		if (extension_loaded('fileinfo') && isset($_ENV['MAGIC']) &&
		($finfo = finfo_open(FILEINFO_MIME, $_ENV['MAGIC'])) !== false) {
			if (($type = finfo_file($finfo, $file)) !== false) {
				//! Remove the charset and grab the last content-type
				$type = explode(' ', str_replace('; charset=', ';charset=', $type));
				$type = array_pop($type);
				$type = explode(';', $type);
				$type = trim(array_shift($type));
			}
			finfo_close($finfo);

		//! If anyone is still using mime_content_type()
		} elseif (function_exists('mime_content_type'))
			$type = trim(mime_content_type($file));

		if ($type !== false && strlen($type) > 0) return $type;

		//! Otherwise do it the old fashioned way
		static $exts = array(
			'jpg' => 'image/jpeg', 
			'gif' => 'image/gif', 
			'png' => 'image/png',
			'tif' => 'image/tiff', 
			'tiff' => 'image/tiff', 
			'ico' => 'image/x-icon',
			'swf' => 'application/x-shockwave-flash', 
			'pdf' => 'application/pdf',
			'zip' => 'application/zip', 
			'gz' => 'application/x-gzip',
			'tar' => 'application/x-tar', 
			'bz' => 'application/x-bzip',
			'bz2' => 'application/x-bzip2', 
			'txt' => 'text/plain',
			'asc' => 'text/plain', 
			'htm' => 'text/html', 
			'html' => 'text/html',
			'css' => 'text/css', 
			'js' => 'text/javascript',
			'xml' => 'text/xml', 
			'xsl' => 'application/xsl+xml',
			'ogg' => 'application/ogg', 
			'mp3' => 'audio/mpeg', 
			'wav' => 'audio/x-wav',
			'avi' => 'video/x-msvideo', 
			'mpg' => 'video/mpeg', 
			'mpeg' => 'video/mpeg',
			'mov' => 'video/quicktime', 
			'flv' => 'video/x-flv', 
			'php' => 'text/x-php'
		);
		$ext = strtolower(pathInfo($file, PATHINFO_EXTENSION));
		return isset($exts[$ext]) ? $exts[$ext] : 'application/octet-stream';
	}
}