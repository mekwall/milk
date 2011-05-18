<?php
namespace Milk\WebService;

use \F3;

use \Milk\Utils\Validator;

class Service {

    //! Validator constants
	const OPTIONAL = Validator::OPTIONAL;
	const REQUIRED = Validator::REQUIRED;

    public function __construct($transport, $request) {
        $this->transport = $transport;
        $this->current_request = $request;
    }

    /**
		CRUD placeholder functions
	**/
	public function create() {
		return $this->error(501);
	}
	
	public function retrieve() {
		return $this->error(501);
	}

	public function update() {
		return $this->error(501);
	}
	
	public function delete() {
		return $this->error(501);
	}
    
    public function options() {
		$ref = new ReflectionClass(get_class($this));
		$data = $ref->getMethods(ReflectionMethod::IS_PUBLIC);
		$this->results(200, $data);
	}
    
    /**
		Validation
	**/
	protected function validate($fields, $values=null) {
		//! instantiate validator
		$validator = new Validator();
		
		//! if there's no values passed, use args from current request
		$values = $values ? $values : $this->current_request->args;
		
		//! build fields array
		foreach ($fields as $name => $opt) {
			$field = array('validate' => $opt);
			if (isset($values->$name)) {
				$field['value'] = (array)$values->$name;
			}
			$fields[$name] = $field;
		}
		
		//! convert from object to array
		if (is_object($fields))
			$fields = $this->objectToArray($fields);

		$validator->setFields($fields);
	    $this->valid = $validator->validate();
		if (!$this->valid)
			$this->errors = $validator->getErrors();
		
		//! return validation result
		return $this->valid;
	}
	
	protected function objectToArray($mixed) {
		if(is_object($mixed)) $mixed = (array)$mixed;
		if(is_array($mixed)) {
			$new = array();
			foreach($mixed as $key => $val) {
				$key = preg_replace("/^\\0(.*)\\0/","",$key);
				$new[$key] = $this->objectToArray($val);
			}
		} 
		else $new = $mixed;
		return $new;        
	}
	
	protected function getErrors() {
		return $this->errors;
	}
    
    /**
		Transport responses
	**/
	protected function result($data, $cache=false, $http_code=200) {
		return $this->transport->result($data, $cache, $http_code, $this->current_request->id);
	}

	protected function success($result, $data=null, $cache=null, $http_code=200) {
		return $this->transport->success($result, $data, $cache, $http_code, $this->current_request->id);
	}

	protected function error($http_code=500, $error=null, $data=null) {
		return $this->transport->error($http_code, $error, $data, $this->current_request->id);
	}
	
	/**
		Shorthand functions
	**/
	protected function syntaxError($error=null, $data=null) {
		return $this->error(400, $error, $data);
	}
	
	protected function conflict($object='', $data=null) {
		return $this->error(409, $object.' already exists', $data);
	}
	
	protected function notFound($object, $data=null) {
		return $this->error(404, $object.' could not be found', $data);
	}
	
	protected function created($object, $data=null) {
		return $this->success($object.' created', $data, false, 201);
	}
	
	protected function updated($object, $data=null) {
		return $this->success($object.' updated', $data, false);
	}
	
	protected function deleted($object, $data=null) {
		return $this->success($object.' deleted', $data, false);
	}
}
