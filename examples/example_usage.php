<?php

require "poundpay.php";

// account information
$account_sid = "your_account_sid";
$auth_token = "your_auth_token";

$client = new PoundPayAPIClient($account_sid, $auth_token);


////////////////////////////////////
//
// CREATING A PAYMENT REQUEST
//
////////////////////////////////////
$data = array(
    /* NOTE: all amounts are in USD cents */

    // total charge is 100 dollars
    "amount" => 10000,

    // charge the payer 2 dollars
    "payer_fee_amount" => 200,

    // charge the recipient nothing
    "recipient_fee_amount" => 0,

    // the payer's email address (usually retrieved from an input form
    // or user credentials stored in the merchant's database)
    "payer_email_address" => "payer@example.com",

    // the recipient's email address (who are we sending the payment to?)
    "recipient_email_address" => "recipient@bigmoney.com",

    // simple description about the payment
    "description" => "Sold The Startupstar for 100 dollars! Big money!!",
);


$response = $client->request("/payment_requests/", "POST", $data);

if($response->is_error) {
    echo "Received an error ({$response->error_name}) when creating a payment_request\n";
    echo "Error Message: {$response->error_msg}\n";
}
else {
    echo "Payment Request SID: {$response->response_json['sid']}\n";
    echo "JSON Response: \n";
    echo $response->response_json;
    echo "\n";
}


////////////////////////////////////
//
// LISTING PAYMENT REQUESTS
//
///////////////////////////////////

$response = $client->request("/payment_requests");
if($response->is_error) {
    echo "Received an error ({$response->error_name}) when listing a payment_request\n";
    echo "Error Message: {$response->error_msg}\n";
}
else {
    echo "Payments: \n";

?>

<html>
  <head>
    <title>List of Payment Requests</title>
  </head>
  <body>
    <h1>List of Payment Requests</h1>
    <?php foreach ($response->response_json as $p): ?>
    <table>
      <?php foreach ($p as $key => $value): ?>
      <tr>
        <td><?php $key ?></td>
        <td><?php $value ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endforeach; ?>
  </body>
</html>

<?php
}
?>