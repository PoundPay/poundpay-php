<?php
/**
 * PoundPay Client Library
 *
 * @category   Web Services
 * @package    PoundPay
 * @author     PoundPay Inc.
 * @version    v2.1.0
 * @link       http://dev.poundpay.com/
 */


namespace Services\PoundPay;
require_once __DIR__ . '/Autoload.php';

/**
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
