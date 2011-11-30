<?php

require_once 'config.php';
require_once 'PoundPay/Autoload.php';

PoundPay\Core::configure(
    $CONFIG['poundpay']['sid'],
    $CONFIG['poundpay']['auth_token'],
    $CONFIG['poundpay']['api_url'],
    $CONFIG['poundpay']['version']);

// controllers

function create_user() {
    $user = new PoundPay\User(array(
        'first_name' => $_POST['user_first_name'],
        'last_name' => $_POST['user_last_name'],
        'email_address' => $_POST['user_email_address'],
    ));
    $user = $user->save();

    header('Content-type: application/json');
    echo json_encode($user);
}

function create_charge_permission() {
    $chargePermission = new PoundPay\ChargePermission(array(
        'email_address' => $_POST['email_address'],
    ));
    $chargePermission = $chargePermission->save();

    header('Content-type: application/json');
    echo json_encode($chargePermission);
}

function find_charge_permission() {
    $emailAddress = $_POST['email_address'];
    $chargePermissions = PoundPay\ChargePermission::all(array(
        'email_address' => $emailAddress,
    ));
    if ($chargePermissions == null) {
        header("HTTP/1.1 404 Not Found");
        echo "<h1>404 Not Found</h1>";
        echo "The page that you have requested could not be found.";
        exit();
    }
    header('Content-type: text/plain');
    echo print_r($chargePermissions);
}

function deactivate_charge_permission() {
    $chargePermission = PoundPay\ChargePermission::find($_POST['sid']);
    $chargePermission->state = 'INACTIVE';
    $chargePermission->save();
    header('Content-type: application/json');
    echo json_encode($chargePermission);
}

function charge_permission_callback() {
    // verify request is from PoundPay; otherwise, 404
    $poundpay_verifier = new PoundPay\SignatureVerifier($CONFIG['poundpay']['sid'], $CONFIG['poundpay']['auth_token']);
    if(!$poundpay_verifier->is_authentic_response($_SERVER['HTTP_X_POUNDPAY_SIGNATURE'], $CONFIG['poundpay']['callback_url'], $_POST)) {
      header("HTTP/1.1 404 Not Found");
      exit();
    }

    // store the payment data locally ...
    // store in a tmp file just as an example, but normally, we'd store in a db
    $tmp_file = fopen("/tmp/{$_POST['sid']}", 'w');
    fwrite($tmp_file, json_encode($_POST));
    fclose($tmp_file);
}

function create_payment() {
    $payment = new PoundPay\Payment($_POST);
    $payment->save();
    header('Content-type: application/json');
    echo json_encode($payment);
}

function authorize_payment() {
    php_fix_raw_query();
    if (is_array($_POST['sid'])) {
        $payments = PoundPay\Payment::batch_update(
                         $_POST['sid'],
                        array('status' => 'authorized'));
        header('Content-type: text/plain');
        echo print_r($payments);
    }
    else {
        $payment = PoundPay\Payment::find($_POST['sid']);
        $payment->status = 'authorized';
        $payment->save();
        header('Content-type: text/plain');
        echo print_r($payment);
    }
}

function escrow_payment() {
    php_fix_raw_query();
    if (is_array($_POST['sid'])) {
        $payments = PoundPay\Payment::batch_update(
                        $_POST['sid'],
                        array('status' => 'escrowed'));
        header('Content-type: text/plain');
        echo print_r($payments);
    }
    else {
        $payment = PoundPay\Payment::find($_POST['sid']);
        $payment->status = 'escrowed';
        $payment->save();
        header('Content-type: text/plain');
        echo print_r($payment);
    }
}

function release_payment() {
    $payment = PoundPay\Payment::find($_POST['sid']);
    $payment->status = 'released';
    $payment->save();
    header('Content-type: text/plain');
    echo print_r($payment);
}

function cancel_payment() {
    $payment = PoundPay\Payment::find($_POST['sid']);
    $payment->status = 'canceled';
    $payment->save();
    header('Content-type: text/plain');
    echo print_r($payment);
}

function payment_callback() {
    // verify request is from PoundPay; otherwise, 404
    $poundpay_verifier = new PoundPay\SignatureVerifier($CONFIG['poundpay']['sid'], $CONFIG['poundpay']['auth_token']);
    if(!$poundpay_verifier->is_authentic_response($_SERVER['HTTP_X_POUNDPAY_SIGNATURE'], $CONFIG['poundpay']['callback_url'], $_POST)) {
      header("HTTP/1.1 404 Not Found");
      exit();
    }

    // store the payment data locally ...
    // store in a tmp file just as an example, but normally, we'd store in a db
    $tmp_file = fopen("/tmp/{$_POST['sid']}", 'w');
    fwrite($tmp_file, json_encode($_POST));
    fclose($tmp_file);
}

