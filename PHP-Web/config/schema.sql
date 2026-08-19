-- ============================================
-- CodeGraph Database Schema
-- ============================================

CREATE DATABASE IF NOT EXISTS `codegraph` 
    DEFAULT CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

USE `codegraph`;

-- ============================================
-- Users Table (for future authentication)
-- ============================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `api_key` VARCHAR(64) UNIQUE,
    `role` ENUM('admin', 'analyst', 'viewer') DEFAULT 'analyst',
    `is_active` TINYINT(1) DEFAULT 1,
    `last_login` DATETIME,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_email` (`email`),
    INDEX `idx_api_key` (`api_key`)
) ENGINE=InnoDB;

-- ============================================
-- Projects Table
-- ============================================
CREATE TABLE IF NOT EXISTS `projects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `status` ENUM('active', 'archived', 'deleted') DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB;

-- ============================================
-- PHP Files Table
-- ============================================
CREATE TABLE IF NOT EXISTS `php_files` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT,
    `filename` VARCHAR(255) NOT NULL,
    `filepath` VARCHAR(500),
    `file_size` INT,
    `file_hash` VARCHAR(64),
    `code_content` LONGTEXT NOT NULL,
    `lines_of_code` INT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
    INDEX `idx_project_id` (`project_id`),
    INDEX `idx_file_hash` (`file_hash`),
    FULLTEXT `ft_code_content` (`code_content`)
) ENGINE=InnoDB;

-- ============================================
-- Graph Analysis Results
-- ============================================
CREATE TABLE IF NOT EXISTS `graph_analyses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `file_id` INT NOT NULL,
    `analysis_type` ENUM('ast', 'cfg', 'dfg', 'joint') DEFAULT 'joint',
    `graph_json` LONGTEXT NOT NULL,
    `node_count` INT DEFAULT 0,
    `edge_count` INT DEFAULT 0,
    `complexity_score` DECIMAL(5,2),
    `execution_time_ms` INT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`file_id`) REFERENCES `php_files`(`id`) ON DELETE CASCADE,
    INDEX `idx_file_id` (`file_id`),
    INDEX `idx_analysis_type` (`analysis_type`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB;

-- ============================================
-- Vulnerabilities Found
-- ============================================
CREATE TABLE IF NOT EXISTS `vulnerabilities` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `analysis_id` INT NOT NULL,
    `vulnerability_type` VARCHAR(50) NOT NULL,
    `severity` ENUM('critical', 'high', 'medium', 'low', 'info') DEFAULT 'medium',
    `cwe_id` VARCHAR(20),
    `description` TEXT,
    `source_node_id` VARCHAR(50),
    `sink_node_id` VARCHAR(50),
    `taint_path` JSON,
    `line_number` INT,
    `code_snippet` TEXT,
    `remediation` TEXT,
    `is_false_positive` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`analysis_id`) REFERENCES `graph_analyses`(`id`) ON DELETE CASCADE,
    INDEX `idx_analysis_id` (`analysis_id`),
    INDEX `idx_severity` (`severity`),
    INDEX `idx_vuln_type` (`vulnerability_type`),
    INDEX `idx_cwe_id` (`cwe_id`)
) ENGINE=InnoDB;

-- ============================================
-- Graph Nodes
-- ============================================
CREATE TABLE IF NOT EXISTS `graph_nodes` (
    `id` VARCHAR(50) PRIMARY KEY,
    `analysis_id` INT NOT NULL,
    `node_type` VARCHAR(100) NOT NULL,
    `node_label` VARCHAR(255),
    `code_snippet` TEXT,
    `line_number` INT,
    `layer` ENUM('ast', 'cfg', 'dfg') DEFAULT 'ast',
    `is_source` TINYINT(1) DEFAULT 0,
    `is_sink` TINYINT(1) DEFAULT 0,
    `is_sanitizer` TINYINT(1) DEFAULT 0,
    `properties` JSON,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`analysis_id`) REFERENCES `graph_analyses`(`id`) ON DELETE CASCADE,
    INDEX `idx_analysis_id` (`analysis_id`),
    INDEX `idx_node_type` (`node_type`),
    INDEX `idx_layer` (`layer`)
) ENGINE=InnoDB;

-- ============================================
-- Graph Edges
-- ============================================
CREATE TABLE IF NOT EXISTS `graph_edges` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `analysis_id` INT NOT NULL,
    `source_node_id` VARCHAR(50) NOT NULL,
    `target_node_id` VARCHAR(50) NOT NULL,
    `edge_type` VARCHAR(50) NOT NULL,
    `edge_label` VARCHAR(100),
    `weight` DECIMAL(5,2) DEFAULT 1.00,
    `properties` JSON,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`analysis_id`) REFERENCES `graph_analyses`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`source_node_id`) REFERENCES `graph_nodes`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`target_node_id`) REFERENCES `graph_nodes`(`id`) ON DELETE CASCADE,
    INDEX `idx_analysis_id` (`analysis_id`),
    INDEX `idx_edge_type` (`edge_type`),
    INDEX `idx_source_node` (`source_node_id`),
    INDEX `idx_target_node` (`target_node_id`)
) ENGINE=InnoDB;

-- ============================================
-- Analysis History (Audit Log)
-- ============================================
CREATE TABLE IF NOT EXISTS `analysis_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT,
    `file_id` INT,
    `analysis_id` INT,
    `action` VARCHAR(50) NOT NULL,
    `details` JSON,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`file_id`) REFERENCES `php_files`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`analysis_id`) REFERENCES `graph_analyses`(`id`) ON DELETE SET NULL,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_file_id` (`file_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB;

