<?php

/**
 *
 * @package OAuth
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 *
 * The MIT License
 *
 * Copyright (c) 2007 Andy Smith
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

class OAuthConsumer {
  var $key;
  var $secret;

  function OAuthConsumer($key, $secret, $callback_url=NULL) {
    $this->key = $key;
    $this->secret = $secret;
    $this->callback_url = $callback_url;
  }

  function __toString() {
    return "OAuthConsumer[key=$this->key,secret=$this->secret]";
  }
}

class OAuthToken {
  // access tokens and request tokens
  var $key;
  var $secret;

  /**
   * key = the token
   * secret = the token secret
   */
  function OAuthToken($key, $secret) {
    $this->key = $key;
    $this->secret = $secret;
  }

  /**
   * generates the basic string serialization of a token that a server
   * would respond to request_token and access_token calls with
   */
  function to_string() {
    return "oauth_token=" . OAuthUtil::urlencode_rfc3986($this->key) .
        "&oauth_token_secret=" . OAuthUtil::urlencode_rfc3986($this->secret);
  }

  function __toString() {
    return $this->to_string();
  }
}

class OAuthSignatureMethod {
  function check_signature(&$request, $consumer, $token, $signature) {
    $built = $this->build_signature($request, $consumer, $token);
    return $built == $signature;
  }
}

class OAuthSignatureMethod_HMAC_SHA1 extends OAuthSignatureMethod {
  function get_name() {
    return "HMAC-SHA1";
  }

  function build_signature($request, $consumer, $token) {
    $base_string = $request->get_signature_base_string();
    $request->base_string = $base_string;

    $key_parts = array(
      $consumer->secret,
      ($token) ? $token->secret : ""
    );

    $key_parts = OAuthUtil::urlencode_rfc3986($key_parts);
    $key = implode('&', $key_parts);

    return base64_encode( hash_hmac('sha1', $base_string, $key, true));
  }
}

class OAuthSignatureMethod_PLAINTEXT extends OAuthSignatureMethod {
  function get_name() {
    return "PLAINTEXT";
  }

  function build_signature($request, $consumer, $token) {
    $sig = array(
      OAuthUtil::urlencode_rfc3986($consumer->secret)
    );

    if ($token) {
      array_push($sig, OAuthUtil::urlencode_rfc3986($token->secret));
    } else {
      array_push($sig, '');
    }

    $raw = implode("&", $sig);
    // for debug purposes
    $request->base_string = $raw;

    return OAuthUtil::urlencode_rfc3986($raw);
  }
}

class OAuthSignatureMethod_RSA_SHA1 extends OAuthSignatureMethod {
  function get_name() {
    return "RSA-SHA1";
  }

  function fetch_public_cert(&$request) {
    // not implemented yet, ideas are:
    // (1) do a lookup in a table of trusted certs keyed off of consumer
    // (2) fetch via http using a url provided by the requester
    // (3) some sort of specific discovery code based on request
    //
    // either way should return a string representation of the certificate
    trigger_error("fetch_public_cert not implemented", E_USER_WARNING);
    return NULL;
  }

  function fetch_private_cert(&$request) {
    // not implemented yet, ideas are:
    // (1) do a lookup in a table of trusted certs keyed off of consumer
    //
    // either way should return a string representation of the certificate
    trigger_error("fetch_private_cert not implemented", E_USER_WARNING);
    return NULL;
  }

  function build_signature(&$request, $consumer, $token) {
    $base_string = $request->get_signature_base_string();
    $request->base_string = $base_string;

    // Fetch the private key cert based on the request
    $cert = $this->fetch_private_cert($request);

    // Pull the private key ID from the certificate
    $privatekeyid = openssl_get_privatekey($cert);

    // Sign using the key
    $ok = openssl_sign($base_string, $signature, $privatekeyid);

    // Release the key resource
    openssl_free_key($privatekeyid);

    return base64_encode($signature);
  }

