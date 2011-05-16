<?php
$env = 'sandbox';

$envs = array(
  'sandbox' => array(
    "https://api-sandbox.poundpay.com",
    'DV<your developer sid>',
    '<your auth token>',
    "https://www-sandbox.poundpay.com"
  )
);

// auth info
list($endpoint, $developerUsername, $developerPassword, $webRoot) = $envs[$env];