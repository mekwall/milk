<?php
namespace Milk\DB;

use Milk\Core\Exception,
	Milk\Utils\Strings,
	Milk\DB\Model\Field;

class Model {

	/**
		The name of the database table to use for this model
		If no value is given, Milk will use the model's name
			@protected
	**/
	protected $table;

	/**
		Array containing all the fields
			@protected
	**/
	protected $fields = array();
	
	/**
		Constructor
			@public
	**/
	public function __construct($options=array()) {
		if (empty($this->table)) {
			$class = get_class($this);
			$class = substr($class, strrpos($class, "\\")+1);
			// Set table name based on model name
			$this->table = Strings\Inflector::tableize($class);
		}
			
		$this->setup();
		if (count($this->fields) == 0) {
		
		} else if (!isset($this->fields["id"])) {
			$this->addField("id", new Field\Auto);
		}
	}
	
	/**
		Setup model
			@protected
	**/
	protected function setup() {}
	
	/**
		Add fields to model
			@protected
			@param $fields array
	**/
	final protected function addFields(array $fields) {
		foreach ($fields as $name => &$field)
			$this->addField($name, $field);
	}
	
	/**
		Add field to model
			@param $name string
			@param $field object
			@protected
	**/
	final protected function addField($name, Model\Field $field) {
		if (empty($field->column))
			$field->column = $name;
		$this->fields[$name] = &$field;
	}
	
	/**
		Get the value of a field
			@param $field mixed
			@return mixed
	**/
	public function __get($field) {
		if (isset($this->fields[$field]))
			return $this->fields[$field]->get();
	}
	
	/**
		Set the value of a field
			@param $field string
			@param $value mixed
			@return boolean
	**/
	public function __set($field, $value) {
		if (isset($this->fields[$field]))
			return $this->fields[$field]->set($value);
		return false;
	}
	
	/**
		Check if value of field is set
			@param $field string
			@return boolean
	**/
	public function __isset($field) {
		return isset($this->fields[$field]);
	}
	
	/**
		Set a value of field to null
			@param $field string
			@return boolean
	**/
	public function __unset($field) {
		return $this->fields[$field] = null;
	}
	
	/**
		Load data into model
			@return object
	**/
	private function load() {
		// Execute beforeLoad
		foreach ($this->fields as $field)
			$field->beforeLoad();
		$this->beforeLoad();
		
		// Execute afterLoad
		foreach ($this->fields as $field)
			$field->afterLoad();
		$this->afterLoad();
		return $this;
	}
	
	/**
		Save data to database
			@return object
	**/
	public function save() {
		// Execute  beforeSave
		foreach ($this->fields as $field)
			$field->beforeSave();
		if (!$this->beforeSave())
			throw new Exception( sprintf(_("Could not save model '%s' to store"), get_class($this)) );

		// Execute  afterSave
		foreach ($this->fields as $field)
			$field->afterSave();
		$this->afterSave();
		return $this;
	}
	
	/**
		Placeholder for beforeLoad
	**/
	protected function beforeLoad() {}
	
	/**
		Placeholder for afterLoad
	**/
	protected function afterLoad() {}
	
	/**
		Placeholder for beforeSave
	**/
	protected function beforeSave() {}
	
	/**
		Placeholder for afterSave
	**/
	protected function afterSave() {}

	/**
		Get or create object based on fields
			@param $fields array
			@return object
	**/
	public function getOrCreate(array $fields){
	}
}