  function check_signature(&$request, $consumer, $token, $signature) {
    $decoded_sig = base64_decode($signature);

    $base_string = $request->get_signature_base_string();

    // Fetch the public key cert based on the request
    $cert = $this->fetch_public_cert($request);

    // Pull the public key ID from the certificate
    $publickeyid = openssl_get_publickey($cert);

    // Check the computed signature against the one passed in the query
    $ok = openssl_verify($base_string, $decoded_sig, $publickeyid);

    // Release the key resource
    openssl_free_key($publickeyid);

    return $ok == 1;
  }
}

class OAuthRequest {
  var $parameters;
  var $http_method;
  var $http_url;
  // for debug purposes
  var $base_string;
  var $version = '1.0';

  function OAuthRequest($http_method, $http_url, $parameters=NULL) {
    @$parameters or $parameters = array();
    $this->parameters = $parameters;
    $this->http_method = $http_method;
    $this->http_url = $http_url;
  }

  function unset_parameter($name) {
    unset($this->parameters[$name]);
  }


  /**
   * attempt to build up a request from what was passed to the server
   */
  static function from_request($http_method=NULL, $http_url=NULL, $parameters=NULL) {
    $scheme = (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != "on") ? 'http' : 'https';
    @$http_url or $http_url = $scheme . '://' . $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
    @$http_method or $http_method = $_SERVER['REQUEST_METHOD'];

    $request_headers = OAuthRequest::get_headers();

    // let the library user override things however they'd like, if they know
    // which parameters to use then go for it, for example XMLRPC might want to
    // do this
    if ($parameters) {
      $req = new OAuthRequest($http_method, $http_url, $parameters);
    } else {
      // collect request parameters from query string (GET) and post-data (POST) if appropriate (note: POST vars have priority)
      // NOTE: $_GET and $_POST will strip duplicate query parameters
      $req_parameters = $_GET;
      if ($http_method == "POST" && @strstr($request_headers["Content-Type"], "application/x-www-form-urlencoded") ) {
        $req_parameters = array_merge($req_parameters, $_POST);
      }

      // next check for the auth header, we need to do some extra stuff
      // if that is the case, namely suck in the parameters from GET or POST
      // so that we can include them in the signature
      if (@substr($request_headers['Authorization'], 0, 6) == "OAuth ") {
        $header_parameters = OAuthRequest::split_header($request_headers['Authorization']);
        $parameters = array_merge($req_parameters, $header_parameters);
        $req = new OAuthRequest($http_method, $http_url, $parameters);
      } else $req = new OAuthRequest($http_method, $http_url, $req_parameters);
    }

    return $req;
  }

  /**
   * pretty much a helper function to set up the request
   */
  static function from_consumer_and_token($consumer, $token, $http_method, $http_url, $parameters=NULL) {
    @$parameters or $parameters = array();
    $defaults = array("oauth_version" => '1.0',
                      "oauth_nonce" => OAuthRequest::generate_nonce(),
                      "oauth_timestamp" => OAuthRequest::generate_timestamp(),
                      "oauth_consumer_key" => $consumer->key);
    $parameters = array_merge($defaults, $parameters);

    if ($token) {
      $parameters['oauth_token'] = $token->key;
    }
    return new OAuthRequest($http_method, $http_url, $parameters);
  }

  function set_parameter($name, $value) {
    $this->parameters[$name] = $value;
  }

  function get_parameter($name) {
    return isset($this->parameters[$name]) ? $this->parameters[$name] : NULL;
  }

  function get_parameters() {
    return $this->parameters;
  }