function show_index() {
    global $CONFIG;

    $payment_details = $CONFIG['payment'];
    $www_url = $CONFIG['www_url'];

?>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="description" content="Simple Marketplace">
    <title>Simple MarketPlace</title>
    <link rel="icon" href="static/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="static/css/simplemp.css">
    <script src="static/js/jquery-1.6.1.js"></script>
    <script src="<?= $www_url ?>/js/poundpay.js"></script>
    <script src="static/js/simplemp.js"></script>
  </head>
  <body>
    <div id="content">
        <h1>Simple Marketplace</h1>
        
        <h2>Create charge permission</h2>
        <table id="chargePermissionTable">
          <tr>
            <td>Email
            <td><input type="text" class="text" id="email_address" value="<?= $payment_details['payer_email_address'] ?>">
          </tr>
        </table>
        <a href="javascript:;" onclick="createChargePermission();">Create Charge Permission</a><br>
        <a href="javascript:;" onclick="findChargePermission();">Find Charge Permission</a><br>
        <pre id="find_charge_permission_results"></pre>
        
        <h2>Display charge permission iframe</h2>
        <table>
          <tr>
            <td>Charge permission id
            <td><input type="text" class="text" id="charge_permission_id">
          </tr>
          <tr>
            <td>Card holder name</td>
            <td><input type="text" class="text" id="charge_permission_cardholder_name">
          </tr>
          <tr>
            <td>server
            <td><input type="text" class="text" id="charge_permission_server" value="<?= $www_url ?>">
          </tr>
        </table>
        <a href="javascript:;" onclick="deactivateChargePermission();">Deactivate Charge Permission</a><br>
        <a href="javascript:;" onclick="startChargePermissionIFrame();">Start Charge Permission IFrame</a><br>
        <div id="pound-pcp"></div>
        
        <h2>Create payment</h2>
        <table id="paymentsTable">
          <tr>
            <td>Payment Amount
            <td><input type="text" class="text" id="amount" value="<?= $payment_details['amount'] ?>">
          </tr>
          <tr>
            <td>Payer Fee</td>
            <td><input type="text" class="text" id="payer_fee_amount" value="<?= $payment_details['payer_fee_amount'] ?>">
          </tr>
          <tr>
            <td>Recipient Fee</td>
            <td><input type="text" class="text" id="recipient_fee_amount" value="<?= $payment_details['recipient_fee_amount'] ?>">
          </tr>
          <tr>
            <td>Payer Email</td>
            <td><input type="text" class="text" id="payer_email_address" value="<?= $payment_details['payer_email_address'] ?>">
          </tr>
          <tr>
            <td>Recipient Email</td>
            <td><input type="text" class="text" id="recipient_email_address" value="<?= $payment_details['recipient_email_address'] ?>">
          </tr>
          <tr>
            <td>Description</td>
            <td><input type="text" class="text" id="description" value="<?= $payment_details['description'] ?>">
          </tr>
        </table>
    
        <a href="javascript:;" onclick="createPayment();">Create Payment</a>
    
        <h2>Display payment iframe</h2>
        <table>
          <tr>
            <td>Payment request id</td>
            <td><input type="text" class="text" id="payment_id">
          </tr>
          <tr>
            <td>Card holder name</td>
            <td><input type="text" class="text" id="cardholder_name">
          </tr>
          <tr>
            <td>server</td>
            <td><input type="text" class="text" id="server" value="<?= $www_url ?>">
          </tr>
        </table>
        <a href="javascript:;" onclick="startIFrame();">Start Payment IFrame</a>
        &nbsp;
        <a href="javascript:;" onclick="launchLightbox();">Launch Lightbox</a>
        <div id="pound-root"></div>
        <h2 id="paymentComplete" style="display:none;color:green;">
          Payment Complete
        </h2>
        <h2>Create User</h2>
        <table id="create_user_table">
          <tr>
            <td>First Name</td>
            <td><input type="text" class="text" id="user_first_name">
          </tr>
          <tr>
            <td>Last Name</td>
            <td><input type="text" class="text" id="user_last_name">
          </tr>
          <tr>
            <td>Email Address</td>
            <td><input type="text" class="text" id="user_email_address">
          </tr>
        </table>
        <a href="javascript:;" onclick="createUser();">Create User</a>
        
        <pre id="created_user_results"></pre>
        
        <h2>Payment Operations</h2>
        <table>
          <tr>
            <td>Payment SID
            <td><input type="text" class="text" id="operating_payment_sid">
          </tr>
        </table>
        <a href="javascript:;" onclick="authorizePayment();">Authorize Payment</a><br>
        <a href="javascript:;" onclick="escrowPayment();">Escrow Payment</a><br>
        <a href="javascript:;" onclick="releasePayment();">Release Payment</a><br>
        <a href="javascript:;" onclick="cancelPayment();">Cancel Payment</a><br>
        
        <pre id="operation_results"></pre>
      </div>
  </body>
</html>
<?
}

