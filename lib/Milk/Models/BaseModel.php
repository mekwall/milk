<?php
namespace Milk\Models;

use \Axon;

class BaseModel extends Axon {

	protected static $table;

	public function __construct() {
		if (empty(static::$table))
			static::$table = strtolower(get_called_class());
		$this->sync(static::$table);
	}

	//! Populate data
	public function populate($data) {
		foreach ($data as $k => $v) {
			$this->$k = $v;
		}
	}
	
	//! Override set
	public function __set($k, $v) {
		if (array_key_exists($k, $this->keys) && empty($this->keys[$k])) {
			$this->keys[$k] = $v;
		}
		parent::__set($k, $v);
	}
	
	//! Reload function
	public function reload() {
		$this->load("id=$this->id");
	}
}