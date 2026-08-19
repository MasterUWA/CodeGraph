<?php
// vulnerable/download_file.php - trusts a user-supplied filename when reading from disk.
$file = $_GET['file'];
$path = "uploads/" . $file;
readfile($path);
