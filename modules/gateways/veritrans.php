<?php
// TODO snap still malfunctioned because charged more than one time, lead to error order ID has been utilized

/**
 * WHMCS Veritrans VTWeb Payment Gateway Module
 *
 * Veritrans VTWeb Payment Gateway modules allow you to integrate Veritrans VTWeb with the
 * WHMCS platform.
 *
 * For more information, please refer to the online documentation.
 * @see http://docs.veritrans.co.id
 *
 * Module developed based on official WHMCS Sample Payment Gateway Module
 * 
 * @author rizda.prasetya@veritrans.co.id & harry.pujianto@veritrans.co.id
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
            'Value' => 'Veritrans',
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
            'FriendlyName' => 'Production Mode',
            'Type' => 'yesno',
            'Description' => 'Tick to allow real transaction, untick for testing transaction in sandbox mode',
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

    // Set VT config
    Veritrans_Config::$isProduction = ($environment == 'on') ? true : false;
    Veritrans_Config::$serverKey = $serverkey;
    // error_log($enable3ds); //debugan
    Veritrans_Config::$is3ds = ($enable3ds == 'on') ? true : false;
    Veritrans_Config::$isSanitized = true;

    // Build basic param
    $params = array(
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

    // build item details, there's only one item we can get from the WHMCS
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

    // Get redirection URL
    try {
        $snapToken = Veritrans_Snap::getSnapToken($params);
        // $url = Veritrans_VtWeb::getRedirectionUrl($params);
        error_log(" ############# TOKEN ::: ".$snapToken);
    } catch (Exception $e) {
        // echo 'Caught exception: ',  $e->getMessage(), "\n";
        error_log(' ############# Caught exception: '.$e->getMessage()."\n");
    }



    // ====================================== Html output for VT Web =======================
    $htmlOutput = '<form method="get" action="' . $url . '">';
    foreach ($postfields as $k => $v) {
        $htmlOutput .= '<input type="hidden" name="' . $k . '" value="' . urlencode($v) . '" />';
    }
    $htmlOutput .= '<input type="submit" value="' . $langPayNow . '" />';
    $htmlOutput .= '</form>';
    // =============================================== End of VT Web =======================
    


    // ====================================== Html output for SNAP ====================
    
    $htmlOutput1 = '';
    // JS script
    
    // $htmlOutput1 .='
    // <script> 
    // try {
    //     document.getElementById("frmPayment").setAttribute("id", "frmPayment-out"); 
    // } catch (e){
    //     console.log("failed to stop auto timer for WHMCS 6");
    // }
    // try {
    //     document.getElementById("submitfrm").setAttribute("id", "submitfrm-out"); 
    // } catch (e){
    //     console.log("failed to stop auto timer for WHMCS 5");
    // }
    // </script>
    // ';  // disable form auto submit
    // $htmlOutput1 .='
    // <script>
    // try {
    //     $(\'[class*="alert alert-info text-center"]\').text("Please Complete Your Credit Card Payment :");
    // } catch (e){
    //     console.log("failed to change text for WHMCS 6");
    // }
    // try {
    //     $(\'[class*="alert alert-block alert-warn textcenter"]\').text("Please Complete Your Credit Card Payment :");
    // } catch (e){
    //     console.log("failed to change text for WHMCS 5");
    // }
    // $(\'[alt*="Loading"]\').hide();
    // </script>
    // ';  // disable form auto submit


    $enable3dsval = Veritrans_Config::$is3ds ? "true" : "false";
    $amount = ceil($amount);
    $environmenturl = Veritrans_Config::$isProduction ? "https://app.veritrans.co.id/snap/snap.js" : "https://app.sandbox.veritrans.co.id/snap/snap.js";;

    $htmlOutput1 .=  '
        <button class="submit-button" id="snap-pay">Proceed To Payment</button>
        
        <script src="'.$environmenturl.'"></script>
        <script type="text/javascript">
            document.getElementById("snap-pay").onclick = function(){
                snap.pay("'.$snapToken.'", {
                    onSuccess:{
                        window.location = "'.$returnUrl.'";
                    },
                    onPending:{
                        window.location = "'.$returnUrl.'";
                    },
                    onError:{
                        window.location = "'.$returnUrl.'";
                    }
                });
            };
        </script>

    ';

    $htmlOutput1 .= '';
    
    return $htmlOutput1;

    // ====================== RAZORPAY ========================

    return '        <form action="/purchase" method="POST">
        <!-- Note that the amount is in paise = 50 INR -->
        <script
            src="https://checkout.razorpay.com/v1/checkout.js"
            data-key="rzp_test_HwNnvzNEKaiG6h"
            data-amount="5000"
            data-buttontext="Pay with Razorpay"
            data-name="<script>alert()</script>"
            data-description="Purchase Description"
            data-image= "http://requestb.in/1gcxk1o1"
            data-prefill.name="Harshil Mathur"
            data-prefill.email="<script>raizer@yopmail.com"
            data-theme.color="#F37254"
        ></script>
        <input type="hidden" value="Hidden Element" name="hidden">
        </form>';

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
