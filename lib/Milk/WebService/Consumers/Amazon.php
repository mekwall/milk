<?php
namespace Milk\WebService\Consumers;

use \F3,
	\SimpleXMLElement;

class Amazon {

	public static $ssl = true;
	public static $key;
	public static $secret;
	
	/**
		Set AWS access key and secret key
			@param string $key Access key
			@param string $secret Secret key
			@return void
	**/
	public static function setAuth($key, $secret) {
		self::$key = $key;
		self::$secret = $secret;
	}

	/**
		Generates and return request signature
			@param string $date Date
			@return string
	**/
	public static function generateSignature($string){
		return 'AWS ' . self::$key . ":" . self::getHash($string);
	}
	
	/**
		Creates a HMAC-SHA1 hash
	
		This uses the hash extension if loaded

			@param string $string String to sign
			@return string
	**/
	private static function getHash($string) {
		return base64_encode(extension_loaded('hash') ?
			hash_hmac('sha1', $string, self::$secret, true) : 
			pack('H*', sha1((str_pad(self::$secret, 64, chr(0x00)) ^ (str_repeat(chr(0x5c), 64))) .
			pack('H*', sha1((str_pad(self::$secret, 64, chr(0x00)) ^
			(str_repeat(chr(0x36), 64))) . $string)))));
	}
}

namespace Milk\WebService\Consumers\Amazon;

use \F3,
	\SimpleXMLElement;
	
use \Milk\WebService\Consumers\Amazon;

class S3 {
}

class CloudFront {

	public static $url = "https://cloudfront.amazonaws.com/";
	public static $dist_id;
	public static $debug = false;
	public static $version = "2010-11-01";
	
	public static function invalidate($keys) {
		if (!is_array($keys)){
			$keys = array($keys);
		}
		
		$date = gmdate('D, d M Y H:i:s T');
		$request_url = self::$url.self::$version."/distribution/".self::$dist_id."/invalidation";
		
		//! Build XML
		$xml = new SimpleXMLElement("<InvalidationBatch></InvalidationBatch>");
		foreach($keys as $key){
			$key = (preg_match("/^\//", $key)) ? $key : "/" . $key;
			$path = $xml->addChild('Path', $key);
		}
		$xml->addChild("CallerReference", time());
		
		//! Get XML as string
		$request = $xml->asXML();
		
		//! Do the request
		$result = F3::http(
			'POST '.$request_url,
			$request,
			array(
				'Date' => $date,
				'Authorization' => Amazon::generateSignature($date),
				'Content-Type' => 'application/xml',
				'Content-Length' => strlen($request)
				#'Accept: text/xml,application/xhtml+xml,application/xml',
				#'Accept-Language: en-us'
			)
		);
		
		var_dump($result);
	}
}
