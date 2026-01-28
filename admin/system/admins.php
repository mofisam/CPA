<?php
require_once '../../config/environment.php';
require_once '../../includes/core/Database.php';
require_once '../../includes/core/Functions.php';
require_once '../../includes/core/Auth.php';

use includes\core\Auth;
use includes\core\Database;

// Check admin authentication
Auth::requireAdmin();

$db = Database::getInstance();

// Get current admin
$currentAdmin = Auth::getCurrentAdmin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new admin
    if (isset($_POST['add_admin'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $full_name = trim($_POST['full_name']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $role = 'admin'; // Default role
        
        $errors = [];
        
        // Validation
        if (empty($username) || empty($email) || empty($full_name) || empty($password)) {
            $errors[] = 'All fields are required';
        }
        
        if (strlen($username) < 3) {
            $errors[] = 'Username must be at least 3 characters';
        }
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }
        
        if ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match';
        }
        
        if (!CPAFunctions::isValidEmail($email)) {
            $errors[] = 'Invalid email format';
        }
        
        // Check if username exists
        $existing = $db->fetchOne("SELECT id FROM admins WHERE username = ?", [$username]);
        if ($existing) {
            $errors[] = 'Username already exists';
        }
        
        // Check if email exists
        $existing = $db->fetchOne("SELECT id FROM admins WHERE email = ?", [$email]);
        if ($existing) {
            $errors[] = 'Email already registered';
        }
        
        if (empty($errors)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $adminData = [
                'username' => $username,
                'password_hash' => $password_hash,
                'email' => $email,
                'full_name' => $full_name,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            if ($db->insert('admins', $adminData)) {
                $_SESSION['flash_message'] = [
                    'text' => 'Admin added successfully!',
                    'type' => 'success'
                ];
                header('Location: admins.php');
                exit();
            } else {
                $errors[] = 'Error adding admin: ' . $db->getConnection()->error;
            }
        }
        
        if (!empty($errors)) {
            $_SESSION['add_admin_errors'] = $errors;
            $_SESSION['add_admin_data'] = $_POST;
        }
    }
    
    // Update admin
    if (isset($_POST['update_admin'])) {
        $admin_id = intval($_POST['admin_id']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $full_name = trim($_POST['full_name']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Don't allow editing of current admin's active status
        if ($admin_id == $currentAdmin['id']) {
            $is_active = 1;
        }
        
        $updateData = [
            'username' => $username,
            'email' => $email,
            'full_name' => $full_name,
        ];
        
        if ($admin_id != $currentAdmin['id']) {
            $updateData['is_active'] = $is_active;
        }
        
        $db->update('admins', $updateData, 'id = ?', [$admin_id]);
        
        $_SESSION['flash_message'] = [
            'text' => 'Admin updated successfully!',
            'type' => 'success'
        ];
        header('Location: admins.php');
        exit();
    }
    
    // Change password
    if (isset($_POST['change_password'])) {
        $admin_id = intval($_POST['admin_id']);
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (strlen($new_password) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = 'Passwords do not match';
        }
        
        if (empty($errors)) {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $db->update('admins', ['password_hash' => $password_hash], 'id = ?', [$admin_id]);
            
            $_SESSION['flash_message'] = [
                'text' => 'Password changed successfully!',
                'type' => 'success'
            ];
            header('Location: admins.php');
            exit();
        }
    }
    
    // Delete admin
    if (isset($_POST['delete_admin'])) {
        $admin_id = intval($_POST['admin_id']);
        
        // Prevent deleting self
        if ($admin_id == $currentAdmin['id']) {
            $_SESSION['flash_message'] = [
                'text' => 'You cannot delete your own account!',
                'type' => 'error'
            ];
        } else {
            $db->delete('admins', 'id = ?', [$admin_id]);
            
            $_SESSION['flash_message'] = [
                'text' => 'Admin deleted successfully!',
                'type' => 'success'
            ];
        }
        header('Location: admins.php');
        exit();
    }
}

// Get all admins
$admins = $db->fetchAll("SELECT * FROM admins ORDER BY created_at DESC");

$pageTitle = "Admin Users Management";
?>
<?php include '../../admin/includes/admin_header.php'; ?>
<?php include '../../admin/includes/admin_sidebar.php'; ?>

            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Admin Users Management</h1>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                    <i class="fas fa-user-plus me-1"></i> Add New Admin
                </button>
            </div>

            <!-- Stats -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs fw-bold text-primary text-uppercase mb-1">
                                        Total Admins
                                    </div>
                                    <div class="h5 mb-0 fw-bold text-gray-800"><?php echo count($admins); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs fw-bold text-success text-uppercase mb-1">
                                        Active Admins
                                    </div>
                                    <div class="h5 mb-0 fw-bold text-gray-800">
                                        <?php 
                                            $activeCount = array_reduce($admins, function($carry, $admin) {
                                                return $carry + (isset($admin['is_active']) && $admin['is_active'] ? 1 : 1);
                                            }, 0);
                                            echo $activeCount;
                                        ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-user-check fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs fw-bold text-info text-uppercase mb-1">
                                        Last Login
                                    </div>
                                    <div class="h5 mb-0 fw-bold text-gray-800">
                                        <?php 
                                            $lastLogin = $currentAdmin['last_login'] ?? date('Y-m-d H:i:s');
                                            echo date('M j, g:i A', strtotime($lastLogin));
                                        ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clock fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Admins Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Admin Users (<?php echo count($admins); ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($admins)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5>No admin users found</h5>
                            <p class="text-muted">Add your first admin user to get started.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Admin</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Last Login</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($admins as $admin): 
                                        $isCurrentUser = $admin['id'] == $currentAdmin['id'];
                                        $isActive = !isset($admin['is_active']) || $admin['is_active'];
                                    ?>
                                        <tr class="<?php echo $isCurrentUser ? 'table-primary' : ''; ?>">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" 
                                                             style="width: 40px; height: 40px;">
                                                            <i class="fas fa-user text-white"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <strong><?php echo htmlspecialchars($admin['full_name']); ?></strong>
                                                        <?php if ($isCurrentUser): ?>
                                                            <span class="badge bg-info">You</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <code><?php echo htmlspecialchars($admin['username']); ?></code>
                                            </td>
                                            <td>
                                                <a href="mailto:<?php echo htmlspecialchars($admin['email']); ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($admin['email']); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <?php if ($isActive): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo date('M j, Y', strtotime($admin['created_at'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo $admin['last_login'] ? date('M j, g:i A', strtotime($admin['last_login'])) : 'Never'; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" 
                                                            class="btn btn-primary" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#editModal<?php echo $admin['id']; ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" 
                                                            class="btn btn-warning" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#passwordModal<?php echo $admin['id']; ?>">
                                                        <i class="fas fa-key"></i>
                                                    </button>
                                                    <?php if (!$isCurrentUser): ?>
                                                        <button type="button" 
                                                                class="btn btn-danger" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#deleteModal<?php echo $admin['id']; ?>">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        
                                        <!-- Edit Modal -->
                                        <div class="modal fade" id="editModal<?php echo $admin['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST" action="">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Edit Admin: <?php echo htmlspecialchars($admin['full_name']); ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                                            
                                                            <div class="mb-3">
                                                                <label for="full_name<?php echo $admin['id']; ?>" class="form-label">Full Name</label>
                                                                <input type="text" 
                                                                       class="form-control" 
                                                                       id="full_name<?php echo $admin['id']; ?>" 
                                                                       name="full_name" 
                                                                       value="<?php echo htmlspecialchars($admin['full_name']); ?>" 
                                                                       required>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label for="username<?php echo $admin['id']; ?>" class="form-label">Username</label>
                                                                <input type="text" 
                                                                       class="form-control" 
                                                                       id="username<?php echo $admin['id']; ?>" 
                                                                       name="username" 
                                                                       value="<?php echo htmlspecialchars($admin['username']); ?>" 
                                                                       required>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label for="email<?php echo $admin['id']; ?>" class="form-label">Email</label>
                                                                <input type="email" 
                                                                       class="form-control" 
                                                                       id="email<?php echo $admin['id']; ?>" 
                                                                       name="email" 
                                                                       value="<?php echo htmlspecialchars($admin['email']); ?>" 
                                                                       required>
                                                            </div>
                                                            
                                                            <?php if (!$isCurrentUser): ?>
                                                            <div class="mb-3">
                                                                <div class="form-check">
                                                                    <input class="form-check-input" 
                                                                           type="checkbox" 
                                                                           id="is_active<?php echo $admin['id']; ?>" 
                                                                           name="is_active" 
                                                                           value="1" 
                                                                           <?php echo $isActive ? 'checked' : ''; ?>>
                                                                    <label class="form-check-label" for="is_active<?php echo $admin['id']; ?>">
                                                                        Active Account
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" name="update_admin" class="btn btn-primary">
                                                                <i class="fas fa-save me-1"></i> Update Admin
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Change Password Modal -->
                                        <div class="modal fade" id="passwordModal<?php echo $admin['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST" action="">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Change Password: <?php echo htmlspecialchars($admin['full_name']); ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                                            
                                                            <div class="mb-3">
                                                                <label for="new_password<?php echo $admin['id']; ?>" class="form-label">New Password</label>
                                                                <input type="password" 
                                                                       class="form-control" 
                                                                       id="new_password<?php echo $admin['id']; ?>" 
                                                                       name="new_password" 
                                                                       required 
                                                                       minlength="8">
                                                                <div class="form-text">Minimum 8 characters</div>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label for="confirm_password<?php echo $admin['id']; ?>" class="form-label">Confirm Password</label>
                                                                <input type="password" 
                                                                       class="form-control" 
                                                                       id="confirm_password<?php echo $admin['id']; ?>" 
                                                                       name="confirm_password" 
                                                                       required 
                                                                       minlength="8">
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" name="change_password" class="btn btn-primary">
                                                                <i class="fas fa-key me-1"></i> Change Password
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Delete Modal -->
                                        <?php if (!$isCurrentUser): ?>
                                        <div class="modal fade" id="deleteModal<?php echo $admin['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST" action="">
                                                        <div class="modal-header bg-danger text-white">
                                                            <h5 class="modal-title">Delete Admin Account</h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                                            
                                                            <div class="alert alert-danger">
                                                                <h5 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i> Warning!</h5>
                                                                <p class="mb-0">
                                                                    You are about to delete the admin account for 
                                                                    <strong><?php echo htmlspecialchars($admin['full_name']); ?></strong>.
                                                                    This action cannot be undone.
                                                                </p>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label for="confirm_delete<?php echo $admin['id']; ?>" class="form-label">
                                                                    Type "DELETE" to confirm
                                                                </label>
                                                                <input type="text" 
                                                                       class="form-control" 
                                                                       id="confirm_delete<?php echo $admin['id']; ?>" 
                                                                       name="confirm_delete" 
                                                                       placeholder="Type DELETE here" 
                                                                       required>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" name="delete_admin" class="btn btn-danger" id="deleteBtn<?php echo $admin['id']; ?>" disabled>
                                                                <i class="fas fa-trash me-1"></i> Delete Account
                                                            </button>
                                                        </div>
                                                    </form>
                                                    <script>
                                                        document.addEventListener('DOMContentLoaded', function() {
                                                            const confirmInput = document.getElementById('confirm_delete<?php echo $admin['id']; ?>');
                                                            const deleteBtn = document.getElementById('deleteBtn<?php echo $admin['id']; ?>');
                                                            
                                                            confirmInput.addEventListener('input', function() {
                                                                deleteBtn.disabled = this.value.toUpperCase() !== 'DELETE';
                                                            });
                                                        });
                                                    </script>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>
</div>

<!-- Add Admin Modal -->
<div class="modal fade" id="addAdminModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i> Add New Admin</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if (isset($_SESSION['add_admin_errors'])): ?>
                        <div class="alert alert-danger">
                            <h6 class="alert-heading">Please fix the following errors:</h6>
                            <ul class="mb-0">
                                <?php foreach ($_SESSION['add_admin_errors'] as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php unset($_SESSION['add_admin_errors']); ?>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name *</label>
                        <input type="text" 
                               class="form-control" 
                               id="full_name" 
                               name="full_name" 
                               value="<?php echo isset($_SESSION['add_admin_data']['full_name']) ? htmlspecialchars($_SESSION['add_admin_data']['full_name']) : ''; ?>" 
                               required>
                        <?php unset($_SESSION['add_admin_data']['full_name']); ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username *</label>
                        <input type="text" 
                               class="form-control" 
                               id="username" 
                               name="username" 
                               value="<?php echo isset($_SESSION['add_admin_data']['username']) ? htmlspecialchars($_SESSION['add_admin_data']['username']) : ''; ?>" 
                               required 
                               minlength="3">
                        <?php unset($_SESSION['add_admin_data']['username']); ?>
                        <div class="form-text">Minimum 3 characters</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email" 
                               value="<?php echo isset($_SESSION['add_admin_data']['email']) ? htmlspecialchars($_SESSION['add_admin_data']['email']) : ''; ?>" 
                               required>
                        <?php unset($_SESSION['add_admin_data']['email']); ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password *</label>
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password" 
                               required 
                               minlength="8">
                        <div class="form-text">Minimum 8 characters</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password *</label>
                        <input type="password" 
                               class="form-control" 
                               id="confirm_password" 
                               name="confirm_password" 
                               required 
                               minlength="8">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_admin" class="btn btn-primary">
                        <i class="fas fa-user-plus me-1"></i> Create Admin
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
// Clear session data
unset($_SESSION['add_admin_data']);
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show add admin modal if there were errors
    <?php if (isset($_SESSION['add_admin_errors'])): ?>
        const addAdminModal = new bootstrap.Modal(document.getElementById('addAdminModal'));
        addAdminModal.show();
        <?php unset($_SESSION['add_admin_errors']); ?>
    <?php endif; ?>
    
    // Password validation in add admin form
    const addAdminForm = document.querySelector('#addAdminModal form');
    if (addAdminForm) {
        addAdminForm.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
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
        });
    }
    
    // Password validation in change password modals
    const passwordModals = document.querySelectorAll('form [name="change_password"]');
    passwordModals.forEach(btn => {
        btn.addEventListener('click', function(e) {
            const form = this.closest('form');
            const newPassword = form.querySelector('input[name="new_password"]').value;
            const confirmPassword = form.querySelector('input[name="confirm_password"]').value;
            
            if (newPassword.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long.');
                return false;
            }
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match.');
                return false;
            }
            
            if (!confirm('Are you sure you want to change this admin\'s password?')) {
                e.preventDefault();
                return false;
            }
        });
    });
});
</script>

<?php include '../../admin/includes/admin_footer.php'; ?>