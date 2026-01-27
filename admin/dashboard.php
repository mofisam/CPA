<?php
require_once '../config/environment.php';
require_once '../includes/core/Database.php';
require_once '../includes/core/Functions.php';
require_once '../includes/core/Auth.php';

use includes\core\Auth;
use includes\core\Database;

// Check admin authentication
Auth::requireAdmin();

// Get database instance
$db = Database::getInstance();

// Get admin info
$admin = Auth::getCurrentAdmin();

// Get statistics
$stats = [];

// Total trainings
$stats['total_trainings'] = $db->fetchOne("SELECT COUNT(*) as count FROM trainings")['count'];

// Active trainings
$stats['active_trainings'] = $db->fetchOne(
    "SELECT COUNT(*) as count FROM trainings WHERE status = 'active'"
)['count'];

// Upcoming trainings
$stats['upcoming_trainings'] = $db->fetchOne(
    "SELECT COUNT(*) as count FROM trainings WHERE status = 'upcoming'"
)['count'];

// Total subscribers
$stats['total_subscribers'] = $db->fetchOne(
    "SELECT COUNT(*) as count FROM subscribers WHERE is_active = 1"
)['count'];

// Unread messages
$stats['unread_messages'] = $db->fetchOne(
    "SELECT COUNT(*) as count FROM contact_messages WHERE is_read = 0"
)['count'];

// Recent registrations (last 30 days)
$stats['recent_registrations'] = $db->fetchOne(
    "SELECT COUNT(*) as count FROM contact_messages 
     WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
)['count'];

// Get recent trainings
$recent_trainings = $db->fetchAll(
    "SELECT t.*, a.full_name as author_name 
     FROM trainings t 
     LEFT JOIN admins a ON t.created_by = a.id 
     ORDER BY t.created_at DESC 
     LIMIT 5"
);

// Get recent messages
$recent_messages = $db->fetchAll(
    "SELECT * FROM contact_messages 
     ORDER BY submitted_at DESC 
     LIMIT 5"
);

// Get recent subscribers
$recent_subscribers = $db->fetchAll(
    "SELECT * FROM subscribers 
     WHERE is_active = 1 
     ORDER BY subscribed_at DESC 
     LIMIT 5"
);

