<?php

require 'config.php';
require dirname(__FILE__).'/../../poundpay.php';

PoundPay\configure($CONFIG['poundpay']['sid'],
                   $CONFIG['poundpay']['auth_token'],
                   $CONFIG['poundpay']['api_uri'],
                   $CONFIG['poundpay']['version']);

$payment = new PoundPay\Payment(array(
  'amount' => 20000, // in USD cents
  'payer_fee_amount' => 0,
  'recipient_fee_amount' => 500,
  'payer_email_address' => 'fred@example.com',
  'recipient_email_address' => 'immanuel@example.com',
  'description' => 'Beats by Dr. Dre (White)',
));
$payment->save();
?>

<html>
  <head>
    <title>Make Payment - Simple Marketplace</title>
    <script type="text/javascript" src="<?php echo $CONFIG['poundpay']['www_uri'] ?>/js/poundpay.js"></script>
  </head>
  <body>
    <h1>Make Payment</h1>
    <h2><?php echo $payment->description ?></h2>
    <div id="pound-root"></div>
    <script type="text/javascript">
      PoundPay.init({
        payment_sid: "<?php echo $payment->sid ?>",
        cardholder_name: "Fred Nietzsche",  // Optional
        phone_number: "6505551234",  // Optional
        server: "<?php echo $CONFIG['poundpay']['www_uri'] ?>",
        success: function() {window.location = '/release.php?payment_sid=<?php echo $payment->sid ?>'}
      })
    </script>
    <h2>PoundPay Response for PaymentRequest</h2>
    <pre><?php echo PoundPay\getLastResponse()->body ?></pre>
  </body>
</html>
