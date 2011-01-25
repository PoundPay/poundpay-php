<?php
/*
POST /poundpay_callback.php
Called by PoundPay when a payment is created.  404 for anyone other than PoundPay.
This URL has to be configured with PoundPay
*/

require 'config.php';
require 'poundpay.php';

// Verify request is from PoundPay; otherwise, 404
$poundpay_verifier = new PoundPaySignatureVerifier($CONFIG['poundpay']['sid'], $CONFIG['poundpay']['auth_token']);
if(!$poundpay_verifier->is_authentic_response($_SERVER['HTTP_X_POUNDPAY_SIGNATURE'], $_SERVER['REQUEST_URI'], $_POST)) {
  header("HTTP/1.1 404 Not Found");
}

// Store the payment data locally ...
