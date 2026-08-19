<?php
// vulnerable/ping_host.php - passes user input directly to a shell command.
$host = $_GET['host'];
$output = shell_exec("ping -n 1 " . $host);
echo "<pre>$output</pre>";
