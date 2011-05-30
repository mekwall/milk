<?php
namespace Milk\Core\Cache\Backend;

/**
	Wrapper for APC
**/
class APC {

	/**
		Get a value from cache
	**/
	public function get($key) {
		return apc_fetch($key);
	}
	
	/**
		Put a value into cache
	**/
	public function put($key, $val, $ttl=0) {
		return apc_add($key, $val, $ttl);
	}
	
	/**
		Increase a stored integer
	**/
	public function inc($key, $amount) {
		return apc_inc($key, $amount);
	}
	
	/**
		Decrease a stored integer
	**/
	public function dec($key, $amount) {
		return apc_dec($key, $amount);
	}
	
	/**
		Check if key is cached
	**/
	public function exists($key) {
		return apc_exists($key);
	}
	
	public function clear($all=false) {
		
	}
	
	public function info() {
		return apc_sma_info();
	}

}