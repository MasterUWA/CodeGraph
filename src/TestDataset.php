<?php
/**
 * Comprehensive Test Dataset Generator
 * Contains 50+ PHP code samples across different vulnerability types
 */

namespace PhpGraphBuilder;

class TestDataset {
    /**
     * Get all test samples
     */
    public static function getAllSamples() {
        return array_merge(
            self::getSQLInjectionSamples(),
            self::getXSSSamples(),
            self::getCommandInjectionSamples(),
            self::getPathTraversalSamples(),
            self::getInsecureDeserializationSamples(),
            self::getSafeSamples()
        );
    }

    /**
     * SQL Injection Samples (10+)
     */
    public static function getSQLInjectionSamples() {
        return [
            [
                'name' => 'SQL Injection - String Concatenation',
                'type' => 'SQL Injection',
                'severity' => 'Critical',
                'code' => '<?php
$username = $_GET["username"];
$query = "SELECT * FROM users WHERE username = \'" . $username . "\'";
$result = mysqli_query($connection, $query);
?>'
            ],
            [
                'name' => 'SQL Injection - Simple String Input',
                'type' => 'SQL Injection',
                'severity' => 'Critical',
                'code' => '<?php
$id = $_POST["id"];
$query = "SELECT * FROM products WHERE id = " . $id;
mysqli_query($connection, $query);
?>'
            ],
            [
                'name' => 'SQL Injection - WITH Sanitizer',
                'type' => 'SQL Injection',
                'severity' => 'Critical',
                'code' => '<?php
$email = $_POST["email"];
$safe_email = mysqli_real_escape_string($connection, $email);
$query = "SELECT * FROM users WHERE email = \'" . $safe_email . "\'";
mysqli_query($connection, $query);
?>'
            ],
            [
                'name' => 'SQL Injection - Second Order',
                'type' => 'SQL Injection',
                'severity' => 'High',
                'code' => '<?php
$user_data = $_POST["data"];
$query = "INSERT INTO logs (data) VALUES (\'" . $user_data . "\')";
mysqli_query($connection, $query);

$logs = mysqli_query($connection, "SELECT data FROM logs");
while ($row = mysqli_fetch_assoc($logs)) {
    $query2 = "SELECT * FROM users WHERE name = \'" . $row["data"] . "\'";
    mysqli_query($connection, $query2);
}
?>'
            ],
            [
                'name' => 'SQL Injection - Prepared Statement (Safe)',
                'type' => 'SQL Injection - Safe',
                'severity' => 'None',
                'code' => '<?php
$username = $_GET["username"];
$stmt = $connection->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
?>'
            ],
            [
                'name' => 'SQL Injection - Multiple Variables',
                'type' => 'SQL Injection',
                'severity' => 'Critical',
                'code' => '<?php
$first = $_GET["first"];
$last = $_GET["last"];
$query = "SELECT * FROM users WHERE firstname = \'" . $first . "\' AND lastname = \'" . $last . "\'";
mysqli_query($connection, $query);
?>'
            ],
            [
                'name' => 'SQL Injection - Cookie Based',
                'type' => 'SQL Injection',
                'severity' => 'High',
                'code' => '<?php
$session_id = $_COOKIE["session_id"];
$query = "SELECT * FROM sessions WHERE id = " . $session_id;
$result = mysqli_query($connection, $query);
?>'
            ],
            [
                'name' => 'SQL Injection - Search Query',
                'type' => 'SQL Injection',
                'severity' => 'Critical',
                'code' => '<?php
$search = $_GET["q"];
$query = "SELECT * FROM articles WHERE title LIKE \'%' . $search . '%\'";
mysqli_query($connection, $query);
?>'
            ],
            [
                'name' => 'SQL Injection - ORDER BY',
                'type' => 'SQL Injection',
                'severity' => 'High',
                'code' => '<?php
$sort = $_GET["sort"];
$query = "SELECT * FROM products ORDER BY " . $sort;
mysqli_query($connection, $query);
?>'
            ],
            [
                'name' => 'SQL Injection - UNION Based',
                'type' => 'SQL Injection',
                'severity' => 'Critical',
                'code' => '<?php
$id = $_GET["id"];
$query = "SELECT name, price FROM products WHERE id = " . $id;
$query .= " UNION SELECT username, password FROM users";
mysqli_query($connection, $query);
?>'
            ]
        ];
    }

