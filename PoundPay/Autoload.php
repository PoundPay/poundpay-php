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


if (!function_exists('poundpay_autoload')) {
    function poundpay_autoload($class)
    {
        $namespace = 'PoundPay\\';
        if (strpos($class, $namespace) === 0) {
            $path = __DIR__ . DIRECTORY_SEPARATOR .
                    substr($class, strlen($namespace)) . '.php';
            include $path;
        }
    }

    spl_autoload_register('poundpay_autoload');
}
