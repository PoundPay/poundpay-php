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
  'recipient_fee_amount' => 500,
  'payer_email_address' => 'fred@example.com',
  'recipient_email_address' => 'immanuel@example.com',
  'description' => 'Beats by Dr. Dre (White)',
);

$response = $poundpay_client->request('/payment', 'POST', $data);
$payment = $response->response_json;
?>

<html>
  <head>
    <title>Make Payment - Simple Marketplace</title>
    <script type="text/javascript" src="<?= $CONFIG['poundpay']['www_uri'] ?>/js/poundpay.js"></script>
  </head>
  <body>
    <h1>Make Payment</h1>
    <h2><?= $payment->description ?></h2>
    <div id="pound-root"></div>
    <script type="text/javascript">
      PoundPay.init({
        payment_sid: "<?= $payment->sid ?>",
        cardholder_name: "Fred Nietzsche", // Optional
        server: "<?= $CONFIG['poundpay']['www_uri'] ?>",
        success: function() {window.location = '/release.php?payment_sid=<?= $payment->sid ?>'}
      })
    </script>
    <h2>PoundPay Response for PaymentRequest</h2>
    <pre><?= $response->response_text ?></pre>
  </body>
</html>