<?php
header('Content-type: application/json');
require_once('PoundPay.php');
require_once('dev_info.php');

$client = new PoundPay\APIClient($developerUsername, $developerPassword, $endpoint);

// create payment data
$post_fields = array();
$post_fields['amount'] = $_POST['amount'];
$post_fields['payer_fee_amount'] = $_POST['payer_fee_amount'];
$post_fields['recipient_fee_amount'] = $_POST['recipient_fee_amount'];
$post_fields['payer_email_address'] = $_POST['payer_email_address'];
$post_fields['recipient_email_address'] = $_POST['recipient_email_address'];
$post_fields['description'] = $_POST['description'];
$post_fields['developer_identifier'] = $_POST['developer_identifier'];

$response = $client->request('payments', 'POST', $post_fields);
echo $response->body;
