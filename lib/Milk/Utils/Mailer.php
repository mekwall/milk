<?php
namespace Milk\Utils;

include_once(LIB_PATH.'/PHPMailer/class.phpmailer.php');

class Mailer {
	
	public function __construct() {
		$this->mailer = new \PHPMailer(true);
		$this->mailer->CharSet = 'UTF-8';
	}

	public function send() {
		$this->mailer->send();
	}
	
	public function setSubject($subject) {
		$this->mailer->Subject = $subject;
		return $this;
	}
	
	public function setFrom($address, $name=null) {
		$this->mailer->SetFrom($address, $name);
		return $this;
	}
	
	public function addRecipient($email, $name=null) {
		$this->mailer->AddAnAddress('to', $email, $name);
		return $this;
	}
	
	public function addRecipientCC($email, $name=null) {
		$this->mailer->AddAnAddress('cc', $email, $name);
		return $this;
	}
	
	public function addRecipientBCC($email, $name=null) {
		$this->mailer->AddAnAddress('bcc', $email, $name);
		return $this;
	}
	
	public function setHTML($html){
		$this->mailer->MsgHTML($html);
		return $this;
	}
	
}