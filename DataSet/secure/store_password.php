<?php
// secure/store_password.php - hashes passwords with a strong, salted algorithm.
$password = $_POST['password'];
$hashed = password_hash($password, PASSWORD_DEFAULT);
file_put_contents('users.txt', $hashed . PHP_EOL, FILE_APPEND);
