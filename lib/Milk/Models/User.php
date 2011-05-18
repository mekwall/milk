<?php
namespace Milk\Models;
/**
-- 
-- Drop tables
-- 

DROP VIEW IF EXISTS `view_users_all`;
DROP TABLE IF EXISTS `users_numbers`;
DROP TABLE IF EXISTS `users_data`;
DROP TABLE IF EXISTS `users_reset_tokens`;
DROP TABLE IF EXISTS `users_login_tokens`;
DROP TABLE IF EXISTS `users`;

-- 
-- Structure for tables
-- 

CREATE TABLE IF NOT EXISTS `users` (
	`id` bigint(20) unsigned not null auto_increment,
	`uuid` char(36) not null,
	`active` tinyint(1) unsigned default '0',
	`activation_code` char(24),
	`group_id` int(10) unsigned default '0',
	`username` varchar(32),
	`password` char(64),
	`email` varchar(255),
	`firstname` varchar(128),
	`lastname` varchar(128),
	`when_created` datetime,
	PRIMARY KEY (`id`),
	UNIQUE KEY (`uuid`),
	UNIQUE KEY (`username`),
	UNIQUE KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `users_data` (
	`id` bigint(20) unsigned not null auto_increment,
	`user_id` bigint(20) unsigned,
	`birthdate` date,
	`serial` smallint(4) unsigned,
	`address` varchar(128),
	`zipcode` mediumint(5) unsigned,
	`city` varchar(64),
	PRIMARY KEY (`id`),
	UNIQUE KEY `user_id` (`user_id`),
	KEY `birthdate` (`birthdate`),
	CONSTRAINT `fk_users_data`
	FOREIGN KEY (`user_id`) REFERENCES users(`id`)
			ON DELETE CASCADE
			ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `users_numbers` (
	`id` bigint(20) unsigned not null auto_increment,
	`user_id` bigint(20) unsigned,
	`country_code` smallint(4) unsigned,
	`number` int(10) unsigned,
	`sms_capable` tinyint(1) unsigned,
	PRIMARY KEY (`id`),
	KEY `user_id` (`user_id`),
	KEY `country_code` (`country_code`),
	KEY `sms_capable` (`sms_capable`),
	CONSTRAINT `fk_users_numbers`
	FOREIGN KEY (`user_id`) REFERENCES users(`id`)
			ON DELETE CASCADE
			ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `users_login_tokens` (
	`id` bigint(20) unsigned not null auto_increment,
	`user_id` bigint(20) unsigned not null,
	`expires` datetime,
	`token` char(64),
	PRIMARY KEY (`id`),
	KEY `token` (`token`),
	KEY `user_id` (`user_id`),
	CONSTRAINT `fk_users_login_tokens`
	FOREIGN KEY (`user_id`) REFERENCES users(`id`)
			ON DELETE CASCADE
			ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `users_reset_tokens` (
	`id` bigint(20) unsigned not null auto_increment,
	`uuid` varchar(36),
	`expires` datetime,
	`token` varchar(64),
	PRIMARY KEY (`id`),
	KEY `token` (`token`),
	KEY `uuid` (`uuid`),
	CONSTRAINT `fk_users_reset_tokens`
	FOREIGN KEY (`uuid`) REFERENCES users(`uuid`)
			ON DELETE CASCADE
			ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE VIEW `view_users_all` AS
	SELECT
		u.*,
		d.`birthdate` AS data_birthdate,
		d.`serial` AS data_serial,
		d.`address` AS data_address,
		d.`zipcode` AS data_zipcode,
		d.`city` AS data_city,
		n.`id` AS number_id,
		n.`country_code` AS number_country_code,
		n.`number` AS number_number,
		n.`sms_capable` AS number_sms_capable
	FROM
		`users` AS u
		LEFT JOIN
			`users_data` AS d ON
				d.`user_id`=u.`id`
		LEFT JOIN
			`users_numbers` AS n ON
				n.`user_id`=u.`id`;
	
**/

use \F3,
	\DateTime;

use \Milk\Utils\UUID,
	\Milk\Models\BaseModel;

/**
	Base model for User
		@package Milk
		@version 0.5.1
**/
class User extends BaseModel {

	public static $table          	 	= 'users';
	public static $session_var       	= 'USER';
	public static $cookie				= '__login_token';

	//! Models
	protected static $user_model		= 'Milk\Models\User';
	protected static $data_model		= 'Milk\Models\User\Data';
	protected static $number_model		= 'Milk\Models\User\Number';
	protected static $token_model		= 'Milk\Models\User\LoginToken';
	protected static $reset_model		= 'Milk\Models\User\ResetToken';
	