  /**
   * Returns the normalized parameters of the request
   *
   * This will be all (except oauth_signature) parameters,
   * sorted first by key, and if duplicate keys, then by
   * value.
   *
   * The returned string will be all the key=value pairs
   * concated by &.
   *
   * @return string
   */
  function get_signable_parameters() {
    // Include query parameters
    $query_str = parse_url($this->http_url, PHP_URL_QUERY);
    if($query_str) {
      $parsed_query = OAuthUtil::oauth_parse_string($query_str);
      parse_str($query_str, $php_parsed_query);

      if(OAuthUtil::oauth_http_build_query($parsed_query) != OAuthUtil::oauth_http_build_query($php_parsed_query)) {
        $parsed_query = $php_parsed_query;
      }

      foreach($parsed_query as $key => $value) {
        $this->set_parameter($key, $value);
      }
    }
    // Grab all parameters
    $params = $this->parameters;

    // Remove oauth_signature if present
    if (isset($params['oauth_signature'])) {
      unset($params['oauth_signature']);
    }

    return OAuthUtil::oauth_http_build_query($params);
  }

  /**
   * Returns the base string of this request
   *
   * The base string defined as the method, the url
   * and the parameters (normalized), each urlencoded
   * and the concated with &.
   */
  function get_signature_base_string() {
    $parts = array(
      $this->get_normalized_http_method(),
      $this->get_normalized_http_url(),
      $this->get_signable_parameters()
    );

    $parts = OAuthUtil::urlencode_rfc3986($parts);

    return implode('&', $parts);
  }

  /**
   * just uppercases the http method
   */
  function get_normalized_http_method() {
    return strtoupper($this->http_method);
  }

  /**
   * parses the url and rebuilds it to be
   * scheme://host/path
   */
  function get_normalized_http_url() {
    $parts = parse_url($this->http_url);

    $port = @$parts['port'];
    $scheme = $parts['scheme'];
    $host = $parts['host'];
    $path = @$parts['path'];

    $port or $port = ($scheme == 'https') ? '443' : '80';

    if (($scheme == 'https' && $port != '443')
        || ($scheme == 'http' && $port != '80')) {
      $host = "$host:$port";
    }
    return "$scheme://$host$path";
  }

  /**
   * builds a url usable for a GET request
   */
  function to_url() {
    $out = $this->get_normalized_http_url() . "?";
    $out .= $this->to_postdata();
    return $out;
  }

  /**
   * builds the data one would send in a POST request
   */
  function to_postdata() {
    return OAuthUtil::oauth_http_build_query($this->parameters);
  }

  /**
   * builds the Authorization: header
   */
  function to_header() {
    $out ='Authorization: OAuth realm="yahooapis.com"';
    $total = array();
    foreach ($this->parameters as $k => $v) {
      if (substr($k, 0, 5) != "oauth")
      {
        continue;
      }
      if (is_array($v))
      {
         trigger_error('Arrays not supported in headers', E_USER_WARNING);
         return NULL;
      }
      $out .= ',' . OAuthUtil::urlencode_rfc3986($k) . '="' . OAuthUtil::urlencode_rfc3986($v) . '"';
    }

    return $out;
  }

  function __toString() {
    return $this->to_url();
  }


  function sign_request($signature_method, $consumer, $token) {
    $this->set_parameter("oauth_signature_method", $signature_method->get_name());
    $signature = $this->build_signature($signature_method, $consumer, $token);
    $this->set_parameter("oauth_signature", $signature);
  }

  function build_signature($signature_method, $consumer, $token) {
    $signature = $signature_method->build_signature($this, $consumer, $token);
    return $signature;
  }

  /**
   * util function: current timestamp
   */
  static function generate_timestamp() {
    return time();
  }

  /**
   * util function: current nonce
   */
  static function generate_nonce() {
    $mt = microtime();
    $rand = mt_rand();

    return md5($mt . $rand); // md5s look nicer than numbers
  }

  /**
   * util function for turning the Authorization: header into
   * parameters, has to do some unescaping
   */
  static function split_header($header) {
    $pattern = '/(([-_a-z]*)=("([^"]*)"|([^,]*)),?)/';
    $offset = 0;
    $params = array();
    while (preg_match($pattern, $header, $matches, PREG_OFFSET_CAPTURE, $offset) > 0) {
      $match = $matches[0];
      $header_name = $matches[2][0];
      $header_content = (isset($matches[5])) ? $matches[5][0] : $matches[4][0];
      $params[$header_name] = OAuthUtil::urldecode_rfc3986( $header_content );
      $offset = $match[1] + strlen($match[0]);
    }

    if (isset($params['realm'])) {
       unset($params['realm']);
    }

    return $params;
  }

