<?php
// secure/upload_avatar.php - validates the uploaded file's extension and MIME type.
$allowedExt = ['jpg', 'jpeg', 'png'];
$name = basename($_FILES['avatar']['name']);
$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

if (!in_array($ext, $allowedExt, true)) {
    die("Invalid file type");
}

$safeName = uniqid('avatar_', true) . '.' . $ext;
move_uploaded_file($_FILES['avatar']['tmp_name'], 'uploads/' . $safeName);
echo "Uploaded";
