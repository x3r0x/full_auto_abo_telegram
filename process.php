<?php
session_start();
require_once dirname(__FILE__) . '/config.php';
require_once dirname(__FILE__) . '/functions.php';
require_once dirname(__FILE__) . '/paypal.class.php';

$paypalmode = ($PayPalMode=='sandbox') ? '.sandbox' : '';
Logger::info("PAYPAL MODE SET TO ".$paypalmode); // LOGGER

if($_POST) //Post Data received from product list page.
{
    //Mainly we need 4 variables from product page Item Name, Item Price, Item Number and Item Quantity.
    
    //Please Note : People can manipulate hidden field amounts in form,
    //In practical world you must fetch actual price from database using item id. Eg: 
    //$ItemPrice = $mysqli->query("SELECT item_price FROM products WHERE id = Product_Number");

    $ItemName		= $_POST["itemname"]; //Item Name
    $ItemNumber 	= mysqli_real_escape_string($mysqli, $_POST["itemnumber"]); //Item Number
    
    $query = "SELECT * FROM products WHERE item_number = '".$ItemNumber."' ORDER BY id DESC";
    $result = $mysqli->query($query);
    $rowData = $result->fetch_array();
    
    $ItemPrice 	= $rowData["item_price"];
    $days_to_end 	= $rowData["abo_days"];
        
    $ItemDesc 	= mysqli_real_escape_string($mysqli, $_POST["itemdesc"]); //Item description
    if(substr($ItemDesc,0,1) !== "@") {
        $ItemDesc = "@".$ItemDesc;
    }
    
    if(isset($_POST["itemdesc2"])) {
        $ItemDesc2 	= mysqli_real_escape_string($mysqli, $_POST["itemdesc2"]); //Item description
    }
    
    $ItemQty 	= $_POST["itemQty"]; // Item Quantity
    $ItemTotalPrice = ($ItemPrice*$ItemQty); //(Item Price x Quantity = Total) Get total amount of product; 
    
    //Other important variables like tax, shipping cost
    $TotalTaxAmount 	= 0.00;  //Sum of tax for all items in this order. 
    $HandalingCost 		= 0.00;  //Handling cost for this order.
    $InsuranceCost 		= 0.00;  //shipping insurance cost for this order.
    $ShippinDiscount 	= 0.00; //Shipping discount for this order. Specify this as negative number.
    $ShippinCost 		= 0.00; //Although you may change the value later, try to pass in a shipping amount that is reasonably accurate.
    
    //Grand total including all tax, insurance, shipping cost and discount
    $GrandTotal = ($ItemTotalPrice + $TotalTaxAmount + $HandalingCost + $InsuranceCost + $ShippinCost + $ShippinDiscount);
    
    //Parameters for SetExpressCheckout, which will be sent to PayPal
    $padata = 	'&METHOD=SetExpressCheckout'.
                '&RETURNURL='.urlencode($PayPalReturnURL ).
                '&CANCELURL='.urlencode($PayPalCancelURL).
                '&PAYMENTREQUEST_0_PAYMENTACTION='.urlencode("SALE").
                
                '&L_PAYMENTREQUEST_0_NAME0='.urlencode($ItemName).
                '&L_PAYMENTREQUEST_0_NUMBER0='.urlencode($ItemNumber).
                '&L_PAYMENTREQUEST_0_DESC0='.urlencode($ItemDesc).
                '&L_PAYMENTREQUEST_0_AMT0='.urlencode($ItemPrice).
                '&L_PAYMENTREQUEST_0_QTY0='. urlencode($ItemQty).
                
                /* 
                //Additional products (L_PAYMENTREQUEST_0_NAME0 becomes L_PAYMENTREQUEST_0_NAME1 and so on)
                '&L_PAYMENTREQUEST_0_NAME1='.urlencode($ItemName2).
                '&L_PAYMENTREQUEST_0_NUMBER1='.urlencode($ItemNumber2).
                '&L_PAYMENTREQUEST_0_DESC1='.urlencode($ItemDesc2).
                '&L_PAYMENTREQUEST_0_AMT1='.urlencode($ItemPrice2).
                '&L_PAYMENTREQUEST_0_QTY1='. urlencode($ItemQty2).
                */
                
                /* 
                //Override the buyer's shipping address stored on PayPal, The buyer cannot edit the overridden address.
                '&ADDROVERRIDE=1'.
                '&PAYMENTREQUEST_0_SHIPTONAME=J Smith'.
                '&PAYMENTREQUEST_0_SHIPTOSTREET=1 Main St'.
                '&PAYMENTREQUEST_0_SHIPTOCITY=San Jose'.
                '&PAYMENTREQUEST_0_SHIPTOSTATE=CA'.
                '&PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE=US'.
                '&PAYMENTREQUEST_0_SHIPTOZIP=95131'.
                '&PAYMENTREQUEST_0_SHIPTOPHONENUM=408-967-4444'.
                */
                
                '&NOSHIPPING=1'. //set 1 to hide buyer's shipping address, in-case products that do not require shipping
                
                '&PAYMENTREQUEST_0_ITEMAMT='.urlencode($ItemTotalPrice).
                '&PAYMENTREQUEST_0_TAXAMT='.urlencode($TotalTaxAmount).
                '&PAYMENTREQUEST_0_SHIPPINGAMT='.urlencode($ShippinCost).
                '&PAYMENTREQUEST_0_HANDLINGAMT='.urlencode($HandalingCost).
                '&PAYMENTREQUEST_0_SHIPDISCAMT='.urlencode($ShippinDiscount).
                '&PAYMENTREQUEST_0_INSURANCEAMT='.urlencode($InsuranceCost).
                '&PAYMENTREQUEST_0_AMT='.urlencode($GrandTotal).
                '&PAYMENTREQUEST_0_CURRENCYCODE='.urlencode($PayPalCurrencyCode).
                '&LOCALECODE=DE'. //PayPal pages to match the language on your website.
                '&LOGOIMG='.$paypallogo. //site logo
                '&CARTBORDERCOLOR=FFFFFF'. //border color of cart
                '&ALLOWNOTE=1';
                
                ############# set session variable we need later for "DoExpressCheckoutPayment" #######
                $_SESSION['ItemName'] 			=  $ItemName; //Item Name
                $_SESSION['ItemPrice'] 			=  $ItemPrice; //Item Price
                $_SESSION['ItemNumber'] 		=  $ItemNumber; //Item Number
                $_SESSION['ItemDesc'] 			=  $ItemDesc; //Item description
                $_SESSION['ItemDesc2'] 			=  $ItemDesc2; //Item description
                $_SESSION['ItemQty'] 			=  $ItemQty; // Item Quantity
                $_SESSION['ItemTotalPrice'] 	=  $ItemTotalPrice; //total amount of product; 
                $_SESSION['TotalTaxAmount'] 	=  $TotalTaxAmount;  //Sum of tax for all items in this order. 
                $_SESSION['HandalingCost'] 		=  $HandalingCost;  //Handling cost for this order.
                $_SESSION['InsuranceCost'] 		=  $InsuranceCost;  //shipping insurance cost for this order.
                $_SESSION['ShippinDiscount'] 	=  $ShippinDiscount; //Shipping discount for this order. Specify this as negative number.
                $_SESSION['ShippinCost'] 		=  $ShippinCost; //Although you may change the value later, try to pass in a shipping amount that is reasonably accurate.
                $_SESSION['GrandTotal'] 		=  $GrandTotal;
                $_SESSION['days_to_end'] 		=  $days_to_end;


        //We need to execute the "SetExpressCheckOut" method to obtain paypal token
        $paypal= new MyPayPal();
        $httpParsedResponseAr = $paypal->PPHttpPost('SetExpressCheckout', $padata, $PayPalApiUsername, $PayPalApiPassword, $PayPalApiSignature, $PayPalMode);
        
        //Respond according to message we receive from Paypal
        if("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"]))
        {

                //Redirect user to PayPal store with Token received.
                 $paypalurl ='https://www'.$paypalmode.'.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token='.$httpParsedResponseAr["TOKEN"].'';
                header('Location: '.$paypalurl);
             
        }else{
            //Show error message
            echo '<div style="color:red"><b>Error : </b>'.urldecode($httpParsedResponseAr["L_LONGMESSAGE0"]).'</div>';
            echo '<pre>';
            print_r($httpParsedResponseAr);
            echo '</pre>';
        }

}

