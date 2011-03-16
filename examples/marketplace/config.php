<?php
$CONFIG = array(
  'poundpay' => array(
    'api_uri' => 'https://api-sandbox.poundpay.com',
    'www_uri' => 'https://www-sandbox.poundpay.com',
    'version' => 'silver',
    'sid' => 'DV9d53695c4de211e085731231400042c7',
    'auth_token' => '443d1b5c9cd80dfd34dc50be6c6a9edd261ecef9ae53a0a71a474c99dc50bf16',
    // It's much more robust to store the callback_url supplied to PoundPay
    // in the configs than to try and reconstruct the url from the request
    'callback_url' => 'http://mycompany.com/poundpay_callback.php',
  ),
);