<?php
// vulnerable/session_restore.php - unserializes untrusted user input (PHP object injection).
$data = $_COOKIE['session_data'];
$session = unserialize($data);
print_r($session);
