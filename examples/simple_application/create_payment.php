<?php

// make sure the poundpay client library is in the PHP's search path
require "../../poundpay.php";

// account information
$account_sid = 'DVXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';
$auth_token = '????????????????????????????????????????????????????????????????';
$api_uri = 'http://api-sandbox.poundpay.com';


$client = new PoundPayAPIClient($account_sid, $auth_token, $api_uri);


////////////////////////////////////
//
// CREATING A PAYMENT REQUEST
//
////////////////////////////////////
$data = array(
    /* NOTE: all amounts are in USD cents */
    "amount" => $_POST['amount'] * 100,
    "payer_fee_amount" => $_POST['payer_fee_amount'] * 100,
    "recipient_fee_amount" => $_POST['recipient_fee_amount'] * 100,
    "payer_email_address" => $_POST['payer_email_address'],
    "recipient_email_address" => $_POST['recipient_email_address'],
    "description" =>  $_POST['description'],
);

$response = $client->request("/payment_requests/", "POST", $data);
echo $response->response_text;
?>