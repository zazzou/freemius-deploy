<?php
/**
 * Deploy WordPress plugin to Freemius using Bearer Token
 * Run inside GitHub Actions
 */

$file_name = $_ENV['INPUT_FILE_NAME'];
$version = $_ENV['INPUT_VERSION'];
$release_mode = empty($_ENV['INPUT_RELEASE_MODE']) ? 'pending' : $_ENV['INPUT_RELEASE_MODE'];
$plugin_id = $_ENV['PLUGIN_ID'];
$dev_id = $_ENV['DEV_ID'];
$bearerToken = $_ENV['FREEMIUS_BEARER_TOKEN'];

echo "\n- Deploying plugin $file_name version $version\n";

// 1️⃣ Vérifier si la release existe déjà
$check_url = "https://api.freemius.com/v1/plugins/{$plugin_id}/tags.json?dev_id={$dev_id}";
$ch = curl_init($check_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $bearerToken",
    "Accept: application/json"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$tags = json_decode($response);
$exists = false;
$existing_tag_id = null;

if (isset($tags->tags)) {
    foreach ($tags->tags as $tag) {
        if ($tag->version === $version) {
            $exists = true;
            $existing_tag_id = $tag->id;
            break;
        }
    }
}

if ($exists) {
    echo "- Version $version already exists with tag ID $existing_tag_id\n";
} else {
    echo "- Uploading new tag $version...\n";
    $api_url = "https://api.freemius.com/v1/plugins/{$plugin_id}/tags.json?dev_id={$dev_id}";
    $cfile = new CURLFile($file_name, 'application/zip', basename($file_name));

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $bearerToken",
        "Accept: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => $cfile]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $upload_response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "- Upload HTTP code: $http_code\n";
    $upload_result = json_decode($upload_response);

    if (!isset($upload_result->id)) {
        echo "- Upload failed:\n";
        print_r($upload_result);
        exit(1);
    }

    $existing_tag_id = $upload_result->id;
    echo "- Upload done with tag ID $existing_tag_id\n";
}

// 2️⃣ Mettre à jour le release_mode
$update_url = "https://api.freemius.com/v1/plugins/{$plugin_id}/tags/{$existing_tag_id}.json?dev_id={$dev_id}";
$ch = curl_init($update_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $bearerToken",
    "Accept: application/json"
]);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['release_mode' => $release_mode]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$update_response = curl_exec($ch);
curl_close($ch);

echo "- Set release_mode to $release_mode\n";