    /**
     * Cross-Site Scripting (XSS) Samples (10+)
     */
    public static function getXSSSamples() {
        return [
            [
                'name' => 'XSS - Reflected in HTML',
                'type' => 'Cross-Site Scripting',
                'severity' => 'High',
                'code' => '<?php
$name = $_GET["name"];
echo "<h1>Hello, " . $name . "</h1>";
?>'
            ],
            [
                'name' => 'XSS - Stored in Database',
                'type' => 'Cross-Site Scripting',
                'severity' => 'High',
                'code' => '<?php
$comment = $_POST["comment"];
$query = "INSERT INTO comments (text) VALUES (\'" . $comment . "\')";
mysqli_query($connection, $query);

$comments = mysqli_query($connection, "SELECT text FROM comments");
while ($row = mysqli_fetch_assoc($comments)) {
    echo $row["text"];
}
?>'
            ],
            [
                'name' => 'XSS - DOM-based',
                'type' => 'Cross-Site Scripting',
                'severity' => 'High',
                'code' => '<?php
$data = $_GET["data"];
echo "<script>var data = \'" . $data . "\';</script>";
?>'
            ],
            [
                'name' => 'XSS - Safe with htmlspecialchars',
                'type' => 'Cross-Site Scripting - Safe',
                'severity' => 'None',
                'code' => '<?php
$name = $_GET["name"];
echo "<h1>Hello, " . htmlspecialchars($name, ENT_QUOTES) . "</h1>";
?>'
            ],
            [
                'name' => 'XSS - Multiple Input Points',
                'type' => 'Cross-Site Scripting',
                'severity' => 'High',
                'code' => '<?php
$title = $_POST["title"];
$content = $_POST["content"];
echo "<div>";
echo "<h1>" . $title . "</h1>";
echo "<p>" . $content . "</p>";
echo "</div>";
?>'
            ],
            [
                'name' => 'XSS - In Attribute',
                'type' => 'Cross-Site Scripting',
                'severity' => 'High',
                'code' => '<?php
$url = $_GET["url"];
echo "<a href=\'" . $url . "\'>Link</a>";
?>'
            ],
            [
                'name' => 'XSS - JSON Context',
                'type' => 'Cross-Site Scripting',
                'severity' => 'High',
                'code' => '<?php
$data = $_GET["data"];
echo "<script>var json = " . json_encode($data) . ";</script>";
?>'
            ],
            [
                'name' => 'XSS - Cookie Based',
                'type' => 'Cross-Site Scripting',
                'severity' => 'Medium',
                'code' => '<?php
$username = $_COOKIE["username"];
echo "Welcome back, " . $username;
?>'
            ],
            [
                'name' => 'XSS - With User Function',
                'type' => 'Cross-Site Scripting',
                'severity' => 'High',
                'code' => '<?php
function displayUser($id) {
    $user = getUser($id);
    echo "<p>" . $user["bio"] . "</p>";
}
$user_id = $_GET["id"];
displayUser($user_id);
?>'
            ],
            [
                'name' => 'XSS - File Upload Name',
                'type' => 'Cross-Site Scripting',
                'severity' => 'High',
                'code' => '<?php
$filename = $_FILES["upload"]["name"];
echo "<p>File uploaded: " . $filename . "</p>";
?>'
            ]
        ];
    }

