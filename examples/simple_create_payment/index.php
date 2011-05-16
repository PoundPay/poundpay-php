<?php
require_once('dev_info.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
  <head>
    
  <title>Example Payment</title>
  <script src="<?php echo $webRoot ?>/js/poundpay.js?<?php echo rand() ?>"></script>
  <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
  <style>
  body {
    font-family: Arial, sans-serif;
    font-size: 12px;
  }
  
  input {
    font-size: 12px;
    width: 300px;
  }
  
  td {
    padding: 3px;
  }

  </style>
  </head>
  <body>
    <h1>Create payment</h1>
    <table id="paymentsTable">
      <tr>
        <td>Payment Amount</td>
        <td><input type="text" class="text" id="amount" value="12311"></td>
      </tr>
      <tr>
        <td>Payer Fee</td>
        <td><input type="text" class="text" id="payer_fee_amount" value="123"></td>
      </tr>
      <tr>
        <td>Recipient Fee</td>
        <td><input type="text" class="text" id="recipient_fee_amount" value="123"></td>
      </tr>
      <tr>
        <td>Payer Email</td>
        <td><input type="text" class="text" id="payer_email_address" value="glenndixon@gmail.com"></td>
      </tr>
      <tr>
        <td>Recipient Email</td>
        <td><input type="text" class="text" id="recipient_email_address" value="dixon.g@gmail.com"></td>
      </tr>
      <tr>
        <td>Description</td>
        <td><input type="text" class="text" id="description" value="this is a sample description"></td>
      </tr>
      <tr>
        <td>Developer identifier</td>
        <td><input type="text" class="text" id="developer_identifier" value=""></td>
      </tr>
    </table>
    <a href="javascript:;" onclick="createPayment();">Create Payment</a>
    <h1>Display iframe</h1>
    <table>
      <tr>
        <td>Payment request id</td>
        <td><input type="text" class="text" id="payment_id"></td>
      </tr>
      <tr>
        <td>Card holder name</td>
        <td><input type="text" class="text" id="cardholder_name"></td>
      </tr>
      <tr>
        <td>server</td>
        <td><input type="text" class="text" id="server" value="<?php echo $webRoot ?>"></td>
      </tr>
    </table>
    <a href="javascript:;" onclick="startIFrame();">Start Payment IFrame</a>
    &nbsp;
    <a href="javascript:;" onclick="launchLightbox();">Launch Lightbox</a>
    <div id="pound-root"></div>
    <h1 id="paymentComplete" style="display:none;color:green;">
      Payment Complete
    </h1>
  </body>
</html>

<script type="text/javascript">

function createPayment () {
  var args = {};
  var inputs = $('#paymentsTable input').each(function(i, item) {
    args[item.id] = item.value;
  });
  var request = {};
  request.url = "createpayment.php";
  request.type = "POST";
  request.data = $.param(args);
  request.success = function(data) {
    $('#payment_id').val(data.sid);
  };
  $.ajax(request);
}

function startIFrame() {
  // invoke Pound iframe
  var args = {
    success: paymentSuccessCallback,
    error: paymentErrorCallback,
    payment_sid: $('#payment_id').val(),
    server: $('#server').val(),
    name: $('#cardholder_name').val(),
    address_street: '',
    address_city: '',
    address_state: '',
    address_zip: ''
  };
  PoundPay.init(args);
}

function paymentSuccessCallback() {
  $("#pound-root").hide();
  $('#paymentComplete').show();
}

function paymentErrorCallback() {
  $("#pound-root").hide();
  alert("an error occurred");
}
</script>