// Page title
$pageTitle = "Dashboard";
?>
<?php include 'includes/admin_header.php'; ?>
<?php include 'includes/admin_sidebar.php'; ?>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stats-card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs fw-bold text-primary text-uppercase mb-1">
                                        Total Trainings
                                    </div>
                                    <div class="h5 mb-0 fw-bold text-gray-800">
                                        <?php echo $stats['total_trainings']; ?>
                                    </div>
                                    <div class="mt-2">
                                        <span class="badge bg-primary">Active: <?php echo $stats['active_trainings']; ?></span>
                                        <span class="badge bg-info">Upcoming: <?php echo $stats['upcoming_trainings']; ?></span>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="stats-card-icon bg-primary text-white">
                                        <i class="fas fa-graduation-cap"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stats-card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs fw-bold text-success text-uppercase mb-1">
                                        Newsletter Subscribers
                                    </div>
                                    <div class="h5 mb-0 fw-bold text-gray-800">
                                        <?php echo $stats['total_subscribers']; ?>
                                    </div>
                                    <div class="mt-2">
                                        <span class="text-success small">
                                            <i class="fas fa-arrow-up"></i> +<?php echo $stats['recent_registrations']; ?> (30d)
                                        </span>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="stats-card-icon bg-success text-white">
                                        <i class="fas fa-users"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stats-card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs fw-bold text-warning text-uppercase mb-1">
                                        Contact Messages
                                    </div>
                                    <div class="row no-gutters align-items-center">
                                        <div class="col-auto">
                                            <div class="h5 mb-0 mr-3 fw-bold text-gray-800">
                                                <?php echo $stats['unread_messages']; ?> unread
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <a href="users/messages.php" class="btn btn-sm btn-warning">
                                            <i class="fas fa-eye me-1"></i> View Messages
                                        </a>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="stats-card-icon bg-warning text-white">
                                        <i class="fas fa-comments"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stats-card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs fw-bold text-info text-uppercase mb-1">
                                        Recent Activity
                                    </div>
                                    <div class="h5 mb-0 fw-bold text-gray-800">
                                        Welcome, <?php echo htmlspecialchars($admin['full_name'] ?? 'Admin'); ?>!
                                    </div>
                                    <div class="mt-2">
                                        <span class="text-info small">
                                            <i class="fas fa-clock me-1"></i>
                                            Last login: <?php echo date('M j, g:i A', strtotime($admin['last_login'] ?? 'now')); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="stats-card-icon bg-info text-white">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Trainings -->
            <div class="row">
                <div class="col-lg-8 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 fw-bold text-primary">Recent Trainings</h6>
                            <a href="trainings/add.php" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus me-1"></i> Add New
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($recent_trainings)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-4">
                                                    <i class="fas fa-info-circle text-muted me-2"></i>
                                                    No trainings found. <a href="trainings/add.php">Create your first training</a>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($recent_trainings as $training): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($training['title']); ?></strong>
                                                        <div class="text-muted small">
                                                            <?php echo htmlspecialchars(substr($training['short_description'], 0, 50)); ?>...
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary">
                                                            <?php echo ucfirst($training['course_type']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                            $statusColors = [
                                                                'draft' => 'secondary',
                                                                'active' => 'success',
                                                                'upcoming' => 'info',
                                                                'completed' => 'dark'
                                                            ];
                                                            $color = $statusColors[$training['status']] ?? 'secondary';
                                                        ?>
                                                        <span class="badge bg-<?php echo $color; ?>">
                                                            <?php echo ucfirst($training['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <?php echo date('M j, Y', strtotime($training['created_at'])); ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <div class="action-buttons">
                                                            <a href="trainings/view.php?id=<?php echo $training['id']; ?>" 
                                                               class="btn btn-sm btn-info" 
                                                               title="View">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="trainings/edit.php?id=<?php echo $training['id']; ?>" 
                                                               class="btn btn-sm btn-warning" 
                                                               title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center mt-3">
                                <a href="trainings/view.php" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-list me-1"></i> View All Trainings
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Sidebar -->
                <div class="col-lg-4 mb-4">
                    <!-- Recent Messages -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 fw-bold text-primary">Recent Messages</h6>
                        </div>
                        <div class="card-body">
                            <div class="activity-timeline">
                                <?php if (empty($recent_messages)): ?>
                                    <div class="text-center py-3">
                                        <i class="fas fa-inbox text-muted fa-2x mb-2"></i>
                                        <p class="text-muted">No messages yet</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($recent_messages as $message): ?>
                                        <div class="activity-item">
                                            <div class="d-flex justify-content-between">
                                                <strong><?php echo htmlspecialchars($message['name']); ?></strong>
                                                <small class="text-muted">
                                                    <?php echo date('g:i A', strtotime($message['submitted_at'])); ?>
                                                </small>
                                            </div>
                                            <p class="mb-1 small text-truncate"><?php echo htmlspecialchars($message['subject'] ?: 'No subject'); ?></p>
                                            <small class="text-muted"><?php echo htmlspecialchars(substr($message['message'], 0, 50)); ?>...</small>
                                            <div class="mt-2">
                                                <a href="users/messages.php#message-<?php echo $message['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-reply me-1"></i> Reply
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <div class="text-center mt-3">
                                <a href="users/messages.php" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-envelope me-1"></i> View All Messages
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 fw-bold text-primary">Quick Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="trainings/add.php" class="btn btn-primary">
                                    <i class="fas fa-plus-circle me-2"></i> Add New Training
                                </a>
                                <a href="users/subscribers.php" class="btn btn-success">
                                    <i class="fas fa-users me-2"></i> View Subscribers
                                </a>
                                <a href="system/settings.php" class="btn btn-info">
                                    <i class="fas fa-cog me-2"></i> Site Settings
                                </a>
                                <a href="profile.php" class="btn btn-warning">
                                    <i class="fas fa-user-cog me-2"></i> Profile Settings
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<script>
// Initialize DataTables
$(document).ready(function() {
    // Toggle sidebar on mobile
    $('#sidebarToggle').click(function() {
        $('#sidebar').toggleClass('show');
    });
    
    // Close sidebar when clicking outside on mobile
    $(document).click(function(event) {
        if ($(window).width() <= 768) {
            if (!$(event.target).closest('#sidebar, #sidebarToggle').length) {
                $('#sidebar').removeClass('show');
            }
        }
    });
});
</script>

<?php include 'includes/admin_footer.php'; ?>