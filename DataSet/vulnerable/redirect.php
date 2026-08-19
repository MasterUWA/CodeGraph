<?php
// vulnerable/redirect.php - redirects to any attacker-supplied URL (open redirect).
$url = $_GET['url'];
header("Location: $url");
exit;
