<?php
namespace Milk\Views;

use \F3 as F3;

use \Milk\Views\BaseView,
	\Milk\WebService\Transports\JSON;

/**
	Base view for User
		@package Milk
		@version 0.5.0
**/
class User extends BaseView {

	protected $userModel = 'Milk\Models\User';

	/**
		Redirect GET-requests to appropriate functions
			@public
	**/
    public function get() {
		switch ($this->request[1]) {
		
			case 'logout':
				$this->logout();
			break;
			
			case 'profile':
				$this->profile();
			break;
			
			case 'activate':
				$this->activate();
			break;
            
            case 'password':
				$this->password();
			break;
			
			case '':
				F3::reroute('/user/profile/');
			break;
			
			default:
				F3::httpStatus(501); // Not implemeneted
				
		}
		F3::http404();
    }

	/**
		Redirect POST-requests to appropriate functions
			@public
	**/
    public function post() {
		switch ($this->request[1]) {
		
			case 'login':
				$this->login();
			break;
			
			case 'profile':
				$this->profile();
			break;
            
            case 'password':
				$this->password();
			break;
			
			default:
				F3::httpStatus(501); // Not implemeneted
				
		}
		F3::http404();
    }
	
    /**
		Redirect PUT-requests to appropriate functions
			@public
	**/
    public function put() {
		switch ($this->request[1]) {
		
			case 'profile':
				$this->profile();
			break;
			
			default:
				F3::httpStatus(501); // Not implemeneted
		}
		F3::http404();
    }
    
	/**
		Redirect DELETE-requests to appropriate functions
			@public
	**/
    public function delete() {
		F3::http404;
    }
	
	/**
		View to logout user.
			@public
	**/
	public function logout() {

		$usermodel = $this->userModel;
		if (!isset($_SESSION[$usermodel::$session_var])) {
			F3::httpStatus(403);
		}
		if (isset($_SESSION[$usermodel::$session_var])) {
			$_SESSION[$usermodel::$session_var]->logout();
		} else {
			setcookie($usermodel::$cookie, '', time()-3600);
		}
		unset($_SESSION[$usermodel::$session_var]);
		if (F3::isAjax())
			JSON::success(true);
		
		F3::twigserve('user/loggedout.html');
		exit;
	}
    
	/**
		View to login user.
			@public
	**/
    public function login() {
		if ($this->method == 'POST') {
			//! We throttle post-requests to reduce risk of hammering/hacking
			F3::set('THROTTLE', 500);
			if (isset($_POST['username']) && isset($_POST['password'])) {
				$username = F3::scrub($_POST['username']);
				$password = F3::scrub($_POST['password']);
				$user = call_user_func($this->userModel.'::loginAs', $username, $password);
				if ($user) {       
					//! Create auto-login token if user chose to be remembered
					if (isset($_POST['remember']))
						$user->createAutoLoginToken();
					
					//! serve JSON for ajax requests
					if (F3::isAjax()) {
                        $json = new JSON();
                        $json->success(true)
                             ->respond();
                        exit;
					}
					
					if (isset($_SERVER['HTTP_REFERER']))
						F3::reroute($_SERVER['HTTP_REFERER']);
					else
						F3::reroute('/');
						
				} else {
					$error = 'User/password is incorrect.';
				}
				
				//! serve JSON for ajax requests
				if (F3::isAjax()) {
                    $json = new JSON();
					$json->denied($error)
                        ->respond();
                    exit;
				}
				F3::reroute('/');
			}
		} else {
			$or = F3::get('OVERRIDE_TEMPLATE');
			if (!empty($or)) {
				F3::twigserve($or);
			} else {
				F3::twigserve('user/login.html');
			}
		}
    }
	
	/**
		View to display/update user profile
			@return mixed
			@public
	**/
	public function profile() {
		if (!isset($_SESSION['USER']))
			F3::httpStatus(403);
	
		switch ($this->method) {
		
			//! Update user profile
			case 'PUT':
			case 'POST':
			
				if (F3::isAjax()) {
                    $json = new JSON();
					return $json->output(true)
                                ->respond();
				}
			//! Display user profile
			case 'GET':
				F3::twigserve('user/profile.html');
			break;
			
			default: 
				F3::http404();
		}
	}
	
