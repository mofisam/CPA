<?php
/**
 * Authentication Functions
 * Handles admin authentication and authorization
 */

namespace includes\core;
class Auth {
    
    private static $db = null;
    
    // Get database instance
    private static function getDB() {
        if (self::$db === null) {
            require_once __DIR__ . '/Database.php';
            self::$db = Database::getInstance();
        }
        return self::$db;
    }
    
    // Check if admin is logged in
    public static function checkAdmin() {
        if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_logged_in'])) {
            return false;
        }
        
        // Additional checks can be added here
        return true;
    }
    
    // Require admin authentication
    public static function requireAdmin() {
        if (!self::checkAdmin()) {
            // Use relative path for redirection
            $baseUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
            header('Location: ' . $baseUrl . '/admin/login.php');
            exit();
        }
    }
    
    // Login admin
    public static function login($username, $password) {
        $db = self::getDB();
        
        $admin = $db->fetchOne(
            "SELECT id, username, password_hash, full_name, email FROM admins WHERE username = ?",
            [$username]
        );
        
        if ($admin && password_verify($password, $admin['password_hash'])) {
            // Set session variables
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_name'] = $admin['full_name'];
            $_SESSION['admin_logged_in'] = true;
            
            // Update last login
            $db->update('admins', 
                ['last_login' => date('Y-m-d H:i:s')], 
                'id = ?', 
                [$admin['id']]
            );
            
            return true;
        }
        
        return false;
    }
    
    // Logout admin
    public static function logout() {
        // Unset all session variables
        $_SESSION = [];
        
        // Destroy the session
        session_destroy();
    }
    
    // Get current admin info
    public static function getCurrentAdmin() {
        if (!self::checkAdmin()) {
            return null;
        }
        
        $db = self::getDB();
        return $db->fetchOne(
            "SELECT id, username, full_name, email, created_at, last_login FROM admins WHERE id = ?",
            [$_SESSION['admin_id']]
        );
    }
    
    // Change admin password
    public static function changePassword($currentPassword, $newPassword) {
        if (!self::checkAdmin()) {
            return false;
        }
        
        $db = self::getDB();
        $admin = self::getCurrentAdmin();
        
        // Verify current password
        $adminCheck = $db->fetchOne(
            "SELECT password_hash FROM admins WHERE id = ?",
            [$admin['id']]
        );
        
        if (!password_verify($currentPassword, $adminCheck['password_hash'])) {
            return false;
        }
        
        // Update password
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $db->update('admins', 
            ['password_hash' => $newHash], 
            'id = ?', 
            [$admin['id']]
        );
        
        return true;
    }
}
?>