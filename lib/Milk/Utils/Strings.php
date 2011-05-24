<?php
namespace Milk\Utils;

use \mt_rand, \strlen;

// Set internal encoding
iconv_set_encoding('internal_encoding', 'UTF-8');
mb_internal_encoding('UTF-8');

abstract class Strings {

	/**
		Capitalize all words
			@param $words string Data to capitalize
			@param $charList string Word delimiters
			@return string Capitalized words
	**/
	public static function capitalize($words, $chars=null) {
		// Use ucwords if no delimiters are given
		if (!isset($chars))
			return ucwords($words);
		
		// Go through all characters
		$next = true;
		for ($i = 0, $max = strlen($words); $i < $max; $i++) {
			if (strpos($chars, $words[$i]) !== false) {
				$next = true;
			} else if ($next) {
				$next = false;
				$words[$i] = strtoupper($words[$i]);
			}
		}
		return $words;
	}

	/**
		Random string generator
			@param $length integer Length of random string
			@param $chars string A string of characters
			@return string Random generated string
	**/
	public static function random(
			$length=10,
			$chars='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'
		) {
		$string = '';    
		for ($p = 0; $p < $length; $p++) {
			$string .= $chars[mt_rand(0, strlen($chars)-1)];
		}
		return $string;
	}
	
	/**
		Convert CamelCase to lower_case_underscored
			@param $string string
			@return string
	**/
	public static function underscore($string) {
		return strtolower(preg_replace(
			// Pattern to match
			'/([^A-Z])([A-Z])/',
			// Replace with
			"$1_$2",
			// Subject
			$string
		));
	}
	
	/**
		Convert lower_case_underscore to CamelCase
			@param $string string
	**/
	public static function camelize($string) {
		return str_replace(" ", "", 
			ucwords(
				str_replace("_", " ", 
					$string)
			)
		);
	}
}

namespace Milk\Utils\Strings;

use Milk\Utils\Strings,
	Milk\Core\Exception;

abstract class Inflector {

	static $plurals 		= array();
	static $singulars 		= array();
	static $uncountables 	= array();
	static $humans 			= array();

	/**
		Add plural word
			@param $rule string
			@param $replacement string
	**/
	public static function plural($rule, $replacement) {
		self::$plurals[$rule] = $replacement;
	}
	
	/**
		Add singular word
			@param $rule string
			@param $replacement string
	**/
	public static function singular($rule, $replacement) {
		self::$singulars[$rule] = $replacement;
	}
	
	/**
		Add irregular word
			@param $singular string
			@param $plural string
	**/
	public static function irregular($singular, $plural) {
		// TODO!
		if (strtoupper($singular) === strtoupper($plural)) {
			$spart1 = $singular[0];
			$spart2 = substr($singular, 1, -1);
			$ppart = substr($plural, 1, -1);
			self::plural("/(#$spart1)#$spart2$/i", '\1'+$ppart);
			self::singular();
		} else {
		}
	}
	
	/**
		Add uncountable word(s)
			@param $words array
	**/
	public static function uncountable(array $words) {
		self::$uncountables = 
			array_merge(self::uncountables, $words);
	}
	
	/**
		Add human word
			@param $rule string
			@param $replacement string
	**/
	public static function human($rule, $replacement) {
		unset(self::$humans[$rule]);
		self::$humans[$rule] = $replacement;
	}
	
	/**
		Clear all
	**/
	public static function clear() {
		// Reset all arrays
		self::$plurals = 
		self::$singulars =
		self::$uncountables	=
		self::$humans = array();
	}
	
	/**
		Pluralize word
			@param $word string
			@return string
	**/
	public static function pluralize($word) {
		if (in_array(mb_strtolower($word), self::$uncountables))
			return $word;
			
		foreach (self::$plurals as $rule => $replace) {
			if (preg_replace($rule, $replace, $word))
				break;
		}
		return $word;
	}
	
	/**
		Singularize word
			@param $word string
			@return string
	**/
	public static function singularize($word) {
		if (in_array(mb_strtolower($word), self::$uncountables))
			return $word;
			
		foreach (self::$singulars as $rule => $replace) {
			if (preg_replace($rule, $replace, $word))
				break;
		}
		return $word;
	}
	
	/**
		Humanize word
			@param $word string
			@return string
	**/
	public static function humanize($word) {
		$word = str_replace("_", " ", $word);
		foreach (self::$humans as $rule => $replace) {
			if (preg_replace($rule, $replace, $word))
				break;
		}
		return ucfirst($word);
	}

	/**
		Tableize word
			@param $word string
			@return string
	**/
	public static function tableize($class) {
		return self::pluralize(
			Strings::underscore(str_replace('\\', '', $class))
		);
	}
}

abstract class Slug {

	public static function split($string) {
		$slen = strlen($string);
		$array = array();
		for ($i=0; $i < $slen; $i++) {
			$array[$i] = $string{$i};
		}
		return $array;
	}

	public static function replaceDiacritics($string) {
		return iconv(
			mb_detect_encoding($string), 
			'US-ASCII//TRANSLIT',
			str_replace(
				array("ä", "ö", "ø", "ü", "ß"),
				array("ae", "oe", "oe", "ue", "ss"),
				$string
			)
		);
	}

	public static function make($string, $maxlen=0) {
		$string = self::replaceDiacritics(mb_strtolower($string));
		$string_tab = str_split($string);
		$numbers = array("0","1","2","3","4","5","6","7","8","9","-");
		foreach($string_tab as $letter) {
			if(in_array($letter, range("a", "z")) || in_array($letter, $numbers)) {
				$new_stringTab[]=$letter;
				//print($letter);
			} elseif($letter==" ") {
				$new_stringTab[]="-";
			}
		}
		if (count($new_stringTab)) {
			$new_string=implode($new_stringTab);
			if($maxlen>0) {
				$new_string=substr($new_string, 0, $maxlen);
			}
			$new_string = self::removeDuplicates('--', '-', $new_string);
		} else {
			$new_string='';
		} 
		return $new_string;
	}
   
	public static function check($slug) {
		return preg_match(
			"/^[a-zA-Z0-9]+[a-zA-Z0-9\_\-]*$/", 
			$slug
		) ? true : false;
	}
   
	public static function removeDuplicates($search, $replace, $subject) {
		$i=0;
		do {
			$subject = str_replace($search, $replace, $subject);         
			$pos = strpos($subject, $search);
			$i++;
			if ($i>100)
				throw new Exception( _("removeDuplicates() loop error") );

		} while ($pos !== false);

		return $subject;
	}

}