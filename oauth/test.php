<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

////require '../firebase/php-jwt/src/vendorJWT.php';
//require 'Key.php';

require __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$key = "___SECRET___";

$token = 'eyJhbGciOiJSUzI1NiIsInR5cCI6ImF0K2p3dCIsImtpZCI6InJ6eVVDS0FjcWF1ZlF6YTFyblY4LSJ9.eyJpc3MiOiJodHRwczovL2Rldi1wdW04c250eGRwaDVwOG5sLnVzLmF1dGgwLmNvbS8iLCJzdWIiOiI5bWp1VkJvUVd4bkhFR21Xc0ZHWFZ6M0dCWjFoSXREa0BjbGllbnRzIiwiYXVkIjoiaHR0cHM6Ly9kaXNjb3ZlcmJydW5laS5mbHlyb3lhbGJydW5laS5jb20vcmItY29ubmVjdC8iLCJpYXQiOjE3NDE3NDQ5NjAsImV4cCI6MTc0MTgzMTM2MCwianRpIjoidWtFNjFEczVKSjE5ODV5UVJkcHBmdCIsImNsaWVudF9pZCI6IjltanVWQm9RV3huSEVHbVdzRkdYVnozR0JaMWhJdERrIn0.lUD0bFXTvigm_E6gu6ZMJ9DXEhsPLOs2EJSDmzC3MQt1VEwoxaXvULdBxN61yo_EZ2-6Wtnkg19XqFLphz2-LvACAL8FlbQU6uH6LcUFTemgZZ8x15Y0euv9WAoQ5jL9SGierG2QOXCi-cP-AOrnL_YJjUIgwkShqgtx0SERN-Pf7B35VidHeCa6QMMHVRrGgCRNU4WhFTRtuoUz4wefjbyHYCVLVPY9gMKf0nU0DCaB3NRZK_vN6xAOHwig7Rq0dXVDcCGpV_l2LeXmtDmTjJHaw5BYLw24DVy8ZLz27IUFYfxyOHMBzHfnJQEpfin31ickLjmoyUzuK7rLiqjGtw';
/*$payload = [
    'iss' => 'https://dev-pum8sntxdph5p8nl.us.auth0.com',
    'aud' => 'https://discoverbrunei.flyroyalbrunei.com/rb-connect/',
    'iat' => 1356999524,
    'nbf' => 1357000000
];*/
/*$payload = [
    'domain' => 'https://dev-pum8sntxdph5p8nl.us.auth0.com',
    'audience' => 'https://discoverbrunei.flyroyalbrunei.com/rb-connect/'
];*/

/**
 * IMPORTANT:
 * You must specify supported algorithms for your application. See
 * https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40
 * for a list of spec-compliant algorithms.
 * 
 * 
 * 
 */
$now = new DateTimeImmutable();

list($header, $payload, $signature) = explode('.', $token);
$jsonToken = base64_decode($payload);
$arrayToken = json_decode($jsonToken, true);
//print_r($arrayToken);

$json = json_encode($jsonToken);
$data = json_decode($json);

$server_iis = $data->iss;
echo "server: " . $server_iis . "<br />";
exit;



/*$serverName = 'https://dev-pum8sntxdph5p8nl.us.auth0.com';
$now = new DateTimeImmutable();
if (
    $data->iss !== $serverName ||
    $data->iat > $now->getTimestamp() ||
    $data->exp < $now->getTimestamp()
) {
    header('HTTP/1.1 401 Unauthorized');
    exit;
} else {
    echo 'Authorized';
}*/

/*foreach ($arrayToken as $key => $value) {
    //echo $key . ' => ' . $value . '<br />';
    $decoded = JWT::decode($jwt, new Key($key, 'HS256'));
    echo 'decoded: <br />';
    print_r($decoded);
}*/

//exit;


$jwt = JWT::encode($payload, $key, 'HS256');
$jwt = base64_decode($key);
$decoded = JWT::decode($jwt, new Key($key, 'HS256'));
//print_r($decoded);


$decoded = json_decode(json_encode($decoded), true);
//print_r($decoded);

list($header, $payload, $signature) = explode('.', $jwt);
$decoded = base64_decode($payload);
$arrayToken = json_decode($decoded, true);
//print_r($arrayToken);

// Pass a stdClass in as the third parameter to get the decoded header values
$headers = new stdClass();
$decoded = JWT::decode($jwt, new Key($key, 'HS256'), $headers);
//echo "decoded header: <br />";
//print_r($decoded);

/*
 NOTE: This will now be an object instead of an associative array. To get
 an associative array, you will need to cast it as such:
*/

$decoded_array = (array) $decoded;

//print_r($decoded_array);
/**
 * You can add a leeway to account for when there is a clock skew times between
 * the signing and verifying servers. It is recommended that this leeway should
 * not be bigger than a few minutes.
 *
 * Source: http://self-issued.info/docs/draft-ietf-oauth-json-web-token.html#nbfDef
 */
JWT::$leeway = 60; // $leeway in seconds
$decoded = JWT::decode($jwt, new Key($key, 'HS256'));

/*
            list($header, $payload, $signature) = explode('.', $encodedToken);
            $jsonToken = base64_decode($payload);
            $arrayToken = json_decode($jsonToken, true);
            print_r($arrayToken);
            */