<?php
namespace Milk\Models;

/**
	Base model for RestorePoint
		@package Milk
		@version 0.5.0
**/
class RestorePoint extends BaseModel {

	public static $table = 'restore_points';
	
	private $_old;
	
	public function __construct($table=null, $id=null, $object=null){
		parent::__construct();
		
		if (!empty($table)) {
			$this->load("object_id=$id AND object_table='$table'");
			$this->object_table = $table;
			$this->object_id = $id;
			if (is_object($object))
				$this->data = clone $object;
			else
				$this->data = $object;
		}
	}

	public static function byObjectId($table, $id) {
		$obj = new RestorePoint;
		$obj->load("object_id=$id AND object_table='$table'");
		return $obj;
	}
	
	protected function beforeSave() {
		$this->created = date("Y-m-d h:i:s");
		$this->data = base64_encode(serialize($this->data));
		$this->checksum = md5($this->data);
		if ($this->checksum === $this->_old->checksum)
			return false;
		return true;
	}
	
	protected function afterLoad() {
		if ($this->id > 0) {
			$this->_old = clone $this;
			$this->data = unserialize(base64_decode($this->data));
		}
	}
}