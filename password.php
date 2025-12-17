<?php
$curl = curl_init();
$APIKEY = 'TLg1GY6Gwcnii12H3EY0LWg4tCFQgcsOg4NVLpdQqm413h32QFJR0VxN4q08jT';
$phoneNo = '+2347030067746';
$data = array("api_key" => $APIKEY, "phone_number" => $phoneNo,);

$post_data = json_encode($data);

curl_setopt_array($curl, array(
    CURLOPT_URL => "https://api.ng.termii.com/api/check/dnd",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_POSTFIELDS => $post_data,
    CURLOPT_HTTPHEADER => array(
        "Content-Type: application/json"
    ),
));

$response = curl_exec($curl);

curl_close($curl);
echo $response;
