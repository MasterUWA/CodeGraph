<?php
// secure/download_file.php - whitelists filenames to prevent path traversal.
$file = basename($_GET['file']);
$path = "uploads/" . $file;

if (!file_exists($path)) {
    die("File not found");
}

readfile($path);