	//! Table data usage
	//! TODO: Should rethink this
	protected static $_table_data		= true;
	protected static $_table_numbers	= true;
	
	//! Login field to use
	protected static $login_field		= 'username';
	
	public $data = null;
	public $numbers = array();

	/**
		Public functions
	**/
	public function __construct($data=null) {
		$this->sync(static::$table);
        if (is_array($data)) {
            foreach ($data as $key => $val) {
                $this->{$key} = $val;
            }
        }
	}
	
	//! Do this before Axon saves the object
	public function beforeSave() {
        //! If id is empty this is a new user
        if (empty($this->id)) {
            $app_uuid = F3::get('APP_UUID');
            //! Generate a v5 uuid based on app uuid and login field
			$login_field = static::$login_field;
            $this->uuid = UUID::v5($app_uuid, 'user.'.$this->$login_field);
            //! If there's no password set, lets just generate a random one
            if (empty($this->password))
                $this->password = \Milk\Utils\Strings::random(8);
            //! Let's hash the password as well
            $this->password = static::passwordHash($this->password);
			//! Let's set the creation time
			$dt = new DateTime('now');
			$this->when_created = $dt->format('Y-m-d H:i:s');
        }
        return true;
    }
	
	//! Do this after Axon load the object
	public function afterLoad() {
		//! Typecasting
		$this->active = (int)$this->active;
		$this->group_id = (int)$this->group_id;
	}
	
	//! Do this after Axon saved the object
	public function afterSave() {
		if ($this->data) {
			if (empty($this->data->user_id))
				$this->data->user_id = $this->id;
			$this->data->save();
		}
		foreach ($this->numbers as $number) {
			if (empty($number->user_id))
				$number->user_id = $this->id;
			$number->save();
		}
	}
    
    //! Do this before erasing a user
    public function beforeErase() {
        \Milk\Utils\S3::deleteObject(
            F3::get('MILK.S3.BUCKET'),
            md5($this->uuid)
        );
        return true;
    }
	
	//! Login current user
	public function login() {
		if (method_exists($this, "beforeLogin"))
			$this->beforeLogin();
	
		//! Save this to session
		$_SESSION[static::$session_var] = &$this;
		
		if (method_exists($this, "afterLogin"))
			$this->afterLogin();
			
        return true;
    }
	
	//! Logout current user
	public function logout() {
		$umodel = static::$user_model;
		//! Remove auto-login token
		$token = isset($_COOKIE[$umodel::$cookie]) ? $_COOKIE[$umodel::$cookie] : false;
		if ($token) {
			$model = $umodel::$token_model;
			$token = $model::byToken($token);
			if ($token->uuid)
				$token->erase();
			//! Unset login token cookie
			// TODO: Add better support for domain specific cookies
			$host = F3::get('DOMAIN') ? F3::get('DOMAIN') : gethostbyaddr($_SERVER['SERVER_ADDR']);
			setcookie($umodel::$cookie, '', time()-3600, '/', $host);
		}
		//! Remove from session
		unset($_SESSION[$umodel::$session_var]);
		return true;
	}
	
	//! Returns a string with both firstname and lastname
	public function fullName() {
		return $this->lastname != '' && $this->lastname != '' ? 
			$this->firstname . ' ' . $this->lastname : 
			$this->firstname.$this->lastname;
	}
	
	//! Creates autologin token for current user
	public function createAutoLoginToken() {
		$umodel = static::$user_model;
		if (!isset($_COOKIE[$umodel::$cookie])) {
			$token = new $umodel::$token_model($this->uuid);
			$token->save();
			//! Let's save the token to a cookie
			// TODO: Add better support for domain specific cookies
			$host = F3::get('DOMAIN') ? F3::get('DOMAIN') : gethostbyaddr($_SERVER['SERVER_ADDR']);
			setcookie($umodel::$cookie, $token->token, strtotime($token->expires), '/', $host);
			return $token;
		} else {
			$model = $umodel::$token_model;
			$token = $model::byToken($_COOKIE[$umodel::$cookie]);
			if (!$token->id) {
				$this->removeAutoLogin();
				$this->createAutoLoginToken();
			}
			return $token;
		}
		return $_COOKIE[$umodel::$cookie];
	}
	
	//! Remove autologin token
	public function removeAutoLogin() {
		$model = static::$token_model;
		$umodel = static::$user_model;
		$token = $model::byToken($_COOKIE[$umodel::$cookie]);
		if (!empty($token->uuid))
			$token->erase();
		setcookie($umodel::$cookie, '', time()-3600);
		unset($_COOKIE[$umodel::$cookie]);
		return true;
	}
	