-- ============================================
-- Vulnerability Patterns (ML Training Data)
-- ============================================
CREATE TABLE IF NOT EXISTS `vulnerability_patterns` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `pattern_name` VARCHAR(100) NOT NULL,
    `vulnerability_type` VARCHAR(50) NOT NULL,
    `pattern_type` ENUM('source', 'sink', 'sanitizer', 'propagation') NOT NULL,
    `pattern_code` VARCHAR(500) NOT NULL,
    `pattern_regex` VARCHAR(500),
    `is_active` TINYINT(1) DEFAULT 1,
    `confidence_score` DECIMAL(3,2) DEFAULT 1.00,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_vuln_type` (`vulnerability_type`),
    INDEX `idx_pattern_type` (`pattern_type`)
) ENGINE=InnoDB;

-- ============================================
-- Insert Default Patterns
-- ============================================
INSERT INTO `vulnerability_patterns` (`pattern_name`, `vulnerability_type`, `pattern_type`, `pattern_code`) VALUES
('GET Parameter', 'sqli', 'source', '$_GET'),
('POST Parameter', 'sqli', 'source', '$_POST'),
('REQUEST Parameter', 'sqli', 'source', '$_REQUEST'),
('COOKIE Value', 'sqli', 'source', '$_COOKIE'),
('mysqli_query', 'sqli', 'sink', 'mysqli_query'),
('mysql_query', 'sqli', 'sink', 'mysql_query'),
('mysqli_real_escape_string', 'sqli', 'sanitizer', 'mysqli_real_escape_string'),
('prepared_statement', 'sqli', 'sanitizer', 'mysqli_prepare'),
('echo statement', 'xss', 'sink', 'echo'),
('print statement', 'xss', 'sink', 'print'),
('htmlspecialchars', 'xss', 'sanitizer', 'htmlspecialchars'),
('shell_exec', 'command_injection', 'sink', 'shell_exec'),
('exec', 'command_injection', 'sink', 'exec'),
('system', 'command_injection', 'sink', 'system'),
('unserialize', 'object_injection', 'sink', 'unserialize'),
('include', 'file_inclusion', 'sink', 'include'),
('require', 'file_inclusion', 'sink', 'require');

-- ============================================
-- Stored Procedures
-- ============================================

DELIMITER //

-- Get analysis summary
CREATE PROCEDURE `GetAnalysisSummary`(IN p_analysis_id INT)
BEGIN
    SELECT 
        ga.*,
        pf.filename,
        COUNT(DISTINCT gn.id) as total_nodes,
        COUNT(DISTINCT ge.id) as total_edges,
        COUNT(DISTINCT v.id) as total_vulns
    FROM graph_analyses ga
    JOIN php_files pf ON ga.file_id = pf.id
    LEFT JOIN graph_nodes gn ON ga.id = gn.analysis_id
    LEFT JOIN graph_edges ge ON ga.id = ge.analysis_id
    LEFT JOIN vulnerabilities v ON ga.id = v.analysis_id
    WHERE ga.id = p_analysis_id
    GROUP BY ga.id;
END //

-- Get vulnerability statistics
CREATE PROCEDURE `GetVulnerabilityStats`(IN p_days INT)
BEGIN
    SELECT 
        vulnerability_type,
        severity,
        COUNT(*) as count,
        DATE(created_at) as analysis_date
    FROM vulnerabilities
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL p_days DAY)
    GROUP BY vulnerability_type, severity, analysis_date
    ORDER BY analysis_date DESC, severity;
END //

-- Search for vulnerable patterns in code
CREATE PROCEDURE `SearchVulnerableCode`(IN p_search_term VARCHAR(255))
BEGIN
    SELECT 
        pf.filename,
        pf.code_content,
        v.vulnerability_type,
        v.severity,
        v.line_number,
        v.code_snippet
    FROM php_files pf
    JOIN graph_analyses ga ON pf.id = ga.file_id
    JOIN vulnerabilities v ON ga.id = v.analysis_id
    WHERE MATCH(pf.code_content) AGAINST(p_search_term IN NATURAL LANGUAGE MODE)
    OR v.vulnerability_type LIKE CONCAT('%', p_search_term, '%')
    ORDER BY v.severity, pf.created_at DESC;
END //

DELIMITER ;

-- ============================================
-- Views
-- ============================================

-- Recent Analyses View
CREATE OR REPLACE VIEW `v_recent_analyses` AS
SELECT 
    ga.id as analysis_id,
    pf.filename,
    pf.lines_of_code,
    ga.node_count,
    ga.edge_count,
    ga.complexity_score,
    COUNT(v.id) as vulnerability_count,
    ga.created_at
FROM graph_analyses ga
JOIN php_files pf ON ga.file_id = pf.id
LEFT JOIN vulnerabilities v ON ga.id = v.analysis_id
GROUP BY ga.id
ORDER BY ga.created_at DESC;

-- Vulnerability Summary View
CREATE OR REPLACE VIEW `v_vulnerability_summary` AS
SELECT 
    vulnerability_type,
    severity,
    COUNT(*) as occurrence_count,
    COUNT(DISTINCT analysis_id) as affected_files,
    MIN(created_at) as first_detected,
    MAX(created_at) as last_detected
FROM vulnerabilities
GROUP BY vulnerability_type, severity
ORDER BY FIELD(severity, 'critical', 'high', 'medium', 'low', 'info');