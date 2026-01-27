<?php
/**
 * Database Configuration
 * This file initializes the database connection using mysqli
 */

require_once __DIR__ . '/environment.php';

class DatabaseConfig {
    public static function getConnection() {
        static $conn = null;
        
        if ($conn === null) {
            $conn = new mysqli(
                DB_HOST,
                DB_USER,
                DB_PASS,
                DB_NAME
            );
            
            // Check connection
            if ($conn->connect_error) {
                if (DEBUG_MODE) {
                    die("Connection failed: " . $conn->connect_error);
                } else {
                    error_log("Database connection error: " . $conn->connect_error);
                    die("Database connection error. Please try again later.");
                }
            }
            
            // Set charset to UTF-8
            $conn->set_charset("utf8mb4");
            
            // Set timezone if needed
            $conn->query("SET time_zone = '+00:00'");
        }
        
        return $conn;
    }
    
    public static function closeConnection() {
        $conn = self::getConnection();
        if ($conn) {
            $conn->close();
        }
    }
}

// Create global database connection
$conn = DatabaseConfig::getConnection();

// Register shutdown function to close connection
register_shutdown_function([DatabaseConfig::class, 'closeConnection']);
?>