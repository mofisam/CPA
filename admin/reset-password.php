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
$validToken = false;
$email = '';

// Check token
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    
    // Check if token exists and is valid
    $resetRequest = $db->fetchOne(
        "SELECT * FROM password_resets WHERE token = ? AND used_at IS NULL AND expires_at > NOW()",
        [$token]
    );
    
    if ($resetRequest) {
        $validToken = true;
        $email = $resetRequest['email'];
    } else {
        $error = 'Invalid or expired reset token. Please request a new password reset.';
    }
} else {
    $error = 'No reset token provided.';
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $token = $_POST['token'];
    
    // Validation
    if (empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all password fields.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Hash new password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $db->beginTransaction();
            
            // Update admin password
            $db->update('admins', 
                ['password_hash' => $hashedPassword], 
                'email = ?', 
                [$email]
            );
            
            // Mark token as used
            $db->update('password_resets', 
                ['used_at' => date('Y-m-d H:i:s')], 
                'token = ?', 
                [$token]
            );
            
            $db->commit();
            
            // Set success message
            $_SESSION['flash_message'] = [
                'text' => 'Password reset successfully! You can now login with your new password.',
                'type' => 'success'
            ];
            
            header('Location: login.php');
            exit();
            
        } catch (Exception $e) {
            $db->rollback();
            $error = 'Error resetting password: ' . $e->getMessage();
        }
    }
}

$pageTitle = "Reset Password";
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
        
        .reset-container {
            max-width: 450px;
            width: 100%;
        }
        
        .reset-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .reset-header {
            background: linear-gradient(135deg, #198754 0%, #157347 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        
        .reset-body {
            padding: 40px;
        }
        
        .brand-logo {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: white;
        }
        
        .btn-reset {
            background: linear-gradient(135deg, #198754 0%, #157347 100%);
            border: none;
            padding: 12px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(25, 135, 84, 0.2);
        }
        
        .password-strength {
            height: 5px;
            border-radius: 3px;
            margin-top: 5px;
            transition: all 0.3s;
        }
        
        .strength-weak {
            background-color: #dc3545;
            width: 25%;
        }
        
        .strength-medium {
            background-color: #ffc107;
            width: 50%;
        }
        
        .strength-good {
            background-color: #0dcaf0;
            width: 75%;
        }
        
        .strength-strong {
            background-color: #198754;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-card">
            <!-- Header -->
            <div class="reset-header">
                <div class="brand-logo">
                    <i class="fas fa-lock"></i>
                </div>
                <h2 class="h4 mb-0">Set New Password</h2>
                <p class="mb-0 opacity-75">Clinical Physiology Academy Admin</p>
            </div>
            
            <!-- Body -->
            <div class="reset-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($validToken): ?>
                    <p class="text-muted mb-4">
                        Please enter your new password below.
                    </p>
                    
                    <form method="POST" action="" id="resetForm">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token']); ?>">
                        
                        <div class="mb-4">
                            <label for="password" class="form-label fw-bold">New Password</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" 
                                       class="form-control" 
                                       id="password" 
                                       name="password" 
                                       placeholder="Enter new password (min 8 characters)" 
                                       required
                                       minlength="8">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div id="passwordStrength" class="password-strength"></div>
                            <div id="passwordFeedback" class="form-text"></div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label fw-bold">Confirm Password</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" 
                                       class="form-control" 
                                       id="confirm_password" 
                                       name="confirm_password" 
                                       placeholder="Confirm new password" 
                                       required
                                       minlength="8">
                                <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div id="passwordMatch" class="form-text"></div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-reset btn-lg">
                                <i class="fas fa-check me-2"></i> Reset Password
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <p class="mb-0"><?php echo htmlspecialchars($error); ?></p>
                        <div class="mt-3">
                            <a href="forgot-password.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-redo me-1"></i> Request New Reset
                            </a>
                            <a href="login.php" class="btn btn-secondary btn-sm ms-2">
                                <i class="fas fa-sign-in-alt me-1"></i> Back to Login
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const togglePasswordBtn = document.getElementById('togglePassword');
            const toggleConfirmPasswordBtn = document.getElementById('toggleConfirmPassword');
            const passwordStrength = document.getElementById('passwordStrength');
            const passwordFeedback = document.getElementById('passwordFeedback');
            const passwordMatch = document.getElementById('passwordMatch');
            const form = document.getElementById('resetForm');
            
            // Toggle password visibility
            togglePasswordBtn.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
            });
            
            toggleConfirmPasswordBtn.addEventListener('click', function() {
                const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                confirmPasswordInput.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
            });
            
            // Check password strength
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                let feedback = '';
                
                // Length check
                if (password.length >= 8) strength++;
                if (password.length >= 12) strength++;
                
                // Complexity checks
                if (/[A-Z]/.test(password)) strength++;
                if (/[a-z]/.test(password)) strength++;
                if (/[0-9]/.test(password)) strength++;
                if (/[^A-Za-z0-9]/.test(password)) strength++;
                
                // Update strength indicator
                passwordStrength.className = 'password-strength';
                
                if (password.length === 0) {
                    feedback = 'Enter a password';
                    passwordStrength.style.display = 'none';
                } else if (password.length < 8) {
                    feedback = 'Too short (min 8 characters)';
                    passwordStrength.className += ' strength-weak';
                } else if (strength <= 3) {
                    feedback = 'Weak password';
                    passwordStrength.className += ' strength-weak';
                } else if (strength <= 4) {
                    feedback = 'Medium password';
                    passwordStrength.className += ' strength-medium';
                } else if (strength <= 5) {
                    feedback = 'Good password';
                    passwordStrength.className += ' strength-good';
                } else {
                    feedback = 'Strong password';
                    passwordStrength.className += ' strength-strong';
                }
                
                passwordFeedback.textContent = feedback;
                passwordStrength.style.display = 'block';
                
                // Check password match
                checkPasswordMatch();
            });
            
            // Check password match
            confirmPasswordInput.addEventListener('input', checkPasswordMatch);
            
            function checkPasswordMatch() {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                
                if (confirmPassword.length === 0) {
                    passwordMatch.textContent = '';
                    passwordMatch.className = 'form-text';
                } else if (password === confirmPassword) {
                    passwordMatch.textContent = '✓ Passwords match';
                    passwordMatch.className = 'form-text text-success';
                } else {
                    passwordMatch.textContent = '✗ Passwords do not match';
                    passwordMatch.className = 'form-text text-danger';
                }
            }
            
            // Form validation
            form.addEventListener('submit', function(e) {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                
                if (!password || !confirmPassword) {
                    e.preventDefault();
                    alert('Please fill in all password fields.');
                    return false;
                }
                
                if (password.length < 8) {
                    e.preventDefault();
                    alert('Password must be at least 8 characters long.');
                    passwordInput.focus();
                    return false;
                }
                
                if (password !== confirmPassword) {
                    e.preventDefault();
                    alert('Passwords do not match.');
                    confirmPasswordInput.focus();
                    return false;
                }
            });
            
            // Focus on password field
            if (passwordInput) {
                passwordInput.focus();
            }
        });
    </script>
</body>
</html>