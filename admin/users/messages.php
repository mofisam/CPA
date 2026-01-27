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

// Handle message actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_read'])) {
        $messageId = intval($_POST['message_id']);
        $db->update('contact_messages', 
            ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')], 
            'id = ?', 
            [$messageId]
        );
        
        $_SESSION['flash_message'] = [
            'text' => 'Message marked as read',
            'type' => 'success'
        ];
        header('Location: messages.php');
        exit();
    }
    
    if (isset($_POST['delete_message'])) {
        $messageId = intval($_POST['message_id']);
        $db->delete('contact_messages', 'id = ?', [$messageId]);
        
        $_SESSION['flash_message'] = [
            'text' => 'Message deleted successfully',
            'type' => 'success'
        ];
        header('Location: messages.php');
        exit();
    }
    
    if (isset($_POST['bulk_action'])) {
        $selectedMessages = $_POST['selected_messages'] ?? [];
        $action = $_POST['bulk_action'];
        
        if (!empty($selectedMessages)) {
            switch ($action) {
                case 'mark_read':
                    $db->update('contact_messages', 
                        ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')], 
                        'id IN (' . implode(',', array_fill(0, count($selectedMessages), '?')) . ')', 
                        $selectedMessages
                    );
                    $message = count($selectedMessages) . ' message(s) marked as read';
                    break;
                    
                case 'mark_unread':
                    $db->update('contact_messages', 
                        ['is_read' => 0, 'read_at' => NULL], 
                        'id IN (' . implode(',', array_fill(0, count($selectedMessages), '?')) . ')', 
                        $selectedMessages
                    );
                    $message = count($selectedMessages) . ' message(s) marked as unread';
                    break;
                    
                case 'delete':
                    $db->delete('contact_messages', 
                        'id IN (' . implode(',', array_fill(0, count($selectedMessages), '?')) . ')', 
                        $selectedMessages
                    );
                    $message = count($selectedMessages) . ' message(s) deleted';
                    break;
            }
            
            $_SESSION['flash_message'] = [
                'text' => $message,
                'type' => 'success'
            ];
            header('Location: messages.php');
            exit();
        }
    }
}