	//! Load user data
	public function getUserData($reload=false) {
		if (!isset($this->data) || $reload)
			$this->data = new static::$data_model($this->id);
		return $this;
	}
	
	//! Update user password
	public function updatePassword($newpass) {
		if ($newpass != $this->password)
			$this->password = static::passwordHash($newpass);
	}
	
	//! Reset password
	public function resetPassword() {
		$token = new static::$reset_model($this->uuid);
		//! check for existing token
		if (!empty($token->token)) {
			//! determine if token have expired or not
			if (new DateTime($token->expires) < new DateTime("now")) {
				//! token expired, erase and create new one
				$token->erase();
				$token = new static::$reset_model($this->uuid);
				$token->save();
			} else {
				return false;
			}
		} else {
			$token->save();
		}
		return $token;
	}
	
	//! Validate reset token
	public function validateResetToken($token) {
		$model = static::$reset_model;
		if ($token = $model::byToken($token)) {
			if (new DateTime($token->expires) > new DateTime("now")) {
				return $token;
			}
		}
		return false;
	}
	
	//! Activate user
	public function activate() {
		$this->active = 1;
		$this->save();
	}
	
	//! Is user an administrator?
	public function isAdmin() {
		return $this->group_id == 1 ? true : false;
	}
	
	/**
		Static functions
	**/
    public static function byField($field, $value, $include_data=false) {
		$user = false;
        switch (gettype($value)) {
            case 'boolean':
				$value = $value ? 1 : 0;
			break;
			
			case 'string':
                $value = "'$value'";
            break;
        }
        if ($include_data) {
            $sql = "SELECT * FROM `view_users_all` WHERE `$field`=$value";
			$result = F3::sql($sql);
			if (count($result)) {
				$usr = array();
				$data = array();
				$numbers = array();
				foreach ($result as $i => $row) {
					foreach ($row as $field => $value) {
						if ($value == null)
							continue;
						if (strpos($field, 'data_') !== false) {
							$data[str_replace('data_', '', $field)] = $value;
						} else if (strpos($field, 'number_') !== false) {
							$numbers[$i][str_replace('number_', '', $field)] = $value;
						} else {
							$usr[$field] = $value;
						}	
					}
				}
				$user = new static::$user_model($usr);
				if (count($data) > 0)
					$user->data = new static::$data_model($data);

				foreach ($numbers as $i => $data) {
					$number = new static::$number_model();
					$number->id = $data['id'];
					$number->user_id = $user->id;
					$number->number = $data['number'];
					$number->sms_capable = $data['sms_capable'];
					$number->country_code = $data['country_code'];
					$user->numbers[$data['id']] = $number;
				}
				return $user;
			}
        } else {
			$user = new static::$user_model;
			$user->load("`$field`=$value");
			if ($user->id > 0)
				return $user;
        }
		return false;
    }
    
	//! Shortcut to retrieve user object by id
    public static function byId($id, $include_data=true) { 
        return static::byField('id', (int)$id, $include_data);
    }
    
	//! Shortcut to retrieve user object by uuid
    public static function byUUID($uuid, $include_data=true) {
        return static::byField('uuid', (string)$uuid, $include_data);
    }
    
	//! Shortcut to retrieve user object by username
    public static function byUsername($username, $include_data=true) {
        return static::byField('username', (string)$username, $include_data);
    }
    
	//! Shortcut to retrieve user object by email
    public static function byEmail($email, $include_data=true) {
        return static::byField('email', (string)$email, $include_data);
    }
    
	/**
		Generates a password hash with bcrypt and APP_UUID as salt.
		Alternates between 5 and 7 rounds per default.
			@return string
			@param $password string
			@param $rounds integer
			@static
			@public
	**/
    public static function passwordHash($password, $rounds=0) {
        if ($rounds == 0)
            $rounds = rand(5,7);
        $app_uuid = F3::get('APP_UUID');
        $salt = '$2a$0'.$rounds.'$'.substr(str_replace('-','', $app_uuid), 0, CRYPT_SALT_LENGTH);
        return crypt($password, $salt);
    }
	
	/**
		Compare two passwords
			@return boolean
			@param $check string
			@param $with string
			@static
			@public
	**/
	public static function checkPassword($check, $with) {
		if (!$this && $with == '')
			return false;
        $rounds = substr($with, 5, 1);
        return (static::passwordHash($check, $rounds) == $with);
    }
	
