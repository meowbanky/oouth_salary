<?php
require_once __DIR__ . '/../vendor/autoload.php'; // Adjust if needed

use Dotenv\Dotenv;

// Load .env
$dotenv = Dotenv::createImmutable(__DIR__.'/../');
$dotenv->load();

function generateStrongPassword($length = 10)
{
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    return substr(str_shuffle(str_repeat($characters, $length)), 0, $length);
}

function emailExists($user, $domain, $cpanelHost, $cpanelUser, $cpanelPassword)
{
    $url = "https://{$cpanelHost}:2083/execute/Email/list_pops?api.version=1";
    $api_url = "https://{$cpanel_host}:2083/execute/Email/add_pop";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERPWD, "{$cpanelUser}:{$cpanelPassword}");
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo "❌ cURL Error (checking existence): " . curl_error($ch) . PHP_EOL;
        curl_close($ch);
        return false;
    }

    $data = json_decode($response, true);
    curl_close($ch);

    if (!isset($data['data'])) {
        echo "❌ Failed to get email list\n";
        return false;
    }

    $fullEmail = "{$user}@{$domain}";
    foreach ($data['data'] as $account) {
        if (isset($account['email']) && strtolower($account['email']) === strtolower($fullEmail)) {
            return true;
        }
    }

    return false;
}

function createEmail($user)
{
    $cpanelHost = $_ENV['CPANEL_HOST'];
    $cpanelUser = $_ENV['CPANEL_USER'];
    $cpanelPassword = $_ENV['CPANEL_PASS'];
//    $domain      = $_ENV['DOMAIN'];

    $domain = $cpanelHost;
    $quota = 250;
    $password = generateStrongPassword();

    $user = trim(strtolower($user));
    if (str_contains($user, '@')) {
        $user = explode('@', $user)[0];
    }

    $email = "{$user}@{$domain}";

    if (emailExists($user, $domain, $cpanelHost, $cpanelUser, $cpanelPassword)) {
        echo "⚠️ Skipped (already exists): {$email}\n";
        return;
    }

    $url = "https://{$cpanelHost}:2083/execute/Email/add_pop?email={$user}&domain={$domain}&password={$password}&quota={$quota}";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERPWD, "{$cpanelUser}:{$cpanelPassword}");
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    if (isset($data['status']) && $data['status'] === 1) {
        echo "✅ Created: {$email}\nPassword: {$password}\n";
    } else {
        echo "❌ Failed to create: {$email}\nResponse:\n";
        print_r($data);
    }
}

// ✅ Example usage
createEmail('adeko.oloruntoba');
