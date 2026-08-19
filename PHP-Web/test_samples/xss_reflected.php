<?php
$search = $_GET['search'];
$message = $_POST['message'];
$username = $_COOKIE['username'];

echo "<h1>Search Results for: " . $search . "</h1>";
echo "<p>Welcome back, " . $username . "</p>";
echo "<div class='message'>" . $message . "</div>";
?>