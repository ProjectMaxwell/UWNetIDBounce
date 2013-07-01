<?php

$uwnetid;

$properties = parse_ini_file("UWNetID.properties");

$accessToken = "";

$jsonBody = '{"grantType":"CLIENT_CREDENTIALS",
"clientId":"' . $properties['PhiAuthClientId'] . '",
"clientSecret":"' . $properties['PhiAuthClientSecret'] . '"}';
$allowMock = $properties['AllowUWNetIDMock'];

$url = 'http://evergreenalumniclub.com:7080/PhiAuth/rest/token';
$ch = curl_init($url);

curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json'
    ));

$response = curl_exec($ch);
curl_close($ch);

$jsonResponse = json_decode($response);
$accessToken = $jsonResponse->accessToken;

$ref = "";
$queryParams = "";
if(array_key_exists('redir', $_GET)){
	$ref= urldecode($_GET['redir']);
}else{
	$ref=getenv("HTTP_REFERER");
}
if($ref == null){
	$ref = "index.php";
}else if(strpos($ref, "?") > 0){
	$ref = substr($ref, 0, strpos($ref, "?"));
}

if($allowMock && array_key_exists('uwnetid', $_POST)){
	$uwnetid = $_POST['uwnetid'];
}else if(array_key_exists('REMOTE_USER', $_ENV)){
	$uwnetid = $_ENV['REMOTE_USER'];
}else{
	$ref .= "?errorCode=UWNETID.NOT.DEFINED";
	header("Location: " . $ref);
}
	

$jsonBody = '{"uwnetid":"' . $uwnetid . '"}';

$url = 'http://evergreenalumniclub.com:7080/PhiAuth/rest/uwnetid_token';
$ch = curl_init($url);

curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Authorization: '  . $accessToken
    ));

$response = curl_exec($ch);

curl_close($ch);

$jsonResponse = json_decode($response);

$data;

if(isset($jsonResponse->token)){

	$data = array("token" => $jsonResponse->token,
				  "uwnetid" => $jsonResponse->uwnetid,
				  "expiration" => $jsonResponse->expiration);
	$ref .= "?token=" . $jsonResponse->token . "&uwnetid=" . $jsonResponse->uwnetid . "&expiration=" . $jsonResponse->expiration;


}else if(isset($jsonResponse->errorCode)){
	$data = array("errorCode" => $jsonResponse->errorCode);
	$ref .= "?errorCode=" . $jsonResponse->errorCode;
}else{
	$data = array("errorCode" => "UNKNOWN.ERROR");
	$ref .= "?errorCode=UNKNOWN.ERROR";
}

header("Location: " . $ref);


?>