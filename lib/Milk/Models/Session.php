<?php
namespace Milk\Models;
/**
    CREATE TABLE `sessions` (
       `id` bigint(20) unsigned not null auto_increment,
       `session_id` varchar(40),
       `data` text,
       `expires` datetime,
       PRIMARY KEY (`id`),
       UNIQUE KEY (`session_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
**/

use \F3;

use Milk\Utils\Config;

class Session {

    /**
        @var Session
    **/
    protected static $_instance;
    
    /**
        @var PDO
    **/
	private $db;
	
	private $name = '__sid';
	private $path = '/';
	private $secure = false;
	private $domain;
	private $lifetime = 1200;
	
	private $encrypt_ua = false;
	
	private $suhosin_encrypt = false;
	private $suhosin_cryptua = false;
	private $suhosin_cryptaddr = false;

    public function __construct() {
		$sconf = Config::get('session');
		$dbconf = Config::get('database');

        $this->db = new \PDO($dbconf->dsn, $dbconf->username, $dbconf->password);
		
		if (isset($sconf->name))
			$this->name = $sconf->name;
		
		$this->lifetime = isset($sconf->lifetime) ?
			$sconf->lifetime : (int)ini_get('session.cookie_lifetime');
		
		$this->domain = isset($sconf->domain) ?
			$sconf->domain : ini_get('session.cookie_domain');
			
		$this->secure = isset($sconf->secure) ?
			$sconf->secure : ini_get('session.cookie_secure');
			
		if (extension_loaded('suhosin')) {
			$this->suhosin_encrypt = (bool)ini_get('suhosin.cookie.encrypt');
			$this->suhosin_cryptua = (bool)ini_get('suhosin.session.cryptua');
			$this->suhosin_cryptaddr = (bool)ini_get('suhosin.session.cryptaddr');
		}
	}
    
    /**
        A no-op function, somethings just aren't worth doing.
    **/
    public function noop() {}
    
    public function read($id) {
        if ($result = $this->db->query("SELECT `data` FROM `sessions` WHERE `session_id`='$id' AND `expires` > NOW()")) {
            $r = $result->fetchObject();
            return $r->data;
        }
        return '';
    }
    
    public function write($id, $data) {
        $lifetime = (int)ini_get('session.gc_maxlifetime');
        $date = new \DateTime("+$lifetime seconds");
        $expires = $date->format('Y-m-d H:i:s');
        
        $sql = "
        INSERT INTO `sessions` (`session_id`, `data`, `expires`) VALUES ('$id', '$data', '$expires')
        ON DUPLICATE KEY UPDATE `data`='$data', `expires`='$expires'";
        if ($result = $this->db->query($sql))
            return true;
            
        return false;
    }
    
    public function destroy($id) {
        unset($_SESSION);
        return (bool)$this->db->query("DELETE FROM `sessions` WHERE `session_id`='$id'"); 
    }
    
    public function gc() {
        $this->db->query("DELETE FROM `sessions` WHERE `expires` <= NOW()"); 
    }
    
    public static function getInstance() {
        if (!self::$_instance instanceof self) {
            self::$_instance = new Session();
        }
        return self::$_instance;
    }

    public static function register() {
		$m = self::getInstance();
		session_name($m->name);
		session_set_cookie_params(
			$m->lifetime, 
			$m->path, 
			$m->domain,
			$m->secure
		);
        session_set_save_handler(
            array($m, 'noop'), // open
            array($m, 'noop'), // close 
            array($m, 'read'),
            array($m, 'write'),
            array($m, 'destroy'),
            array($m, 'gc')
        );
		
		//! let's start the session
        session_start();

		//! if suhosin is not loaded or transparent session encryption based on ua is disabled
		if ($m->encrypt_ua && !$m->suhosin_encrypt && !$m->suhosin_cryptua) {
			/**
				Primitive session hijack detection
			**/
			//! generate time-based fingerprint
			$fp = md5(time().F3::get('APP_UUID'));
			//! generate time-based hash of user-agent
			$uahash = md5($_SERVER['HTTP_USER_AGENT'].$fp);
			if (!isset($_SESSION['__ua'])) {
				//! save hash to session
				$_SESSION['__ua'] = $uahash;
				//! set fingerprint cookie
				setcookie('__milk_sfp', $fp, time()+$m->lifetime, $m->path, $m->domain, $m->secure);
			} else {
				if ($_SESSION['__ua'] !== md5($_SERVER['HTTP_USER_AGENT'].$_COOKIE['__milk_sfp'])) {
					//! session was most likely hijacked, let's unset it to make it unusable
					setcookie('__milk_sfp', null, time()-3200);
					setcookie(session_name(), null, time()-3200);
					F3::httpStatus(403);
					return false;
				} else {
					//! update hash
					$_SESSION['__ua'] = $uahash;
					//! update fingerprint
					setcookie('__milk_sfp', $fp, time()+$m->lifetime, $m->path, $m->domain, $m->secure);
					
				}
			}
		}
		//! update lifetime of cookie
		setcookie(session_name(), session_id(), time()+$m->lifetime, $m->path, $m->domain, $m->secure);
		
        return $m;
    }
}