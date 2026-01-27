<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get flash message if exists
$flashMessage = null;
if (function_exists('CPAFunctions::getFlashMessage')) {
    $flashMessage = CPAFunctions::getFlashMessage();
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php 
        if (isset($pageTitle)) {
            echo htmlspecialchars($pageTitle) . ' | ';
        }
        echo defined('SITE_NAME') ? SITE_NAME : 'Clinical Physiology Academy';
    ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo isset($base_url) ? $base_url : ''; ?>assets/css/style.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo isset($base_url) ? $base_url : ''; ?>assets/images/logos/favicon.ico">
    
    <!-- Meta Tags -->
    <meta name="description" content="<?php echo isset($pageDescription) ? htmlspecialchars($pageDescription) : 'Clinical Physiology Academy - Cutting-edge clinical physiology education and training.'; ?>">
    <meta name="keywords" content="clinical physiology, echocardiography, ECG training, cardiac diagnostics, medical training, Nigeria">
    <meta name="author" content="Clinical Physiology Academy">
    
    <!-- Open Graph Meta Tags for Social Media -->
    <meta property="og:title" content="<?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Clinical Physiology Academy'; ?>">
    <meta property="og:description" content="<?php echo isset($pageDescription) ? htmlspecialchars($pageDescription) : 'Cutting-edge clinical physiology education and training.'; ?>">
    <meta property="og:image" content="<?php echo isset($base_url) ? $base_url : ''; ?>assets/images/logos/og-image.jpg">
    <meta property="og:url" content="<?php echo isset($_SERVER['REQUEST_URI']) ? 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] : ''; ?>">
    <meta property="og:type" content="website">
    
    <!-- Theme Color -->
    <meta name="theme-color" content="#0d6efd">
</head>
<body>
    <!-- Flash Messages -->
    <?php if ($flashMessage): ?>
    <div class="flash-message-container position-fixed top-0 start-0 end-0 z-3">
        <div class="alert alert-<?php echo htmlspecialchars($flashMessage['type']); ?> alert-dismissible fade show m-3 shadow" role="alert">
            <div class="container">
                <i class="fas fa-<?php echo $flashMessage['type'] === 'success' ? 'check-circle' : ($flashMessage['type'] === 'error' ? 'exclamation-circle' : 'info-circle'); ?> me-2"></i>
                <?php echo htmlspecialchars($flashMessage['text']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Skip to Main Content -->
    <a href="#main-content" class="skip-to-content visually-hidden-focusable">Skip to main content</a>
    
    <!-- Top Bar -->
    <div class="top-bar bg-primary text-white py-2 d-none d-md-block">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-envelope me-2"></i>
                        <span><?php echo defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'info@clinicalphysiologyacademy.com'; ?></span>
                    </div>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="d-inline-flex align-items-center">
                        <span class="me-3">Follow us:</span>
                        <div class="social-icons">
                            <a href="#" class="text-white me-2" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="text-white me-2" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="text-white me-2" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                            <a href="#" class="text-white" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <!-- Logo -->
            <a class="navbar-brand d-flex align-items-center" href="/">
                <div class="logo-wrapper me-2">
                    <img src="assets/images/logo.jpg" alt="Clinical PhysiologyAcademy logo" style="height:40px; width:auto; margin-right:8px; border-radius: 20px;">
                </div>
                <div>
                    <span class="fw-bold fs-4 text-primary">Clinical Physiology</span>
                    <small class="d-block text-muted" style="line-height: 1; margin-top: -2px;">Academy</small>
                </div>
            </a>
            
            <!-- Mobile Toggle -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Navigation Links -->
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active fw-bold' : ''; ?>" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active fw-bold' : ''; ?>" href="about.php">About Us</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="coursesDropdown" role="button" data-bs-toggle="dropdown">
                            Courses
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="courses.php">All Courses</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="courses.php?type=echocardiography"><i class="fas fa-heartbeat text-danger me-2"></i> Echocardiography Training</a></li>
                            <li><a class="dropdown-item" href="courses.php?type=ecg"><i class="fas fa-heart text-success me-2"></i> ECG Masterclass</a></li>
                            <li><a class="dropdown-item" href="courses.php?type=other"><i class="fas fa-stethoscope text-info me-2"></i> Other Courses</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active fw-bold' : ''; ?>" href="contact.php">Contact</a>
                    </li>
                    
                    <!-- Admin Link (only shown to logged-in admins) -->
                    <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="admin/dashboard.php">
                            <i class="fas fa-user-shield me-1"></i> Admin Panel
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <!-- Call to Action Button -->
                <div class="d-flex ms-lg-3">
                    <a href="register-interest.php" class="btn btn-primary">
                        <i class="fas fa-user-plus me-2"></i> Register Interest
                    </a>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Page Content -->
    <main id="main-content">