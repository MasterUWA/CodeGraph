<?php
$id = $_GET['id'];
$sql = "SELECT * FROM products WHERE id=" . $id;
$result = mysqli_query($conn, $sql);

while ($row = mysqli_fetch_assoc($result)) {
    echo $row['name'] . "<br>";
}
?>