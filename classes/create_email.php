<?php
function generateStrongPassword($length = 6)
{
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+[]{}|;:,.<>?';
    $password = '';

    for ($i = 0; $i < $length; $i++) {
        $index = rand(0, strlen($characters) - 1);
        $password .= $characters[$index];
    }

    return $password;
}

function createEmail($postemail)
{
    $pass = generateStrongPassword(10);
    // Replace these with your actual values

    $apiToken = '7IJBJPYHKF15Z41YTPKPOBEN9BNHP7JL';
    $cpanelUsername = 'oouthco';
    $domain = 'oouth.com';
    $email = $postemail . '@oouth.com';
    $password = 'password@2022';
    $quota = 250;
    $position = strpos($postemail, '@oouth.com');
    if ($position !== 'false') {
        $email = $postemail;
    } else {
        $email = $postemail . '@oouth.com';
    }
    // API endpoint for email creation

    //curl -H'Authorization: cpanel username:U7HMR63FHY282DQZ4H5BIH16JLYSO01M' 'https://example.com:2083/execute/Email/add_pop?email=newuser&password=12345luggage'

    $apiUrl = "https://{$domain}:2083/execute/Email/add_pop";

    // Data to be sent in the POST request
    $data = [
        'domain' => $domain,
        'email' => $email,
        'password' => $password,
        'quota' => $quota,
    ];

    // Headers including the API token
    $headers = [
        // 'Authorization: cpanel ' . $apiToken,
        "Authorization: cpanel {$cpanelUsername}:" . $apiToken
    ];

    // Initialize cURL session
    $ch = curl_init();

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // Execute the cURL session
    $response = curl_exec($ch);

    // Check for errors
    if (curl_errno($ch)) {
        echo 'cURL Error: ' . curl_error($ch);
    } else {
        echo 'Email creation response: ' . $response;
    }
    // Close the cURL session
    curl_close($ch);
}
