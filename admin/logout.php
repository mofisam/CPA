<?php
require_once '../config/environment.php';
require_once '../includes/core/Auth.php';


use includes\core\Auth;

// Logout admin
Auth::logout();

// Set success message
$_SESSION['flash_message'] = [
    'text' => 'You have been logged out successfully.',
    'type' => 'success'
];

// Redirect to login page
header('Location: login.php');
exit();
?>