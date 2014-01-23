<?php
/*
Plugin name: WP HTTP API Extension
Description: Some classes to extend the WordPress HTTP API. Includes: <code>WP_HTTP_Request</code> and <code>WP_HTTP_Response</code>.
Author: wells
Version: 0.0.2
*/

/**
* class WP_HTTP_Request
* 
* Object representation of an HTTP request using WordPress' HTTP API.
*/
class WP_HTTP_Request {
	
	public 
		$method,
		$timeout		= 5,
		$redirection	= 5,
		$httpversion	= '1.0',
		$user_agent,
		$blocking		= true,
		$headers		= array(),
		$cookies		= array(),
		$body			= null,
		$compress		= false,
		$decompress		= true,
		$sslverify		= true,
		$stream			= false,
		$filename		= null;
	
	public function get( $url ){
		
		return $this->send_request( $url, 'GET' );	
	}
	
	public function post( $url ){
		return $this->send_request( $url, 'POST' );	
	}
	
	public function put( $url ){
		return $this->send_request( $url, 'PUT' );	
	}
	
	public function send_request( $url, $method = 'GET' ){
		
		$this->method = strtoupper($method);
		
		$args = array();
		
		foreach( get_object_vars( $this ) as $var => $val ){
			
			if ( !isset($this->$var) )
				continue;
			
			$key = $var;
			
			if ( false !== strpos($var, '_') ){ 
				// 'user_agent' => 'user-agent'
				$key = str_replace('_', '-', $var);
			}
			
			$args[ $key ] = $this->$var;
		}
		
		$start_time = microtime(true);
		
		$response = _wp_http_get_object()->request( $url, $args );
		
		if ( is_wp_error($response) )
			return $response;
		
		$response['_start_time'] = $start_time;
		
		return new WP_HTTP_Response( $response );
	}
	
		
	public function set_header( $name, $value, $replace = true ){
		
		if ( ! $replace && isset($this->headers[ $name ]) ){
			return;
		}
		
		$this->headers[ $name ] = $value;
	}
	
	public function add_header( $name, $value ){
		$this->set_header( $name, $value, false );	
	}
	
	public function remove_header( $name, $value = '' ){
		
		if ( !isset($this->headers[ $name ]) )
			return;
		
		if ( empty($value) || $value == $this->headers[ $name ] ){
			unset( $this->headers[ $name ] );
		}	
			
	}
		
	public function basic_auth( $username, $password ){
		
		$this->set_header( 'Authorization', 'Basic ' . base64_encode( $username . ':' . $password ) );
	}
	
}