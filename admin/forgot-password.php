<?php
require_once '../config/environment.php';
require_once '../includes/core/Database.php';
require_once '../includes/core/Functions.php';

use includes\core\Database;

// Check if already logged in
session_start();
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit();
}

$db = Database::getInstance();
$message = '';
$error = '';

// Generate random token
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Send reset email (simplified - implement actual email sending)
function sendResetEmail($email, $token) {
    $resetLink = SITE_URL . 'admin/reset-password.php?token=' . $token;
    
    $subject = "Password Reset Request - Clinical Physiology Academy";
    $message = "Hello,\n\n";
    $message .= "You have requested to reset your password for the CPA Admin Panel.\n\n";
    $message .= "Please click the following link to reset your password:\n";
    $message .= $resetLink . "\n\n";
    $message .= "This link will expire in 1 hour.\n\n";
    $message .= "If you did not request this, please ignore this email.\n\n";
    $message .= "Best regards,\n";
    $message .= "Clinical Physiology Academy Team";
    
    $headers = "From: " . ADMIN_EMAIL . "\r\n";
    $headers .= "Reply-To: " . ADMIN_EMAIL . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    // In production, use a proper mailer like PHPMailer
    return mail($email, $subject, $message, $headers);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } elseif (!CPAFunctions::isValidEmail($email)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if admin exists with this email
        $admin = $db->fetchOne(
            "SELECT id, username, email FROM admins WHERE email = ?",
            [$email]
        );
        
        if ($admin) {
            // Generate reset token
            $token = generateToken();
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token in database
            $db->insert('password_resets', [
                'email' => $email,
                'token' => $token,
                'expires_at' => $expires,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Send reset email
            if (sendResetEmail($email, $token)) {
                $message = 'Password reset instructions have been sent to your email.';
            } else {
                $error = 'Failed to send reset email. Please try again.';
            }
        } else {
            // For security, don't reveal if email exists
            $message = 'If your email exists in our system, you will receive reset instructions.';
        }
    }
}

$pageTitle = "Forgot Password";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> | CPA Admin</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .forgot-container {
            max-width: 450px;
            width: 100%;
        }
        
        .forgot-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .forgot-header {
            background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        
        .forgot-body {
            padding: 40px;
        }
        
        .brand-logo {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: white;
        }
        
        .btn-reset {
            background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
            border: none;
            padding: 12px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(13, 110, 253, 0.2);
        }
        
        .forgot-footer {
            text-align: center;
            padding: 20px;
            border-top: 1px solid #dee2e6;
            background-color: #f8f9fa;
        }
        
        .back-to-login {
            color: #6c757d;
            text-decoration: none;
        }
        
        .back-to-login:hover {
            color: #0d6efd;
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="forgot-card">
            <!-- Header -->
            <div class="forgot-header">
                <div class="brand-logo">
                    <i class="fas fa-key"></i>
                </div>
                <h2 class="h4 mb-0">Reset Password</h2>
                <p class="mb-0 opacity-75">Clinical Physiology Academy Admin</p>
            </div>
            
            <!-- Body -->
            <div class="forgot-body">
                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <p class="text-muted mb-4">
                    Enter your email address and we'll send you instructions to reset your password.
                </p>
                
                <form method="POST" action="" id="forgotForm">
                    <div class="mb-4">
                        <label for="email" class="form-label fw-bold">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   placeholder="Enter your admin email address" 
                                   required
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-reset btn-lg">
                            <i class="fas fa-paper-plane me-2"></i> Send Reset Instructions
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Footer -->
            <div class="forgot-footer">
                <a href="login.php" class="back-to-login">
                    <i class="fas fa-arrow-left me-1"></i> Back to Login
                </a>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('forgotForm');
            const emailInput = document.getElementById('email');
            
            // Focus on email field
            emailInput.focus();
            
            // Form validation
            form.addEventListener('submit', function(e) {
                const email = emailInput.value.trim();
                
                if (!email) {
                    e.preventDefault();
                    alert('Please enter your email address.');
                    emailInput.focus();
                    return false;
                }
                
                // Simple email validation
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    e.preventDefault();
                    alert('Please enter a valid email address.');
                    emailInput.focus();
                    return false;
                }
            });
        });
    </script>
</body>
</html>