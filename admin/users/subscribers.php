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

// Handle subscriber actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_status'])) {
        $subscriberId = intval($_POST['subscriber_id']);
        $currentStatus = $db->fetchOne(
            "SELECT is_active FROM subscribers WHERE id = ?",
            [$subscriberId]
        )['is_active'];
        
        $newStatus = $currentStatus ? 0 : 1;
        $db->update('subscribers', 
            ['is_active' => $newStatus], 
            'id = ?', 
            [$subscriberId]
        );
        
        $_SESSION['flash_message'] = [
            'text' => 'Subscriber status updated',
            'type' => 'success'
        ];
        header('Location: subscribers.php');
        exit();
    }
    
    if (isset($_POST['delete_subscriber'])) {
        $subscriberId = intval($_POST['subscriber_id']);
        $db->delete('subscribers', 'id = ?', [$subscriberId]);
        
        $_SESSION['flash_message'] = [
            'text' => 'Subscriber deleted successfully',
            'type' => 'success'
        ];
        header('Location: subscribers.php');
        exit();
    }
    
    if (isset($_POST['export_csv'])) {
        // Get all active subscribers
        $subscribers = $db->fetchAll(
            "SELECT email, full_name, subscribed_at, source 
             FROM subscribers 
             WHERE is_active = 1 
             ORDER BY subscribed_at DESC"
        );
        
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=subscribers_' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        
        // Add CSV headers
        fputcsv($output, ['Email', 'Name', 'Subscription Date', 'Source']);
        
        // Add data
        foreach ($subscribers as $subscriber) {
            fputcsv($output, [
                $subscriber['email'],
                $subscriber['full_name'] ?? '',
                $subscriber['subscribed_at'],
                $subscriber['source'] ?? 'website'
            ]);
        }
        
        fclose($output);
        exit();
    }
    
    if (isset($_POST['bulk_action'])) {
        $selectedSubscribers = $_POST['selected_subscribers'] ?? [];
        $action = $_POST['bulk_action'];
        
        if (!empty($selectedSubscribers)) {
            switch ($action) {
                case 'activate':
                    $db->update('subscribers', 
                        ['is_active' => 1], 
                        'id IN (' . implode(',', array_fill(0, count($selectedSubscribers), '?')) . ')', 
                        $selectedSubscribers
                    );
                    $message = count($selectedSubscribers) . ' subscriber(s) activated';
                    break;
                    
                case 'deactivate':
                    $db->update('subscribers', 
                        ['is_active' => 0], 
                        'id IN (' . implode(',', array_fill(0, count($selectedSubscribers), '?')) . ')', 
                        $selectedSubscribers
                    );
                    $message = count($selectedSubscribers) . ' subscriber(s) deactivated';
                    break;
                    
                case 'delete':
                    $db->delete('subscribers', 
                        'id IN (' . implode(',', array_fill(0, count($selectedSubscribers), '?')) . ')', 
                        $selectedSubscribers
                    );
                    $message = count($selectedSubscribers) . ' subscriber(s) deleted';
                    break;
            }
            
            $_SESSION['flash_message'] = [
                'text' => $message,
                'type' => 'success'
            ];
            header('Location: subscribers.php');
            exit();
        }
    }
}

// Get filter parameters
$filterStatus = $_GET['status'] ?? 'active';
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = 30;
$offset = ($page - 1) * $perPage;

// Build query
$query = "SELECT * FROM subscribers";
$countQuery = "SELECT COUNT(*) as count FROM subscribers";
$where = [];
$params = [];

if ($filterStatus !== 'all') {
    $where[] = "is_active = ?";
    $params[] = ($filterStatus === 'active') ? 1 : 0;
}

if (!empty($search)) {
    $where[] = "(email LIKE ? OR full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
    $countQuery .= " WHERE " . implode(" AND ", $where);
}

$query .= " ORDER BY subscribed_at DESC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;

$subscribers = $db->fetchAll($query, $params);
$totalCount = $db->fetchOne($countQuery, array_slice($params, 0, -2))['count'];
$totalPages = ceil($totalCount / $perPage);

