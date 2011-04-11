<?php
/**
 * PoundPay Client Library
 *
 * @category   APIClients
 * @package    PoundPay
 * @author     PoundPay Inc.
 * @version    v2.0.0
 * @link       http://dev.poundpay.com/
 */

namespace PoundPay;
require_once __DIR__ . '/Autoload.php';

class Core {
    public static function configure($developer_sid,
                       $auth_token,
                       $api_uri = "https://api.poundpay.com",
                       $version = 'silver') {
        Resource::setClient(new APIClient($developer_sid, $auth_token, $api_uri, $version));
    }

    public static function getLastResponse() {
        return Resource::getClient()->getLastResponse();
    }
}
