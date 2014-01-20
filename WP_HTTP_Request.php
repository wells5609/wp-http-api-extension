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
	
	public function request( $url, $method = null ){
		
		if ( !empty($method) )
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
		
		$response = _wp_http_get_object()->request( $url, $args );
		
		if ( is_wp_error($response) )
			return $response;
		
		return new WP_HTTP_Response( $response );
	}
	
}