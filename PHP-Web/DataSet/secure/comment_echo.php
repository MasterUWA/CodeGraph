<?php
// secure/comment_echo.php - escapes user input before rendering it as HTML.
$comment = $_GET['comment'];
echo "<div class='comment'>" . htmlspecialchars($comment, ENT_QUOTES, 'UTF-8') . "</div>";