  /**
   * helper to try to sort out headers for people who aren't running apache
   */
  static function get_headers() {
    if (function_exists('apache_request_headers')) {
      // we need this to get the actual Authorization: header
      // because apache tends to tell us it doesn't exist
      return apache_request_headers();
    }
    // otherwise we don't have apache and are just going to have to hope
    // that $_SERVER actually contains what we need
    $out = array();
    foreach ($_SERVER as $key => $value) {
      if (substr($key, 0, 5) == "HTTP_") {
        // this is chaos, basically it is just there to capitalize the first
        // letter of every word that is not an initial HTTP and strip HTTP
        // code from przemek
        $key = str_replace(" ", "-", ucwords(strtolower(str_replace("_", " ", substr($key, 5)))));
        $out[$key] = $value;
      }
    }

    if(!isset($out['Content-Type'])) {
      $out['Content-Type'] = @$_SERVER['CONTENT_TYPE'];
    }

    return $out;
  }
}

class OAuthServer {
  var $timestamp_threshold = 300; // in seconds, five minutes
  var $version = '1.0';             // hi blaine
  var $signature_methods = array();

  var $data_store;

  function OAuthServer($data_store) {
    $this->data_store = $data_store;
  }

  function add_signature_method($signature_method) {
    $this->signature_methods[$signature_method->get_name()] =
        $signature_method;
  }

  // high level functions

  /**
   * process a request_token request
   * returns the request token on success
   */
  function fetch_request_token(&$request) {
    $this->get_version($request);

    $consumer = $this->get_consumer($request);

    // no token required for the initial token request
    $token = NULL;

    $this->check_signature($request, $consumer, $token);

    $new_token = $this->data_store->new_request_token($consumer);

    return $new_token;
  }

  /**
   * process an access_token request
   * returns the access token on success
   */
  function fetch_access_token(&$request) {
    $this->get_version($request);

    $consumer = $this->get_consumer($request);

    // requires authorized request token
    $token = $this->get_token($request, $consumer, "request");


    $this->check_signature($request, $consumer, $token);

    $new_token = $this->data_store->new_access_token($token, $consumer);

    return $new_token;
  }

  /**
   * verify an api call, checks all the parameters
   */
  function verify_request(&$request) {
    $this->get_version($request);
    $consumer = $this->get_consumer($request);
    $token = $this->get_token($request, $consumer, "access");
    $this->check_signature($request, $consumer, $token);
    return array($consumer, $token);
  }

  // Internals from here
  /**
   * version 1
   */
  function get_version(&$request) {
    $version = $request->get_parameter("oauth_version");
    if (!$version) {
      $version = '1.0';
    }
    if ($version && $version != $this->version) {
      trigger_error("OAuth version '$version' not supported", E_USER_WARNING);
      return '1.0';
    }
    return $version;
  }

  /**
   * figure out the signature with some defaults
   */
  function get_signature_method(&$request) {
    $signature_method =
        @$request->get_parameter("oauth_signature_method");
    if (!$signature_method) {
      $signature_method = "PLAINTEXT";
    }
    if (!in_array($signature_method,
                  array_keys($this->signature_methods))) {
      trigger_error(
        "Signature method '$signature_method' not supported try one of the following: " . implode(", ", array_keys($this->signature_methods)), E_USER_WARNING
      ); return NULL;
    }
    return $this->signature_methods[$signature_method];
  }

  /**
   * try to find the consumer for the provided request's consumer key
   */
  function get_consumer(&$request) {
    $consumer_key = @$request->get_parameter("oauth_consumer_key");
    if (!$consumer_key) {
      trigger_error("Invalid consumer key", E_USER_WARNING);
      return NULL;
    }

    $consumer = $this->data_store->lookup_consumer($consumer_key);
    if (!$consumer) {
      trigger_error("Invalid consumer", E_USER_WARNING);
      return NULL;
    }

    return $consumer;
  }

