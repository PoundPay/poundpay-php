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

class Payment extends Resource {
    protected static $_name = "payments";
    
    public static function batch_update($sids, $params) {
        $params['sid'] = $sids;
        $resp = self::$_client->put(static::$_name, $params);
        $resources = array();
        foreach ($resp->json[static::$_name] as $vars) {
            $resources[] = new static($vars);
        }
        return $resources;
    }
}
