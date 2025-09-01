<?php

//echo strlen($t), PHP_EOL;
//$token = bin2hex($t), PHP_EOL;

$client_id_input = $_POST["client_id"];
$client_secret_input = $_POST['client_secret'];

$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => "https://dev-pum8sntxdph5p8nl.us.auth0.com/oauth/token",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => "{\"client_id\":\"$client_id_input\",\"client_secret\":\"$client_secret_input\",\"audience\":\"https://discoverbrunei.flyroyalbrunei.com/rb-connect/\",\"grant_type\":\"client_credentials\"}",
    CURLOPT_HTTPHEADER => array(
        "content-type: application/json"
    ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
    echo "cURL Error #:" . $err;
} else {
    echo $response;
}
exit();