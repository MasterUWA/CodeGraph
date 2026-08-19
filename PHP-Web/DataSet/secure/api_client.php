<?php
// secure/api_client.php - loads the API secret from an environment variable, not source code.
$apiKey = getenv('API_KEY');

function callApi($apiKey, $endpoint) {
    return file_get_contents("https://api.example.com/$endpoint?key=" . urlencode($apiKey));
}

echo callApi($apiKey, 'status');
