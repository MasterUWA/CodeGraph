<?php
// First store user input
$name = $_POST['name'];
$insert_sql = "INSERT INTO users (name) VALUES ('$name')";
mysqli_query($conn, $insert_sql);

// Later retrieve and use without sanitization
$stored_name = mysqli_query($conn, "SELECT name FROM users WHERE id=1");
$row = mysqli_fetch_assoc($stored_name);
$stored = $row['name'];

// Vulnerable query using stored data
$query = "SELECT * FROM orders WHERE customer='$stored'";
$result = mysqli_query($conn, $query);
?>