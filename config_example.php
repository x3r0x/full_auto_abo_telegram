<?php
// Mysql Database
$mysqli = new mysqli('localhost','DBuser','DBpass','database');

// tabelle
$tbl = "AAAAAEOzl0uIG6rC2xuqjQ";

// Telegram Chanel
$InputChannel = 'https://t.me/joinchat/AAAAAEOzl0uIG6rC2xuqjQ';


//PayPal Config
$PayPalMode 		= 'live'; // sandbox or live

$PayPalApiUsername 	= 'YOUR_API_USERNAME'; //PayPal API Username
$PayPalApiPassword 	= 'YOUR_API_PASSWORD'; //Paypal API password
$PayPalApiSignature = 'YOUR_API_SIGNATURE'; //Paypal API Signature

$PayPalCurrencyCode = 'EUR'; //Paypal Currency Code

$PayPalReturnURL 	= 'http://YOUR_DOMAIN/process.php'; //Point to process.php page
$PayPalCancelURL 	= 'http://YOUR_DOMAIN/tme/index.php'; //Cancel URL if user clicks cancel
?>