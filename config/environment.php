<?php
/**
 * Environment Configuration Loader
 * Loads settings from .env file or uses defaults
 */

class Environment {
    private static $config = [];
    
    public static function load($path = null) {
        if ($path === null) {
            $path = dirname(__DIR__) . '/.env';
        }
        
        // Default configuration
        self::$config = [
            // Database
            'DB_HOST' => 'localhost',
            'DB_USER' => 'root',
            'DB_PASS' => '1234',
            'DB_NAME' => 'clinical_physiology_academy',
            
            // Site
            'SITE_NAME' => 'Clinical Physiology Academy',
            'SITE_URL' => 'http://localhost/cpa/',
            'ADMIN_EMAIL' => 'admin@clinicalphysiologyacademy.com',
            
            // Security
            'SECRET_KEY' => 'development-secret-key-change-in-production',
            'DEBUG_MODE' => true,
            
            // File Uploads
            'MAX_FILE_SIZE' => 5242880, // 5MB
            'ALLOWED_IMAGE_TYPES' => ['jpg', 'jpeg', 'png', 'gif'],
            'UPLOAD_PATH' => 'assets/uploads/',
        ];
        
        // Load from .env file if exists
        if (file_exists($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                // Skip comments
                if (strpos(trim($line), '#') === 0) continue;
                
                // Parse key=value pairs
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Handle array values (comma-separated)
                if (strpos($value, ',') !== false) {
                    $value = array_map('trim', explode(',', $value));
                }
                
                self::$config[$key] = $value;
            }
        }
        
        // Define constants for backward compatibility
        foreach (self::$config as $key => $value) {
            if (!defined($key)) {
                define($key, $value);
            }
        }
         
        // Set error reporting based on debug mode
        if (self::get('DEBUG_MODE')) {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        } else {
            error_reporting(0);
            ini_set('display_errors', 0);
        }
        
        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public static function get($key, $default = null) {
        return self::$config[$key] ?? $default;
    }
}

// Auto-load environment on include
Environment::load();
?>