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
 * @requires   Zend, json
 */
namespace PoundPay;

require_once 'Zend/Http/Client.php';

if( !extension_loaded("json") ) {
    $error_msg = "JSON extension is required for PoundPay client library";
    throw(new \Exception($error_msg));
}

function configure($developer_sid,
                   $auth_token,
                   $api_uri = "https://api.poundpay.com",
                   $version = 'silver') {
    Resource::setClient(new APIClient($developer_sid, $auth_token, $api_uri, $version));
}

function get_last_response() {
    return Resource::getClient()->get_last_response();
}

class Resource {
    /** @var APIClient set by PoundPay\configure() **/
    protected static $_client;
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
        foreach ($resp->json[static::$_name] as $vars) {
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

    public static function setClient($client) {
        self::$_client = $client;
    }

    public static function getClient() {
        return self::$_client;
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

    public function __construct(APIResponse $api_response, $url) {
        $status_code = $api_response->http_response->getStatus();
        $this->api_response = $api_response;
        $exceptionMessage = "PoundPay API error. http code: $status_code, " .
                            "message: $api_response->error_msg, url: $url";
        parent::__construct($exceptionMessage, $status_code);
    }
}

/*
 * APIClient: the core API client, talks to the PoundPay REST API.
 *
 * @return APIResponse for all responses if PoundPay's API was reachable.
 * @throws APIException for error responses from the API
 * @throws Exception for all other errors
 */
class APIClient {

    protected $developer_sid;
    protected $auth_token;
    protected $api_uri;
    protected $version;

    /** @var APIResponse the response from the last api call */
    protected $last_response;

    /**
     * @param string $developer_sid Your Developer SID
     * @param string $auth_token Your account's auth_token
     * @param string $api_uri The PoundPay REST Service URI, defaults to https://api.poundpay.com
     * @param string $version The PoundPay API version
     */
    public function __construct($developer_sid,
                                $auth_token,
                                $api_uri = "https://api.poundpay.com",
                                $version = 'silver') {

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

    /**
     * Sends an HTTP Request to the PoundPay API
     *
     * @param string $endpoint The URL (relative to the endpoint URL, after the /{version})
     * @param string $method The HTTP method to use, defaults to GET
     * @param array $vars For POST or PUT, a key/value associative array of data to
     *                    send, for GET will be appended to the URL as query params
     */
    public function request($endpoint, $method = 'GET', $vars = array()) {
        // construct full url
        $endpoint = rtrim($endpoint, "/"); // normalize - slash added below
        $url = "{$this->api_uri}/{$this->version}/{$endpoint}/";

        $client = new \Zend_Http_Client($url);
        $client->setAuth($this->developer_sid, $this->auth_token);

        $method = strtoupper($method);
        switch($method) {
            case "GET":
                $client->setParameterGet($vars);
                break;

            case "POST":
            case "PUT":
                $client->setParameterPost($vars);
                break;

           case "DELETE":
               break;

           default:
               throw(new Exception("Unsupported method $method"));
               break;
        }

        $httpResponse = $client->request($method);

        $response = $this->last_response = new APIResponse($httpResponse);
        if ($response->is_error) {
            throw new APIException($response, $url);
        }

        return $response;
    }
}

/**
 * Contains the parsed data from a PoundPay API response.
 */
class APIResponse {

    /** @var array The decoded json response */
    public $json;
    /** @var bool Whether the response indicates an error condition */
    public $is_error;
    /** @var string The name of the error if $is_error is true */
    public $error_name;
    /** @var string The error message if $is_error is true */
    public $error_msg;
    /** @var \Zend_Http_Response The lower level HTTP response object */
    public $http_response;

    public function __construct(\Zend_Http_Response $http_response) {
        $this->http_response = $http_response;

        if($http_response->getStatus() != 204) {
            $this->json = json_decode($http_response->getBody(), true);
        }

        $this->is_error = FALSE;
        if($http_response->getStatus() >= 400) {
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
