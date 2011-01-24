<?php
/**
 * PoundPay API Client / Response / Signature Verifier
 *
 * TODO: give example usage
 *
 * @category   APIClients
 * @package    poundpay
 * @author     PoundPay Inc.
 * @version    v1.0.2
 * @link       http://dev.poundpay.com/
 * @requires   php-curl, json
 */


// does curl extension exist?
if( !extension_loaded("curl") ) {
    $error_msg = "Curl extension is required for PoundPayAPIClient to work";
    throw(new Exception($error_msg));
}

// does the json extension exist?
if( !extension_loaded("json") ) {
    $error_msg = "JSON extension is required for PoundPayAPIClient to work";
    throw(new Exception($error_msg));
}


/*
 * PoundPayAPIClient throws PoundPayAPIException on any errors
 * encountered during a REST request.
 * Catch this exception when making a request
 *
 */
class PoundPayAPIException extends Exception {}


/*
 * PoundPayAPIClient: the core API client, talks to the PoundPay REST API.
 * @return
 *   Returns a PoundPayAPIResponse object for all responses if PoundPay's
 *   API was reachable.
 * @throws
 *   Throws a PoundPayAPIException if PoundPay's API was unreachable
 *
 */

class PoundPayAPIClient {

    protected $developer_sid;
    protected $auth_token;
    protected $api_uri;
    protected $version;

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

    /*
     * request()
     *   Sends an HTTP Request to the PoundPay API
     *   $endpoint : the URL (relative to the endpoint URL, after the /{version})
     *   $method : the HTTP method to use, defaults to GET
     *   $vars : for POST or PUT, a key/value associative array of data to
     *           send, for GET will be appended to the URL as query params
     */
    public function request($endpoint, $method="GET", $vars=array()) {
        $fp = null;
        $tmpfile = "";
        $encoded = "";

        foreach($vars AS $key => $value) {
            $encoded .= "$key=" . urlencode($value) . "&";
        }

        $encoded = rtrim($encoded, "&");

        // construct full url
        $endpoint = rtrim($endpoint, "/");  // ensure that they're one slash at the end
        $url = "{$this->api_uri}/{$this->version}{$endpoint}/";

        // if GET and vars, append them
        if($method == "GET") {
            // checks to see if the path already has encoded attributes
            // then just append the encoded string using & if it does or
            // append post ?
            $url .= (FALSE === strpos($path, '?') ? "?" : "&") . $encoded;
        }

        // initialize a new curl object
        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);

        switch(strtoupper($method)) {

           case "GET":
               curl_setopt($curl, CURLOPT_HTTPGET, TRUE);
               break;

           case "POST":
               curl_setopt($curl, CURLOPT_POST, TRUE);
               curl_setopt($curl, CURLOPT_POSTFIELDS, $encoded);
               break;

           case "PUT":
               $tmpfile = tempnam("/tmp", "put_");
               $fp = fopen($tmpfile, 'r');

               curl_setopt($curl, CURLOPT_POSTFIELDS, $encoded);
               curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");

               file_put_contents($tmpfile, $encoded);

               curl_setopt($curl, CURLOPT_INFILE, $fp);
               curl_setopt($curl, CURLOPT_INFILESIZE, filesize($tmpfile));
               break;

           case "DELETE":
               curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
               break;

           default:
               throw(new PoundPayAPIException("Unknown method $method"));
               break;

        }

        // send credentials
        curl_setopt($curl, CURLOPT_USERPWD, $pwd = "{$this->developer_sid}:{$this->auth_token}");

        // do the request. If FALSE, then an exception occurred
        if(FALSE === ($result = curl_exec($curl))) {
            throw(new PoundPayAPIException("Curl failed with error " . curl_error($curl)));
        }

        // get result code
        $response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        // unlink tmpfiles and clean up
        if($fp) {
            fclose($fp);
        }
        if(strlen($tmpfile)) {
            unlink($tmpfile);
        }

        return new PoundPayAPIResponse($url, $result, $response_code);
    }
}


/*
 * PoundPayAPIResponse holds all the resource response data
 * Before using the reponse, check $is_error to see if an exception
 * occurred with the data sent to PoundPay.
 * $response_json will contain a decoded json response object
 * $response_text contains the raw string response
 * $url and $query_string are from the original HTTP request
 * $status_code is the response code of the request
 */
class PoundPayAPIResponse {

    public $response_text;
    public $response_json;
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
        $this->response_text = $text;
        $this->status_code = $status_code;

        if($status_code != 204) {
            $this->response_json = @json_decode($text);
        }

        $this->is_error = FALSE;
        if($this->error_msg = ($status_code >= 400)) {
            $this->is_error = TRUE;
            $this->error_name = $this->response_json['error_name'];
            $this->error_msg = $this->response_json['error_message'];
        }

    }

}


class PoundPaySignatureVerifier {

    protected $developer_sid;
    protected $auth_token;

    function __construct($developer_sid, $auth_token){
        $this->auth_token = $auth_token;
        $this->developer_sid = $developer_sid;
    }

    public function is_authentic_response($expected_signature, $url, $data = array()) {

        // sort the array by keys
        ksort($data);

        // append them to the data string in order
        // with no delimiters
        foreach($data AS $key => $value) {
            $url .= "{$key}{$value}";
        }

        // This function calculates the HMAC hash of the data with the key
        // passed in
        // Note: hash_hmac requires PHP 5 >= 5.1.2 or PECL hash:1.1-1.5
        // Or http://pear.php.net/package/Crypt_HMAC/
        $signature = hash_hmac("sha512", $url, $this->auth_token, TRUE);

        // encode signature in base64
        $encoded_signature = base64_encode($signature);

        return $encoded_signature == $expected_signature;

    }

}
?>