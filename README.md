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
 


### Examples

```php
$request = new WP_HTTP_Request();

$response = $request->request( $some_url . '?format=xml' );

if ( !is_wp_error($response) ){
  
  $phpObj = $response->get_body_object();
  
  $desired_item = $phpObj->xpath( '//document/items[@id="item-two"]' );
  
}