// Get filter parameters
$filterRead = $_GET['read'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build query
$query = "SELECT * FROM contact_messages";
$countQuery = "SELECT COUNT(*) as count FROM contact_messages";
$where = [];
$params = [];

if ($filterRead !== 'all') {
    $where[] = "is_read = ?";
    $params[] = ($filterRead === 'read') ? 1 : 0;
}

if (!empty($search)) {
    $where[] = "(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
    $countQuery .= " WHERE " . implode(" AND ", $where);
}

$query .= " ORDER BY submitted_at DESC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;

$messages = $db->fetchAll($query, $params);
$totalCount = $db->fetchOne($countQuery, array_slice($params, 0, -2))['count'];
$totalPages = ceil($totalCount / $perPage);

// Get stats
$stats = [
    'total' => $db->fetchOne("SELECT COUNT(*) as count FROM contact_messages")['count'],
    'unread' => $db->fetchOne("SELECT COUNT(*) as count FROM contact_messages WHERE is_read = 0")['count'],
    'today' => $db->fetchOne("SELECT COUNT(*) as count FROM contact_messages WHERE DATE(submitted_at) = CURDATE()")['count'],
    'week' => $db->fetchOne("SELECT COUNT(*) as count FROM contact_messages WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['count']
];

$pageTitle = "Contact Messages";
?>
<?php include '../../admin/includes/admin_header.php'; ?>
<?php include '../../admin/includes/admin_sidebar.php'; ?>

            <!-- Stats -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs fw-bold text-primary text-uppercase mb-1">
                                        Total Messages
                                    </div>
                                    <div class="h5 mb-0 fw-bold text-gray-800"><?php echo $stats['total']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-envelope fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs fw-bold text-warning text-uppercase mb-1">
                                        Unread Messages
                                    </div>
                                    <div class="h5 mb-0 fw-bold text-gray-800"><?php echo $stats['unread']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-envelope-open-text fa-2x text-gray-300"></i>
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
                
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs fw-bold text-info text-uppercase mb-1">
                                        Last 7 Days
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
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-4">
                            <label for="read" class="form-label">Status</label>
                            <select class="form-select" id="read" name="read">
                                <option value="all" <?php echo $filterRead == 'all' ? 'selected' : ''; ?>>All Messages</option>
                                <option value="unread" <?php echo $filterRead == 'unread' ? 'selected' : ''; ?>>Unread Only</option>
                                <option value="read" <?php echo $filterRead == 'read' ? 'selected' : ''; ?>>Read Only</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="search" 
                                   name="search" 
                                   placeholder="Search by name, email, subject, or message..."
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="d-grid gap-2 d-md-flex">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter me-1"></i> Filter
                                </button>
                                <a href="messages.php" class="btn btn-secondary">
                                    <i class="fas fa-redo me-1"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Messages Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Messages (<?php echo $totalCount; ?>)</h5>
                    
                    <!-- Bulk Actions -->
                    <div class="d-flex align-items-center">
                        <select class="form-select form-select-sm me-2" id="bulkAction" style="width: auto;">
                            <option value="">Bulk Actions</option>
                            <option value="mark_read">Mark as Read</option>
                            <option value="mark_unread">Mark as Unread</option>
                            <option value="delete">Delete</option>
                        </select>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="applyBulkAction">
                            Apply
                        </button>
                    </div>
                </div>
                
                <div class="card-body">
                    <?php if (empty($messages)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5>No messages found</h5>
                            <p class="text-muted">
                                <?php if ($filterRead !== 'all' || !empty($search)): ?>
                                    Try changing your filters or search terms.
                                <?php else: ?>
                                    No contact messages have been received yet.
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
                                            <th>From</th>
                                            <th>Subject</th>
                                            <th>Message</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($messages as $msg): ?>
                                            <tr class="<?php echo !$msg['is_read'] ? 'table-active' : ''; ?>" id="message-<?php echo $msg['id']; ?>">
                                                <td>
                                                    <div class="form-check">
                                                        <input class="form-check-input message-checkbox" 
                                                               type="checkbox" 
                                                               name="selected_messages[]" 
                                                               value="<?php echo $msg['id']; ?>">
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($msg['name']); ?></strong>
                                                        <div class="text-muted small">
                                                            <?php echo htmlspecialchars($msg['email']); ?>
                                                            <?php if ($msg['phone']): ?>
                                                                <br><?php echo htmlspecialchars($msg['phone']); ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($msg['subject'] ?: 'No subject'); ?>
                                                </td>
                                                <td>
                                                    <small class="text-truncate d-block" style="max-width: 200px;">
                                                        <?php echo htmlspecialchars(substr($msg['message'], 0, 80)); ?>...
                                                    </small>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php echo date('M j, Y', strtotime($msg['submitted_at'])); ?><br>
                                                        <?php echo date('g:i A', strtotime($msg['submitted_at'])); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php if ($msg['is_read']): ?>
                                                        <span class="badge bg-success">Read</span>
                                                        <?php if ($msg['read_at']): ?>
                                                            <div class="text-muted small">
                                                                <?php echo date('M j', strtotime($msg['read_at'])); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">Unread</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <button type="button" 
                                                                class="btn btn-sm btn-primary" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#viewModal<?php echo $msg['id']; ?>">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure?');">
                                                            <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                                            <?php if (!$msg['is_read']): ?>
                                                                <button type="submit" name="mark_read" class="btn btn-sm btn-success" title="Mark as Read">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                            <button type="submit" name="delete_message" class="btn btn-sm btn-danger" title="Delete">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                            
                                            <!-- View Modal -->
                                            <div class="modal fade" id="viewModal<?php echo $msg['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Message from <?php echo htmlspecialchars($msg['name']); ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="row mb-4">
                                                                <div class="col-md-6">
                                                                    <p><strong>Name:</strong> <?php echo htmlspecialchars($msg['name']); ?></p>
                                                                    <p><strong>Email:</strong> 
                                                                        <a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>">
                                                                            <?php echo htmlspecialchars($msg['email']); ?>
                                                                        </a>
                                                                    </p>
                                                                    <?php if ($msg['phone']): ?>
                                                                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($msg['phone']); ?></p>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <p><strong>Subject:</strong> <?php echo htmlspecialchars($msg['subject'] ?: 'No subject'); ?></p>
                                                                    <p><strong>Received:</strong> <?php echo date('F j, Y g:i A', strtotime($msg['submitted_at'])); ?></p>
                                                                    <?php if ($msg['is_read'] && $msg['read_at']): ?>
                                                                        <p><strong>Read:</strong> <?php echo date('F j, Y g:i A', strtotime($msg['read_at'])); ?></p>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="mb-4">
                                                                <h6 class="border-bottom pb-2">Message</h6>
                                                                <div class="p-3 bg-light rounded">
                                                                    <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="d-flex justify-content-between">
                                                                <div>
                                                                    <?php if (!$msg['is_read']): ?>
                                                                        <form method="POST" action="" class="d-inline">
                                                                            <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                                                            <button type="submit" name="mark_read" class="btn btn-success">
                                                                                <i class="fas fa-check me-1"></i> Mark as Read
                                                                            </button>
                                                                        </form>
                                                                    <?php endif; ?>
                                                                    <form method="POST" action="" class="d-inline" onsubmit="return confirm('Delete this message?');">
                                                                        <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                                                        <button type="submit" name="delete_message" class="btn btn-danger">
                                                                            <i class="fas fa-trash me-1"></i> Delete
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
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
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" 
                                       href="?page=<?php echo $page - 1; ?>&read=<?php echo $filterRead; ?>&search=<?php echo urlencode($search); ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                                
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" 
                                               href="?page=<?php echo $i; ?>&read=<?php echo $filterRead; ?>&search=<?php echo urlencode($search); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" 
                                       href="?page=<?php echo $page + 1; ?>&read=<?php echo $filterRead; ?>&search=<?php echo urlencode($search); ?>">
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
    const checkboxes = document.querySelectorAll('.message-checkbox');
    
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
        const selectedMessages = Array.from(checkboxes).filter(cb => cb.checked);
        
        if (!selectedAction) {
            alert('Please select a bulk action.');
            return;
        }
        
        if (selectedMessages.length === 0) {
            alert('Please select at least one message.');
            return;
        }
        
        if (selectedAction === 'delete') {
            if (!confirm(`Are you sure you want to delete ${selectedMessages.length} message(s)? This action cannot be undone.`)) {
                return;
            }
        } else {
            if (!confirm(`Are you sure you want to ${selectedAction.replace('_', ' ')} ${selectedMessages.length} message(s)?`)) {
                return;
            }
        }
        
        bulkActionInput.value = selectedAction;
        bulkForm.submit();
    });
    
    // Mark as read when viewing message
    const viewModals = document.querySelectorAll('[id^="viewModal"]');
    viewModals.forEach(modal => {
        modal.addEventListener('show.bs.modal', function() {
            const messageId = this.id.replace('viewModal', '');
            const messageRow = document.getElementById('message-' + messageId);
            if (messageRow && messageRow.classList.contains('table-active')) {
                // Mark as read via AJAX
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'mark_read=1&message_id=' + messageId
                })
                .then(() => {
                    messageRow.classList.remove('table-active');
                    const statusBadge = messageRow.querySelector('.badge');
                    if (statusBadge) {
                        statusBadge.className = 'badge bg-success';
                        statusBadge.textContent = 'Read';
                    }
                });
            }
        });
    });
});
</script>

<?php include '../../admin/includes/admin_footer.php'; ?>