    /**
     * Command Injection Samples (5+)
     */
    public static function getCommandInjectionSamples() {
        return [
            [
                'name' => 'Command Injection - exec()',
                'type' => 'Command Injection',
                'severity' => 'Critical',
                'code' => '<?php
$filename = $_GET["file"];
$output = exec("cat " . $filename);
echo $output;
?>'
            ],
            [
                'name' => 'Command Injection - system()',
                'type' => 'Command Injection',
                'severity' => 'Critical',
                'code' => '<?php
$hostname = $_POST["host"];
system("ping -c 1 " . $hostname);
?>'
            ],
            [
                'name' => 'Command Injection - passthru()',
                'type' => 'Command Injection',
                'severity' => 'Critical',
                'code' => '<?php
$username = $_GET["user"];
passthru("id " . $username);
?>'
            ],
            [
                'name' => 'Command Injection - backticks',
                'type' => 'Command Injection',
                'severity' => 'Critical',
                'code' => '<?php
$dir = $_GET["dir"];
$files = `ls -la ' . $dir . '`;
echo $files;
?>'
            ],
            [
                'name' => 'Command Injection - Safe with escapeshellarg',
                'type' => 'Command Injection - Safe',
                'severity' => 'None',
                'code' => '<?php
$hostname = $_POST["host"];
system("ping -c 1 " . escapeshellarg($hostname));
?>'
            ]
        ];
    }

    /**
     * Path Traversal Samples (5+)
     */
    public static function getPathTraversalSamples() {
        return [
            [
                'name' => 'Path Traversal - File Read',
                'type' => 'Path Traversal',
                'severity' => 'High',
                'code' => '<?php
$file = $_GET["file"];
$content = file_get_contents("/var/www/files/" . $file);
echo $content;
?>'
            ],
            [
                'name' => 'Path Traversal - File Inclusion',
                'type' => 'Path Traversal',
                'severity' => 'Critical',
                'code' => '<?php
$page = $_GET["page"];
include("/templates/" . $page . ".php");
?>'
            ],
            [
                'name' => 'Path Traversal - Directory Download',
                'type' => 'Path Traversal',
                'severity' => 'High',
                'code' => '<?php
$dir = $_GET["dir"];
$files = scandir("/uploads/" . $dir);
foreach ($files as $file) {
    echo $file;
}
?>'
            ],
            [
                'name' => 'Path Traversal - With Basename (Safe)',
                'type' => 'Path Traversal - Safe',
                'severity' => 'None',
                'code' => '<?php
$file = $_GET["file"];
$safe_file = basename($file);
$content = file_get_contents("/var/www/files/" . $safe_file);
echo $content;
?>'
            ],
            [
                'name' => 'Path Traversal - Symlink Attack',
                'type' => 'Path Traversal',
                'severity' => 'High',
                'code' => '<?php
$link = $_POST["link"];
symlink("/etc/passwd", "/var/www/uploads/" . $link);
?>'
            ]
        ];
    }

    /**
     * Insecure Deserialization Samples (5+)
     */
    public static function getInsecureDeserializationSamples() {
        return [
            [
                'name' => 'Insecure Deserialization - unserialize()',
                'type' => 'Insecure Deserialization',
                'severity' => 'Critical',
                'code' => '<?php
$data = $_POST["data"];
$object = unserialize($data);
$object->execute();
?>'
            ],
            [
                'name' => 'Insecure Deserialization - Cookie',
                'type' => 'Insecure Deserialization',
                'severity' => 'Critical',
                'code' => '<?php
$user = unserialize($_COOKIE["user"]);
echo $user->name;
?>'
            ],
            [
                'name' => 'Insecure Deserialization - Session',
                'type' => 'Insecure Deserialization',
                'severity' => 'High',
                'code' => '<?php
$session_data = $_SESSION["data"];
$config = unserialize($session_data);
?>
'
            ],
            [
                'name' => 'Insecure Deserialization - Safe with json_decode',
                'type' => 'Insecure Deserialization - Safe',
                'severity' => 'None',
                'code' => '<?php
$data = $_POST["data"];
$object = json_decode($data);
echo $object->name;
?>'
            ],
            [
                'name' => 'Insecure Deserialization - File Based',
                'type' => 'Insecure Deserialization',
                'severity' => 'Critical',
                'code' => '<?php
$filename = $_GET["file"];
$content = file_get_contents("/var/www/cache/" . $filename);
$data = unserialize($content);
?>'
            ]
        ];
    }

