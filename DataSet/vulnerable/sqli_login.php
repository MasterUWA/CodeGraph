<?php
// vulnerable/sqli_login.php - builds a SQL query by directly concatenating user input.
$conn = new mysqli('localhost', 'root', '', 'app');

$username = $_POST['username'];
$password = $_POST['password'];

$query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    echo "Login successful";
} else {
    echo "Login failed";
}
