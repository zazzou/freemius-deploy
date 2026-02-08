<?php
$file_name = $_ENV['INPUT_FILE_NAME'];
$version = $_ENV['INPUT_VERSION'];
$release_mode = !isset($_ENV['INPUT_RELEASE_MODE']) || empty($_ENV['INPUT_RELEASE_MODE']) ? 'pending' : $_ENV['INPUT_RELEASE_MODE'];

$bearerToken = $_ENV['FREEMIUS_BEARER_TOKEN'];
$plugin_id = $_ENV['PLUGIN_ID']; // 22442
$dev_id = $_ENV['DEV_ID'];       // 26791

echo "\n- Deploying plugin with Bearer token\n";

// URL API pour créer une release
$api_url = "https://api.freemius.com/v1/plugins/{$plugin_id}/tags.json?dev_id={$dev_id}";

// Préparer le fichier ZIP à uploader
$cfile = new CURLFile($file_name, 'application/zip', basename($file_name));

// Préparer POST avec Bearer Auth
$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $bearerToken",
    "Accept: application/json"
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => $cfile]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "\nHTTP Code: $http_code\n";
echo "Response: $response\n";
