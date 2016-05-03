<?php
/**
 * WHMCS Sample Payment Gateway Module
 *
 * Payment Gateway modules allow you to integrate payment solutions with the
 * WHMCS platform.
 *
 * This sample file demonstrates how a payment gateway module for WHMCS should
 * be structured and all supported functionality it can contain.
 *
 * Within the module itself, all functions must be prefixed with the module
 * filename, followed by an underscore, and then the function name. For this
 * example file, the filename is "gatewaymodule" and therefore all functions
 * begin "gatewaymodule_".
 *
 * If your module or third party API does not support a given function, you
 * should not define that function within your module. Only the _config
 * function is required.
 *
 * For more information, please refer to the online documentation.
 *
 * @see http://docs.whmcs.com/Gateway_Module_Developer_Docs
 *
 * @copyright Copyright (c) WHMCS Limited 2015
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once(dirname(__FILE__) . '/veritrans-lib/Veritrans.php');

/**
 * Define module related meta data.
 *
 * Values returned here are used to determine module related capabilities and
 * settings.
 *
 * @see http://docs.whmcs.com/Gateway_Module_Meta_Data_Parameters
 *
 * @return array
 */
function veritrans_MetaData()
{
    return array(
        'DisplayName' => 'Veritrans Payment Gateway Module',
        'APIVersion' => '1.1', // Use API Version 1.1
        'DisableLocalCredtCardInput' => true,
        'TokenisedStorage' => true,
    );
}

/**
 * Define gateway configuration options.
 *
 * The fields you define here determine the configuration options that are
 * presented to administrator users when activating and configuring your
 * payment gateway module for use.
 *
 * Supported field types include:
 * * text
 * * password
 * * yesno
 * * dropdown
 * * radio
 * * textarea
 *
 * Examples of each field type and their possible configuration parameters are
 * provided in the sample function below.
 *
 * @return array
 */
function veritrans_config()
{
    return array(
        // the friendly display name for a payment gateway should be
        // defined here for backwards compatibility
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Credit Card & other(by Veritrans)',
        ),
        // a text field type allows for single line text input
        'clientkey' => array(
            'FriendlyName' => 'Veritrans Client Key',
            'Type' => 'text',
            'Size' => '50',
            'Default' => '',
            'Description' => 'Input your Client Server Key. Get it at my.veritrans.co.id',
        ),
        // a text field type allows for single line text input
        'serverkey' => array(
            'FriendlyName' => 'Veritrans Server Key',
            'Type' => 'text',
            'Size' => '50',
            'Default' => '',
            'Description' => 'Input your Veritrans Server Key. Get it at my.veritrans.co.id',
        ),
        // the dropdown field type renders a select menu of options
        'environment' => array(
            'FriendlyName' => 'Environment Mode',
            'Type' => 'dropdown',
            'Options' => array(
                'sandbox' => 'Sandbox',
                'production' => 'Production',
            ),
            'Description' => 'Select the Veritrans Environment, sandbox is for testing transaction',
        ),
        // the yesno field type displays a single checkbox option
        'enable3ds' => array(
            'FriendlyName' => 'Credit Card 3DS',
            'Type' => 'yesno',
            'Description' => 'Tick to enable 3DS for Credit Card payment',
        ),
    );
}

/**
 * Payment link.
 *
 * Required by third party payment gateway modules only.
 *
 * Defines the HTML output displayed on an invoice. Typically consists of an
 * HTML form that will take the user to the payment gateway endpoint.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @see http://docs.whmcs.com/Payment_Gateway_Module_Parameters
 *
 * @return string
 */
