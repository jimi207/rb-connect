<?php

//ini_set('display_errors', '1');
//ini_set('display_startup_errors', '1');
//error_reporting(E_ALL);

$client_id_input = $_POST["client_id"];
$client_secret_input = $_POST['client_secret'];
$key = $_POST['key'];
$client_id = "intelsat001";
$client_secret = "RBCSPX-m0LtBYDa2BjcFzjM4MxOyYCoSbGY";
$key_input = "9mjuVBoQWxnHEGmWsFGXVz3GBZ1hItDk";

$myObj = new stdClass();

header('Content-Type: application/json; charset=utf-8');

if ($client_id_input != $client_id || $client_secret_input != $client_secret || $key != $key_input) {
    header('HTTP/1.0 401 UnAuthorized');
    $myObj = new stdClass();
    $myObj->error = "invalid_token";
    $myObj->error_code = "1001";
    $myObj->error_msg = "Invalid Credentials";
}

require __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$exp_time = time() + (24*60*60);

$payload = [
    'iss' => 'https://www.flyroyalbrunei.com',
    'aud' => 'https://service.flyroyalbrunei.com/',
    'iat' => time(),
    'exp' => $exp_time
];

/*$payload = [
    'iss' => 'https://www.flyroyalbrunei.com',
    'aud' => 'https://discoverbrunei.flyroyalbrunei.com/rb-connect/',
    'iat' => time(),
    'exp' => $exp_time
];*/
// 1 day

/**
 * IMPORTANT:
 * You must specify supported algorithms for your application. See
 * https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40
 * for a list of spec-compliant algorithms.
 */
$jwt = JWT::encode($payload, $key, 'HS256');
//$decoded = JWT::decode($jwt, new Key($key, 'HS256'));
//print_r($decoded);

// Pass a stdClass in as the third parameter to get the decoded header values
//$headers = new stdClass();
//$decoded = JWT::decode($jwt, new Key($key, 'HS256'), $headers);
//print_r($headers);

/*
 NOTE: This will now be an object instead of an associative array. To get
 an associative array, you will need to cast it as such:
*/

$decoded_array = (array) $decoded;

/**
 * You can add a leeway to account for when there is a clock skew times between
 * the signing and verifying servers. It is recommended that this leeway should
 * not be bigger than a few minutes.
 *
 * Source: http://self-issued.info/docs/draft-ietf-oauth-json-web-token.html#nbfDef
 */
//JWT::$leeway = 60; // $leeway in seconds
//$decoded = JWT::decode($jwt, new Key($key, 'HS256'));



if ($client_id_input == $client_id && $client_secret_input == $client_secret) {

    $myObj->access_token = $jwt;
    $myObj->token_type = "Bearer";
    $myObj->scope = "read-booking";
    $myObj->expires_in = $exp_time;


}

$myJSON = json_encode($myObj);

echo $myJSON;
exit();