<?php
// secure/redirect.php - only redirects to a whitelisted set of internal paths.
$allowed = ['home', 'profile', 'settings'];
$page = $_GET['url'];

if (!in_array($page, $allowed, true)) {
    $page = 'home';
}

header("Location: /$page");
exit;
