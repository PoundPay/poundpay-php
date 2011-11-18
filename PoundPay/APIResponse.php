<?php
/**
 * PoundPay Client Library
 *
 * @category   APIClients
 * @package    PoundPay
 * @author     PoundPay Inc.
 * @version    v2.1.0
 * @link       http://dev.poundpay.com/
 */


namespace PoundPay;
require_once __DIR__ . '/Autoload.php';
require_once 'HTTP/Request2/Response.php';

if( !extension_loaded("json") ) {
    $error_msg = "JSON extension is required for PoundPay\\APIResponse";
    throw(new \Exception($error_msg));
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
    /** @var \HTTP_Request2_Response The lower level HTTP response object */
    public $http_response;

    public function __construct(\HTTP_Request2_Response $http_response) {
        $this->http_response = $http_response;

        if($http_response->getStatus() != 204) {
            $this->json = json_decode($http_response->getBody(), true);
        }

        $this->is_error = FALSE;
        if($http_response->getStatus() >= 400) {
            $this->is_error = TRUE;
            $this->error_name = isset($this->json['error_name']) ? $this->json['error_name'] : '<unknown>';
            $this->error_msg = isset($this->json['error_message']) ? $this->json['error_message'] : '<unknown>';
        }
    }
}
