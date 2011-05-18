<?php
namespace Milk\Views;

use \F3 as F3;

class BaseView {
    
    protected $method;
    protected $request;
	protected $query;

    public function __construct() {
        $this->method = $_SERVER['REQUEST_METHOD'];
		$req = explode('/', $_SERVER['REQUEST_URI']);
		array_shift($req);
		$this->request = $req;
		$this->query = $_SERVER['QUERY_STRING'];
    }

    public function __call($name, $arguments) {
        if (!method_exists($this, $name))
            if (!F3::get('RELEASE'))
                trigger_error("View $name does not exist in ".get_class($this));
            else
                F3::http404();
        
        return $this->fields[$args[0]];
    }
	
}