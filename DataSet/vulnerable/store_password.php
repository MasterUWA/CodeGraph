<?php
// vulnerable/store_password.php - hashes passwords with a fast, unsalted algorithm.
$password = $_POST['password'];
$hashed = md5($password);
file_put_contents('users.txt', $hashed . PHP_EOL, FILE_APPEND);