function veritrans_link($params)
{
    // Gateway Configuration Parameters
    $clientkey = $params['clientkey'];
    $serverkey = $params['serverkey'];
    $environment = $params['environment'];
    $enable3ds = $params['enable3ds'];

    // Invoice Parameters
    $invoiceId = $params['invoiceid'];
    $description = $params["description"];
    $amount = $params['amount'];
    $currencyCode = $params['currency'];

    // Client Parameters
    $firstname = $params['clientdetails']['firstname'];
    $lastname = $params['clientdetails']['lastname'];
    $email = $params['clientdetails']['email'];
    $address1 = $params['clientdetails']['address1'];
    $address2 = $params['clientdetails']['address2'];
    $city = $params['clientdetails']['city'];
    $state = $params['clientdetails']['state'];
    $postcode = $params['clientdetails']['postcode'];
    $country = $params['clientdetails']['country'];
    $phone = $params['clientdetails']['phonenumber'];

    // System Parameters
    $companyName = $params['companyname'];
    $systemUrl = $params['systemurl'];
    $returnUrl = $params['returnurl'];
    $langPayNow = $params['langpaynow'];
    $moduleDisplayName = $params['name'];
    $moduleName = $params['paymentmethod'];
    $whmcsVersion = $params['whmcsVersion'];

    ## TODO: Build Param
    // Set VT config
    Veritrans_Config::$isProduction = ($environment == 'production') ? true : false;
    Veritrans_Config::$serverKey = $serverkey;
    // error_log($enable3ds); //debugan
    Veritrans_Config::$is3ds = ($enable3ds == 'on') ? true : false;
    Veritrans_Config::$isSanitized = true;
    
    // Build basic param
    $params = array(
        'vtweb' => array(
            "finish_redirect_url" => $returnUrl,
            "unfinish_redirect_url" => $returnUrl,
            "error_redirect_url" => $returnUrl
            ),
        'transaction_details' => array(
            'order_id' => $invoiceId,
            'gross_amount' => ceil($amount),
      )
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
    // $billing_address['country_code'] = (strlen($this->convert_country_code($order->billing_country) != 3 ) ? 'IDN' : $this->convert_country_code($order->billing_country) );
    // error_log("===== country :".$country); //debugan
    $billing_address['country_code'] = (strlen($country) != 3 ) ? 'IDN' : $country;
    
    // Insert array to param
    $customer_details['billing_address'] = $billing_address;
    $params['customer_details'] = $customer_details;

    // build item details
    $item1 = array(
        'id' => 'a1',
        'price' => ceil($amount),
        'quantity' => 1,
        'name' => $description
    );
    $item_details = array ($item1);

    // Insert array to param
    $params['item_details'] = $item_details;
    // error_log("===== params :"); //debugan
    // error_log(print_r($params,true)); //debugan

    ## TODO: Get redirection URL
    try {
        $url = Veritrans_VtWeb::getRedirectionUrl($params);
    } catch (Exception $e) {
        // echo 'Caught exception: ',  $e->getMessage(), "\n";
        error_log('Caught exception: '.$e->getMessage()."\n");
    }

    // ## Unused POST method
    // $postfields = array();
    // $postfields['username'] = $username;
    // $postfields['invoice_id'] = $invoiceId;
    // $postfields['description'] = $description;
    // $postfields['amount'] = $amount;
    // $postfields['currency'] = $currencyCode;
    // $postfields['first_name'] = $firstname;
    // $postfields['last_name'] = $lastname;
    // $postfields['email'] = $email;
    // $postfields['address1'] = $address1;
    // $postfields['address2'] = $address2;
    // $postfields['city'] = $city;
    // $postfields['state'] = $state;
    // $postfields['postcode'] = $postcode;
    // $postfields['country'] = $country;
    // $postfields['phone'] = $phone;
    // $postfields['callback_url'] = $systemUrl . '/modules/gateways/callback/' . $moduleName . '.php';
    // $postfields['return_url'] = $returnUrl;
    // ## Unused Post Method

    $htmlOutput = '<form method="get" action="' . $url . '">';
    foreach ($postfields as $k => $v) {
        $htmlOutput .= '<input type="hidden" name="' . $k . '" value="' . urlencode($v) . '" />';
    }
    $htmlOutput .= '<input type="submit" value="' . $langPayNow . '" />';
    $htmlOutput .= '</form>';

    // ## debugan
    $htmlOutput1 = '';
    // $htmlOutput .=  '<form method="post" action="google.com">';
    $htmlOutput1 .=      '<div><h1> Custom Message </h1></div>';
    $htmlOutput1 .=      '<script> document.getElementById("frmPayment").setAttribute("id", "frmPayment-out"); </script>';  // disable form auto submit
    // $htmlOutput .=  '</form>';
    //  ## end of debugan
    
    return $htmlOutput;
}

/**
 * Refund transaction.
 *
 * Called when a refund is requested for a previously successful transaction.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @see http://docs.whmcs.com/Payment_Gateway_Module_Parameters
 *
 * @return array Transaction response status
 */

/** ## Method not supported on Veritrans
function veritrans_refund($params)
{
    // Gateway Configuration Parameters
    $accountId = $params['accountID'];
    $secretKey = $params['secretKey'];
    $testMode = $params['testMode'];
    $dropdownField = $params['dropdownField'];
    $radioField = $params['radioField'];
    $textareaField = $params['textareaField'];

    // Transaction Parameters
    $transactionIdToRefund = $params['transid'];
    $refundAmount = $params['amount'];
    $currencyCode = $params['currency'];

    // Client Parameters
    $firstname = $params['clientdetails']['firstname'];
    $lastname = $params['clientdetails']['lastname'];
    $email = $params['clientdetails']['email'];
    $address1 = $params['clientdetails']['address1'];
    $address2 = $params['clientdetails']['address2'];
    $city = $params['clientdetails']['city'];
    $state = $params['clientdetails']['state'];
    $postcode = $params['clientdetails']['postcode'];
    $country = $params['clientdetails']['country'];
    $phone = $params['clientdetails']['phonenumber'];

    // System Parameters
    $companyName = $params['companyname'];
    $systemUrl = $params['systemurl'];
    $langPayNow = $params['langpaynow'];
    $moduleDisplayName = $params['name'];
    $moduleName = $params['paymentmethod'];
    $whmcsVersion = $params['whmcsVersion'];

    // perform API call to initiate refund and interpret result

    return array(
        // 'success' if successful, otherwise 'declined', 'error' for failure
        'status' => 'success',
        // Data to be recorded in the gateway log - can be a string or array
        'rawdata' => $responseData,
        // Unique Transaction ID for the refund transaction
        'transid' => $refundTransactionId,
        // Optional fee amount for the fee value refunded
        'fees' => $feeAmount,
    );
}
*/

/**
 * Cancel subscription.
 *
 * If the payment gateway creates subscriptions and stores the subscription
 * ID in tblhosting.subscriptionid, this function is called upon cancellation
 * or request by an admin user.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @see http://docs.whmcs.com/Payment_Gateway_Module_Parameters
 *
 * @return array Transaction response status
 */

/** ## Method 
function veritrans_cancelSubscription($params)
{
    // Gateway Configuration Parameters
    $accountId = $params['accountID'];
    $secretKey = $params['secretKey'];
    $testMode = $params['testMode'];
    $dropdownField = $params['dropdownField'];
    $radioField = $params['radioField'];
    $textareaField = $params['textareaField'];

    // Subscription Parameters
    $subscriptionIdToCancel = $params['subscriptionID'];

    // System Parameters
    $companyName = $params['companyname'];
    $systemUrl = $params['systemurl'];
    $langPayNow = $params['langpaynow'];
    $moduleDisplayName = $params['name'];
    $moduleName = $params['paymentmethod'];
    $whmcsVersion = $params['whmcsVersion'];

    // perform API call to cancel subscription and interpret result

    return array(
        // 'success' if successful, any other value for failure
        'status' => 'success',
        // Data to be recorded in the gateway log - can be a string or array
        'rawdata' => $responseData,
    );
}
*/