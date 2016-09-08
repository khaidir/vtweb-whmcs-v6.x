<?php
/**
 * Veritrans Payment Callback File
 *
 * Call back used to retrieve transaction HTTP notification, then check validity using 
 * GetStatus API Call
 * validating an Invoice ID, checking for the existence of a Transaction ID,
 * Logging the Transaction for debugging and Adding Payment to an Invoice.
 *
 * For more information, please refer to the online documentation.
 * @see http://docs.veritrans.co.id
 *
 * Module developed based on official WHMCS Sample Payment Gateway Module
 * 
 * @author rizda.prasetya@veritrans.co.id & harry.pujianto@veritrans.co.id
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

/**
 * Validate callback authenticity.
 */

// Create veritrans notif object from HTTP POST notif
Veritrans_Config::$isProduction = ($gatewayParams['environment'] == 'on') ? true : false;
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
logTransaction($gatewayParams['name'], print_r($veritrans_notification,true), $transaction_status);


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
    // error_log("=====================".$invoiceId."-".$transactionId."-".$paymentAmount."-".$paymentFee."-".$gatewayModuleName); //debugan
    echo "Payment success notification accepted";

}
else{
    die("Payment failed, denied or pending");
}
