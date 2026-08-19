<?php
// secure/ping_host.php - validates input and avoids shell interpolation.
$host = $_GET['host'];

if (!filter_var($host, FILTER_VALIDATE_IP) && !preg_match('/^[a-zA-Z0-9.-]+$/', $host)) {
    die("Invalid host");
}

$output = shell_exec('ping -n 1 ' . escapeshellarg($host));
echo "<pre>$output</pre>";