  /**
   * try to find the token for the provided request's token key
   */
  function get_token(&$request, $consumer, $token_type="access") {
    $token_field = @$request->get_parameter('oauth_token');
    $token = $this->data_store->lookup_token(
      $consumer, $token_type, $token_field
    );
    if (!$token) {
      trigger_error("Invalid $token_type token: $token_field", E_USER_WARNING);
      return NULL;
    }
    return $token;
  }

  /**
   * all-in-one function to check the signature on a request
   * should guess the signature method appropriately
   */
  function check_signature(&$request, $consumer, $token) {
    // this should probably be in a different method
    $timestamp = @$request->get_parameter('oauth_timestamp');
    $nonce = @$request->get_parameter('oauth_nonce');

    $this->check_timestamp($timestamp);
    $this->check_nonce($consumer, $token, $nonce, $timestamp);

    $signature_method = $this->get_signature_method($request);

    $signature = $request->get_parameter('oauth_signature');
    $valid_sig = $signature_method->check_signature(
      $request,
      $consumer,
      $token,
      $signature
    );

    if (!$valid_sig) {
      trigger_error("Invalid signature", E_USER_WARNING);
      return NULL;
    }
  }

  /**
   * check that the timestamp is new enough
   */
  function check_timestamp($timestamp) {
    // verify that timestamp is recentish
    $now = time();
    if ($now - $timestamp > $this->timestamp_threshold) {
      trigger_error("Expired timestamp, yours $timestamp, ours $now", E_USER_WARNING);
      return NULL;
    }
  }

  /**
   * check that the nonce is not repeated
   */
  function check_nonce($consumer, $token, $nonce, $timestamp) {
    // verify that the nonce is uniqueish
    $found = $this->data_store->lookup_nonce($consumer, $token, $nonce, $timestamp);
    if ($found) {
      trigger_error("Nonce already used: $nonce", E_USER_WARNING);
      return NULL;
    }
  }



}

class OAuthDataStore {
  function lookup_consumer($consumer_key) {
    // implement me
  }

  function lookup_token($consumer, $token_type, $token) {
    // implement me
  }

  function lookup_nonce($consumer, $token, $nonce, $timestamp) {
    // implement me
  }

  function new_request_token($consumer) {
    // return a new token attached to this consumer
  }

  function new_access_token($token, $consumer) {
    // return a new access token attached to this consumer
    // for the user associated with this token if the request token
    // is authorized
    // should also invalidate the request token
  }

}


/*  A very naive dbm-based oauth storage
 */
class SimpleOAuthDataStore extends OAuthDataStore {
  var $dbh;

  function SimpleOAuthDataStore($path = "oauth.gdbm") {
    $this->dbh = dba_popen($path, 'c', 'gdbm');
  }

  function __destruct() {
    dba_close($this->dbh);
  }

  function lookup_consumer($consumer_key) {
    $rv = dba_fetch("consumer_$consumer_key", $this->dbh);
    if ($rv === FALSE) {
      return NULL;
    }
    $obj = unserialize($rv);
    if (!($obj instanceof OAuthConsumer)) {
      return NULL;
    }
    return $obj;
  }

  function lookup_token($consumer, $token_type, $token) {
    $rv = dba_fetch("${token_type}_${token}", $this->dbh);
    if ($rv === FALSE) {
      return NULL;
    }
    $obj = unserialize($rv);
    if (!($obj instanceof OAuthToken)) {
      return NULL;
    }
    return $obj;
  }

  function lookup_nonce($consumer, $token, $nonce, $timestamp) {
    if (dba_exists("nonce_$nonce", $this->dbh)) {
      return TRUE;
    } else {
      dba_insert("nonce_$nonce", "1", $this->dbh);
      return FALSE;
    }
  }

