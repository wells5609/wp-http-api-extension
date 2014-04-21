<?php
/*
Plugin name: WP HTTP API Adapter
Description: An adapter class for interfacing with external APIs via WordPress' HTTP API. Requires WP_HTTP_Request class
Author: wells
Version: 0.0.2-alpha
*/

function register_api_adapter( $name, array $args ){
	return WP_HTTP_API_Adapter::register_adapter($name, $args);
}

function get_api_adapter( $name ){
	return WP_HTTP_API_Adapter::get_adapter($name);
}

add_filter('yahoo_api_adapter_class', create_function('', "return 'WP_Yahoo_API_Adapter';"));

class WP_Yahoo_API_Adapter extends WP_HTTP_API_Adapter {
	
	public function yql($params = array(), $http_method = null) {
		
		if (is_string($params)) {
			$params = array('q' => $params);	
		}
		
		return $this->call_method('yql', '', $params, $http_method);
	}
		
}

register_api_adapter('yahoo', array(
	'baseurl' => 'http://query.yahooapis.com/v1/public',
	'methods' => array(
		'yql' => array(
			'params' => array(
				'format' => array('json','xml'),
				'env' => '',
				'diagnostics' => 1,
				'*q' => '',
			),
			'method' => 'GET',
		),
	)
));

/**
* class WP_HTTP_API_Adapter
*/
class WP_HTTP_API_Adapter {
	
	public $baseurl;
	
	public $request;
	
	public $last_request = array();
	
	protected $methods = array();
	
	protected static $instances = array();
	
	static public function register_adapter($id, array $args) {
		
		if (! isset($args['baseurl']))
			return new WP_Error('api_adapter', 'missing baseurl');
		if (! isset( $args['methods']))
			return new WP_Error('api_adapter', 'missing methods');
		
		$class = apply_filters("{$id}_api_adapter_class", __CLASS__);
		
		$a = self::$instances[ $id ] = new $class($args['baseurl']);
		
		$a->build_methods($args['methods']);
		
		return $a;
	}
	
	static public function get_adapter($id) {
		
		return isset(self::$instances[ $id ]) ? self::$instances[ $id ] : null;	
	}
	
	public function __construct( $baseurl = null ){
		
		if (! empty($baseurl)) {
			$this->baseurl = $baseurl;	
		}
		
		$this->reset_request();
	}
	
	public function reset_request(){
		
		$this->request = new WP_HTTP_Request;
		$this->last_request = array();
	}
	
	public function set_option($var, $value) {
		
		$this->request->$var = $value;	
		
		return $this;
	}
	
	public function get_option($var) {
		
		return $this->request->$var;	
	}
	
	public function call_method($method, $path = '', $params = array(), $http_method = null) {
		
		if (! isset($this->methods[$method])) return false;
		
		$def = $this->methods[ $method ];
		
		if (! empty($http_method) && ! in_array($http_method, $def->http_methods)) {
			return new WP_Error('invalid_http_method', "HTTP method '$http_method' not allowed for method '$def->name'.");
		}
		
		$method_string = $def->build_url($path, $params);
		
		if (is_wp_error($method_string)) {
			return $method_string;	
		}
		
		$url = trailingslashit($this->baseurl) . $method_string;
		
		if (empty($http_method) && ! empty($def->http_methods)) {
			$_methods = $def->http_methods;
			$http_method = array_shift($_methods);
		} else {
			$http_method = 'GET';	
		}
		
		$this->last_request['method'] = $method;
		$this->last_request['http_method'] = $http_method;
		$this->last_request['url'] = $url;
		
		return $this->request->send_request($url, $http_method);
	}
	
	public function build_methods(array $methods) {
		
		foreach($methods as $method => $args) {
			
			$this->methods[ $method ] = new WP_HTTP_API_Method($method);
			
			if (! empty($args['params'])) {
				$this->methods[ $method ]->set_params($args['params']);	
			}
			
			if (! isset($args['paths'])) {
				$args['paths'] = false;
			}
			
			$this->methods[ $method ]->set_paths($args['paths']);
			
			if (! isset($args['method'])) {
				$args['method'] = 'GET';
			}
			
			$this->methods[ $method ]->http_methods = (array) $args['method'];
		}
	}
	
	function get_last_request() {
		return empty($this->last_request) ? null : $this->last_request;	
	}
	
	function __call($func, $params) {
		
		if (isset($this->methods[ $func ])) {
			
			return $this->call_method($func, 
				isset($params[0]) ? $params[0] : '', 
				isset($params[1]) ? $params[1] : array(), 
				isset($params[2]) ? $params[2] : null 
			);	
		}		
	}
		
}

/**
* class WP_HTTP_API_Method
*/
class WP_HTTP_API_Method {
	
	const STRING = 'string';
	const BOOL = 'bool';
	const ENUM = 'enum';
	
	public $name;
	
	public $http_methods = array();
	
	public $params = array();
	
	public $params_required = array();
	
	public $param_options = array();
	
	// if false, paths not allowed. anything else paths ok
	public $paths = false;
	
	// if true, $path must not be empty in build_url()
	public $path_required = false; 
	
	function __construct($name){
		$this->name = $name;	
	}
		
	public function set_params(array $params) {
		foreach($params as $param => $val){
			$this->set_param($param, $val);	
		}	
	}
	
	public function set_param( $var, $valtype ){
		
		if (0 === strpos($var, '*')) {
			$var = substr($var, 1);	
			$this->params_required[] = $var;
		}
		
		if (1 === $valtype || 0 === $valtype) {
			$this->params[ $var ] = self::BOOL;
		} else if ('' === $valtype) {
			$this->params[ $var ] = self::STRING;
		} else if (is_array($valtype)) {
			$this->params[ $var ] = self::ENUM;
			$this->param_options[ $var ] = $valtype;
		}
	}
	
	public function set_paths($arg) {
		$this->paths = $arg;
		if ('*' === $arg) {
			$this->path_required = true;	
		}
	}
	
	public function build_url($path = '', $params = array()) {
		
		if (empty($path) && $this->path_required) {
			return new WP_Error('missing_path', "Missing required method path for '$this->name'.");	
		}
		
		if (! empty($this->params_required)) {
			foreach( $this->params_required as $required ) {
				if (! isset($params[ $required ])) {
					return new WP_Error('missing_required_param', "Missing required parameter '$required' for method '$this->name'.");
				}	
			}
		}
		
		$return = $this->name;
		
		if (! empty($path) && $this->paths) {
			$return .= '/' . trim($path, '/');	
		}
		
		if (! empty($params)) {
			$return .= '?';
			foreach( $params as $param => $value ) {
				if (! $this->is_param_valid($param, $value)) {
					return new WP_Error('invalid_param', "Invalid parameter '$param' for method '$this->name'.");
				}
				$return .= urlencode($param) . '=' . urlencode($value) . '&';
			}
		}
		
		return rtrim($return, '&');
	}
	
	public function is_param_valid($param, $value) {
		
		if (! isset($this->params[$param]))
			return false;
		
		$p = $this->params[$param];
		
		if ($this->isBool($p)) {
			return 0 === $value || 1 === $value || '1' === $value || '0' === $value;	
		} else if ($this->isString($p)){
			return is_string($value);
		} else if ($this->isEnum($p)) {
			return in_array($value, $this->param_options[$param]);	
		}
		
		return false;
	}
	
	protected function isString($val) {
		return $val === self::STRING;	
	}
	
	protected function isBool($val) {
		return $val === self::BOOL;	
	}
	
	protected function isEnum($val) {
		return $val === self::ENUM;	
	}
	
}
