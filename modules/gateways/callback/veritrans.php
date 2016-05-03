<?php
/**
 * WHMCS Sample Payment Callback File
 *
 * This sample file demonstrates how a payment gateway callback should be
 * handled within WHMCS.
 *
 * It demonstrates verifying that the payment gateway module is active,
 * validating an Invoice ID, checking for the existence of a Transaction ID,
 * Logging the Transaction for debugging and Adding Payment to an Invoice.
 *
 * For more information, please refer to the online documentation.
 *
 * @see http://docs.whmcs.com/Gateway_Module_Developer_Docs
 *
 * @copyright Copyright (c) WHMCS Limited 2015
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */

// Require libraries needed for gateway module functions.
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

// Require Veritrans Library
require_once __DIR__ . '/../veritrans-lib/Veritrans.php';

// Detect module name from filename.
$gatewayModuleName = basename(__FILE__, '.php');

// Fetch gateway configuration parameters.
$gatewayParams = getGatewayVariables($gatewayModuleName);

// Die if module is not active.
if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

// Retrieve data returned in payment gateway callback
// Varies per payment gateway
// $success = $_POST["x_status"];
// $invoiceId = $_POST["x_invoice_id"];
// $transactionId = $_POST["x_trans_id"];
// $paymentAmount = $_POST["x_amount"];
// $paymentFee = $_POST["x_fee"];
// $hash = $_POST["x_hash"];

/**
 * Validate callback authenticity.
 *
 * Most payment gateways provide a method of verifying that a callback
 * originated from them. In the case of our example here, this is achieved by
 * way of a shared secret which is used to build and compare a hash.
 */

// Create veritrans notif object from HTTP POST notif
Veritrans_Config::$isProduction = ($gatewayParams['environment'] == 'production') ? true : false;
Veritrans_Config::$serverKey = $gatewayParams['serverkey'];
$veritrans_notification = new Veritrans_Notification();

$transaction_status = $veritrans_notification->transaction_status;
$order_id = $veritrans_notification->order_id;
$invoiceId = $order_id;
$payment_type = $veritrans_notification->payment_type;
$paymentAmount = $veritrans_notification->gross_amount;
$transactionId = $veritrans_notification->transaction_id;
$paymentFee = 0;

/**
 * Validate Callback Invoice ID.
 *
 * Checks invoice ID is a valid invoice number. Note it will count an
 * invoice in any status as valid.
 *
 * Performs a die upon encountering an invalid Invoice ID.
 *
 * Returns a normalised invoice ID.
 */
$invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);

/**
 * Check Callback Transaction ID.
 *
 * Performs a check for any existing transactions with the same given
 * transaction number.
 *
 * Performs a die upon encountering a duplicate.
 */
// checkCbTransID($transactionId); // No need to check, because Veritrans notification can be send more than once per transactionid.

/**
 * Log Transaction.
 *
 * Add an entry to the Gateway Log for debugging purposes.
 *
 * The debug data can be a string or an array. In the case of an
 * array it will be
 *
 * @param string $gatewayName        Display label
 * @param string|array $debugData    Data to log
 * @param string $transactionStatus  Status
 */
logTransaction($gatewayParams['name'], $_POST, $transaction_status);


// trx status handler
$success = false;
if ($veritrans_notification->transaction_status == 'capture') {
  if ($veritrans_notification->fraud_status == 'accept') {
    checkCbTransID($transactionId);
    $success = true;
  }
  else if ($veritrans_notification->fraud_status == 'challenge') {
    $success = false;
  }
}
else if ($veritrans_notification->transaction_status == 'cancel') {
  $success = false;
}
else if ($veritrans_notification->transaction_status == 'deny') {
  $success = false;
}
else if ($veritrans_notification->transaction_status == 'settlement') {
  if($veritrans_notification->payment_type == 'credit_card'){
    die("Credit Card Settlement Notification Received");
  }
  else{
    checkCbTransID($transactionId);
    $success = true;
  }
}
else if ($veritrans_notification->transaction_status == 'pending') {
  $success = false;
}

if ($success) {

    /**
     * Add Invoice Payment.
     *
     * Applies a payment transaction entry to the given invoice ID.
     *
     * @param int $invoiceId         Invoice ID
     * @param string $transactionId  Transaction ID
     * @param float $paymentAmount   Amount paid (defaults to full balance)
     * @param float $paymentFee      Payment fee (optional)
     * @param string $gatewayModule  Gateway module name
     */
    addInvoicePayment(
        $invoiceId,
        $transactionId,
        $paymentAmount,
        $paymentFee,
        $gatewayModuleName
    );

    echo "Payment success notification accepted";

}
else{
    die("Payment failed, denied or pending");
}
