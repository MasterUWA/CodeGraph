<?php
// vulnerable/api_client.php - ships a hardcoded API secret in source control.
$apiKey = "REPLACE_WITH_YOUR_OWN_FAKE_TEST_KEY_0000";

function callApi($apiKey, $endpoint) {
    return file_get_contents("https://api.example.com/$endpoint?key=$apiKey");
}

echo callApi($apiKey, 'status');
