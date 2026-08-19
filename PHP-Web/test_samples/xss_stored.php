<?php
$comment = $_POST['comment'];
$user_id = $_SESSION['user_id'];

// Store comment without sanitization
$sql = "INSERT INTO comments (user_id, comment) VALUES ($user_id, '$comment')";
mysqli_query($conn, $sql);

// Display all comments
$result = mysqli_query($conn, "SELECT * FROM comments");
while ($row = mysqli_fetch_assoc($result)) {
    echo "<div class='comment'>" . $row['comment'] . "</div>";
}
?>