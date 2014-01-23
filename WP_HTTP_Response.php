<?php
/**
* class WP_HTTP_Response
* 
* Object representation of HTTP response.
*/
class WP_HTTP_Response extends ArrayObject {
		
	public function __construct( $response ){
		
		parent::__construct( $response, ArrayObject::ARRAY_AS_PROPS );
		
		if ( isset( $this->_start_time ) ){
			$this->offsetSet( 'request_time', microtime(true) - $this->_start_time );	
			unset( $this->_start_time );
		}
	}
	
	public function get_body(){
		return $this->offsetGet( 'body' );	
	}
	
	public function get_cookies(){
		return $this->offsetGet( 'cookies' );	
	}
	
	public function get_headers(){
		return $this->offsetGet( 'headers' );	
	}
	
	public function get_header( $name ){
		return $this->offsetExists( 'headers' ) && isset( $this->headers[ $name ] ) ? $this->headers[ $name ] : null;	
	}
	
	public function get_content_type(){
		return $this->get_header( 'content-type' );	
	}
	
	public function get_response( $part = null ){
		
		if ( $this->offsetExists( 'response' ) ){
			
			if ( empty($part) )
				return $this->response;
			
			if ( isset($this->response[ $part ]) )
				return $this->response[ $part ];	
		}
		
		return null;	
	}
	
	public function is_content_type( $type ){
		
		if ( ! $content_type = $this->get_content_type() )
			return null;
		
		if ( $type == $content_type ) return true;
		
		if ( false !== strpos( $content_type, $type ) )
			return true;
		
		return false;
	}
	
	public function get_body_object(){
		
		if ( $this->offsetExists( 'body' ) ){
			
			if ( $this->is_content_type( 'json' ) ){
				return json_decode( $this->body );
			} elseif ( $this->is_content_type( 'xml' ) ){
				libxml_use_internal_errors();
				return simplexml_load_string( $this->body );
			} else {
				return (object) $this->body; // just for sanity...
			}
		}
		
		return null;	
	}
	
	public function __toString(){
		
		if ( ! isset($this['body']) ){
			return '';
		}
		
		return (string) $this->body;		
	}
	
}