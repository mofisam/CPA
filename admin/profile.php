<?php
require_once '../config/environment.php';
require_once '../includes/core/Database.php';
require_once '../includes/core/Functions.php';
require_once '../includes/core/Auth.php';

use includes\core\Auth;
use includes\core\Database;

// Check admin authentication
Auth::requireAdmin();

$db = Database::getInstance();
$admin = Auth::getCurrentAdmin();

// Initialize variables
$success = false;
$errors = [];
$passwordErrors = [];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    
    // Validation
    if (empty($full_name)) {
        $errors[] = 'Full name is required';
    }
    
    if (empty($email)) {
        $errors[] = 'Email address is required';
    } elseif (!CPAFunctions::isValidEmail($email)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    if (empty($username)) {
        $errors[] = 'Username is required';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters';
    }
    
    // Check if email already exists (excluding current admin)
    if ($email !== $admin['email']) {
        $existingEmail = $db->fetchOne(
            "SELECT id FROM admins WHERE email = ? AND id != ?",
            [$email, $admin['id']]
        );
        if ($existingEmail) {
            $errors[] = 'Email address already in use';
        }
    }
    
    // Check if username already exists (excluding current admin)
    if ($username !== $admin['username']) {
        $existingUsername = $db->fetchOne(
            "SELECT id FROM admins WHERE username = ? AND id != ?",
            [$username, $admin['id']]
        );
        if ($existingUsername) {
            $errors[] = 'Username already in use';
        }
    }
    
    // Update if no errors
    if (empty($errors)) {
        $updateData = [
            'full_name' => $full_name,
            'email' => $email,
            'username' => $username
        ];
        
        $db->update('admins', $updateData, 'id = ?', [$admin['id']]);
        
        // Update session data
        $_SESSION['admin_name'] = $full_name;
        $_SESSION['admin_username'] = $username;
        
        $success = true;
        $admin = Auth::getCurrentAdmin(); // Refresh admin data
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($current_password)) {
        $passwordErrors[] = 'Current password is required';
    }
    
    if (empty($new_password)) {
        $passwordErrors[] = 'New password is required';
    } elseif (strlen($new_password) < 8) {
        $passwordErrors[] = 'New password must be at least 8 characters';
    }
    
    if ($new_password !== $confirm_password) {
        $passwordErrors[] = 'Passwords do not match';
    }
    
    // Verify current password
    if (empty($passwordErrors)) {
        $adminCheck = $db->fetchOne(
            "SELECT password_hash FROM admins WHERE id = ?",
            [$admin['id']]
        );
        
        if (!password_verify($current_password, $adminCheck['password_hash'])) {
            $passwordErrors[] = 'Current password is incorrect';
        }
    }
    
    // Change password if no errors
    if (empty($passwordErrors)) {
        $newHash = password_hash($new_password, PASSWORD_DEFAULT);
        $db->update('admins', 
            ['password_hash' => $newHash], 
            'id = ?', 
            [$admin['id']]
        );
        
        $_SESSION['flash_message'] = [
            'text' => 'Password changed successfully!',
            'type' => 'success'
        ];
        header('Location: profile.php');
        exit();
    }
}

