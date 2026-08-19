<?php
/**
 * Database Setup Script
 * Creates necessary tables for CodeGraph
 */

require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Create users table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(50) NOT NULL UNIQUE,
            `email` VARCHAR(100) NOT NULL UNIQUE,
            `password_hash` VARCHAR(255) NOT NULL,
            `role` ENUM('admin', 'analyst', 'viewer') DEFAULT 'analyst',
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_email` (`email`)
        ) ENGINE=InnoDB;
    ");
    
    // Create analyses table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `analyses` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `code` LONGTEXT NOT NULL,
            `graph_json` LONGTEXT,
            `node_count` INT DEFAULT 0,
            `edge_count` INT DEFAULT 0,
            `vulnerability_detected` TINYINT(1) DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            INDEX `idx_user_id` (`user_id`),
            INDEX `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB;
    ");
    
    // Create vulnerabilities table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `vulnerabilities` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `analysis_id` INT NOT NULL,
            `type` VARCHAR(100) NOT NULL,
            `severity` ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
            `description` TEXT,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`analysis_id`) REFERENCES `analyses`(`id`) ON DELETE CASCADE,
            INDEX `idx_analysis_id` (`analysis_id`),
            INDEX `idx_type` (`type`)
        ) ENGINE=InnoDB;
    ");
    
    echo "✓ Database setup complete!\n";
    echo "  - users table created\n";
    echo "  - analyses table created\n";
    echo "  - vulnerabilities table created\n";
    
} catch (\Exception $e) {
    die("✗ Error: " . $e->getMessage() . "\n");
}
?>
