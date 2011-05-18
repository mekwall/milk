<?php
namespace Milk\Forms;

use \F3 as F3;
use \Milk\Utils\Validator as Validator;

class Form {
	
	protected $fields = array(),
			  $data = array(),
			  $input = array(),
			  $errors = array();
			  
	protected $valid = true,
			  $model = false;
			  
	protected $field_defaults = array(
		'type' => 'single', // Single or multiple field
		'fields' => array(),
	);
			  
	public	$form_name;

    public function __construct($data=array(), $method=null, $object=null) {
		//! Set form name
		$this->form_name = 'form_'.strtolower(substr(get_class($this), strrpos(get_class($this),'\\')+1));
	
		//! Get data
		if ($this->model)
			$this->getFromModel($object);
		else
			$this->data = $data;

		if (!empty($method)) {
			//! Get input data
			switch ($method) {
				case 'GET':
					$inputs = F3::scrub($_GET);
				break;
			
				case 'POST':
					$inputs = F3::scrub($_POST);
				break;
				
				case 'PUT':
				case 'DELETE':
					parse_str(file_get_contents("php://input"), $inputs);
				break;
			}
			
			$this->input = $inputs;
		}
		
		//! Setup fields
		$this->setupFields();

		//! Make fields available to twig
		F3::twigset($this->form_name, $this->fields);
	}
	
	public function __get($field) {
		if (isset($this->fields[$field]))
			return $this->fields[$field];
	}
	
	protected function setupFields() { /** Overide this function to setup your fields */ }
	
	protected function getFromModel() { }
	
	protected function saveToModel() { }
	
	public function validate() {
		//! Prepare array that can be sent to validator
		$valfields = array();
		foreach ($this->fields as $name => $field) {
			if (is_array($field)) {
				foreach ($field as $i => $subfield) {
					if (is_array($subfield) && (count($subfield) > 0)) {
						foreach ($subfield as $j => $subfield2) {
							$valfields[substr($subfield2->name, 0, strpos($subfield2->name, '['))][$j]['validate'] = $subfield2->validate;
						}
					} else {
						$valfields[substr($subfield->name, 0, strpos($subfield->name, '['))] = array('validate' => $subfield->validate);
					}
				}
			} else {
				$valfields[$field->name] = array('validate' => $field->validate);
			}
		}
		
		if (method_exists($this, 'feedValues')) {
			$valfields = call_user_func(array($this, 'feedValues'), $valfields);
		} else {
			//! Feed the values to the fields
			foreach ($this->input as $field => $input) {
				$valfields[$field]['value'] = $input;
			}
		}
		
		//! Let's run the validator
		$validator = new Validator();
		$validator->setFields($valfields);
		
		$this->valid = $validator->validate();
		if (!$this->valid) {
			$this->errors = $validator->getErrors();
		} else {
			$fields = $validator->getFields();
			
			foreach ($fields as $field => $opts) {
				$field = str_replace($this->form_name.'_','',$field);
				if (isset($this->fields[$field])) {
					if (is_array($opts['value'])) {
						foreach ($opts['value'] as $i => $subfields) {
							foreach ($subfields as $subfield => $val) {
								$this->fields[$field][$i][$subfield]->value = $val;
							}
						}
					} else {
						$this->fields[$field]->value = $opts['value'];
					}
				}
			}
		}
		return $this->valid;
	}
	
	public function getErrors() {
		return $this->errors;
	}
	
	public function save() {
		if ($this->model)
			$this->saveToModel();
	}
	
	public function setValues($values) {
		$this->values = F3::scrub($values);
	}
	
	public function addField($name, $options=array(), $validate=null) {
		$options = array_merge($this->field_defaults, !is_null($options) ? $options : array());
		
		switch ($options['type']) {
			case 'multiple':
				$this->fields[$name] = array();
				if (is_array($this->data[$name])) {
					foreach ($this->data[$name] as $i => $fields) {
						foreach ($fields as $field => $value) {
							$fobj = new Field(
								$this->form_name.'_'.$name."[$i][".$field."]",
								$options['fields'][$field][0],
								$options['fields'][$field][1]
							);
							$fobj->value =  $value;
							$this->fields[$name][$i][$field] = $fobj;
						}
					}
				} else {
					$this->fields[$name][0] = array();
					foreach ($options['fields'] as $field => $data) {
						$fobj = new Field(
							$this->form_name.'_'.$name.'[0]['.$field.']',
							$data[0],
							$data[1]
						);
						$this->fields[$name][0][$field] = $fobj;
					}
				}
			break;
			
			case 'single':
				$field = new Field($this->form_name.'_'.$name, $options, $validate);
				$field->value = isset($this->data[$name]) ? $this->data[$name] : null;
				$this->fields[$name] = $field;
			break;
		
		}
	}
	
	public function addFields($fields) {
		foreach ($fields as $field) {
			$this->addField(
				$field[0], 
				isset($field[1]) ? $field[1] : null,
				isset($field[2]) ? $field[2] : null
			);
		}
	}
}

class Field {
	public $name;
	public $value;
	public $options = array();
	public $validate = array();

	public function __construct($name, $options, $validate) {
		$this->name = $name;
		$this->options = is_array($options) ? $options : array();
		$this->validate = is_array($validate) ? $validate : array();
	}
	
	public function __toString() {
		return $this->name;
	}
}