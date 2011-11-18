<?php

$CONFIG = array(
  'poundpay' => array(
    'sid' => 'DVxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',    
    'auth_token' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
    'api_url' => 'https://api-sandbox.poundpay.com',    
    'version' => 'silver',
    'callback_url' => 'http://mycompany.com/poundpay_callback.php',
  ),
  'www_url' => 'https://www-sandbox.poundpay.com',
  'payment' => array(
    'amount' => 20000, // in USD cents
    'payer_fee_amount' => 0,
    'recipient_fee_amount' => 500,
    'payer_email_address' => 'fred@example.com',
    'recipient_email_address' => 'immanuel@example.com',
    'description' => 'Beats by Dr. Dre (White)',
    )
);

?>