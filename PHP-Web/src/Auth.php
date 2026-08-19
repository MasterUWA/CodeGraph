<?php
namespace PhpGraphBuilder;

class Auth {
    private $db;

    public function __construct() {
        try {
            require_once __DIR__ . '/../config/database.php';
            if (!class_exists('Database')) {
                throw new \RuntimeException('Database class is not available');
            }
            $this->db = \Database::getInstance()->getConnection();
        } catch (\Throwable $e) {
            // Database not available - demo mode
            $this->db = null;
        }
    }

    /**
     * Register a new user
     */
    public function register($username, $email, $password) {
        if (!$this->db) {
            return ['success' => false, 'error' => 'Database not available'];
        }

        // Validate input
        if (strlen($username) < 3) {
            return ['success' => false, 'error' => 'Username must be at least 3 characters'];
        }
        if (strlen($password) < 6) {
            return ['success' => false, 'error' => 'Password must be at least 6 characters'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid email format'];
        }

        try {
            // Check if user exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                return ['success' => false, 'error' => 'Username or email already exists'];
            }

            // Create user
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $this->db->prepare("
                INSERT INTO users (username, email, password_hash, role, is_active)
                VALUES (?, ?, ?, 'analyst', 1)
            ");
            $stmt->execute([$username, $email, $hashedPassword]);

            return ['success' => true, 'message' => 'User registered successfully'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Login user
     */
    public function login($username, $password) {
        if (!$this->db) {
            return ['success' => false, 'error' => 'Database not available'];
        }

        try {
            $stmt = $this->db->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ? AND is_active = 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                return ['success' => false, 'error' => 'Invalid username or password'];
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            return ['success' => true, 'message' => 'Login successful'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Logout user
     */
    public function logout() {
        session_destroy();
        return ['success' => true];
    }

    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    /**
     * Get current user
     */
    public static function getCurrentUser() {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Get current username
     */
    public static function getCurrentUsername() {
        return $_SESSION['username'] ?? null;
    }

    /**
     * Check if user is admin
     */
    public static function isAdmin() {
        return ($_SESSION['role'] ?? '') === 'admin';
    }

    /**
     * Require authentication
     */
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: /php-graph-builder/');
            exit;
        }
    }
}
?>
