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
