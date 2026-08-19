<?php
// secure/sqli_login.php - uses a parameterized query to prevent SQL injection.
$conn = new mysqli('localhost', 'root', '', 'app');

$username = $_POST['username'];
$password = $_POST['password'];

$stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
$stmt->bind_param('ss', $username, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    echo "Login successful";
} else {
    echo "Login failed";
}
