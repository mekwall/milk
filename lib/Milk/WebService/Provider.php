<?php
namespace Milk\WebService;

use \F3;

use Milk\WebService\Transports,
    Milk\WebService\Request,
    Milk\Utils\Validator;

class Provider {

	//! Provider types
	const PLAIN		= 1;
	const JSON 		= 2;
	const JSONRPC	= 3;
	const YAML		= 4;
	const XMLRPC	= 5;
	const SOAP		= 6;
	
	//! HTTP status codes
	protected $http_codes = array(
		//! Success
		200 => 'OK',
		201 => 'Created Successfully',
		202 => 'Accepted',
		204 => 'No Content',
		//! Error
		400 => 'Bad Request',
		401 => 'Not Authorized',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		409 => 'Record Conflict',
		500 => 'Unknown Error',
		501 => 'Method Not Implemented'
	);
	
	//! Protected variables
	protected $type,
			  $resource,
			  $transport,
              $services = array(),
			  $requests = array(),
			  $errors = array(),
			  $uri_params = array(),
			  $valid = true,
			  $content_type,
			  $accept_content,
			  $request_method,
			  $current_request,
              $namespace,
			  $show_docs = false;

	public function serve() {
		//! Check if cached
		if ($etag = $_SERVER['HTTP_IF_NONE_MATCH'] && F3::cached($etag)) {
			F3::httpStatus(304);
			exit;
		}
		
		//! Fat-Free URI params
		$this->uri_params = F3::get('PARAMS');
		//! Content-Type
        //! Some clients might specify other stuff in Content-Type as well
        $ct = explode(';', $_SERVER['CONTENT_TYPE']); 
		$this->content_type = $ct[0];
		//! Accepted Content-Types
		$this->accept_content = explode(',', str_replace(' ', '', $_SERVER['HTTP_ACCEPT']));
		//! Request method
		$this->request_method = $_SERVER['REQUEST_METHOD'];
		
		//! Let's check what transport the request is asking for
		if ($this->request_method == 'GET' && $_GET['jsonrpc']) {
			$this->type = self::JSONRPC;
			$this->transport = new Transports\JSONRPC;
			$this->requests[] = new Request($_GET['method'],  (object)$_GET['params'], $_GET['id']);
		} else {
			switch ($this->accept_content[0]) {
				case 'application/json':
				case 'application/x-json':
				case 'application/javascript':
				case 'application/x-javascript':
				case 'application/jsonp':
				case 'text/json':
				case 'text/jsonp':
				case 'text/javascript':
					//! We got two transports using JSON, let's find out which one
					switch ($this->content_type) {
					
						case "application/json":
						case "application/x-json":
						case "text/json":
							$this->type = self::JSONRPC;
							$this->transport = new Transports\JSONRPC;
							//! parse request
							$requests = $this->transport->parse();
							if (!is_array($requests))
								return;
							//! create Request object for each valid request
							foreach ($requests as $request) {
								if ($request->jsonrpc == "2.0")
									$this->requests[] = new Request($request->method, $request->params, $request->id);
								else
									$this->requests[] = false;
							}
						break;
						
						case "application/x-www-form-urlencoded":
						default:
							$this->type = self::JSON;
							$this->transport = new Transports\JSON;
							switch ($thus->request_method) {
								case 'GET':
									$params = $_GET;
								break;
								
								case 'POST':
									$params = $_POST;
								break;
								
								case 'PUT':
								case 'DELETE':
									$params = file_get_contents('php://input');
								break;
							}
							$this->requests[] = new Request(null, $params);
						break;
						
					}
					
				break;

				case 'text/plain':
					$this->type = self::PLAIN;
					$this->transport = new Transports\Plain;
					$this->requests[] = new Request(null, $_GET);
				default:
					if (isset($this->uri_params['service'])) {
						$this->type = self::PLAIN;
						$this->transport = new Transports\Plain;
						$this->requests[] = new Request(null, $_GET);
					} else if ($this->show_docs) {
						F3::twigserve('api/index.html');
						exit;
					}
				break;
			}
		}
		
		//! If no type was set, set to PLAIN and
		if (!$this->type || !$this->transport) {
			$this->type = self::PLAIN;
			$this->transport = new Transports\Plain;
			$this->error(501);
			return $this->transport->respond();
		}
		
		//! We are ready to call the method that serves the request
		foreach ($this->requests as $i => $request) {
			if (!$request) {
				$this->error(400);
				continue;
			}
			$this->current_request = $request;
            
            $service_name = $request->service ? $request->service : $this->uri_params['service'];
            
            if ($service_name) {
                if ($this->services[$service_name]) {
                    $class = $this->namespace.'\\'.$this->services[$service_name];
                    $service = new $class($this->transport, $request);
                } else {
                    //! Service does not exist
                    $this->error(501);
                    continue;
                }
            } else {
                //! Service was not specified
                $this->error(501);
                continue;
            }

			if (!method_exists($service, $request->method)) {
                //! Method does not exist in service
				$this->error(501);
				continue;
			}
			$service->{$request->method}();
		}
		
		//! Respond with the results
		$this->transport->respond();
	}
    
    protected function addService($name, $class) {
        $this->services[$name] = $class;
    }
    
    protected function error($http_code=500, $error=null, $data=null) {
		$this->transport->error($http_code, $error, $data, $this->current_request->id);
	}
}