  function new_token($consumer, $type="request") {
    $key = md5(time());
    $secret = mt_rand();
    $token = new OAuthToken($key, md5($secret));
    if (!dba_insert("${type}_$key", serialize($token), $this->dbh)) {
      trigger_error("doooom!", E_USER_WARNING);
      return NULL;
    }
    return $token;
  }

  function new_request_token($consumer) {
    return $this->new_token($consumer, "request");
  }

  function new_access_token($token, $consumer) {

    $token = $this->new_token($consumer, 'access');
    dba_delete("request_" . $token->key, $this->dbh);
    return $token;
  }
}

class OAuthUtil {

  // This function takes a input like a=b&a=c&d=e and returns the parsed
  // parameters like this
  // array('a' => array('b','c'), 'd' => 'e')
  static function parse_parameters( $input ) {
    if (!isset($input) || !$input) return array();

    $pairs = explode('&', $input);

    $parsed_parameters = array();
    foreach ($pairs as $pair) {
      $split = explode('=', $pair, 2);
      $parameter = OAuthUtil::urldecode_rfc3986($split[0]);
      $value = isset($split[1]) ? OAuthUtil::urldecode_rfc3986($split[1]) : '';

      if (isset($parsed_parameters[$parameter])) {
        // We have already recieved parameter(s) with this name, so add to the list
        // of parameters with this name

        if (is_scalar($parsed_parameters[$parameter])) {
          // This is the first duplicate, so transform scalar (string) into an array
          // so we can add the duplicates
          $parsed_parameters[$parameter] = array($parsed_parameters[$parameter]);
        }

        $parsed_parameters[$parameter][] = $value;
      } else {
        $parsed_parameters[$parameter] = $value;
      }
    }
    return $parsed_parameters;
  }

  // Utility function for turning the Authorization: header into
  // parameters, has to do some unescaping
  // Can filter out any non-oauth parameters if needed (default behaviour)
  static function split_header($header, $only_allow_oauth_parameters = true) {
    $pattern = '/(([-_a-z]*)=("([^"]*)"|([^,]*)),?)/';
    $offset = 0;
    $params = array();
    while (preg_match($pattern, $header, $matches, PREG_OFFSET_CAPTURE, $offset) > 0) {
      $match = $matches[0];
      $header_name = $matches[2][0];
      $header_content = (isset($matches[5])) ? $matches[5][0] : $matches[4][0];
      if (preg_match('/^oauth_/', $header_name) || !$only_allow_oauth_parameters) {
        $params[$header_name] = OAuthUtil::urldecode_rfc3986($header_content);
      }
      $offset = $match[1] + strlen($match[0]);
    }

    if (isset($params['realm'])) {
       unset($params['realm']);
    }

    return $params;
  }

  // helper to try to sort out headers for people who aren't running apache
  static function get_headers() {
    if (function_exists('apache_request_headers')) {
      // we need this to get the actual Authorization: header
      // because apache tends to tell us it doesn't exist
      return apache_request_headers();
    }
    // otherwise we don't have apache and are just going to have to hope
    // that $_SERVER actually contains what we need
    $out = array();
    foreach ($_SERVER as $key => $value) {
      if (substr($key, 0, 5) == "HTTP_") {
        // this is chaos, basically it is just there to capitalize the first
        // letter of every word that is not an initial HTTP and strip HTTP
        // code from przemek
        $key = str_replace(
          " ",
          "-",
          ucwords(strtolower(str_replace("_", " ", substr($key, 5))))
        );
        $out[$key] = $value;
      }
    }
    return $out;
  }


