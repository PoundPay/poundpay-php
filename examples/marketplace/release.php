<?php

require 'config.php';
require dirname(__FILE__).'/../../poundpay.php';

PoundPay\configure(
    $CONFIG['poundpay']['sid'],
    $CONFIG['poundpay']['auth_token'],
    $CONFIG['poundpay']['api_uri'],
    $CONFIG['poundpay']['version']
);

// Release payment
$payment = new PoundPay\Payment(array(
    'sid' => $_GET['payment_sid'],
    'status' => 'released'
));
$payment->save();
?>

<html>
  <head>
    <title>Release Payment - Simple Marketplace</title>
  </head>
  <body>
    <h1>Release Payment</h1>
    <h2>$<?= $payment->amount / 100 ?> was released for <?= $payment->description ?></h2>
    <h2>PoundPay Response for Payment</h2>
    <pre><?= PoundPay\getLastResponse()->body ?></pre>
  </body>
</html>