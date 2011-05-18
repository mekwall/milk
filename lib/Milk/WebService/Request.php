<?php
namespace Milk\WebService;

use \F3 as F3;

class Request {

    public $service;
	public $method;
	public $args;
	public $id;
	public $notification = false;
	
	private $request_method;
	
	public function __construct($method=null, $args=null, $id=null) {
		//! Request method
		$this->request_method = $_SERVER['REQUEST_METHOD'];
        
        if (strpos($method, '.')) {
            $marr = explode('.', $method);
            $this->service = $marr[0];
            $method = $marr[1];
        }
	
		//! If args are not already set, get em
		if (empty($args)) {
			switch ($this->request_method) {
				case 'PUT':
				case 'DELETE':
					parse_str(file_get_contents('php://input'), $args);
				break;
			
				case 'GET':
					$args = $_GET;
				break;
			
				case 'POST':
					$args = $_POST;
				break;

				case 'OPTIONS':
					$this->options();
				break;
				
				default: 
					$args = $_REQUEST;
				break;
			}
			$args = (object)$args;
		}
		
		//! Implement RESTful method calling
		if (empty($method)) {
			switch ($this->request_method) {
				case 'POST':
					$method = 'create';
				break;
				
				case 'GET':
					$method = 'retrieve';
				break;
				
				case 'PUT':
					$method = 'update';
				break;
				
				case 'DELETE':
					$method = 'delete';
				break;
				
				case 'OPTIONS':
					$method = 'options';
				break;
			}
		}

		$this->method = $method;
		$this->args = $args;
		
		if ($id) {
			$this->id = $id;
		} else {
			$this->notification = true;
		}
	}
	
}