<?php
/**
 * Helper Functions
 * Commonly used functions throughout the application
 */

require_once dirname(__DIR__, 2) . '/config/environment.php';

class CPAFunctions {
    
    // Generate URL
    public static function url($path = '') {
        return SITE_URL . ltrim($path, '/');
    }
    
    // Generate asset URL
    public static function asset($path) {
        return self::url('assets/' . ltrim($path, '/'));
    }
    
    // Create SEO-friendly slug
    public static function createSlug($string) {
        $slug = strtolower(trim($string));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-');
    }
    
    // Format price
    public static function formatPrice($price) {
        if (is_null($price) || $price == 0) return 'Free';
        return '₦' . number_format($price, 2); // Nigerian Naira
    }
    
    // Get status badge HTML
    public static function getStatusBadge($status) {
        $badges = [
            'draft' => ['color' => 'secondary', 'text' => 'Draft'],
            'upcoming' => ['color' => 'info', 'text' => 'Upcoming'],
            'active' => ['color' => 'success', 'text' => 'Active'],
            'completed' => ['color' => 'dark', 'text' => 'Completed']
        ];
        
        $statusData = $badges[$status] ?? ['color' => 'secondary', 'text' => $status];
        return '<span class="badge bg-' . $statusData['color'] . '">' . $statusData['text'] . '</span>';
    }
    
    // Truncate text
    public static function truncate($text, $length = 100, $ellipsis = '...') {
        if (strlen($text) <= $length) return $text;
        return substr($text, 0, $length) . $ellipsis;
    }
    
    // Format date
    public static function formatDate($date, $format = 'F j, Y') {
        if (empty($date)) return 'Not set';
        $timestamp = strtotime($date);
        return date($format, $timestamp);
    }
    
    // Safe output (prevents XSS)
    public static function safeOutput($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
    
    // Redirect with message
    public static function redirect($url, $message = null, $type = 'success') {
        if ($message) {
            $_SESSION['flash_message'] = [
                'text' => $message,
                'type' => $type
            ];
        }
        header('Location: ' . $url);
        exit();
    }
    
    // Get flash message
    public static function getFlashMessage() {
        if (isset($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);
            return $message;
        }
        return null;
    }
    
    // Validate email
    public static function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    // Upload file
    public static function uploadFile($file, $targetDir, $allowedTypes = null) {
        if ($allowedTypes === null) {
            $allowedTypes = ALLOWED_IMAGE_TYPES;
        }
        
        if (!is_array($allowedTypes)) {
            $allowedTypes = explode(',', $allowedTypes);
        }
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error: ' . $file['error']);
        }
        
        // Check file size
        if ($file['size'] > MAX_FILE_SIZE) {
            throw new Exception('File is too large. Maximum size: ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB');
        }
        
        // Get file extension
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Check file type
        if (!in_array($fileExt, $allowedTypes)) {
            throw new Exception('File type not allowed. Allowed types: ' . implode(', ', $allowedTypes));
        }
        
        // Create target directory if it doesn't exist
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        // Generate unique filename
        $filename = uniqid() . '_' . time() . '.' . $fileExt;
        $targetPath = $targetDir . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception('Failed to move uploaded file.');
        }
        
        return $filename;
    }
}
?>