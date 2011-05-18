<?php
namespace Milk\Utils;

use Milk\Models\User;

/**
	Validator
		@package Milk\Utils
		@version 0.5.0
**/
class Validator {
	//! Constants
	const OPTIONAL = 0,
		  REQUIRED = 1;

	//! Field defaults
	protected $defaults = array(
		'type'			=> 'mixed',
		'requirement'	=> self::OPTIONAL,
		'requires'		=> null,			//! Requires another field
		'or'			=> null,			//! or another field
		'max-length'	=> 255,				//! varchar(255)
		'min-length'	=> 0,
		'max-value'		=> 4294967295, 		//! Unsigned INT(11)
		'min-value'		=> 0,
		'default'		=> null,			//! Default value
		'pattern'		=> null,			//! PCRE pattern
		'function'		=> null,			//! Custom validation function
		'date-format'	=> null,			//! date() format
		'insert-before' => null,			//! Insert another value before
		'insert-after'	=> null,			//! Insert another value before
		'compare' 		=> null,			//! Compare two fields
		'exact'			=> null				//! Has exact value
	);
	
	protected $locale = array();
	
	
	public function __construct() {
		$this->locale = localeconv();
	}
	
	//! Some vars
	protected $fields = array(),
			  $errors = array(),
			  $valid = true;
	
	/**
		Checks if valid
			@return boolean
			@public
	**/
	public function isValid() {
		return $this->valid;
	}
	
	/**
		Set fields to be processed
			@return self
			@public
	**/
	public function setFields($fields) {
		$this->fields = is_array($fields) ? $fields : array();
		return $this;
	}
	
	/**
		Return processed fields
			@return array
			@public
	**/
	public function getFields() {
		return $this->fields;
	}
	
	/**
		Return encountered errors
			@return array
			@public
	**/
	public function getErrors() {
		return $this->errors;
	}
	
	/**
		Add encountered error
			@param $field string
			@param $reason string
			@protected
	**/
	protected function error($field, $reason) {
		$this->valid = false;
		if (!isset($this->errors[$field]))
			$this->errors[$field] = array();
		$this->errors[$field][] = $reason;
	}
	