  static function build_http_query($params) {
    if (!$params) return '';

    // Urlencode both keys and values
    $keys = OAuthUtil::urlencode_rfc3986(array_keys($params));
    $values = OAuthUtil::urlencode_rfc3986(array_values($params));
    $params = array_combine($keys, $values);

    // Parameters are sorted by name, using lexicographical byte value ordering.
    // Ref: Spec: 9.1.1 (1)
    uksort($params, 'strcmp');

    $pairs = array();
    foreach ($params as $parameter => $value) {
      if (is_array($value)) {
        // If two or more parameters share the same name, they are sorted by their value
        // Ref: Spec: 9.1.1 (1)
        natsort($value);
        foreach ($value as $duplicate_value) {
          $pairs[] = $parameter . '=' . $duplicate_value;
        }
      } else {
        $pairs[] = $parameter . '=' . $value;
      }
    }
    // For each parameter, the name is separated from the corresponding value by an '=' character (ASCII code 61)
    // Each name-value pair is separated by an '&' character (ASCII code 38)
    return implode('&', $pairs);
  }

  static function oauth_parse_string($query_string) {
    if(!isset($query_string)) {
      return array();
    }
    $pairs = explode('&', $query_string);
    $query_arr = array();
    foreach($pairs as $pair) {
      list($k, $v) = explode('=', $pair, 2);

      // Handle duplicate query params
      if(isset($query_arr[$k])) {

        // Transform scalar to array
        if(is_scalar($query_arr[$k])) {
          $query_arr[$k] = array($query_arr[$k]);
        }
        $query_arr[$k][] = $v;
      } else {
        $query_arr[$k] = $v;
      }
    }
    return $query_arr;
  }

  static function oauth_http_build_query($params) {

    $out = false;
    if(!empty($params))
    {
      // Urlencode both keys and values
      $keys = OAuthUtil::urlencode_rfc3986(array_keys($params));
      $values = OAuthUtil::urlencode_rfc3986(array_values($params));
      $params = array_combine($keys, $values);

      // Sort by keys (natsort)
      uksort($params, 'strcmp');

      $pairs = array();
      foreach ($params as $k => $v) {
        if (is_array($v)) {
          natsort($v);
          foreach ($v as $duplicate_value) {
            $pairs[] = $k . '=' . $duplicate_value;
          }
        } else {
          $pairs[] = $k . '=' . $v;
        }
      }
      $out = implode('&', $pairs);
    }
    return $out;
  }

  static function urlencode_rfc3986($input) {
    if (is_array($input)) {
      return array_map(array('OAuthUtil','urlencode_rfc3986'), $input);
    } else if (is_scalar($input)) {
      return str_replace('+', ' ',
                           str_replace('%7E', '~', rawurlencode($input)));
    } else {
      return '';
    }
  }

  // This decode function isn't taking into consideration the above
  // modifications to the encoding process. However, this method doesn't
  // seem to be used anywhere so leaving it as is.
  static function urldecode_rfc3986($string) {
    return urldecode($string);
  }

  static function urlencodeRFC3986($input) {
    return OAuthUtil::urlencode_rfc3986($input);
  }

  static function urldecodeRFC3986($input) {
    return OAuthUtil::urldecode_rfcC3986($input);
  }
}


/**
 * Crib'd native implementation of hash_hmac() for SHA1 from the
 * Fire Eagle PHP code:
 *
 * http://fireeagle.yahoo.net/developer/code/php
 */
if (!function_exists("hash_hmac")) {
    // Earlier versions of PHP5 are missing hash_hmac().  Here's a
    // pure-PHP version in case you're using one of them.
    function hash_hmac($algo, $data, $key) {
        // Thanks, Kellan: http://laughingmeme.org/code/hmacsha1.php.txt
        if ($algo != 'sha1') {
            trigger_error("Internal hash_hmac() can only do sha1, sorry", E_USER_WARNING);
            return NULL;
        }

        $blocksize = 64;
        $hashfunc = 'sha1';
        if (strlen($key)>$blocksize)
            $key = pack('H*', $hashfunc($key));
        $key = str_pad($key,$blocksize,chr(0x00));
        $ipad = str_repeat(chr(0x36),$blocksize);
        $opad = str_repeat(chr(0x5c),$blocksize);
        $hmac = pack(
                'H*',$hashfunc(
                    ($key^$opad).pack(
                                      'H*',$hashfunc(
                                          ($key^$ipad).$data
                                          )
                                     )
                    )
                );
        return $hmac;
    }
}
