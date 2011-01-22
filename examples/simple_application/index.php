<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
  <head>

  <title>Example Payment</title>
  <script src="http://www-sandbox.poundpay.com/js/pmp/pound_pmp.js"></script>
  <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
  <style>
  body {
    font-family: Arial;
  }

  input {
    font-size: 16px;
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
        <td>Payment Amount (in USD)</td>
        <td><input type="text" class="text" id="amount" ></td>
      </tr>
      <tr>
        <td>Payer Fee (in USD)</td>
        <td><input type="text" class="text" id="payer_fee_amount"></td>
      </tr>
      <tr>
        <td>Recipient Fee in (USD)</td>
        <td><input type="text" class="text" id="recipient_fee_amount"></td>
      </tr>
      <tr>
        <td>Payer Email</td>
        <td><input type="text" class="text" id="payer_email_address"></td>
      </tr>
      <tr>
        <td>Recipient Email</td>
        <td><input type="text" class="text" id="recipient_email_address"></td>
      </tr>
      <tr>
        <td>Description</td>
        <td><input type="text" class="text" id="description"></td>
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
        <td>server</td>
        <td><input type="text" class="text" id="server" value="http://www-sandbox.poundpay.com/"></td>
      </tr>
    </table>
    <a href="javascript:;" onclick="startIFrame();">Start Payment IFrame</a>
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
  request.url = "/create_payment.php";
  request.type = "POST";
  request.data = $.param(args);
  request.success = function(data) {
    $('#payment_id').val(data.sid);
  };
  $.ajax(request);
}

function startIFrame() {
  // invoke Pound iframe
  PoundPayment.init({
    success: paymentSuccessCallback,
    payment_request_sid: $('#payment_id').val(),
    server: $('#server').val()
  });
}

function paymentSuccessCallback() {
  $("#pound-root").hide();
  $('#paymentComplete').show();
}

</script>