	/**
		Check conditions on a field
			@param $field object
			@param $value mixed
			@param $cond array
			@protected
	**/	
	protected function checkConditions($field, $value, $cond) {
		if ($cond == null)
			return $value;
	
		//! Merge default conditions with provided conditions
		$cond = array_merge($this->defaults, $cond);
		
		//! Process the value if a process function was given
		if (!empty($cond['process']))
			$value = $cond['process']($value);
		
		//! Check if required and exists
		if  (($cond['requirement'] == self::REQUIRED) && empty($value)) {
			//! Check that required field cannot be another one
			if (empty($cond['or']) && empty($this->fields[$cond['or']]['value']))
				$this->error($field, !empty($cond['error']) ? $cond['error'] : "Field is required to have a value");
		} else {
			//! Insert before/after
			if (!empty($cond['insert-before'])) {
				$orgval = $value;
				$value = $this->fields[$cond['insert-before']]['value'].$value;
			}
			if (!empty($cond['insert-after'])) {
				$orgval = $value;
				$value .= $this->fields[$cond['insert-after']]['value'];
			}
			
			if (!empty($value)) {
				//! Check if other field is required
				if (!empty($cond['requires'])) {
					if (!is_array($cond['requires'])) {
						$cond['requires'] = array($cond['requires']);
					}
					foreach ($cond['requires'] as $req) {
						if (empty($this->fields[$req]['value'])) {
							$this->error($req, "Value is required by another field: ".$req);
							$this->error($field, "Required field $req does not have a value");
						}
					}
				}
				
				//! Compare with provided pattern if there is one
				if (!empty($cond['pattern']))
					if (!preg_match($cond['pattern'], $value))
						$this->error($field, !empty($cond['error']) ? $cond['error'] : "Value does not match pattern: ".$cond['pattern']);
						
				//! Run provided function if there is one
				if (!empty($cond['function']) && is_callable($cond['function']))
					if (!call_user_func($cond['function'], $value))
						$this->error($field, !empty($cond['error']) ? $cond['error'] : "Value failed custom function validation");
						
				//! Compare to other field
				if (!empty($cond['compare'])) {
					if ($value != $this->fields[$cond['compare']]['value'])
						$this->error($field, !empty($cond['error']) ? $cond['error'] : "Value must have the same value as ".$cond['compare']);
				}
						
				//! Check exact value match
				if (!empty($cond['exact'])) {
					if ($cond['type'] == 'password') {
						if (!User::checkPassword($value, $cond['exact'])) {
							$this->error($field, !empty($cond['error']) ? $cond['error'] : "Value does not match compared password");
						}
					} elseif ($value !== $cond['exact']) {
						$this->error($field, !empty($cond['error']) ? $cond['error'] : "Value does not match exact value");
					}
				}
			}

			switch ($cond['type']) {
				case 'boolean':
					if (empty($value) || $value == 0 || $value == '')
						$value = false;
					elseif ($value == 1)
						$value = true;
					if (!empty($value) && !filter_var($value, FILTER_VALIDATE_BOOLEAN))
						$this->error($field, !empty($cond['error']) ? $cond['error'] : "Value is not a boolean");
				break;
			
				//! Number-based fields
				case 'float':
				case 'double':
					
					if (!empty($value)) {
						$value = Numbers::restoreDecimal($value);
						if (is_numeric($value)) {
							$value = Numbers::toFloat($value);
						}
						if (!is_float($value))
							$this->error($field, !empty($cond['error']) ? $cond['error'] : "Value is not a float/double");
							
						if (!empty($cond['max-value']))
							if ($value > $cond['max-value'])
								$this->error($field, !empty($cond['error']) ? $cond['error'] : "Value is exceeding max-value of ".$cond['max-value']);
						
						if (!empty($cond['min-value']))
							if ($value < $cond['min-value'])
								$this->error($field, !empty($cond['error']) ? $cond['error'] : "Value is below min-value of ".$cond['min-value']);
					}

				break;
				
				case 'integer':
					if (!empty($value) && !filter_var($value, FILTER_VALIDATE_INT))
						$this->error($field, !empty($cond['error']) ? $cond['error'] : "Value is not an integer");
				case 'numeric':
				case 'number':
					if (!empty($value) && (count($this->errors[$field]) == 0)) {
						if (!is_numeric($value)) {
							$this->error($field, !empty($cond['error']) ? $cond['error'] : "Value is not a number");
						} else {
							if ($value > $cond['max-value'])
							$this->error($field, !empty($cond['error']) ? $cond['error'] : "Value is exceeding max-value of ".$cond['max-value']);
							
							if ($value < $cond['min-value'])
								$this->error($field, !empty($cond['error']) ? $cond['error'] : "Value is below min-value of ".$cond['min-value']);
						}
					}
				break;
				
				//! Date-based fields
				case 'datetime':
				case 'date':
					if (!empty($value)) {
						try {
							if (!empty($cond['date-format']))
								$dt = \DateTime::createFromFormat($cond['date-format'], $value);
							else
								$dt = new \DateTime($value);
								
							//! Throw a generic Exception if $dt is false and DateTime didn't do it itself
							if (!$dt)
								throw new \Exception();
								
							if (!empty($cond['date-range'])) {
								if ($dt < $cond['date-range']['from'])
									$this->error($field, !empty($cond['error']) ? $cond['error'] : "Value cannot be earlier then ".$cond['date-range']['from']->format('Y-m-d H:i:s'));
								if ($dt > $cond['date-range']['to'])
									$this->error($field, !empty($cond['error']) ? $cond['error'] : "Value cannot be later then ".$cond['date-range']['to']->format('Y-m-d H:i:s'));
							}
							
							//! Save the DateTime object to value
							$value = $dt;
						} catch (\Exception $e) {
							if (!empty($cond['date-format']))
								$this->error($field, !empty($cond['error']) ? $cond['error'] : "Value does not match date format: ".$cond['date-format']);
							else
								$this->error($field, !empty($cond['error']) ? $cond['error'] : "Value is not a valid date");
						}
					}
				break;
				
				//! Custom fields
				case 'ip':
					if (!empty($value) && !filter_var($value, FILTER_VALIDATE_IP))
						$this->error($field, !empty($cond['error']) ? $cond['error'] : "Value is not a valid IP");
				
				case 'url':
					if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL))
						$this->error($field, !empty($cond['error']) ? $cond['error'] : "Value is not a valid URI");
				break;
				
				case 'email':
					if (!empty($value) && !self::validateEmail($value))
						$this->error($field, !empty($cond['error']) ? $cond['error'] : "Value is not a valid email address");
				break;
						
				//! String-based fields
				case 'password':
				case 'string':
					if (!empty($value) && !is_string($value))
						$this->error($field, !empty($cond['error']) ? $cond['error'] : "Value is not a string");
				case 'mixed':
					if (!empty($value)) {
						if (strlen($value) > $cond['max-length'])
							$this->error($field, !empty($cond['error']) ? $cond['error'] : "Value is exceeding max-length of ".$cond['max-length']);
							
						if (strlen($value) < $cond['min-length'])
							$this->error($field, !empty($cond['error']) ? $cond['error'] : "Value is below min-length of ".$cond['min-length']);
					}
				break;
			}
			
			//! If insert-before/after was used, restore original value
			if ($cond['insert-before'] || $cond['insert-after'])
				$value = $orgval;
		}
				
		//! Set default value if no value was given
		if (empty($value) && !empty($cond['default']))
			$value = $cond['default'];
			
		return $value;
	}
	

	/**
		Run the validation process
			@return boolean
			@protected
	**/
	public function validate() {
		foreach ($this->fields as $field => $opts) {
			//! Multiple values
			if (is_array($opts['value'])) {
				foreach ($opts['value'] as $i => $subfields) {
					if (is_array($subfields))
						foreach ($subfields as $sfield => $value) {
							$this->fields[$field]['value'][$i][$sfield] = $this->checkConditions($field."[$i][$sfield]", $value, $opts[$sfield]['validate']);
						}
				}
			} else {
				if (count($opts) > 0)
					$this->fields[$field]['value'] = $this->checkConditions($field, isset($opts['value']) ? $opts['value'] : null, $opts['validate']);
			}
		}	
		return $this->valid;
	}
	
	/**
		Validate email address
	**/
	static function email($address) {
		return filter_var($address, FILTER_VALIDATE_EMAIL) ? true : false;
	}
}