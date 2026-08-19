<?php
// vulnerable/comment_echo.php - reflects user input straight into the HTML response.
$comment = $_GET['comment'];
echo "<div class='comment'>" . $comment . "</div>";
