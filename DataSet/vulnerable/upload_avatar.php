<?php
// vulnerable/upload_avatar.php - saves any uploaded file without validating type or extension.
$name = $_FILES['avatar']['name'];
move_uploaded_file($_FILES['avatar']['tmp_name'], 'uploads/' . $name);
echo "Uploaded";
