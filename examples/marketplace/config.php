<?php
$CONFIG = array(
  'poundpay' => array(
    'api_uri' => 'https://api-sandbox.poundpay.com',
    'www_uri' => 'https://www-sandbox.poundpay.com',
    'version' => 'silver',
    'sid' => 'DVxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
    'auth_token' => 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
    // It's much more robust to store the callback_url supplied to PoundPay
    // in the configs than to try and reconstruct the url from the request
    'callback_url' => 'http://mycompany.com/poundpay_callback.php',
  ),
);