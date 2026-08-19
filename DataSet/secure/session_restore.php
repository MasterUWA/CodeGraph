<?php
// secure/session_restore.php - uses JSON instead of unserialize() to avoid object injection.
$data = $_COOKIE['session_data'];
$session = json_decode($data, true);
print_r($session);
