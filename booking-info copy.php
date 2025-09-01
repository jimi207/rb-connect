<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require 'hitit.class.php';

ini_set('session.gc_maxlifetime', 60);
session_start();
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
$token = getBearerToken();
//session_destroy();
$token_in_session = "";
if (isset($_SESSION['token'])) {
    $token_in_session = $_SESSION['token'];
}
$tokenError = "";

if ($token == null || $token == "") {
    $tokenError = "Missing token";
}

if ($token != $token_in_session) {
    $tokenError = "Invalid Token";
}

if ($tokenError != "") {
    sendToken($token);
    //exit();
}

function sendToken($token)
{
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://discoverbrunei.flyroyalbrunei.com/rb-connect/booking-info",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "authorization: Bearer $token"
        ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        echo "cURL Error #:" . $err;
        exit;
    } else {
        //echo $response;
    }
}


header('Content-Type: application/json; charset=utf-8');

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
$responseObj->benefit_code = "WXY_2HR";
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