<?php
/*
GET /release.php?payment_request_sid=<PRxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx>
Releases the escrowed money to the recipient
*/

require 'config.php';
require 'poundpay.php';

$poundpay_client = new PoundPayAPIClient($CONFIG['poundpay']['sid'], 
                                         $CONFIG['poundpay']['auth_token'],
                                         $CONFIG['poundpay']['api_uri'],
                                         $CONFIG['poundpay']['version']);
$payment_request_sid = $_GET['payment_request_sid'];

// Get payment, assuming we don't already have it from the callback
$response = $poundpay_client->request("/payment_requests/{$payment_request_sid}/payments", 'GET');
$payment = $response->response_json->payments[0];

// Release payment
$response = $poundpay_client->request("/payments/{$payment->sid}", 'PUT', array('status' => 'released'));
$payment = $response->response_json;
?>

<html>
  <title>Released Payment - Simple Marketplace</title>
  <body>
    <h2>$<?= $payment->amount / 100 ?> was released for <?= $payment->description ?></h2>
    <pre><?= $response->response_text ?></pre>
  </body>
</html>