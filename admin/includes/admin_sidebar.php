        <!-- Sidebar -->
        <nav id="sidebar" class="bg-dark text-white smooth-transition position-fixed start-0 top-0 h-100" style="width: var(--sidebar-width); z-index: 1050; transform: translateX(0);">
            <div class="sidebar-sticky h-100 d-flex flex-column">
                <!-- Sidebar Header -->
                <div class="sidebar-header p-3 border-bottom border-secondary">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-user-shield fs-4 text-primary me-3"></i>
                        <div>
                            <h5 class="mb-0 fw-bold">CPA Admin</h5>
                            <small class="text-muted">Clinical Physiology Academy</small>
                        </div>
                    </div>
                </div>
                
                <!-- Navigation -->
                <div class="sidebar-content flex-grow-1 overflow-auto py-3" style="scrollbar-width: thin;">
                    <ul class="nav flex-column">
                        <!-- Dashboard -->
                        <li class="nav-item px-3">
                            <a class="nav-link text-white rounded-3 px-3 d-flex align-items-center <?php echo $currentPage == 'dashboard.php' ? 'active bg-primary shadow-sm' : ''; ?>" href="<?php echo SITE_URL; ?>/admin/dashboard.php">
                                <i class="fas fa-tachometer-alt me-3"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                        
                        <!-- Trainings Section -->
                        <li class="nav-item px-3 mt-2">
                            <div class="nav-link text-uppercase small  px-3">
                                <i class="fas fa-graduation-cap me-2"></i>
                                <span>Trainings Management</span>
                            </div>
                        </li>
                        
                        <li class="nav-item px-3 ">
                            <a class="nav-link text-white rounded-3 px-3 d-flex align-items-center <?php echo in_array($currentPage, ['add.php', 'edit.php']) ? 'active bg-primary shadow-sm' : ''; ?>" href="<?php echo SITE_URL; ?>/admin/trainings/add.php">
                                <i class="fas fa-plus-circle me-3"></i>
                                <span>Add New Training</span>
                            </a>
                        </li>
                        
                        <li class="nav-item px-3">
                            <a class="nav-link text-white rounded-3 px-3 d-flex align-items-center <?php echo $currentPage == 'view.php' ? 'active bg-primary shadow-sm' : ''; ?>" href="<?php echo SITE_URL; ?>/admin/trainings/view.php">
                                <i class="fas fa-list me-3"></i>
                                <span>View All Trainings</span>
                            </a>
                        </li>
                        
                        <!-- Users Section -->
                        <li class="nav-item px-3 mt-4">
                            <div class="nav-link text-uppercase small px-3">
                                <i class="fas fa-users me-2"></i>
                                <span>Users Management</span>
                            </div>
                        </li>
                        
                        <li class="nav-item px-3">
                            <a class="nav-link text-white rounded-3 px-3 d-flex align-items-center <?php echo $currentPage == 'subscribers.php' ? 'active bg-primary shadow-sm' : ''; ?>" href="<?php echo SITE_URL; ?>/admin/users/subscribers.php">
                                <i class="fas fa-envelope me-3"></i>
                                <span>Newsletter Subscribers</span>
                            </a>
                        </li>
                        
                        <li class="nav-item px-3 mb-1">
                            <a class="nav-link text-white rounded-3 px-3 d-flex align-items-center <?php echo $currentPage == 'interests.php' ? 'active bg-primary shadow-sm' : ''; ?>" href="<?php echo SITE_URL; ?>/admin/users/interests.php">
                                <i class="fas fa-user-plus me-3"></i>
                                <span>Course Interests</span>
                            </a>
                        </li>
                        
                        <li class="nav-item px-3">
                            <a class="nav-link text-white rounded-3 px-3 d-flex align-items-center <?php echo $currentPage == 'messages.php' ? 'active bg-primary shadow-sm' : ''; ?>" href="<?php echo SITE_URL; ?>/admin/users/messages.php">
                                <i class="fas fa-comments me-3"></i>
                                <span>Contact Messages</span>
                            </a>
                        </li>
                        
                        <!-- System Section -->
                        <li class="nav-item px-3 mt-4">
                            <div class="nav-link text-uppercase small px-3">
                                <i class="fas fa-cog me-2"></i>
                                <span>System</span>
                            </div>
                        </li>
                        
                        <li class="nav-item px-3">
                            <a class="nav-link text-white rounded-3 px-3 d-flex align-items-center <?php echo $currentPage == 'admins.php' ? 'active bg-primary shadow-sm' : ''; ?>" href="<?php echo SITE_URL; ?>/admin/system/admins.php">
                                <i class="fas fa-user-shield me-3"></i>
                                <span>Admin Users</span>
                            </a>
                        </li>
                        <!--
                        <li class="nav-item px-3">
                            <a class="nav-link text-white rounded-3 py-3 d-flex align-items-center <?php echo $currentPage == 'settings.php' ? 'active bg-primary shadow-sm' : ''; ?>" href="<?php echo SITE_URL; ?>/admin/system/settings.php">
                                <i class="fas fa-sliders-h me-3"></i>
                                <span>Site Settings</span>
                            </a>
                        </li>
                        -->
                        
                        <li class="nav-item px-3">
                            <a class="nav-link text-white rounded-3 px-3 d-flex align-items-center <?php echo $currentPage == 'profile.php' ? 'active bg-primary shadow-sm' : ''; ?>" href="<?php echo SITE_URL; ?>/admin/profile.php">
                                <i class="fas fa-user-cog me-3"></i>
                                <span>Profile Settings</span>
                            </a>
                        </li>
                    </ul>
                </div>
                
                <!-- Sidebar Footer -->
                <div class="sidebar-footer mt-auto p-3 border-top border-secondary">
                    <div class="text-center">
                        <small class="d-block">CPA Admin Panel</small>
                        <small class="">v1.0.0</small>
                        <button class="btn btn-sm btn-outline-light mt-2 d-lg-none w-100" id="closeSidebar">
                            <i class="fas fa-times me-2"></i> Close Sidebar
                        </button>
                    </div>
                </div>
            </div>
        </nav>
        
        <!-- Main Content Area -->
        <main class="flex-grow-1 smooth-transition" style="margin-top: var(--header-height); margin-left: var(--sidebar-width); min-height: calc(100vh - var(--header-height));">
            <div class="container-fluid p-3 p-md-4">
                <!-- Page Header -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center border-bottom">
                    <div class="d-flex align-items-center">
                        <?php if (isset($pageSubtitle)): ?>
                            <small class="text-muted ms-3 mt-1"><?php echo htmlspecialchars($pageSubtitle); ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <?php if (isset($pageActions)): ?>
                            <?php echo $pageActions; ?>
                        <?php endif; ?>
                        
                        <!-- Mobile Back Button -->
                        <button class="btn btn-outline-secondary d-lg-none ms-2 btn-touch" onclick="history.back()">
                            <i class="fas fa-arrow-left"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Mobile Breadcrumb (for nested pages) -->
                <?php 
                $path = $_SERVER['REQUEST_URI'];
                if (strpos($path, '/trainings/') !== false || strpos($path, '/users/') !== false || strpos($path, '/system/') !== false): 
                ?>
                <nav aria-label="breadcrumb" class="d-lg-none mb-4">
                    <ol class="breadcrumb bg-light p-2 rounded">
                        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/dashboard.php"><i class="fas fa-home"></i></a></li>
                        <?php if (strpos($path, '/trainings/') !== false): ?>
                            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/trainings/view.php">Trainings</a></li>
                        <?php elseif (strpos($path, '/users/') !== false): ?>
                            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/users/subscribers.php">Users</a></li>
                        <?php elseif (strpos($path, '/system/') !== false): ?>
                            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/system/settings.php">System</a></li>
                        <?php endif; ?>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo $currentPageTitle; ?></li>
                    </ol>
                </nav>
                <?php endif; ?>
                
                <!-- Page Content -->
                <div class="content-wrapper">