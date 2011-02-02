<?php
/*
GET /release.php?payment_sid=<PYxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx>
Releases the escrowed money to the recipient
*/

require 'config.php';
require 'poundpay.php';

$poundpay_client = new PoundPayAPIClient($CONFIG['poundpay']['sid'],
                                         $CONFIG['poundpay']['auth_token'],
                                         $CONFIG['poundpay']['api_uri'],
                                         $CONFIG['poundpay']['version']);
$payment_sid = $_GET['payment_sid'];

// Release payment
$response = $poundpay_client->request("/payments/{$payment_sid}", 'PUT', array('status' => 'released'));
$payment = $response->response_json;
?>

<html>
  <head>
    <title>Release Payment - Simple Marketplace</title>
  </head>
  <body>
    <h1>Release Payment</h1>
    <h2>$<?= $payment->amount / 100 ?> was released for <?= $payment->description ?></h2>
    <h2>PoundPay Response for Payment</h2>
    <pre><?= $response->response_text ?></pre>
  </body>
</html>