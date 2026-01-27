        <!-- Sidebar -->
        <nav id="sidebar" class="bg-dark text-white" style="width: var(--sidebar-width); min-height: calc(100vh - var(--header-height)); margin-top: var(--header-height);">
            <div class="sidebar-sticky pt-3">
                <!-- Navigation -->
                <ul class="nav flex-column">
                    <!-- Dashboard -->
                    <li class="nav-item">
                        <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active bg-primary' : ''; ?>" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>
                            Dashboard
                        </a>
                    </li>
                    
                    <!-- Trainings Section -->
                    <li class="nav-item mt-3">
                        <div class="nav-link text-uppercase small text-white">
                            <i class="fas fa-graduation-cap me-2"></i>
                            Trainings Management
                        </div>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link text-white <?php echo (basename($_SERVER['PHP_SELF']) == 'add.php' || basename($_SERVER['PHP_SELF']) == 'edit.php') ? 'active bg-primary' : ''; ?>" href="trainings/add.php">
                            <i class="fas fa-plus-circle me-2"></i>
                            Add New Training
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'view.php' ? 'active bg-primary' : ''; ?>" href="trainings/view.php">
                            <i class="fas fa-list me-2"></i>
                            View All Trainings
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active bg-primary' : ''; ?>" href="trainings/categories.php">
                            <i class="fas fa-tags me-2"></i>
                            Categories
                        </a>
                    </li>
                    
                    <!-- Users Section -->
                    <li class="nav-item mt-3">
                        <div class="nav-link text-uppercase small text-white">
                            <i class="fas fa-users me-2"></i>
                            Users Management
                        </div>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'subscribers.php' ? 'active bg-primary' : ''; ?>" href="users/subscribers.php">
                            <i class="fas fa-envelope me-2"></i>
                            Newsletter Subscribers
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'registrations.php' ? 'active bg-primary' : ''; ?>" href="users/registrations.php">
                            <i class="fas fa-user-plus me-2"></i>
                            Course Registrations
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active bg-primary' : ''; ?>" href="users/messages.php">
                            <i class="fas fa-comments me-2"></i>
                            Contact Messages
                        </a>
                    </li>
                    
                    <!-- Content Section -->
                    <li class="nav-item mt-3">
                        <div class="nav-link text-uppercase small text-white">
                            <i class="fas fa-file-alt me-2"></i>
                            Content Management
                        </div>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'pages.php' ? 'active bg-primary' : ''; ?>" href="content/pages.php">
                            <i class="fas fa-file me-2"></i>
                            Static Pages
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'faqs.php' ? 'active bg-primary' : ''; ?>" href="content/faqs.php">
                            <i class="fas fa-question-circle me-2"></i>
                            FAQs
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'testimonials.php' ? 'active bg-primary' : ''; ?>" href="content/testimonials.php">
                            <i class="fas fa-star me-2"></i>
                            Testimonials
                        </a>
                    </li>
                    
                    <!-- System Section -->
                    <li class="nav-item mt-3">
                        <div class="nav-link text-uppercase small text-white">
                            <i class="fas fa-cog me-2"></i>
                            System
                        </div>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'admins.php' ? 'active bg-primary' : ''; ?>" href="system/admins.php">
                            <i class="fas fa-user-shield me-2"></i>
                            Admin Users
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active bg-primary' : ''; ?>" href="system/settings.php">
                            <i class="fas fa-sliders-h me-2"></i>
                            Site Settings
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'backup.php' ? 'active bg-primary' : ''; ?>" href="system/backup.php">
                            <i class="fas fa-database me-2"></i>
                            Database Backup
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'logs.php' ? 'active bg-primary' : ''; ?>" href="system/logs.php">
                            <i class="fas fa-clipboard-list me-2"></i>
                            System Logs
                        </a>
                    </li>
                </ul>
                
                <!-- Sidebar Footer -->
                <div class="position-absolute bottom-0 start-0 end-0 p-3 border-top border-secondary">
                    <div class="text-center">
                        <small class="text-muted">CPA Admin Panel</small><br>
                        <small class="text-muted">v1.0.0</small>
                    </div>
                </div>
            </div>
        </nav>
        
        <!-- Main Content Area -->
        <main class="flex-grow-1 p-4" style="margin-top: var(--header-height); ">
            <!-- Page Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                
                <div class="btn-toolbar mb-2 mb-md-0">
                    <?php if (isset($pageActions)): ?>
                        <?php echo $pageActions; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Page Content -->
            <div class="container-fluid">