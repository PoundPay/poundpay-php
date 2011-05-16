<?php
/**
 * PoundPay API Client / Response / Signature Verifier
 *
 * TODO: give example usage
 *
 * @category   APIClients
 * @package    poundpay
 * @author     PoundPay Inc.
 * @version    v1.0.3
 * @link       http://dev.poundpay.com/
 * @requires   php-curl, json
 */
namespace PoundPay;

if( !extension_loaded("curl") ) {
    $error_msg = "Curl extension is required for PoundPay client library";
    throw(new \Exception($error_msg));
}

if( !extension_loaded("json") ) {
    $error_msg = "JSON extension is required for PoundPay client library";
    throw(new \Exception($error_msg));
}


function configure($developer_sid,
                   $auth_token,
                   $api_uri="https://api.poundpay.com",
                   $version='silver') {
    Resource::$_client = new APIClient($developer_sid, $auth_token, $api_uri, $version);
}

function get_last_response() {
    return Resource::$_client->get_last_response();
}

class Resource {
    /** @var APIClient set by PoundPay\configure() **/
    public static $_client;
    /** @var string must be set by subclass **/
    protected static $_name;

    public function __construct($vars) {
        $this->setVars($vars);
    }

    public function setVars($vars) {
        foreach ($vars as $key => $val) {
            $this->$key = $val;
        }
    }

    public static function all() {
        $resp = self::$_client->get(static::$_name);
        $resources = array();
        foreach ($resp->json->{static::$_name} as $vars) {
            $resources[] = new static($vars);
        }
        return $resources;
    }

    public static function find($sid) {
        $resp = self::$_client->get(self::getPath($sid));
        return new static($resp->json);
    }

    public function save() {
        if (isset($this->sid)) {
            $vars = self::update($this->sid, get_object_vars($this));
        } else {
            $vars = self::create(get_object_vars($this));
        }
        $this->setVars($vars);
        return $this;
    }

    public function delete($sid) {
        self::$_client->delete(self::getPath($sid));
    }

    protected static function update($sid, $vars) {
        $resp = self::$_client->put(self::getPath($sid), $vars);
        return $resp->json;
    }

    protected static function create($vars) {
        $resp = self::$_client->post(static::$_name, $vars);
        return $resp->json;
    }

    protected static function getPath($sid) {
        return static::$_name . '/' . $sid;
    }
}

class Developer extends Resource {
    protected static $_name = "developers";
}

class Payment extends Resource {
    protected static $_name = "payments";
}


/*
 * Base exception class for PoundPay errors.
 */
class Exception extends \Exception {}

/*
 * Class for errors returned from the PoundPay API.
 */
class APIException extends Exception {
    /** @var APIResponse **/
    public $api_response;

    /** @param APIResponse $api_response **/
    public function __construct($api_response) {
        $this->api_response = $api_response;
        $exceptionMessage = "PoundPay API error: http code: $api_response->status_code, message: $api_response->error_msg, url: $api_response->url";
        parent::__construct($exceptionMessage, $api_response->status_code);
    }
}

/*
 * APIClient: the core API client, talks to the PoundPay REST API.
 * @return APIResponse for all responses if PoundPay's API was reachable.
 * @throws APIException for error responses from the API
 * @throws Exception for all other errors
 */
class APIClient {

    protected $developer_sid;
    protected $auth_token;
    protected $api_uri;
    protected $version;

    protected $curl_handle;
    /** @var APIResponse the response from the last api call */
    protected $last_response;

    /*
     * __construct
     *   $developer_sid : Your Developer SID
     *   $auth_token : Your account's auth_token
     *   $api_uri : The PoundPay REST Service URI, defaults to https://api.poundpay.com
     *   $version : The PoundPay API version
     */
    public function __construct($developer_sid,
                                $auth_token,
                                $api_uri="https://api.poundpay.com",
                                $version='silver') {

        $this->developer_sid = $developer_sid;
        $this->auth_token = $auth_token;
        $this->api_uri = $api_uri;
        $this->version = $version;
    }

    public function get($endpoint) {
        return $this->request($endpoint, 'GET');
    }

    public function post($endpoint, $vars) {
        return $this->request($endpoint, 'POST', $vars);
    }

    public function put($endpoint, $vars) {
        return $this->request($endpoint, 'PUT', $vars);
    }

    public function delete($endpoint) {
        return $this->request($endpoint, 'DELETE');
    }

    public function get_last_response() {
        return $this->last_response;
    }

    /** curl wrapper for mocking purposes **/
    protected function curl_init($url) {
        $this->curl_handle = curl_init($url);
    }

    /** curl wrapper for mocking purposes **/
    protected function curl_setopt($option, $value) {
        return curl_setopt($this->curl_handle, $option, $value);
    }

    /** curl wrapper for mocking purposes **/
    protected function curl_exec() {
        return curl_exec($this->curl_handle);
    }

    /** curl wrapper for mocking purposes **/
    protected function curl_getinfo($opt = 0) {
        return curl_getinfo($this->curl_handle, $opt);
    }

    protected function curl_error() {
        return curl_error($this->curl_handle);
    }

    protected function curl_close() {
        return curl_close($this->curl_handle);
    }

