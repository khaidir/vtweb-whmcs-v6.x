<?php

// Require libraries needed for gateway module functions.
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';
require_once(dirname(__FILE__) . '/Veritrans.php');

if(empty($_POST['token_id'])) {
  die('Empty token_id!');
}

// get gateway params
$gatewayParams = getGatewayVariables('veritrans');

// Set veritrans config
Veritrans_Config::$isProduction = ($gatewayParams['environment'] == 'production') ? true : false;
Veritrans_Config::$serverKey = $gatewayParams['serverkey'];
Veritrans_Config::$is3ds = ($params['enable3ds'] == 'on') ? true : false;
Veritrans_Config::$isSanitized = true;

// populate cust data
$firstname = urldecode($_POST['first_name']);
$lastname = urldecode($_POST['last_name']);
$email = urldecode($_POST['email']);
$address1 = urldecode($_POST['address1']);
$address2 = urldecode($_POST['address2']);
$city = urldecode($_POST['city']);
$state = urldecode($_POST['state']);
$postcode = urldecode($_POST['postcode']);
$country = urldecode($_POST['country']);
$phone = urldecode($_POST['phone']);

// Build trx details
$transaction_details = array(
  'order_id'    => $_POST['order_id'],
  'gross_amount'  => ceil($_POST['gross_amount'])
);

// Build customer details param
$customer_details = array();
$customer_details['first_name'] = $firstname;
$customer_details['last_name'] = $lastname;
$customer_details['email'] = $email;
$customer_details['phone'] = $phone;

$billing_address = array();
$billing_address['first_name'] = $firstname;
$billing_address['last_name'] = $lastname;
$billing_address['address'] = $address1.$address2;
$billing_address['city'] = $city;
$billing_address['postal_code'] = $postcode;
$billing_address['phone'] = $phone;
$billing_address['country_code'] = (strlen($country) != 3 ) ? 'IDN' : $country;

// Insert array to param
$customer_details['billing_address'] = $billing_address;

// build item details
$item1 = array(
    'id' => 'a1',
    'price' => ceil($_POST['gross_amount']),
    'quantity' => 1,
    'name' => $_POST['description']
);
$item_details = array ($item1);

// Token ID from checkout page
$token_id = $_POST['token_id'];

// Transaction data to be sent
$params = array(
    'payment_type' => 'credit_card',
    'credit_card'  => array(
      'token_id'      => $token_id,
      // 'save_token_id' => isset($_POST['save_cc'])
    ),
    'transaction_details' => $transaction_details,
    'item_details'        => $items,
    'customer_details'    => $customer_details
  );

error_log(print_r($params,true));
try {
  $response = Veritrans_VtDirect::charge($params);
} catch (Exception $e) {
  echo $e->getMessage();
  die();
}

// Success
if($response->transaction_status == 'capture') {
  sleep(4);
}
// Deny
else if($response->transaction_status == 'deny') {

}
// Challenge
else if($response->transaction_status == 'challenge') {

}
// Error
else {
  echo "<p>Terjadi kesalahan pada data transaksi yang dikirim.</p>";
  echo "<p>Status message: [$response->status_code] " .
      "$response->status_message</p>";

  echo "<pre>";
  var_dump($response);
  echo "</pre>";
  die;
}

// redirect to $_POST['return_url']
header("Location: ".urldecode($_POST['return_url']));
die();