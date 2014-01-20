wp-http-api-extension
=====================

A few simple classes to use with the WordPress HTTP API


### `WP_HTTP_Request`

Basically the vars for `wp_remote_request()` (and friends) in a class.

**Methods:**

 * `request( $url, $method = null )` - Sends a request to $url. Returns `WP_HTTP_Response` if successful, otherwise `WP_Error`.


### `WP_HTTP_Response`

Basically the array returned by `wp_remote_request()` in a class.

**Methods:**
 
 * `get_body_object()` - Returns response body as a PHP object. If reponse is JSON, uses `json_decode()`; if XML, uses `simplexml_load_string()`; otherwise just casts `$body` to an object.
 * `is_content_type( $format )` - Returns true if `Content-Type` response header is the given format.
 * `get_content_type()` - Returns `Content-Type` HTTP header.
 * `get_header( $name )` - Returns HTTP response header specified by $name.
 * `get_headers()` - Returns array of HTTP response headers.
 * `get_cookies()` - Returns array of response cookies.
 * `get_body()` - Returns response `body`.


#### Example

```php
$request = new WP_HTTP_Request();

$response = $request->request( $some_url . '?format=xml' );

if ( !is_wp_error($response) ){
  
  $phpObj = $response->get_body_object();
  
  $desired_item = $phpObj->xpath( '//document/items[@id="item-two"]' );
  
}
```

### `WP_HTTP_API_Adapter`

A class used to interface with external API's. Takes care of parameter checking and validation and other cool stuff.

#### Registering an adapter

Register an adapter by calling `api_register_adapter()`, which takes two parameters:

 1. `$name` (string) - the adapter name slug (lowercase, no spaces)
 2. `$args` (array) - an associative array including `baseurl` (string) and `methods` (array).
 
 * `baseurl` is simply the API's URI path (i.e. doesn't change).
 * `methods` is an associative array of method arguments. The key is the method name.

For example, the Yahoo adapter is registered like so:

```php
register_api_adapter( 'yahoo', array(
  'baseurl' => 'http://query.yahooapis.com/v1/public',	// base URI
  'methods' => array(
	'yql' => array(                                 // corresponds to "http://query.yahooapis.com/v1/public/yql"
		'params' => array(              		// define all possible parameters
			'format' => array('json','xml'),        // pass an array to restrict valid param values
			'env' => '',                            // Empty string ('') means must pass string
			'diagnostics' => 1,                     // 1 or 0 means must pass boolean
			'*q' => '',                             // Asterik (*) before parameter means required
		),
		'method' => 'GET',                              // HTTP method - for completeness (GET is default)
		'paths' => false,                               // False means no additional URI components allowed (e.g. '../yql/something')
	),
  )
) );

```

#### Example

```php

$yahoo = get_api_adapter( 'yahoo' );

// we can call API methods as PHP methods, passing params in an array:

$response = $yahoo->yql( array( 'q' => 'select tables', 'format' => 'xml' ) );

if ( !is_wp_error($response) ){

  $simple_xml_element = $response->get_body_object(); // load body as PHP object (in this case, a Simple XML Element)

  $item = $simple_xml_element->xpath( '//response/item' );
}

```


