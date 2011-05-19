<?php
namespace Milk\Admin\Models;

use \Milk\Models\User as BaseUser;

/**
    Admin user model
        @package Milk
        @version 0.5.0
**/
class User extends BaseUser {
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