	/**
		View to activate user
			@return mixed
			@public
	**/
	public function activate() {
		$user_uuid = F3::get('PARAMS[uuid]');
		$code = F3::get('PARAMS[code]');
	
        if (isset($_SESSION['USER']) && !$user_uuid) {
            if ($_SESSION['USER']->active != 1) {
                F3::twigserve('user/activation_needed.html');
                exit;
            } else {
				F3::twigset('already_activated', true);
                F3::twigserve('user/activation.html');
                exit;
            }
        }
        
        if ($user_uuid === NULL || $code == NULL) {
            F3::reroute('/');
            return;
        }
		
		if (isset($_SESSION['USER']))
			$_SESSION['USER']->logout();
		
		$user = call_user_func($this->userModel.'::byUUID', $user_uuid, false);
		
		F3::twigset('user', $user);
		F3::twigset('code', $code);
		
		if (($user->activation_code == $code) && ($user->active != 1)) {
			//! Generate and update with temporary password
			$password = \Milk\Utils\Strings::random(6);
			$user->updatePassword($password);
			
			$user->activate();
			$user->login();

			//! Fill the twig context
			F3::twigset('password', $password);
            
            /*
			//! Send mail with password
			$mailer = new  \Milk\Utils\Mailer;
			$mailer->setFrom('noreply@maklarpaket.se', 'Mäklarpaket.se')
					->addRecipient($user->email, $user->firstname . ' ' . $user->lastname)
					->setSubject('Ditt lösenord')
					->setHTML(F3::twigreturn('mails/temporary_password.html'))
					->send();
            */
		} elseif ($user->active) {
			F3::twigset('already_activated', true);
		}
		F3::twigserve('user/activation.html');
		exit;
	}
	
	public function activate_resend(){
		$user = $_SESSION['USER'];
	
		//! Fill twig context
		F3::twigset('firstname', $user->firstname);
		F3::twigset('user_uuid', $user->uuid);
		F3::twigset('activation_code', $user->activation_code);
	
		//! Send activation mail
		$mailer = new  \Milk\Utils\Mailer;
		$mailer->setFrom('noreply@maklarpaket.se', 'Mäklarpaket.se')
				->addRecipient($user->email, $user->firstname . ' ' . $user->lastname)
				->setSubject('Aktivering')
				->setHTML(F3::twigreturn('mails/activation.html'))
				->send();
				
		if (F3::isAjax()) {
			$json = new JSON();
			$json->success(true)
				 ->respond();
			exit;
		}
		F3::reroute("/aktivera/");
	}
	
	/**
		View to reset password
			@return mixed
			@public
	**/
	public function password_reset() {
		switch ($this->method) {
            case 'POST':
				if (F3::isAjax()) {
					F3::set('THROTTLE', 500);
					$uuid = F3::scrub($_POST['form_password_uuid']);
					$token = F3::scrub($_POST['form_password_token']);
					$password = F3::scrub($_POST['form_password_new']);
					$password2 = F3::scrub($_POST['form_password_repeat']);
					
					if ($user = call_user_func($this->userModel.'::byUUID', $uuid)) {
						if ($token = $user->validateResetToken($token)) {
							$json = new JSON();
							if ($password === $password2) {
								//! update password and save user
								$user->updatePassword($password);
								$user->save();
								//! erase reset token so it cant be reused
								$token->erase();
								$json->success("Password updated");
							} else {
								$json->error(409, "Passwords do not match");
							}
						} else {
							$json->error(401, "Token is not valid");
						}
					} else {
						$json->error(404, "User not found");
					}
					$json->respond();
				} else {
					F3::http404();
				}
            break;
			case 'GET':
				$uuid = F3::get('PARAMS[uuid]');
				$token = F3::get('PARAMS[code]');
				if ($uuid && $token) {
					if ($user = call_user_func($this->userModel.'::byUUID', $uuid)) {
						if ($user->validateResetToken($token)) {
							F3::twigset('uuid', $uuid);
							F3::twigset('token', $token);
							F3::twigserve('user/password_reset.html');
							exit;
						}
					}
				}
				F3::http404();
            break;
        }
	}
	
	/**
		View when user forgot password
			@return mixed
			@public
	**/
	public function password_forgot() {
		switch ($this->method) {
            case 'POST':
                F3::set('THROTTLE', 500);
                $email = F3::scrub($_POST['form_password_email']);
                $user = call_user_func($this->userModel.'::byEmail', $email);
                if (F3::isAjax()) {
                    $json = new JSON();
                    if (!$user) {
                        $json->error(404, "User not found");
                    } else {
                        //! Generate reset token
                        if (!$token = $user->resetPassword()) {
							//! An active token already exists
							$json->conflict("Token already exists");
						} else {
							F3::twigset('user_uuid', $user->uuid);
							F3::twigset('reset_token', $token->token);
						
							//! Send password reset mail
							$mailer = new  \Milk\Utils\Mailer;
							$mailer->setFrom('noreply@maklarpaket.se', 'Mäklarpaket.se')
									->addRecipient($user->email, $user->firstname . ' ' . $user->lastname)
									->setSubject('Återställ lösenord')
									->setHTML(F3::twigreturn('mails/reset_password.html'))
									->send();

							$json->success(true);
						}
                    }

                    $json->respond();
				} else {
					F3::http404();
				}
            break;
			
			case 'GET':
				F3::twigserve('user/password_forgot.html');
            break;
        }
	}
	
}