    /*
     * request()
     *   Sends an HTTP Request to the PoundPay API
     *   $endpoint : the URL (relative to the endpoint URL, after the /{version})
     *   $method : the HTTP method to use, defaults to GET
     *   $vars : for POST or PUT, a key/value associative array of data to
     *           send, for GET will be appended to the URL as query params
     */
    public function request($endpoint, $method='GET', $vars=array()) {
        $fp = null;
        $tmpfile = "";
        $encoded = "";

        foreach($vars AS $key => $value) {
            $encoded .= "$key=" . urlencode($value) . "&";
        }

        $encoded = rtrim($encoded, "&");

        // construct full url
        $endpoint = rtrim($endpoint, "/");  // ensure that they're one slash at the end
        $url = "{$this->api_uri}/{$this->version}/{$endpoint}/";

        // if GET and vars, append them
        if($method == "GET") {
            // checks to see if the path already has encoded attributes
            // then just append the encoded string using & if it does or
            // append post ?
            $url .= (FALSE === strpos($url, '?') ? "?" : "&") . $encoded;
        }
        // initialize a new curl object
        $this->curl_init($url);

        $this->curl_setopt(CURLOPT_RETURNTRANSFER, TRUE);

        switch(strtoupper($method)) {

           case "GET":
               $this->curl_setopt(CURLOPT_HTTPGET, TRUE);
               break;

           case "POST":
               $this->curl_setopt(CURLOPT_POST, TRUE);
               $this->curl_setopt(CURLOPT_POSTFIELDS, $encoded);
               break;

           case "PUT":
               $tmpfile = tempnam("/tmp", "put_");
               $fp = fopen($tmpfile, 'r');

               $this->curl_setopt(CURLOPT_POSTFIELDS, $encoded);
               $this->curl_setopt(CURLOPT_CUSTOMREQUEST, "PUT");

               file_put_contents($tmpfile, $encoded);

               $this->curl_setopt(CURLOPT_INFILE, $fp);
               $this->curl_setopt(CURLOPT_INFILESIZE, filesize($tmpfile));
               break;

           case "DELETE":
               $this->curl_setopt(CURLOPT_CUSTOMREQUEST, "DELETE");
               break;

           default:
               throw(new Exception("Unknown method $method"));
               break;

        }

        // send credentials
        $this->curl_setopt(CURLOPT_USERPWD, $pwd = "{$this->developer_sid}:{$this->auth_token}");
        $this->curl_setopt(CURLOPT_SSL_VERIFYPEER, false);

        // do the request. If FALSE, then an exception occurred
        if(FALSE === ($result = $this->curl_exec())) {
            throw(new Exception("Curl failed with error: " . $this->curl_error()));
        }

        // get result code
        $response_code = $this->curl_getinfo(CURLINFO_HTTP_CODE);

        // unlink tmpfiles and clean up
        if($fp) {
            fclose($fp);
        }
        if(strlen($tmpfile)) {
            unlink($tmpfile);
        }

        $this->curl_close();

        $response = $this->last_response = new APIResponse($url, $result, $response_code);
        if ($response->is_error) {
            throw new APIException($response);
        }

        return $response;
    }
}

/*
 * APIResponse holds all the resource response data
 * $json will contain a decoded json response object
 * $body contains the raw string response
 * $url and $query_string are from the original HTTP request
 * $status_code is the response code of the request
 */
class APIResponse {

    public $body;
    public $json;
    public $status_code;
    public $url;
    public $query_string;
    public $is_error;
    public $error_name;
    public $error_msg;

    public function __construct($url, $text, $status_code) {
        $parsed_url = parse_url($url);
        $this->url = $parsed_url["scheme"] . "//" . $parsed_url["host"];
        $this->query_string = null;
        if(array_key_exists("query", $parsed_url)){
            $this->query_string = $parsed_url["query"];
        }
        $this->body = $text;
        $this->status_code = $status_code;

        if($status_code != 204) {
            $this->json = json_decode($text, true);
        }

        $this->is_error = FALSE;
        if($this->error_msg = ($status_code >= 400)) {
            $this->is_error = TRUE;
            $this->error_name = $this->json['error_name'];
            $this->error_msg = $this->json['error_message'];
        }
    }
}


class SignatureVerifier {

    protected $developer_sid;
    protected $auth_token;

    function __construct($developer_sid, $auth_token) {
        $this->auth_token = $auth_token;
        $this->developer_sid = $developer_sid;
    }

    public function is_authentic_response($expected_signature, $url, $post_data = array()) {
        $data_string = $this->build_data_string($url, $post_data);
        $calculated_signature = $this->calculate_signature($data_string);
        return $expected_signature == $calculated_signature;
    }

    public function build_data_string($url, $data) {
        $data_string = $url;

        // sort the array by keys
        ksort($data);

        // append them to the data string in order
        // with no delimiters
        foreach($data AS $key => $value) {
            $data_string .= "{$key}{$value}";
        }
        return $data_string;
    }

    public function calculate_signature($data_string) {
        // Note: hash_hmac requires PHP 5 >= 5.1.2 or PECL hash:1.1-1.5
        // Or http://pear.php.net/package/Crypt_HMAC/
        $signature = hash_hmac("sha1", $data_string, $this->auth_token, true);

        // encode signature in base64
        $encoded_signature = base64_encode($signature);
        return $encoded_signature;
    }
}