// Get stats
$stats = [
    'total' => $db->fetchOne("SELECT COUNT(*) as count FROM subscribers")['count'],
    'active' => $db->fetchOne("SELECT COUNT(*) as count FROM subscribers WHERE is_active = 1")['count'],
    'inactive' => $db->fetchOne("SELECT COUNT(*) as count FROM subscribers WHERE is_active = 0")['count'],
    'today' => $db->fetchOne("SELECT COUNT(*) as count FROM subscribers WHERE DATE(subscribed_at) = CURDATE()")['count'],
    'week' => $db->fetchOne("SELECT COUNT(*) as count FROM subscribers WHERE subscribed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['count'],
    'month' => $db->fetchOne("SELECT COUNT(*) as count FROM subscribers WHERE subscribed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['count']
];

// Get sources
$sources = $db->fetchAll(
    "SELECT source, COUNT(*) as count FROM subscribers GROUP BY source ORDER BY count DESC"
);

$pageTitle = "Newsletter Subscribers";
?>
<?php include '../../admin/includes/admin_header.php'; ?>
<?php include '../../admin/includes/admin_sidebar.php'; ?>

            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Newsletter Subscribers</h1>
                <form method="POST" action="" class="d-inline">
                    <button type="submit" name="export_csv" class="btn btn-success">
                        <i class="fas fa-file-export me-1"></i> Export CSV
                    </button>
                </form>
            </div>

            <!-- Stats -->
            <div class="row mb-4">
                <div class="col-xl-2 col-md-4 col-6 mb-3">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs fw-bold text-primary text-uppercase mb-1">
                                        Total
                                    </div>
                                    <div class="h5 mb-0 fw-bold text-gray-800"><?php echo $stats['total']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-2 col-md-4 col-6 mb-3">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs fw-bold text-success text-uppercase mb-1">
                                        Active
                                    </div>
                                    <div class="h5 mb-0 fw-bold text-gray-800"><?php echo $stats['active']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-user-check fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-2 col-md-4 col-6 mb-3">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs fw-bold text-warning text-uppercase mb-1">
                                        Inactive
                                    </div>
                                    <div class="h5 mb-0 fw-bold text-gray-800"><?php echo $stats['inactive']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-user-times fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-2 col-md-4 col-6 mb-3">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs fw-bold text-info text-uppercase mb-1">
                                        Today
                                    </div>
                                    <div class="h5 mb-0 fw-bold text-gray-800"><?php echo $stats['today']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-2 col-md-4 col-6 mb-3">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs fw-bold text-info text-uppercase mb-1">
                                        7 Days
                                    </div>
                                    <div class="h5 mb-0 fw-bold text-gray-800"><?php echo $stats['week']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar-week fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-2 col-md-4 col-6 mb-3">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs fw-bold text-info text-uppercase mb-1">
                                        30 Days
                                    </div>
                                    <div class="h5 mb-0 fw-bold text-gray-800"><?php echo $stats['month']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sources -->
            <?php if (!empty($sources)): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-tag me-2"></i> Subscription Sources</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($sources as $source): ?>
                                <div class="col-md-3 col-6 mb-2">
                                    <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                                        <span><?php echo htmlspecialchars($source['source'] ?: 'Unknown'); ?></span>
                                        <span class="badge bg-primary"><?php echo $source['count']; ?></span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="all" <?php echo $filterStatus == 'all' ? 'selected' : ''; ?>>All Subscribers</option>
                                <option value="active" <?php echo $filterStatus == 'active' ? 'selected' : ''; ?>>Active Only</option>
                                <option value="inactive" <?php echo $filterStatus == 'inactive' ? 'selected' : ''; ?>>Inactive Only</option>
                            </select>
                        </div>
                        
                        <div class="col-md-7">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="search" 
                                   name="search" 
                                   placeholder="Search by email or name..."
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="d-grid gap-2 d-md-flex">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter me-1"></i> Filter
                                </button>
                                <a href="subscribers.php" class="btn btn-secondary">
                                    <i class="fas fa-redo me-1"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Subscribers Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Subscribers (<?php echo $totalCount; ?>)</h5>
                    
                    <!-- Bulk Actions -->
                    <div class="d-flex align-items-center">
                        <select class="form-select form-select-sm me-2" id="bulkAction" style="width: auto;">
                            <option value="">Bulk Actions</option>
                            <option value="activate">Activate</option>
                            <option value="deactivate">Deactivate</option>
                            <option value="delete">Delete</option>
                        </select>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="applyBulkAction">
                            Apply
                        </button>
                    </div>
                </div>
                
                <div class="card-body">
                    <?php if (empty($subscribers)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5>No subscribers found</h5>
                            <p class="text-muted">
                                <?php if ($filterStatus !== 'all' || !empty($search)): ?>
                                    Try changing your filters or search terms.
                                <?php else: ?>
                                    No newsletter subscribers yet.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="" id="bulkForm">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th width="50">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="selectAll">
                                                </div>
                                            </th>
                                            <th>Subscriber</th>
                                            <th>Status</th>
                                            <th>Source</th>
                                            <th>Subscribed</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($subscribers as $subscriber): ?>
                                            <tr>
                                                <td>
                                                    <div class="form-check">
                                                        <input class="form-check-input subscriber-checkbox" 
                                                               type="checkbox" 
                                                               name="selected_subscribers[]" 
                                                               value="<?php echo $subscriber['id']; ?>">
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($subscriber['email']); ?></strong>
                                                        <?php if ($subscriber['full_name']): ?>
                                                            <div class="text-muted small">
                                                                <?php echo htmlspecialchars($subscriber['full_name']); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if ($subscriber['is_active']): ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php echo htmlspecialchars($subscriber['source'] ?: 'Website'); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php echo date('M j, Y', strtotime($subscriber['subscribed_at'])); ?><br>
                                                        <?php echo date('g:i A', strtotime($subscriber['subscribed_at'])); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <form method="POST" action="" class="d-inline">
                                                            <input type="hidden" name="subscriber_id" value="<?php echo $subscriber['id']; ?>">
                                                            <button type="submit" name="toggle_status" class="btn btn-sm btn-<?php echo $subscriber['is_active'] ? 'warning' : 'success'; ?>" 
                                                                    title="<?php echo $subscriber['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                                <i class="fas fa-<?php echo $subscriber['is_active'] ? 'times' : 'check'; ?>"></i>
                                                            </button>
                                                            <button type="submit" name="delete_subscriber" 
                                                                    class="btn btn-sm btn-danger" 
                                                                    onclick="return confirm('Are you sure you want to delete this subscriber?');"
                                                                    title="Delete">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Bulk Action Hidden Input -->
                            <input type="hidden" name="bulk_action" id="bulkActionInput" value="">
                        </form>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Page navigation" class="mt-4">
                                <ul class="pagination justify-content-center">

                                    <!-- Prev -->
                                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link"
                                        href="?page=<?= max(1, $page - 1) ?>&status=<?= $filterStatus ?>&search=<?= urlencode($search) ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>

                                    <?php
                                    $range = 2;
                                    $ellipsisShown = false;

                                    for ($i = 1; $i <= $totalPages; $i++) {
                                        if (
                                            $i == 1 ||
                                            $i == $totalPages ||
                                            ($i >= $page - $range && $i <= $page + $range)
                                        ) {
                                            $ellipsisShown = false;
                                            ?>
                                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                <a class="page-link"
                                                href="?page=<?= $i ?>&status=<?= $filterStatus ?>&search=<?= urlencode($search) ?>">
                                                    <?= $i ?>
                                                </a>
                                            </li>
                                            <?php
                                        } elseif (!$ellipsisShown) {
                                            $ellipsisShown = true;
                                            ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">…</span>
                                            </li>
                                            <?php
                                        }
                                    }
                                    ?>

                                    <!-- Next -->
                                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                        <a class="page-link"
                                        href="?page=<?= min($totalPages, $page + 1) ?>&status=<?= $filterStatus ?>&search=<?= urlencode($search) ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>

                                </ul>
                            </nav>
                        <?php endif; ?>

                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select All checkbox
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.subscriber-checkbox');
    
    selectAll.addEventListener('change', function() {
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });
    
    // Update select all when individual checkboxes change
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            selectAll.checked = allChecked;
        });
    });
    
    // Bulk actions
    const bulkActionSelect = document.getElementById('bulkAction');
    const applyBulkAction = document.getElementById('applyBulkAction');
    const bulkForm = document.getElementById('bulkForm');
    const bulkActionInput = document.getElementById('bulkActionInput');
    
    applyBulkAction.addEventListener('click', function() {
        const selectedAction = bulkActionSelect.value;
        const selectedSubscribers = Array.from(checkboxes).filter(cb => cb.checked);
        
        if (!selectedAction) {
            alert('Please select a bulk action.');
            return;
        }
        
        if (selectedSubscribers.length === 0) {
            alert('Please select at least one subscriber.');
            return;
        }
        
        if (selectedAction === 'delete') {
            if (!confirm(`Are you sure you want to delete ${selectedSubscribers.length} subscriber(s)? This action cannot be undone.`)) {
                return;
            }
        } else {
            if (!confirm(`Are you sure you want to ${selectedAction} ${selectedSubscribers.length} subscriber(s)?`)) {
                return;
            }
        }
        
        bulkActionInput.value = selectedAction;
        bulkForm.submit();
    });
});
</script>

<?php include '../../admin/includes/admin_footer.php'; ?>