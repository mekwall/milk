<?php
namespace Milk\DB\Model;

use Milk\Utils\Strings;

class Field {

	/**
		The name of the database column to use for this field. 
		If this isn't given, Milk will use the field's name.
	**/
	public $column;
	
	/**
		If true, this field is the primary key for the model.
	**/
	protected $primary_key = false;
	
	/**
		If true, this field must be unique throughout the table.
	**/
	protected $unique = false;
	
	/**
		If true, will output a CREATE INDEX statement for this field.
	**/
	protected $index = false;
	
	/**
		The name of the database tablespace to use for this field's index,
		if this field is indexed. If the backend doesn't support tablespaces, 
		this option is ignored.
	**/
	protected $tablespace;
	
	/**
		The default value for the field. This can be a value or a callable object.
		If callable it will be called every time a new object is created.
	**/
	protected $default;
	
	/**
		If false, the field will not be editable in the admin or via forms 
		automatically generated from the model class.
	**/
	protected $editable = true;
	
	/**
		Extra "help" text to be displayed under the field on the object's form. 
		It's useful for documentation even if your object doesn't have a form.
	**/
	protected $help_text;

	/**
		A human-readable name for the field. If the verbose name isn't given, 
		Milk will automatically create it using the field's attribute name, 
		converting underscores to spaces.
	**/
	protected $verbose_name;
	
	/**
		A list of validators to run for this field.
	**/
	protected $validators;
	
	/**
		The value of the field
	**/
	protected $value;
	
	public function __construct($options = array()) {
		foreach ($options as $option => $value) {
			$this->$option = $value;
		}
	}
	
	public function create() {}
	public function beforeSave() {}
	public function afterSave() {}
	public function beforeLoad() {}
	public function afterLoad() {}
	
	/**
		Validate the value of the field
			@param $value mixed
			@return boolean
	**/
	protected function validate($value=null) {
		if (!empty($value)) {
		} else {
			$value = $this->value;
		}
		return true;
	}
	
	/**
		Set the value of the field
			@param $value mixed
			@return boolean
	**/
	public function set($value) {
		if (!$this->validate($value))
			return false;
		$this->value = $value;
		return true;
	}
	
	/**
		Get the value of the field
			@return mixed
	**/
	public function get() {
		return $this->value;
	}
}


namespace Milk\DB\Model\Field;

use Milk\DB\Model\Field;

/**
	A 64 bit integer, much like an IntegerField except that it is guaranteed 
	to fit numbers from -9223372036854775808 to 9223372036854775807
**/
class BigInteger extends Field {
	protected $max_value = 9223372036854775807;
	protected $min_value = -9223372036854775808;
}

class Boolean extends Field {
	protected $possible_values = array(true, false);
}

class Char extends Field {
	protected $max_length;
}

class CommaSeparatedInteger extends Field {
	protected $max_length;
}

class Date extends Field {
	protected $auto_now 	= FALSE;
	protected $auto_now_add = FALSE;
	
	public function set($value) {
		if (!($value instanceOf \DateTime))
			$value = new \DateTime($value);
		parent::set($value);
	}
	
	public function beforeSave() {
		if ($this->value instanceOf \DateTime)
			$this->value = $this->value->format("Y-m-d");
	}
	
	public function afterLoad() {
		$this->value = \DateTime::createFromFormat("Y-m-d", $this->value);
	}
}

class DateTime extends Field {
	protected $auto_now 	= FALSE;
	protected $auto_now_add = FALSE;
	
	public function set($value) {
		if (!($value instanceOf \DateTime))
			$value = new \DateTime($value);
		parent::set($value);
	}
	
	public function beforeSave() {
		if ($this->value instanceOf \DateTime)
			$this->value = $this->value->format("Y-m-d H:i:s");
	}
	
	public function afterLoad() {
		$this->value = \DateTime::createFromFormat("Y-m-d H:i:s", $this->value);
	}
}

class Time extends Field {
	protected $auto_now		= FALSE;
	protected $auto_now_add	= FALSE;
}

class Text extends Field {
}

class Email extends Field {
	protected $max_length = 254;
}

class Integer extends Field {
}

class PositiveInteger extends Field {
}

class SmallInteger extends Field {
}

class PositiveSmallInteger extends Field {
}

class Decimal extends Field {
	protected $max_digits;
	protected $decimal_places;
}

class Auto extends Integer {
	protected $auto_increment = true;
	protected $primary_key = true;
}

class IPAddress extends Field {
}

class NullBoolean extends Field {
}

class Float extends Field {
}

class URLField extends Char {
	protected $verify_exists = true;
	protected $max_length = 200;
}

class Slug extends Char {
}

class File extends Field {
	protected $upload_to;
	protected $storage;
	
	public function open($mode='rb') {
	}
	
	public function close() {
	}
	
	public function save($name, $content, $save=TRUE) {
	}
	
	public function delete($save=TRUE) {
	}
}

class FilePath extends Field {
	protected $path;
	protected $match;
	protected $recursive = TRUE;
}

class Image extends File {
	protected $order_field;
	protected $height_field;
	protected $width_field;
}

class XMLField extends Field {
	protected $schema_path;
}


class Relation extends Field {

	const DO_NOTHING	= 0;
	const CASCADE 		= 1;
	const PROTECT 		= 2;
	const SET_NULL 		= 3;
	const SET_DEFAULT 	= 4;
	
	protected $related_name;
	protected $limit_choices_to;
}

class ForeignKey extends Relation {
	protected $other_model;
	protected $to_field;
	protected $on_delete = Relation::CASCADE;
	protected $on_update = Relation::CASCADE;
}

class ManyToMany extends Relation {
	protected $through;
	protected $table;
	protected $symmetrical;
}

class OneToOne extends Relation {
	protected $parent_link;
}