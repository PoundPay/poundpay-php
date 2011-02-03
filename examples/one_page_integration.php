<?php
  require "poundpay.php";

  $account_sid = 'DVxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
  $auth_token = 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';
  $api_uri = 'https://api-sandbox.poundpay.com';
  $www_uri = 'https://www-sandbox.poundpay.com';

  $client = new PoundPayAPIClient($account_sid, $auth_token, $api_uri);
  $data = array(
    "amount" => 20000, // in USD cents
    "payer_fee_amount" => 0,
    "recipient_fee_amount" => 500,
    "recipient_email_address" => "david@example.com",
    "description" => "Beats by Dr. Dre (White)",
  );

  $response = $client->request("/payment_requests/", "POST", $data);
  $payment_request = $response->response_json;
?>

<html>
  <head>
    <title>Simple Marketplace</title>
    <script type="text/javascript" src="<?= $www_uri ?>/js/poundpay.js"></script>
  </head>
  <body>
    <h1>Simple Marketplace</h1>
    <h2><?= $payment_request->description ?></h2>
    <div id="pound-root"></div>
    <script type="text/javascript">
      PoundPay.init({
        payment_request_sid: "<?= $payment_request->sid ?>",
        server: "<?= $www_uri ?>"
      })
    </script>
  </body>
</html>