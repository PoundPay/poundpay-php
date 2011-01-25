<?php
require 'config.php';
require 'poundpay.php';

$poundpay_client = new PoundPayAPIClient($CONFIG['poundpay']['sid'],
                                         $CONFIG['poundpay']['auth_token'],
                                         $CONFIG['poundpay']['api_uri'],
                                         $CONFIG['poundpay']['version']);

$data = array(
  'amount' => 20000, // in USD cents
  'payer_fee_amount' => 0,
  'payer_email_address' => 'fred@example.com',
  'payer_phone_number' => '6505551234',
  'recipient_fee_amount' => 500,
  'recipient_email_address' => 'immanuel@example.com',
  'description' => 'Beats by Dr. Dre (White)',
);

$response = $poundpay_client->request('/payment_requests', 'POST', $data);
$payment_request = $response->response_json;
?>

<html>
  <head>
    <title>Make Payment - Simple Marketplace</title>
    <script type="text/javascript" src="<?= $CONFIG['poundpay']['www_uri'] ?>/js/pmp/pound_pmp.js"></script>
  </head>
  <body>
    <h1>Make Payment</h1>
    <h2><?= $payment_request->description ?></h2>
    <div id="pound-root"></div>
    <script type="text/javascript">
      PoundPayment.init({
        payment_request_sid: "<?= $payment_request->sid ?>",
        cardholder_name: "Fred Nietzsche",
        server: "<?= $CONFIG['poundpay']['www_uri'] ?>",
        success: function() {window.location = '/release.php?payment_request_sid=<?= $payment_request->sid ?>'}
      })
    </script>
  </body>
</html>