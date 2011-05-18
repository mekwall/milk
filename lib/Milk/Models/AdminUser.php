<?php
namespace Milk\Models;

use \Milk\Models\User as BaseUser;

/**
	Admin user model
		@package Milk
		@version 0.5.0
**/
class AdminUser extends BaseUser {
    public static $session_var = 'ADMIN';
	public static $cookie = '__admin_login_token';
	
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
				if (!$user->isAdmin())
					return false;
					
				if ($token->uuid) {
					$uuid = $token->uuid;
					$token->erase();
				} else {
					$uuid = $user->uuid;
				}
				$token = new static::$token_model($uuid);
				$token->save();
				$user->login();
				return $user;
			}
		}
		setcookie(static::$cookie, '', time()-3600);
		return false;
	}
}