<?php
/**
 * Admin Registration Page
 * This file will self-delete after successful registration
 * Use only once to create the first admin
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'clinical_physiology_academy');

// Start session
session_start();

// Check if file should exist
$lockFile = dirname(__FILE__) . '/.admin_created';
if (file_exists($lockFile)) {
    die('Admin already created. This page has been disabled.');
}

// Initialize variables
$errors = [];
$success = false;

// Create database connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// Check if admins table exists, create if not
$tableCheck = $conn->query("SHOW TABLES LIKE 'admins'");
if ($tableCheck->num_rows == 0) {
    // Create admins table
    $createTable = "
    CREATE TABLE admins (
        id INT(11) PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        email VARCHAR(100) NOT NULL,
        full_name VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL
    )";
    
    if (!$conn->query($createTable)) {
        die("Error creating admins table: " . $conn->error);
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    
    // Validation
    if (empty($username)) {
        $errors[] = 'Username is required';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    // Check if username already exists
    $stmt = $conn->prepare("SELECT id FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $errors[] = 'Username already exists';
    }
    $stmt->close();
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM admins WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $errors[] = 'Email already registered';
    }
    $stmt->close();
    
    // If no errors, create admin
    if (empty($errors)) {
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert admin
        $stmt = $conn->prepare("
            INSERT INTO admins (username, password_hash, email, full_name) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("ssss", $username, $password_hash, $email, $full_name);
        
        if ($stmt->execute()) {
            $success = true;
            
            // Create lock file to prevent future access
            file_put_contents($lockFile, 'Admin created on ' . date('Y-m-d H:i:s'));
            
            // Schedule file deletion after 5 seconds
            $_SESSION['delete_file'] = true;
        } else {
            $errors[] = 'Error creating admin: ' . $stmt->error;
        }
        $stmt->close();
    }
}

// Delete file if registration was successful in previous request
if (isset($_SESSION['delete_file']) && $_SESSION['delete_file'] === true) {
    unset($_SESSION['delete_file']);
    // Redirect first, then delete
    header('Refresh: 3; url=login.php');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Redirecting...</title></head><body>';
    echo '<p>Registration successful! Redirecting to login page...</p>';
    echo '</body></html>';
    
    // Delete this file after output
    register_shutdown_function(function() {
        unlink(__FILE__);
    });
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin Account</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .register-container {
            max-width: 500px;
            width: 100%;
        }
        
        .register-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .register-header {
            background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        
        .register-body {
            padding: 40px;
        }
        
        .brand-logo {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: white;
        }
        
        .btn-register {
            background: linear-gradient(135deg, #198754 0%, #157347 100%);
            border: none;
            padding: 12px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(25, 135, 84, 0.2);
        }
        
        .warning-box {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <!-- Header -->
            <div class="register-header">
                <div class="brand-logo">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h2 class="h4 mb-0">Create Admin Account</h2>
                <p class="mb-0 opacity-75">Clinical Physiology Academy</p>
            </div>
            
            <!-- Body -->
            <div class="register-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <h4 class="alert-heading"><i class="fas fa-check-circle me-2"></i> Success!</h4>
                        <p class="mb-0">Admin account created successfully!</p>
                        <hr>
                        <p class="mb-0">
                            This page will self-destruct in 3 seconds and redirect to login page.<br>
                            <strong>Please save these credentials:</strong>
                        </p>
                        <div class="mt-3 p-3 bg-light rounded">
                            <p class="mb-1"><strong>Username:</strong> <?php echo htmlspecialchars($username); ?></p>
                            <p class="mb-0"><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="warning-box">
                        <h5 class="text-warning"><i class="fas fa-exclamation-triangle me-2"></i> Important Notice</h5>
                        <p class="mb-0">
                            This page will <strong>self-delete</strong> after successful registration.<br>
                            Use it only once to create the first admin account.
                        </p>
                    </div>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <h5><i class="fas fa-exclamation-triangle me-2"></i> Please fix the following errors:</h5>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" id="registerForm">
                        <!-- Full Name -->
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="full_name" 
                                   name="full_name" 
                                   placeholder="Enter your full name"
                                   value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                        </div>
                        
                        <!-- Username -->
                        <div class="mb-3">
                            <label for="username" class="form-label">Username *</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="username" 
                                   name="username" 
                                   placeholder="Choose a username (min 3 characters)"
                                   required
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                            <div class="form-text">This will be used to login</div>
                        </div>
                        
                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   placeholder="Enter your email address"
                                   required
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                        
                        <!-- Password -->
                        <div class="mb-3">
                            <label for="password" class="form-label">Password *</label>
                            <div class="input-group">
                                <input type="password" 
                                       class="form-control" 
                                       id="password" 
                                       name="password" 
                                       placeholder="Enter password (min 8 characters)"
                                       required
                                       minlength="8">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Confirm Password -->
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Confirm Password *</label>
                            <div class="input-group">
                                <input type="password" 
                                       class="form-control" 
                                       id="confirm_password" 
                                       name="confirm_password" 
                                       placeholder="Confirm your password"
                                       required
                                       minlength="8">
                                <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-register btn-lg">
                                <i class="fas fa-user-plus me-2"></i> Create Admin Account
                            </button>
                        </div>
                        
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                After creation, use <a href="login.php">login.php</a> to access admin panel
                            </small>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle password visibility
            const togglePasswordBtn = document.getElementById('togglePassword');
            const toggleConfirmPasswordBtn = document.getElementById('toggleConfirmPassword');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            
            if (togglePasswordBtn) {
                togglePasswordBtn.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
                });
            }
            
            if (toggleConfirmPasswordBtn) {
                toggleConfirmPasswordBtn.addEventListener('click', function() {
                    const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    confirmPasswordInput.setAttribute('type', type);
                    this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
                });
            }
            
            // Form validation
            const form = document.getElementById('registerForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const username = document.getElementById('username').value.trim();
                    const password = document.getElementById('password').value;
                    const confirmPassword = document.getElementById('confirm_password').value;
                    const email = document.getElementById('email').value.trim();
                    
                    // Basic validation
                    if (username.length < 3) {
                        e.preventDefault();
                        alert('Username must be at least 3 characters long.');
                        return false;
                    }
                    
                    if (password.length < 8) {
                        e.preventDefault();
                        alert('Password must be at least 8 characters long.');
                        return false;
                    }
                    
                    if (password !== confirmPassword) {
                        e.preventDefault();
                        alert('Passwords do not match.');
                        return false;
                    }
                    
                    if (!email.includes('@')) {
                        e.preventDefault();
                        alert('Please enter a valid email address.');
                        return false;
                    }
                    
                    // Final confirmation
                    if (!confirm('Are you sure you want to create the admin account? This action cannot be undone and this page will be deleted.')) {
                        e.preventDefault();
                        return false;
                    }
                });
            }
            
            // Auto-focus on first input
            const firstInput = document.querySelector('input[type="text"], input[type="email"]');
            if (firstInput) {
                firstInput.focus();
            }
        });
    </script>
</body>
</html>