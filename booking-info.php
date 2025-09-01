<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);


require 'hitit.class.php';

function getAuthorizationHeader()
{
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        //print_r($requestHeaders);
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }
    return $headers;
}

/**
 * get access token from header
 * */
function getBearerToken()
{
    $headers = getAuthorizationHeader();
    // HEADER: Get the access token from the header
    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

header('Content-Type: application/json; charset=utf-8');

$token = getBearerToken();

if ($token == null) {
    header('HTTP/1.0 400 Bad Request');
    echo 'Token not found in request';
    exit;
}


require __DIR__ . '/vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$key = "9mjuVBoQWxnHEGmWsFGXVz3GBZ1hItDk";

try {

    $decoded = JWT::decode($token, new Key($key, 'HS256'));

} catch (InvalidArgumentException $e) {
    // provided key/key-array is empty or malformed.
} catch (DomainException $e) {
    // provided algorithm is unsupported OR
    // provided key is invalid OR
    // unknown error thrown in openSSL or libsodium OR
    // libsodium is required but not available.
} catch (SignatureInvalidException $e) {
    // provided JWT signature verification failed.
} catch (BeforeValidException $e) {
    // provided JWT is trying to be used before "nbf" claim OR
    // provided JWT is trying to be used before "iat" claim.
} catch (ExpiredException $e) {
    // provided JWT is trying to be used after "exp" claim.
} catch (UnexpectedValueException $e) {
    // provided JWT is malformed OR
    // provided JWT is missing an algorithm / using an unsupported algorithm OR
    // provided JWT algorithm does not match provided key OR
    // provided key ID in key/key-array is empty or invalid.
}

//echo 'exp: ' . $decoded->exp;
//exit;

$now = new DateTimeImmutable();
$serverName = "https://www.flyroyalbrunei.com";

if (
    $decoded->iss !== $serverName ||
    $decoded->iat > $now->getTimestamp() ||
    $decoded->exp < $now->getTimestamp()
) {
    header('HTTP/1.1 401 Unauthorized');
    exit;
}


$json = file_get_contents('php://input');

$data = json_decode($json);

$data = json_decode($json);
$trackingId = $data->tracking_id;
$flightNoInput = $data->flight_info->iata->flight_number;
$depDateInput = $data->flight_info->iata->departure_date_utc;
$pnrInput = "";
$lastNameInput = "";
//$pnrInput = $data->user_details->[0]->pnr;
$userDetailsArray = $data->user_details;

foreach ($userDetailsArray as $object) {
    if ($object->name === 'pnr') {
        //echo 'exp: ' . $object->SSR->explanation;
        $pnrInput = $object->value;
        break;
    }
}

foreach ($userDetailsArray as $object) {
    if ($object->name === 'last_name') {
        //echo 'exp: ' . $object->SSR->explanation;
        $lastNameInput = $object->value;
        break;
    }
}

//process

$hitit = new Hitit();
$hitit->pnr = $pnrInput;
$hitit->last_name = $lastNameInput;
$hitit->getProfile();
//$membership = $hitit->fqtv;
$coupons = $hitit->coupons;
$members = $hitit->members;

$errorMessage = "";
$errorCode = "";

$isFlightMatch = false;
foreach ($coupons as $object) {
    if ($flightNoInput === 'BI' . $object["flightNumber"] && substr($object["departureDateTimeUTC"], 0, 11) === substr($depDateInput, 0, 11)) {
        $isFlightMatch = true;
        break;
    }
}
$isFfpMember = false;

foreach ($members as $object) {
    if ($object["last_name"] === $lastNameInput) {
        $isFfpMember = true;
        break;
    }
}

if ($pnrInput == "" || $lastNameInput == "" || $depDateInput == null || $flightNoInput == null || $userDetailsArray == null) {
    $errorMessage = "Invalid Input";
}

if ($isFlightMatch == false) {
    $errorMessage = "Flight not found";
}

if ($isFfpMember == false) {
    $errorMessage = "FFP Member not found";
}

if ($errorMessage != "") {
    header("HTTP/1.1 400 Invalid Request");
    $myObj = new stdClass();
    $myObj->error_code = "1004";
    $myObj->error_msg = $errorMessage;
    echo json_encode($myObj);
    exit();
}

$strCustomerID = "SPONSOR/" . $pnrInput . "_" . $flightNoInput . "_" . $lastNameInput . "_XXX";

$responseObj = new stdClass();
$responseObj->status_code = "200";
$responseObj->pnr = $pnrInput;
//$responseObj->last_name = $hitit->last_name;
$responseObj->is_ffp_member = $isFfpMember;
$responseObj->status_msg = "Ok";
$responseObj->tracking_id = $trackingId;
$responseObj->benefit_code = "BI_RSMEMBERS";
$responseObj->flight_matched = $isFlightMatch;
$responseObj->flight_number = $flightNoInput;
$responseObj->departure_date_utc = $depDateInput;
$responseObj->partner_customer_id = $strCustomerID;
$responseObj->additional_details["lastname"] = $lastNameInput;
$responseObj->additional_details["email"] = $hitit->email;
//$responseObj->additional_details["membership"] = $membership;

$couponssNode = [];
$membersNode = [];

if ($coupons != null) {
    foreach ($coupons as $object) {
        $couponsNode[] = ["ticket_number" => $object["ticketNumber"], "surname" => $object["surname"], "flight_number" => $object["flightNumber"], "departure_date_utc" => $object["departureDateTimeUTC"], "seat" => $object["seat"]];
    }
    $responseObj->DOCS = $couponsNode;
}

if ($members != null && $isFfpMember) {
    foreach ($members as $object) {
        $membersNode[] = ["lastname" => $object["last_name"], "tier" => $object["fqtv"]];
    }
    $responseObj->members = $membersNode;
}

echo json_encode($responseObj);
exit();