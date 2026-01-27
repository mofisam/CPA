<?php
// Check if session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Get admin info
$adminName = $_SESSION['admin_name'] ?? 'Administrator';
$adminUsername = $_SESSION['admin_username'] ?? 'admin';

// Get flash message
$flashMessage = null;
if (isset($_SESSION['flash_message'])) {
    $flashMessage = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?php echo defined('SITE_NAME') ? SITE_NAME : 'Clinical Physiology Academy'; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <!-- Custom Admin CSS -->
    <link rel="stylesheet" href="../assets/css/admin.css">
    
    <style>
        :root {
            --sidebar-width: 250px;
            --header-height: 60px;
            --primary-color: #0d6efd;
            --sidebar-bg: #2c3e50;
            --sidebar-color: #ecf0f1;
            --sidebar-hover: #34495e;
        }
        
        body {
            font-size: 0.875rem;
            background-color: #f8f9fa;
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar styles will be handled in admin_sidebar.php */
    </style>
</head>
<body>
    <!-- Flash Messages -->
    <?php if ($flashMessage): ?>
    <div class="flash-message-container position-fixed top-0 end-0 z-3" style="max-width: 400px; margin-top: 70px;">
        <div class="alert alert-<?php echo htmlspecialchars($flashMessage['type']); ?> alert-dismissible fade show m-3 shadow" role="alert">
            <i class="fas fa-<?php echo $flashMessage['type'] === 'success' ? 'check-circle' : ($flashMessage['type'] === 'error' ? 'exclamation-circle' : 'info-circle'); ?> me-2"></i>
            <?php echo htmlspecialchars($flashMessage['text']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow-sm" style="height: var(--header-height);">
        <div class="container-fluid">
            <!-- Sidebar Toggle Button -->
            <button class="btn btn-primary me-2" type="button" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <!-- Brand -->
            <a class="navbar-brand me-auto" href="dashboard.php">
                <i class="fas fa-user-shield me-2"></i>
                <span class="d-none d-sm-inline">CPA Admin</span>
            </a>
            
            <!-- Right Side Admin Menu -->
            <div class="d-flex align-items-center">
                <!-- Admin Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-primary dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                        <div class="me-2">
                            <i class="fas fa-user-circle fs-5"></i>
                        </div>
                        <div class="d-none d-md-block text-start">
                            <div class="fw-bold" style="line-height: 1;"><?php echo htmlspecialchars($adminName); ?></div>
                            <small class="text-light"><?php echo htmlspecialchars($adminUsername); ?></small>
                        </div>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header">Logged in as</h6></li>
                        <li><div class="dropdown-item disabled"><small><?php echo htmlspecialchars($adminName); ?><br><?php echo htmlspecialchars($adminUsername); ?></small></div></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../index.php" target="_blank"><i class="fas fa-external-link-alt me-2"></i> View Site</a></li>
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-cog me-2"></i> Profile Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Main Container -->
    <div class="admin-container">