// dispatch

$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

try
{
    if ($requestUri == '/' && $requestMethod == 'GET')
        show_index();
    else if ($requestUri == '/user' && $requestMethod == 'POST')
        create_user();
    else if ($requestUri == '/charge_permission' && $requestMethod == 'POST')
        create_charge_permission();
    else if ($requestUri == '/charge_permission/find' && $requestMethod == 'POST')
        find_charge_permission();
    else if ($requestUri == '/charge_permission/deactivate' && $requestMethod == 'POST')
        deactivate_charge_permission();
    else if ($requestUri == '/charge_permission/callback' && $requestMethod == 'POST')
        charge_permission_callback();
    else if ($requestUri == '/payment' && $requestMethod == 'POST')
        create_payment();
    else if ($requestUri == '/payment/authorize' && $requestMethod == 'POST')
        authorize_payment();
    else if ($requestUri == '/payment/escrow' && $requestMethod == 'POST')
        escrow_payment();
    else if ($requestUri == '/payment/release' && $requestMethod == 'POST')
        release_payment();
    else if ($requestUri == '/payment/cancel' && $requestMethod == 'POST')
        cancel_payment();
    else if ($requestUri == '/payment/callback' && $requestMethod == 'POST')
        payment_callback();
    else {
        header("HTTP/1.1 404 Not Found");
        echo "<h1>404 Not Found</h1>";
        echo "The page that you have requested could not be found.";
        exit();
    }
}
catch (Exception $e) {
    header("HTTP/1.1 500 Internal Server Error");
    echo $e->getMessage();
    echo $e->getTraceAsString();
    exit();
}

// helpers

# XXX: http://www.php.net/manual/en/reserved.variables.get.php#92439
function php_fix_raw_query() {
    $post = '';
    
    // Try globals array
    if (!$post && isset($_GLOBALS) && isset($_GLOBALS["HTTP_RAW_POST_DATA"]))
        $post = $_GLOBALS["HTTP_RAW_POST_DATA"];
    
    // Try globals variable
    if (!$post && isset($HTTP_RAW_POST_DATA))
        $post = $HTTP_RAW_POST_DATA;
    
    // Try stream
    if (!$post) {
        if (!function_exists('file_get_contents')) {
            $fp = fopen("php://input", "r");
            if ($fp) {
                $post = '';
                
                while (!feof($fp))
                $post = fread($fp, 1024);
                
                fclose($fp);
            }
        } else {
            $post = "" . file_get_contents("php://input");
        }
    }
    
    $raw = !empty($_SERVER['QUERY_STRING']) ? sprintf('%s&%s', $_SERVER['QUERY_STRING'], $post) : $post;
    
    $arr = array();
    $pairs = explode('&', $raw);
    
    foreach ($pairs as $i) {
        if (!empty($i)) {
            list($name, $value) = explode('=', $i, 2);
            
            if (isset($arr[$name]) ) {
                if (is_array($arr[$name]) ) {
                    $arr[$name][] = $value;
                } else {
                    $arr[$name] = array($arr[$name], $value);
                }
            } else {
                $arr[$name] = $value;
            }
        }
    }
    
    foreach ( $_POST as $key => $value ) {
        if (is_array($arr[$key]) ) {
            $_POST[$key] = $arr[$name];
            $_REQUEST[$key] = $arr[$name];
        }
    }
            
    foreach ( $_GET as $key => $value ) {
        if (is_array($arr[$key]) ) {
            $_GET[$key] = $arr[$name];
            $_REQUEST[$key] = $arr[$name];
        }
    }

    # optionally return result array
    return $arr;
}

?>