    /**
     * Safe Code Samples (10+)
     */
    public static function getSafeSamples() {
        return [
            [
                'name' => 'Safe - Using htmlspecialchars',
                'type' => 'Safe Code',
                'severity' => 'None',
                'code' => '<?php
$name = $_GET["name"];
$safe_name = htmlspecialchars($name, ENT_QUOTES, "UTF-8");
echo "<p>Hello, " . $safe_name . "</p>";
?>'
            ],
            [
                'name' => 'Safe - Using Prepared Statements',
                'type' => 'Safe Code',
                'severity' => 'None',
                'code' => '<?php
$email = $_POST["email"];
$stmt = $connection->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
?>'
            ],
            [
                'name' => 'Safe - Input Validation',
                'type' => 'Safe Code',
                'severity' => 'None',
                'code' => '<?php
$age = $_POST["age"];
if (!is_numeric($age) || $age < 0 || $age > 150) {
    die("Invalid age");
}
$query = "SELECT * FROM users WHERE age = " . $age;
mysqli_query($connection, $query);
?>'
            ],
            [
                'name' => 'Safe - Using escapeshellarg',
                'type' => 'Safe Code',
                'severity' => 'None',
                'code' => '<?php
$hostname = $_POST["host"];
$output = shell_exec("ping -c 1 " . escapeshellarg($hostname));
echo $output;
?>'
            ],
            [
                'name' => 'Safe - Whitelist Validation',
                'type' => 'Safe Code',
                'severity' => 'None',
                'code' => '<?php
$action = $_GET["action"];
$allowed = ["create", "update", "delete"];
if (!in_array($action, $allowed)) {
    die("Invalid action");
}
?>'
            ],
            [
                'name' => 'Safe - Using json_decode',
                'type' => 'Safe Code',
                'severity' => 'None',
                'code' => '<?php
$data = $_POST["data"];
$object = json_decode($data);
if ($object === null) {
    die("Invalid JSON");
}
echo $object->name;
?>'
            ],
            [
                'name' => 'Safe - Type Casting',
                'type' => 'Safe Code',
                'severity' => 'None',
                'code' => '<?php
$id = (int)$_GET["id"];
$query = "SELECT * FROM products WHERE id = " . $id;
mysqli_query($connection, $query);
?>'
            ],
            [
                'name' => 'Safe - Constant Files',
                'type' => 'Safe Code',
                'severity' => 'None',
                'code' => '<?php
define("ALLOWED_FILES", ["config.php", "database.php"]);
$file = $_GET["file"];
if (!in_array($file, ALLOWED_FILES)) {
    die("File not allowed");
}
include($file);
?>'
            ],
            [
                'name' => 'Safe - Using filter_var',
                'type' => 'Safe Code',
                'severity' => 'None',
                'code' => '<?php
$email = $_POST["email"];
if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $query = "SELECT * FROM users WHERE email = ?";
} else {
    die("Invalid email");
}
?>'
            ],
            [
                'name' => 'Safe - Using filter_input',
                'type' => 'Safe Code',
                'severity' => 'None',
                'code' => '<?php
$url = filter_input(INPUT_GET, "url", FILTER_VALIDATE_URL);
if ($url === false) {
    die("Invalid URL");
}
echo $url;
?>'
            ]
        ];
    }

    /**
     * Get sample by name
     */
    public static function getSampleByName($name) {
        $samples = self::getAllSamples();
        foreach ($samples as $sample) {
            if ($sample['name'] === $name) {
                return $sample;
            }
        }
        return null;
    }
}
?>
