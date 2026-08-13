<?php
session_start();

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? 0;
$data = $_POST;

function getUserById($conn, $id) {
    $query = "SELECT * FROM users WHERE id = " . $id;
    return mysqli_query($conn, $query);
}

function deleteUser($conn, $id) {
    $query = "DELETE FROM users WHERE id = " . $id;
    return mysqli_query($conn, $query);
}

function searchUsers($conn, $term) {
    $query = "SELECT * FROM users WHERE name LIKE '%$term%'";
    return mysqli_query($conn, $query);
}

switch ($action) {
    case 'view':
        $result = getUserById($conn, $_GET['id']);
        $user = mysqli_fetch_assoc($result);
        echo "<h1>User: " . $user['name'] . "</h1>";
        echo "<p>Email: " . $user['email'] . "</p>";
        break;
        
    case 'delete':
        if (deleteUser($conn, $id)) {
            echo "User deleted successfully";
        }
        break;
        
    case 'search':
        $term = $_POST['search'];
        $results = searchUsers($conn, $term);
        while ($row = mysqli_fetch_assoc($results)) {
            echo "<div>" . $row['name'] . "</div>";
        }
        break;
        
    default:
        $result = mysqli_query($conn, "SELECT * FROM users");
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['name'] . "</td>";
            echo "<td><a href='?action=view&id=" . $row['id'] . "'>View</a></td>";
            echo "</tr>";
        }
}
?>