$pageTitle = "Admin Profile";
?>
<?php include '../admin/includes/admin_header.php'; ?>
<?php include '../admin/includes/admin_sidebar.php'; ?>

            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Admin Profile</h1>
            </div>

            <!-- Profile Update Form -->
            <div class="row">
                <!-- Profile Information -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-user-cog me-2"></i> Profile Information</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($success): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="fas fa-check-circle me-2"></i>
                                    Profile updated successfully!
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger">
                                    <h6 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i> Please fix the following errors:</h6>
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="" id="profileForm">
                                <div class="mb-3">
                                    <label for="full_name" class="form-label">Full Name</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="full_name" 
                                           name="full_name" 
                                           required
                                           value="<?php echo htmlspecialchars($admin['full_name'] ?? ''); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" 
                                           class="form-control" 
                                           id="email" 
                                           name="email" 
                                           required
                                           value="<?php echo htmlspecialchars($admin['email']); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="username" 
                                           name="username" 
                                           required
                                           value="<?php echo htmlspecialchars($admin['username']); ?>">
                                    <div class="form-text">This is used for login</div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i> Update Profile
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Account Information -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i> Account Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-shrink-0">
                                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" 
                                             style="width: 60px; height: 60px;">
                                            <i class="fas fa-user-shield text-white fa-lg"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($admin['full_name'] ?? $admin['username']); ?></h6>
                                        <small class="text-muted">Administrator</small>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-6">
                                        <div class="border rounded p-3 text-center mb-3">
                                            <div class="text-muted small">Member Since</div>
                                            <div class="fw-bold"><?php echo date('M Y', strtotime($admin['created_at'])); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="border rounded p-3 text-center mb-3">
                                            <div class="text-muted small">Last Login</div>
                                            <div class="fw-bold"><?php echo $admin['last_login'] ? date('M j, g:i A', strtotime($admin['last_login'])) : 'Never'; ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <h6 class="alert-heading"><i class="fas fa-shield-alt me-2"></i> Account Security</h6>
                                <p class="mb-0 small">
                                    Your account is protected with password hashing and session management. 
                                    For optimal security, change your password regularly and never share your credentials.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Change Password -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-lock me-2"></i> Change Password</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($passwordErrors)): ?>
                                <div class="alert alert-danger">
                                    <h6 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i> Please fix the following errors:</h6>
                                    <ul class="mb-0">
                                        <?php foreach ($passwordErrors as $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="" id="passwordForm">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <div class="input-group">
                                        <input type="password" 
                                               class="form-control" 
                                               id="current_password" 
                                               name="current_password" 
                                               required>
                                        <button class="btn btn-outline-secondary" type="button" id="toggleCurrentPassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <div class="input-group">
                                        <input type="password" 
                                               class="form-control" 
                                               id="new_password" 
                                               name="new_password" 
                                               required
                                               minlength="8">
                                        <button class="btn btn-outline-secondary" type="button" id="toggleNewPassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Minimum 8 characters</div>
                                    <div id="passwordStrength" class="progress mt-2" style="height: 5px; display: none;">
                                        <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <div class="input-group">
                                        <input type="password" 
                                               class="form-control" 
                                               id="confirm_password" 
                                               name="confirm_password" 
                                               required
                                               minlength="8">
                                        <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div id="passwordMatch" class="form-text"></div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" name="change_password" class="btn btn-primary">
                                        <i class="fas fa-key me-2"></i> Change Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Activity Log -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-history me-2"></i> Recent Activity</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            // Get recent activities (you might want to create an activities table)
                            $recentActivities = [
                                ['icon' => 'fas fa-user', 'text' => 'Logged in to admin panel', 'time' => $admin['last_login'] ?? 'Never'],
                                ['icon' => 'fas fa-graduation-cap', 'text' => 'Manage training courses', 'time' => 'Recently'],
                                ['icon' => 'fas fa-cog', 'text' => 'Updated profile information', 'time' => 'Just now']
                            ];
                            ?>
                            
                            <div class="activity-timeline">
                                <?php foreach ($recentActivities as $activity): ?>
                                <div class="activity-item mb-3">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0">
                                            <i class="<?php echo $activity['icon']; ?> text-primary"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <p class="mb-1"><?php echo htmlspecialchars($activity['text']); ?></p>
                                            <small class="text-muted"><?php echo $activity['time']; ?></small>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="mt-4">
                                <h6 class="text-muted mb-3">Security Tips</h6>
                                <ul class="small text-muted">
                                    <li>Use a strong, unique password</li>
                                    <li>Change your password every 90 days</li>
                                    <li>Never share your login credentials</li>
                                    <li>Log out when using shared computers</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    const toggleCurrentPassword = document.getElementById('toggleCurrentPassword');
    const toggleNewPassword = document.getElementById('toggleNewPassword');
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
    const currentPasswordInput = document.getElementById('current_password');
    const newPasswordInput = document.getElementById('new_password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const passwordStrength = document.getElementById('passwordStrength');
    const passwordMatch = document.getElementById('passwordMatch');
    
    if (toggleCurrentPassword) {
        toggleCurrentPassword.addEventListener('click', function() {
            const type = currentPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            currentPasswordInput.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });
    }
    
    if (toggleNewPassword) {
        toggleNewPassword.addEventListener('click', function() {
            const type = newPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            newPasswordInput.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });
    }
    
    if (toggleConfirmPassword) {
        toggleConfirmPassword.addEventListener('click', function() {
            const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPasswordInput.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });
    }
    
    // Check password strength
    if (newPasswordInput) {
        newPasswordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            // Length check
            if (password.length >= 8) strength += 25;
            if (password.length >= 12) strength += 25;
            
            // Complexity checks
            if (/[A-Z]/.test(password)) strength += 15;
            if (/[a-z]/.test(password)) strength += 15;
            if (/[0-9]/.test(password)) strength += 10;
            if (/[^A-Za-z0-9]/.test(password)) strength += 10;
            
            // Update strength indicator
            const progressBar = passwordStrength.querySelector('.progress-bar');
            progressBar.style.width = strength + '%';
            
            if (strength <= 25) {
                progressBar.className = 'progress-bar bg-danger';
            } else if (strength <= 50) {
                progressBar.className = 'progress-bar bg-warning';
            } else if (strength <= 75) {
                progressBar.className = 'progress-bar bg-info';
            } else {
                progressBar.className = 'progress-bar bg-success';
            }
            
            passwordStrength.style.display = password ? 'block' : 'none';
            
            // Check password match
            checkPasswordMatch();
        });
    }
    
    // Check password match
    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', checkPasswordMatch);
    }
    
    function checkPasswordMatch() {
        const newPassword = newPasswordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        
        if (confirmPassword.length === 0) {
            passwordMatch.textContent = '';
            passwordMatch.className = 'form-text';
        } else if (newPassword === confirmPassword) {
            passwordMatch.textContent = '✓ Passwords match';
            passwordMatch.className = 'form-text text-success';
        } else {
            passwordMatch.textContent = '✗ Passwords do not match';
            passwordMatch.className = 'form-text text-danger';
        }
    }
    
    // Form validation
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            const fullName = document.getElementById('full_name').value.trim();
            const email = document.getElementById('email').value.trim();
            const username = document.getElementById('username').value.trim();
            
            if (!fullName || !email || !username) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return false;
            }
            
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address.');
                return false;
            }
            
            if (username.length < 3) {
                e.preventDefault();
                alert('Username must be at least 3 characters.');
                return false;
            }
        });
    }
    
    const passwordForm = document.getElementById('passwordForm');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            const currentPassword = currentPasswordInput.value;
            const newPassword = newPasswordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            if (!currentPassword || !newPassword || !confirmPassword) {
                e.preventDefault();
                alert('Please fill in all password fields.');
                return false;
            }
            
            if (newPassword.length < 8) {
                e.preventDefault();
                alert('New password must be at least 8 characters.');
                return false;
            }
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match.');
                return false;
            }
            
            if (!confirm('Are you sure you want to change your password?')) {
                e.preventDefault();
                return false;
            }
        });
    }
});
</script>

<?php include '../admin/includes/admin_footer.php'; ?>