	/**
		Log in user
			@return mixed
			@param $username string
			@param $password string
			@static
			@public
	**/
	public static function loginAs($username, $password) {
		if ($username != '') {
			switch (static::$login_field) {
				case 'username':
					if ($user = static::byUsername($username, false)) {
						if (static::checkPassword($password, $user->password)) {
							$user->login();
							return $user;
						}
					}
				break;
				
				case 'email':
					if ($user = static::byEmail($username, false)) {
						if (static::checkPassword($password, $user->password)) {
							$user->login();
							return $user;
						}
					}
				break;
			}
		}
		return false;
	}
	
	/**
		Log in user automatically with uuid and token
			@return mixed
			@param $userId double
			@param $token string
			@static
			@public
	**/	
	public static function autoLogin($token) {
		$model = static::$token_model;
		if ($token = $model::byToken($token)) {
			if ($user = static::byUUID($token->uuid, false)) {
				$token->erase();
				$user->login();
				$user->createAutoLoginToken();
				return $user;
			}
		}
		setcookie(static::$cookie, '', time()-3600);
		return false;
	}
}

namespace Milk\Models\User;

use \F3,
	\DateTime;

use \Milk\Models\User,
	\Milk\Models\BaseModel,
	\Milk\Utils\Strings;

class Data extends BaseModel {
	
	public static $table = 'users_data';
	
	public function __construct($data=false) {
		$this->sync(static::$table);
		if (is_array($data)) {
			foreach ($data as $field => $value) {
				$this->$field = $value;
			}
		} else if ($data) {
			$this->user_id = $data;
			$this->load("`user_id`=$data");
		}
	}
	
	public function beforeSave() {
		if ($this->birthdate instanceOf DateTime) {
			$this->birthdate = $this->birthdate->format('Y-m-d');
		}
		return true;
	}
}

class Number extends BaseModel {
	
	public static $table = 'users_numbers';
	
	public function __construct($user_id=null) {
		$this->sync(static::$table);
		if (!empty($user_id)) {
			$this->user_id = $user_id;
		}
	}
	
	public function beforeSave() {
		if (!empty($this->id) && ($this->keys['id'] == null)) {
			if ($this->found("`id`=$this->id AND `user_id`=$this->user_id")) {
				$num = $this->number;
				$cc = $this->country_code;
				$sms = $this->sms_capable;
				$this->load("`id`=$this->id AND `user_id`=$this->user_id");
				$this->number = $num;
				$this->country_code = $cc;
				$this->sms_capable = $sms;
			} else {
				$this->id = null;
			}
		}
		if (!$this->sms_capable)
			$this->sms_capable = 0;
		else
			$this->sms_capable = 1;
		return true;
	}
}

class LoginToken extends BaseModel {

	public static $table = 'users_login_tokens';

	public function __construct($data=false) {
		$this->sync(static::$table);
		if (is_array($data)) {
			foreach ($data as $field => $value) {
				$this->$field = $value;
			}
		} elseif ($data) {
			//$this->load("`uuid`='$data'");
			$this->uuid = $data;
		}
	}

	//! We need to do some stuff before a new token is saved
	public function beforeSave() {
		if (empty($this->token)) {
			//! Generate a token
			$this->token = static::generateToken();
			//! Set expiration date of token
			$dt = new DateTime('+1 year');
			$this->expires = $dt->format('Y-m-d H:i:s');
		}
		return true;
	}
	
	//! Retrieve token from database by token
	public static function byToken($token) {
		$obj = new LoginToken;
		$obj->token = $token;
		$obj->load("token='$token'");
		if (!$obj->id)
			return false;
			
		if (new DateTime($now) > new DateTime($obj->expires)) {
			$obj->erase();
			return false;
		}
		return $obj;
	}
	
	//! Generate token
	public static function generateToken() {
		$random_string = Strings::random(10);
		$token = User::passwordHash($random_string);
		return $token;
	}
}

class ResetToken extends BaseModel {

	public static $table = 'users_reset_tokens';

	public function __construct($data=false) {
		parent::__construct();
		$this->load("`uuid`='$data'");
		$this->uuid = $data;
	}

	//! We need to do some stuff before a new token is saved
	public function beforeSave() {
		if (empty($this->token)) {
			//! Generate a token
			$this->token = static::generateToken();
			//! Set expiration date of token
			$dt = new DateTime('+1 hour');
			$this->expires = $dt->format('Y-m-d H:i:s');
		}
		return true;
	}
	
	//! Retrieve token from database by token
	public static function byToken($token) {
		$obj = new ResetToken;
		$obj->token = $token;
		$obj->load("`token`='$token'");
		return $obj;
	}
	
	//! Generate token
	public static function generateToken() {
		$token = Strings::random(24);
		return $token;
	}
}