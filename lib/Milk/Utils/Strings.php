<?php
namespace Milk\Utils;

use \mt_rand, \strlen;

abstract class Strings {

	/**
		Capitalize all words
			@param string Data to capitalize
			@param string Word delimiters
			@return string Capitalized words
	**/
	static public function capitalizeWords($words, $charList = null) {
		//! use ucwords if no delimiters are given
		if (!isset($charList)) {
			return ucwords($words);
		}
		
		//! go through all characters
		$capitalizeNext = true;
		
		for ($i = 0, $max = strlen($words); $i < $max; $i++) {
			if (strpos($charList, $words[$i]) !== false) {
				$capitalizeNext = true;
			} else if ($capitalizeNext) {
				$capitalizeNext = false;
				$words[$i] = strtoupper($words[$i]);
			}
		}

		return $words;
	}

	static public function random($length=10) {
		$characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$string = '';    
		for ($p = 0; $p < $length; $p++) {
			$string .= $characters[mt_rand(0, strlen($characters))];
		}
		return $string;
	}
}