<?php
$host = $_POST['host'];
$output = shell_exec("ping -c 4 " . $host);
echo "<pre>$output</pre>";

$filename = $_GET['file'];
include("includes/" . $filename . ".php");
?>