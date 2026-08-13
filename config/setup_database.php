<?php
/**
 * Database Setup Script
 * Run this once to create all tables and initial data
 */

echo "🗄️ CodeGraph Database Setup\n";
echo "============================\n\n";

// Check if config exists
if (!file_exists(__DIR__ . '/database.php')) {
    die("❌ Error: database.php not found in config directory\n");
}

// Database configuration
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'codegraph';

try {
    // Connect without database
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "✅ Connected to MySQL server\n";
    
    // Read and execute schema
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    
    if ($schema === false) {
        die("❌ Error: Could not read schema.sql\n");
    }
    
    // Execute schema
    echo "📝 Creating database and tables...\n";
    $pdo->exec($schema);
    
    echo "✅ Database schema created successfully\n\n";
    
    // Verify tables
    $tables = $pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES 
                           WHERE TABLE_SCHEMA = '$dbname'")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "📊 Created tables:\n";
    foreach ($tables as $table) {
        echo "   - $table\n";
    }
    
    // Create default admin user (optional)
    echo "\n👤 Creating default admin user...\n";
    
    $defaultPassword = password_hash('admin123', PASSWORD_BCRYPT);
    $apiKey = bin2hex(random_bytes(32));
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO $dbname.users (username, email, password_hash, api_key, role) 
                           VALUES (?, ?, ?, ?, 'admin')");
    $stmt->execute(['admin', 'admin@codegraph.local', $defaultPassword, $apiKey]);
    
    echo "✅ Default user created (username: admin, password: admin123)\n";
    echo "🔑 API Key: $apiKey\n\n";

    // Create dummy users for development/demo
    echo "👥 Seeding dummy users...\n";
    $dummyUsers = [
        ['analyst1', 'analyst1@codegraph.local', 'analyst123', 'analyst'],
        ['analyst2', 'analyst2@codegraph.local', 'analyst123', 'analyst'],
        ['viewer1', 'viewer1@codegraph.local', 'viewer123', 'viewer'],
        ['viewer2', 'viewer2@codegraph.local', 'viewer123', 'viewer'],
        ['researcher', 'researcher@codegraph.local', 'research123', 'analyst']
    ];

    $seedStmt = $pdo->prepare("INSERT IGNORE INTO $dbname.users (username, email, password_hash, api_key, role, is_active) VALUES (?, ?, ?, ?, ?, 1)");
    $seededCount = 0;

    foreach ($dummyUsers as $dummyUser) {
        [$username, $email, $plainPassword, $role] = $dummyUser;
        $passwordHash = password_hash($plainPassword, PASSWORD_BCRYPT);
        $dummyApiKey = bin2hex(random_bytes(32));

        $seedStmt->execute([$username, $email, $passwordHash, $dummyApiKey, $role]);
        if ($seedStmt->rowCount() > 0) {
            $seededCount++;
        }
    }

    echo "✅ Dummy users seeded: $seededCount new record(s)\n";
    echo "   Sample credentials:\n";
    echo "   - analyst1 / analyst123\n";
    echo "   - viewer1 / viewer123\n";
    echo "   - researcher / research123\n\n";
    
    // Verify vulnerability patterns
    $patternCount = $pdo->query("SELECT COUNT(*) FROM $dbname.vulnerability_patterns")->fetchColumn();
    echo "🔍 Vulnerability patterns loaded: $patternCount\n\n";
    
    echo "🎉 Database setup complete!\n";
    echo "================================\n";
    echo "Database: $dbname\n";
    echo "Host: $host\n";
    echo "User: $user\n";
    echo "\nYou can now start using CodeGraph!\n";
    
} catch (PDOException $e) {
    die("❌ Database Error: " . $e->getMessage() . "\n");
}