//Paypal redirects back to this page using ReturnURL, We should receive TOKEN and Payer ID
if(isset($_GET["token"]) && isset($_GET["PayerID"]))
{
    //we will be using these two variables to execute the "DoExpressCheckoutPayment"
    //Note: we haven't received any payment yet.
    
    $token = $_GET["token"];
    $payer_id = $_GET["PayerID"];
    
    //get session variables
    $ItemName 			= $_SESSION['ItemName']; //Item Name
    $ItemPrice 			= $_SESSION['ItemPrice']; //Item Price
    $ItemNumber 		= $_SESSION['ItemNumber']; //Item Number
    $ItemDesc 			= $_SESSION['ItemDesc']; //Item Number
    $ItemDesc2 			= $_SESSION['ItemDesc2']; //Item Number
    $ItemQty 			= $_SESSION['ItemQty']; // Item Quantity
    $ItemTotalPrice 	= $_SESSION['ItemTotalPrice']; //total amount of product; 
    $TotalTaxAmount 	= $_SESSION['TotalTaxAmount'];  //Sum of tax for all items in this order. 
    $HandalingCost 		= $_SESSION['HandalingCost'];  //Handling cost for this order.
    $InsuranceCost 		= $_SESSION['InsuranceCost'];  //shipping insurance cost for this order.
    $ShippinDiscount 	= $_SESSION['ShippinDiscount']; //Shipping discount for this order. Specify this as negative number.
    $ShippinCost 		= $_SESSION['ShippinCost']; //Although you may change the value later, try to pass in a shipping amount that is reasonably accurate.
    $GrandTotal 		= $_SESSION['GrandTotal'];
    $days_to_end		= $_SESSION['days_to_end'];

    $padata = 	'&TOKEN='.urlencode($token).
                '&PAYERID='.urlencode($payer_id).
                '&PAYMENTREQUEST_0_PAYMENTACTION='.urlencode("SALE").
                
                //set item info here, otherwise we won't see product details later	
                '&L_PAYMENTREQUEST_0_NAME0='.urlencode($ItemName).
                '&L_PAYMENTREQUEST_0_NUMBER0='.urlencode($ItemNumber).
                '&L_PAYMENTREQUEST_0_DESC0='.urlencode($ItemDesc).
                '&L_PAYMENTREQUEST_0_AMT0='.urlencode($ItemPrice).
                '&L_PAYMENTREQUEST_0_QTY0='. urlencode($ItemQty).

                /* 
                //Additional products (L_PAYMENTREQUEST_0_NAME0 becomes L_PAYMENTREQUEST_0_NAME1 and so on)
                '&L_PAYMENTREQUEST_0_NAME1='.urlencode($ItemName2).
                '&L_PAYMENTREQUEST_0_NUMBER1='.urlencode($ItemNumber2).
                '&L_PAYMENTREQUEST_0_DESC1=Description text'.
                '&L_PAYMENTREQUEST_0_AMT1='.urlencode($ItemPrice2).
                '&L_PAYMENTREQUEST_0_QTY1='. urlencode($ItemQty2).
                */

                '&PAYMENTREQUEST_0_ITEMAMT='.urlencode($ItemTotalPrice).
                '&PAYMENTREQUEST_0_TAXAMT='.urlencode($TotalTaxAmount).
                '&PAYMENTREQUEST_0_SHIPPINGAMT='.urlencode($ShippinCost).
                '&PAYMENTREQUEST_0_HANDLINGAMT='.urlencode($HandalingCost).
                '&PAYMENTREQUEST_0_SHIPDISCAMT='.urlencode($ShippinDiscount).
                '&PAYMENTREQUEST_0_INSURANCEAMT='.urlencode($InsuranceCost).
                '&PAYMENTREQUEST_0_AMT='.urlencode($GrandTotal).
                '&PAYMENTREQUEST_0_CURRENCYCODE='.urlencode($PayPalCurrencyCode);
    
    //We need to execute the "DoExpressCheckoutPayment" at this point to Receive payment from user.
    $paypal= new MyPayPal();
    $httpParsedResponseAr = $paypal->PPHttpPost('DoExpressCheckoutPayment', $padata, $PayPalApiUsername, $PayPalApiPassword, $PayPalApiSignature, $PayPalMode);
    
    //Check if everything went ok..
    if("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"])) 
    {

            if($mailSend == '1') { $output_message = "<br><b>Schau in deinem Email Postfach nach...<b>"; }
            
            $TansID = urldecode($httpParsedResponseAr["PAYMENTINFO_0_TRANSACTIONID"]);
            
            echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">';
            echo '<h2>Zahlung erfolgreich!</h2>';
            echo 'Deine Transaction ID : '.urldecode($httpParsedResponseAr["PAYMENTINFO_0_TRANSACTIONID"]);
            echo '<div style="color:green"><br>Vielen Dank, du hast einen Link zu den ausgew&auml;hlten Kan&auml;len soeben erhalten!'.$output_message.'</div>';
            
                /*
                //Sometimes Payment are kept pending even when transaction is complete. 
                //hence we need to notify user about it and ask him manually approve the transiction
                */
                
                if('Completed' == $httpParsedResponseAr["PAYMENTINFO_0_PAYMENTSTATUS"])
                {
                    //echo '<div style="color:green"><br>Zahlung erfolgreich! Du bist dem Kanal soeben beigetreten!</div>';
                    $nix = '';
                }
                elseif('Pending' == $httpParsedResponseAr["PAYMENTINFO_0_PAYMENTSTATUS"])
                {
                    echo '<div style="color:red">Transaction Complete, but payment is still pending! '.
                    'You need to manually authorize this payment in your <a target="_new" href="http://www.paypal.com">Paypal Account</a></div>';
                    Logger::warn("AN NEWER PAYPAL ACC, PENDING..."); // LOGGER
                }

                // we can retrive transection details using either GetTransactionDetails or GetExpressCheckoutDetails
                // GetTransactionDetails requires a Transaction ID, and GetExpressCheckoutDetails requires Token returned by SetExpressCheckOut
                $padata = 	'&TOKEN='.urlencode($token);
                $paypal= new MyPayPal();
                $httpParsedResponseAr = $paypal->PPHttpPost('GetExpressCheckoutDetails', $padata, $PayPalApiUsername, $PayPalApiPassword, $PayPalApiSignature, $PayPalMode);

                if("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"])) 
                {
                    
                    Logger::info("SUCESS PAYMENT"); // LOGGER
                    #### SAVE BUYER INFORMATION IN DATABASE ###
                    
                    $buyerName = $httpParsedResponseAr["FIRSTNAME"].' '.$httpParsedResponseAr["LASTNAME"];
                    $buyerEmail = $httpParsedResponseAr["EMAIL"];
                                        
                    $buyName = urldecode($buyerName);
                    $buyEmail= urldecode($buyerEmail);
                    
                    function generateRandomString($length = 10) {
                        //return substr(str_shuffle(str_repeat(implode('', range('!','z')), $length)), 0, $length);
                        return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
                    } $passwd = generateRandomString(8);
                    
                    // NEW USER OR UPDATE
                    $check = $mysqli->query("SELECT * FROM ".$tbl." WHERE TelegramUser = '".$ItemDesc."' ");
                    $row_cnt = $check->num_rows;
    
                    if($row_cnt != 0) {
                        $update = $check->fetch_array();
                        if($update["TransID"] == $TansID) {
                            $mysqli->close();
                            echo "Die Zahlung wurde schon abgeschlossen!";
                            exit;
                        }
                        $statement = "update";
                        $passwd = $update["pass"];
                        $date = date('Y-m-d H:i:s', strtotime($update["endtime"]. " + {$days_to_end} days"));
                        $amountInsert = $update["Amount"];
                        $amountInsert+=$ItemTotalPrice;
                    } else {
                        $statement = "insert";
                        $date = date('Y-m-d H:i:s', strtotime('+'.$days_to_end.' days'));
                        $amountInsert = $ItemTotalPrice;
                    }
                    
                    Logger::info("USE ".$statement." FOR USER"); // LOGGER
                    
                    $getInfo	= callAPI('GET', $apiServer."getfullInfo/?id=".$ItemDesc, false);
                    $getUserId	= json_decode($getInfo, true);
                    $userid		= $getUserId["response"]["InputPeer"]["user_id"];					
                    
                    if($userid) {
                        $useridnow = ", userid = '$userid'";
                    }
                                        
                    if($use_map == "PMSF") {
                        Logger::info("USE PMSF AS MAP"); // LOGGER
                        $hashedPwd = password_hash($passwd, PASSWORD_DEFAULT);
                        
                        $datum = new DateTime($date);
                        $datum = $datum->getTimestamp();
                        $expire_timestamp = $datum;
                        
                        Logger::info("SET TIMESTAMP TO ".$expire_timestamp); // LOGGER
                        
                        $empfaenger	= $ItemDesc2;
                        $loginName	= $empfaenger;
                        
                        $check_user = $mysqli->query("SELECT id FROM users WHERE user = '".$loginName."' ");
                        if($check_user->num_rows != 0) {
                            mysqli_query($mysqli, "UPDATE users SET password = NULL, temp_password = '".$hashedPwd."', expire_timestamp = '".$expire_timestamp."', session_id = NULL, login_system = '".$login_system."', access_level = '".$access_level."'  WHERE user = '".$loginName."' ");
                        } elseif($statement == "insert") {
                            $insert_pmsf_user = $mysqli->query("INSERT INTO users 
                            (user,temp_password,expire_timestamp,login_system,access_level)
                            VALUES ('$ItemDesc2','$hashedPwd','$expire_timestamp','$login_system','$access_level')");
                        } else {
                            $update_user = $check_user->fetch_array();
                            mysqli_query($mysqli, "UPDATE users SET expire_timestamp = '".$expire_timestamp."' WHERE id = ".$update_user["id"]);
                        }
                    }
                    
                    elseif($use_map == "Rocketmap") {
                        Logger::info("USE ROCKETMAP AS MAP"); // LOGGER
                        include("Htpasswd.php");
                        $htpasswd = new Htpasswd('.htpasswd');
                        if($htpasswd->addUser($ItemDesc, $passwd)) {
                            Logger::info("CREATE USER ".$ItemDesc." ON .htpasswd"); // LOGGER
                        }
                        
                        $empfaenger	= $buyEmail;
                        $loginName	= $ItemDesc;
                    }
                    
                    else {
                        Logger::warn("USE NO MAP IN YOUR CONFIG !!!"); // LOGGER
                        $empfaenger	= $buyEmail;
                    }
                                        
                    if($statement == "insert") {
                        $sql_insert = "INSERT INTO ".$tbl." SET buyerName = '$buyName', buyerEmail = '$empfaenger', Amount = '$amountInsert', TelegramUser = '$ItemDesc'".$useridnow.", channels = '', pass = '$passwd', TransID = '$TansID', paydate = now(), endtime = NOW() + INTERVAL $days_to_end DAY";
                        if($insert_row = $mysqli->query($sql_insert)) {
                            Logger::info("INSERT USER ON DATABASE SUCESS"); // LOGGER
                        } else {
                            Logger::error("INSERT USER ON DATABASE FAILED\n".$sql_insert); // LOGGER
                        }
                    } else {
                        mysqli_query($mysqli, "UPDATE ".$tbl." SET Amount = $amountInsert, TransID = '$TansID', paydate = now(), endtime = DATE_ADD(endtime,INTERVAL $days_to_end DAY), info = NULL WHERE id = ".$update["id"]);
                        Logger::info("UPDATE USER ON DATABASE"); // LOGGER
                    }

                    include_once("admin/msg.php");
					
					if(isset($admins) and empty($admins) === false) {
                        $sendAdmins = explode(",", trim($admins));
                        $sendAdmins = explode(',', $admins);
                        $sendAdmins = array_map('trim', $sendAdmins);
                        for($i=0; $i < count($sendAdmins); $i++) {
                            $sendMessage = callAPI('GET', $apiServer."sendMessage/?data[peer]=".$sendAdmins[$i]."&data[message]=new $statement User: $ItemDesc, Amount payed $ItemTotalPrice, date to set $date&data[parse_mode]=html", false);
                        }
                    }
                    
                    if($use_map == "PMSF" or $use_map == "Rocketmap") {
                        $botMessage = urlencode($userPayedMsg);
                        $mailMessage= nl2br($userPayedMsg);
                    } else {
                        $botMessage = urlencode($userPayedMsgShort);
                        $mailMessage= nl2br($userPayedMsgShort);
                    }
                    
                    $all_channels = $mysqli->query("SELECT * FROM channels");
                    while($unsert_bann = $all_channels->fetch_array()) {		
                        $chat_id = $unsert_bann["chatid"];
                        $editBanned = callAPI('GET', $apiServer."channels.editBanned/?data[channel]=$chat_id&data[user_id]=$ItemDesc&data[banned_rights][until_date]=0&data[banned_rights][view_messages]=0&data[banned_rights][_]=chatBannedRights", false);
                    }
        
                    if($botSend == '1') {
                        Logger::info("USE BOT TO SEND MESSAGE"); // LOGGER
                        $sendMessage = callAPI('GET', $apiServer."sendMessage/?data[peer]=$userid&data[message]=$botMessage&data[parse_mode]=html", false);
                    }
                    
                    if($mailSend == '1') {
                        Logger::info("USE MAIL TO SEND MESSAGE"); // LOGGER
                        
                        require_once('mailer/class.phpmailer.php');

                        $mail             = new PHPMailer();
                        $mail->CharSet	  = 'utf-8';
        
                        $mail->IsSMTP(); // telling the class to use SMTP
                        $mail->Host       = $mailHost; // SMTP server
                        $mail->Port       = $smtpPort;                    // set the SMTP port for the GMAIL server
                        $mail->SMTPSecure = $smtpSecure;
                        $mail->SMTPDebug  = 0;                     // enables SMTP debug information (for testing)
                                           // 1 = errors and messages
                                           // 2 = messages only
                        $mail->SMTPAuth   = true;                  // enable SMTP authentication
                        $mail->Username   = $smtpUser; // SMTP account username
                        $mail->Password   = $smtpPass;        // SMTP account password
        
                        $mail->SetFrom($mailSender, $WebsiteTitle);
                        $mail->AddReplyTo($mailSender, $WebsiteTitle);
        
                        $mail->Subject    = mb_encode_mimeheader($mailSubject,"UTF-8");;
                        $mail->AltBody    = strip_tags($mailMessage); // optional, comment out and test
                        $mail->MsgHTML($mailMessage);
                        $mail->AddAddress($empfaenger, $WebsiteTitle);

                        $mail->Send();
                         
                        if(!$mail->Send()) {
                            Logger::info("MAIL SEND TO ".$empfaenger); // LOGGER
                        } else {
                            Logger::error("CAN NOT MAIL SEND TO ".$empfaenger); // LOGGER
                        }
                    }
                    
                    
                    Logger::info("FINISH SUCESS !!!"); // LOGGER
                    //echo '<pre>';
                    //print_r($httpParsedResponseAr);
                    //echo '</pre>';
                } else  {
                    echo '<div style="color:red"><b>GetTransactionDetails failed:</b>'.urldecode($httpParsedResponseAr["L_LONGMESSAGE0"]).'</div>';
                    echo '<pre>';
                    print_r($httpParsedResponseAr);
                    echo '</pre>';

                }
    
    }else{
            echo '<div style="color:red"><b>Error : </b>'.urldecode($httpParsedResponseAr["L_LONGMESSAGE0"]).'</div>';
            echo '<pre>';
            print_r($httpParsedResponseAr);
            echo '</pre>';
    }
}
?>
