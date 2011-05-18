<?php
namespace Milk\Utils;

abstract class OS {

	/**
		Detect if OS architecture is 64bit
		by checking memory address space
			@return boolean
	**/
	static public function is64() {
		return (int)"9223372036854775807" === 9223372036854775807 ? true : false;
	}
	
}