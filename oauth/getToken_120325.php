<?php

$p = new OAuthProvider();

$t = $p->generateToken(20);

//echo strlen($t), PHP_EOL;
//$token = bin2hex($t), PHP_EOL;
$token = bin2hex($t);

$client_id_input = $_POST["client_id"];
$client_secret_input = $_POST['client_secret'];
$client_id = "18656374081-7i7aft5bd7u7rsm5bmrt60ri232qi8irm";
$client_secret = "RBCSPX-m0LtBYDa2BjcFzjM4MxOyYCoSbGY";

$myObj = new stdClass();

header('Content-Type: application/json; charset=utf-8');

if ($client_id_input != $client_id || $client_secret_input != $client_secret) {
    $myObj = new stdClass();
    $myObj->error = "invalid_token";
    $myObj->error_code = "1001";
    $myObj->error_msg = "Invalid Credentials";
}

if ($client_id_input == $client_id && $client_secret_input == $client_secret) {



    ini_set('session.gc_maxlifetime', 60);
    session_start();
    $_SESSION['token'] = $token; //set into session

    $myObj->access_token = $token;
    $myObj->token_type = "Bearer";
    $myObj->scope = "rbconnect";
    $myObj->expires_in = 60;


}

$myJSON = json_encode($myObj);

echo $myJSON;
exit();