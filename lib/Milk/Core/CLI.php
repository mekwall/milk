<?php
namespace Milk\Core;

use Milk\Core\CLI\Colors;

/**
	Milk\Core\CLI
	Class to help build CLI-based apps and scripts
		@package Milk
		@subpackage Core
		@version 0.5.0
**/
class CLI {

	/**
		Output a message
			@param $message string
			@param $eol boolean
	**/
	public function out($message, $eol=true) {
		echo $message.($eol ? PHP_EOL : '');
	}
	
	/**
		Output a line
	**/
	public function line() {
		self::out("---------------------------------");
	}
	
	/**
		Output an error
	**/
	public function error($message) {
		self::out(Colors::paint("Error: ".$message, "red"));
	}
	
	public function warning($message) {
		self::out(Colors::paint("Warning: ".$message, "yellow"));
	}
	
	public function outOk() {
		self::out("[".Colors::paint("OK", "green")."]", false);
	}
	
	public function outError() {
		self::out("[".Colors::paint("ERROR", "red")."]", false);
	}
	
	public function outWarning() {
		self::out("[".Colors::paint("WARNING", "yellow")."]", false);
	}
	
	public function eol() {
		echo PHP_EOL;
	}
}

namespace Milk\Core\CLI;

/**
	CLI helper class for adding colors to strings
**/
class Colors {

	// Foreground color table
	private static $fg = array(
		'black' 		=> '0;30',
		'dark_grey' 	=> '1;30',
		'red'			=> '0;31',
		'light_red'		=> '1;31',
		'green'			=> '0;32',
		'light_green'	=> '1;32',
		'brown'			=> '0;33',
		'yellow'		=> '1;33',
		'blue' 			=> '0;34',
		'light_blue' 	=> '1;34',
		'purple'		=> '0;35',
		'light_purple'	=> '1;35',
		'cyan'			=> '0;36',
		'light_cyan'	=> '1;36',
		'light_gray'	=> '0;37',
		'white'			=> '1;37'
	);
	
	// Background color table
	private static $bg = array(
		'black'			=> '40',
		'red'			=> '41',
		'green'			=> '42',
		'yellow'		=> '43',
		'blue'			=> '44',
		'magenta'		=> '45',
		'cyan'			=> '46',
		'light_gray'	=> '47'
	);
	
	/**
		Paint a string
			@param $string string
			@param $fg string
			@param $bg string
			@return string 
	**/
	public static function paint($string, $fg=null, $bg=null) {
		$coloring = "";

		// Paint foreground
		if (isset(self::$fg[$fg]))
			$coloring .= "\033[" . self::$fg[$fg] . "m";
		
		// Paint background
		if (isset(self::$bg[$bg]))
			$coloring .= "\033[" . self::$bg[$bg] . "m";

		// Let's paint the string
		$painted = $coloring . $string . "\033[0m";
		return $painted;
	}
}