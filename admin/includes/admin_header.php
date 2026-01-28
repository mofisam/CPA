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

// Get current page for mobile breadcrumb
$currentPage = basename($_SERVER['PHP_SELF']);
$pageTitles = [
    'dashboard.php' => 'Dashboard',
    'add.php' => 'Add Training',
    'edit.php' => 'Edit Training',
    'view.php' => 'View Trainings',
    'messages.php' => 'Messages',
    'subscribers.php' => 'Subscribers',
    'profile.php' => 'Profile',
    'interests.php' => 'Course Interests'
];
$currentPageTitle = $pageTitles[$currentPage] ?? 'Admin Panel';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : $currentPageTitle; ?> - CPA Admin</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    
    <!-- Custom Admin CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
    
    <style>
        :root {
            --sidebar-width: 260px;
            --header-height: 60px;
            --primary-color: #0d6efd;
            --sidebar-bg: #1a252f;
            --sidebar-color: #bdc3c7;
            --sidebar-hover: #2c3e50;
            --sidebar-active: #0d6efd;
            --mobile-breakpoint: 992px;
        }
        
        * {
            -webkit-tap-highlight-color: transparent;
        }
        
        body {
            font-size: 0.9rem;
            background-color: #f8f9fa;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        @media (max-width: 768px) {
            body {
                font-size: 0.85rem;
            }
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }
        
        /* Flash Messages - Mobile Optimized */
        .flash-message-container {
            z-index: 9999;
        }
        
        @media (max-width: 768px) {
            .flash-message-container {
                max-width: 100% !important;
                margin: 0 !important;
            }
            
            .flash-message-container .alert {
                margin: 10px !important;
                border-radius: 8px;
                font-size: 0.85rem;
            }
        }
        
        /* Top Navigation - Mobile Optimized */
        .navbar-top {
            height: var(--header-height);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .navbar-top .navbar-toggler {
            border: none;
            padding: 8px;
            font-size: 1.2rem;
        }
        
        .navbar-top .navbar-toggler:focus {
            box-shadow: none;
        }
        
        .navbar-top .dropdown-toggle {
            background: transparent;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 50px;
            padding: 6px 12px;
            transition: all 0.2s ease;
        }
        
        .navbar-top .dropdown-toggle:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .navbar-top .dropdown-toggle:after {
            display: none;
        }
        
        .navbar-top .dropdown-menu {
            border: none;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            border-radius: 10px;
            margin-top: 10px;
        }
        
        /* Mobile Page Title */
        .mobile-page-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            display: none;
        }
        
        @media (max-width: 768px) {
            .mobile-page-title {
                display: block;
                text-align: center;
                flex-grow: 1;
                margin: 0 10px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            
            .navbar-top .navbar-brand {
                display: none;
            }
            
            .navbar-top .dropdown .d-none {
                display: none !important;
            }
            
            .navbar-top .dropdown-toggle {
                padding: 6px;
                min-width: 40px;
                justify-content: center;
            }
        }
        
        /* Touch-friendly buttons */
        .btn-touch {
            min-height: 44px;
            min-width: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Prevent text selection on mobile */
        .no-select {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        
        /* Smooth transitions */
        .smooth-transition {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Mobile overlay for sidebar */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1040;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .sidebar-overlay.show {
            display: block;
            opacity: 1;
        }
        
        
    </style>
</head>
<body class="no-select">
    <!-- Flash Messages -->
    <?php if ($flashMessage): ?>
    <div class="flash-message-container position-fixed top-0 end-0 z-3" style="max-width: 400px; margin-top: 70px;">
        <div class="alert alert-<?php echo htmlspecialchars($flashMessage['type']); ?> alert-dismissible fade show m-3 shadow-lg" role="alert">
            <i class="fas fa-<?php echo $flashMessage['type'] === 'success' ? 'check-circle' : ($flashMessage['type'] === 'error' ? 'exclamation-circle' : 'info-circle'); ?> me-2"></i>
            <?php echo htmlspecialchars($flashMessage['text']); ?>
            <button type="button" class="btn-close btn-touch" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow-sm navbar-top">
        <div class="container-fluid px-3">
            <!-- Sidebar Toggle Button -->
            <button class="btn btn-primary btn-touch me-2" type="button" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <!-- Mobile Page Title -->
            <div class="mobile-page-title">
                <?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : $currentPageTitle; ?>
            </div>
            
            <!-- Brand for Desktop -->
            <a class="navbar-brand d-none d-md-flex align-items-center" href="dashboard.php">
                <i class="fas fa-user-shield me-2"></i>
                <span>CPA Admin</span>
            </a>
            
            <!-- Right Side Admin Menu -->
            <div class="d-flex align-items-center">
                <!-- View Site Link (Mobile Only) -->
                <a href="../index.php" target="_blank" class="btn btn-primary btn-touch d-md-none me-2">
                    <i class="fas fa-external-link-alt"></i>
                </a>
                
                <!-- Admin Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-primary dropdown-toggle btn-touch d-flex align-items-center" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="me-1 me-md-2">
                            <i class="fas fa-user-circle fs-5"></i>
                        </div>
                        <div class="d-none d-md-block text-start">
                            <div class="fw-bold" style="line-height: 1.2; font-size: 0.9rem;"><?php echo htmlspecialchars($adminName); ?></div>
                            <small class="text-light opacity-75" style="font-size: 0.75rem;"><?php echo htmlspecialchars($adminUsername); ?></small>
                        </div>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg">
                        <li><h6 class="dropdown-header">Logged in as</h6></li>
                        <li><div class="dropdown-item disabled py-2">
                            <div class="fw-bold small"><?php echo htmlspecialchars($adminName); ?></div>
                            <div class="text-muted small"><?php echo htmlspecialchars($adminUsername); ?></div>
                        </div></li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li><a class="dropdown-item py-2" href="../index.php" target="_blank"><i class="fas fa-external-link-alt me-2"></i> View Site</a></li>
                        <li><a class="dropdown-item py-2" href="profile.php"><i class="fas fa-user-cog me-2"></i> Profile Settings</a></li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li><a class="dropdown-item text-danger py-2" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Main Container -->
    <div class="admin-container">