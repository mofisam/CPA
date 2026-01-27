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

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    if (isset($_POST['selected_trainings']) && !empty($_POST['selected_trainings'])) {
        $selectedIds = $_POST['selected_trainings'];
        $action = $_POST['bulk_action'];
        
        try {
            $db->beginTransaction();
            
            switch ($action) {
                case 'delete':
                    foreach ($selectedIds as $id) {
                        // Get training to delete image
                        $training = $db->fetchOne("SELECT featured_image FROM trainings WHERE id = ?", [$id]);
                        if ($training && $training['featured_image']) {
                            if (file_exists('../../' . $training['featured_image'])) {
                                unlink('../../' . $training['featured_image']);
                            }
                        }
                        
                        // Delete related data
                        $db->delete('course_modules', 'training_id = ?', [$id]);
                        $db->delete('target_audience', 'training_id = ?', [$id]);
                        $db->delete('training_features', 'training_id = ?', [$id]);
                        
                        // Delete training
                        $db->delete('trainings', 'id = ?', [$id]);
                    }
                    $message = count($selectedIds) . ' training(s) deleted successfully';
                    break;
                    
                case 'activate':
                    $db->update('trainings', ['status' => 'active'], "id IN (" . implode(',', array_fill(0, count($selectedIds), '?')) . ")", $selectedIds);
                    $message = count($selectedIds) . ' training(s) activated';
                    break;
                    
                case 'deactivate':
                    $db->update('trainings', ['status' => 'draft'], "id IN (" . implode(',', array_fill(0, count($selectedIds), '?')) . ")", $selectedIds);
                    $message = count($selectedIds) . ' training(s) marked as draft';
                    break;
                    
                case 'mark_upcoming':
                    $db->update('trainings', ['status' => 'upcoming'], "id IN (" . implode(',', array_fill(0, count($selectedIds), '?')) . ")", $selectedIds);
                    $message = count($selectedIds) . ' training(s) marked as upcoming';
                    break;
                    
                case 'mark_completed':
                    $db->update('trainings', ['status' => 'completed'], "id IN (" . implode(',', array_fill(0, count($selectedIds), '?')) . ")", $selectedIds);
                    $message = count($selectedIds) . ' training(s) marked as completed';
                    break;
            }
            
            $db->commit();
            
            $_SESSION['flash_message'] = [
                'text' => $message,
                'type' => 'success'
            ];
            header('Location: view.php');
            exit();
            
        } catch (Exception $e) {
            $db->rollback();
            $error = 'Error performing bulk action: ' . $e->getMessage();
        }
    }
}

// Get filter parameters
$filterType = $_GET['type'] ?? 'all';
$filterStatus = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Build query
$query = "SELECT t.*, a.full_name as author_name FROM trainings t LEFT JOIN admins a ON t.created_by = a.id";
$countQuery = "SELECT COUNT(*) as count FROM trainings t";
$where = [];
$params = [];

// Apply filters
if ($filterType !== 'all') {
    $where[] = "t.course_type = ?";
    $params[] = $filterType;
}

if ($filterStatus !== 'all') {
    $where[] = "t.status = ?";
    $params[] = $filterStatus;
}

if (!empty($search)) {
    $where[] = "(t.title LIKE ? OR t.short_description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Add WHERE clause if needed
if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
    $countQuery .= " WHERE " . implode(" AND ", $where);
}

// Add ordering and pagination
$query .= " ORDER BY t.created_at DESC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;

// Execute queries
$trainings = $db->fetchAll($query, $params);
$totalCount = $db->fetchOne($countQuery, array_slice($params, 0, -2))['count'];
$totalPages = ceil($totalCount / $perPage);

// Get statistics
$stats = $db->fetchAll(
    "SELECT status, COUNT(*) as count FROM trainings GROUP BY status"
);

$typeStats = $db->fetchAll(
    "SELECT course_type, COUNT(*) as count FROM trainings GROUP BY course_type"
);

$pageTitle = "Manage Trainings";
?>
<?php include '../../admin/includes/admin_header.php'; ?>
<?php include '../../admin/includes/admin_sidebar.php'; ?>

            <!-- Page Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Trainings</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="add.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus me-1"></i> Add New Training
                    </a>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="row mb-4">
                <?php 
                $statusColors = [
                    'active' => 'success',
                    'draft' => 'secondary',
                    'upcoming' => 'info',
                    'completed' => 'dark'
                ];
                
                foreach ($stats as $stat): ?>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-left-<?php echo $statusColors[$stat['status']] ?? 'secondary'; ?> shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs fw-bold text-uppercase mb-1 text-<?php echo $statusColors[$stat['status']] ?? 'secondary'; ?>">
                                        <?php echo ucfirst($stat['status']); ?> Trainings
                                    </div>
                                    <div class="h5 mb-0 fw-bold text-gray-800"><?php echo $stat['count']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-graduation-cap fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Filters and Search -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-3">
                            <label for="type" class="form-label">Course Type</label>
                            <select class="form-select" id="type" name="type">
                                <option value="all" <?php echo $filterType == 'all' ? 'selected' : ''; ?>>All Types</option>
                                <option value="echocardiography" <?php echo $filterType == 'echocardiography' ? 'selected' : ''; ?>>Echocardiography</option>
                                <option value="ecg_masterclass" <?php echo $filterType == 'ecg_masterclass' ? 'selected' : ''; ?>>ECG Masterclass</option>
                                <option value="other" <?php echo $filterType == 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="all" <?php echo $filterStatus == 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="draft" <?php echo $filterStatus == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="upcoming" <?php echo $filterStatus == 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                                <option value="active" <?php echo $filterStatus == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="completed" <?php echo $filterStatus == 'completed' ? 'selected' : ''; ?>>Completed</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="search" 
                                   name="search" 
                                   placeholder="Search by title or description..."
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="d-grid gap-2 d-md-flex">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i> Filter
                                </button>
                                <a href="view.php" class="btn btn-secondary">
                                    <i class="fas fa-redo me-1"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Trainings Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Trainings (<?php echo $totalCount; ?>)</h5>
                    
                    <!-- Bulk Actions -->
                    <div class="d-flex align-items-center">
                        <select class="form-select form-select-sm me-2" id="bulkAction" style="width: auto;">
                            <option value="">Bulk Actions</option>
                            <option value="activate">Activate</option>
                            <option value="deactivate">Mark as Draft</option>
                            <option value="mark_upcoming">Mark as Upcoming</option>
                            <option value="mark_completed">Mark as Completed</option>
                            <option value="delete">Delete</option>
                        </select>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="applyBulkAction">
                            Apply
                        </button>
                    </div>
                </div>
                
                <div class="card-body">
                    <?php if (empty($trainings)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-graduation-cap fa-3x text-muted mb-3"></i>
                            <h5>No trainings found</h5>
                            <p class="text-muted">
                                <?php if ($filterType !== 'all' || $filterStatus !== 'all' || !empty($search)): ?>
                                    Try changing your filters or search terms.
                                <?php else: ?>
                                    Get started by <a href="add.php">creating your first training</a>.
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
                                            <th>Training</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                            <th>Dates</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($trainings as $training): ?>
                                            <tr>
                                                <td>
                                                    <div class="form-check">
                                                        <input class="form-check-input training-checkbox" 
                                                               type="checkbox" 
                                                               name="selected_trainings[]" 
                                                               value="<?php echo $training['id']; ?>">
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-start">
                                                        <?php if ($training['featured_image']): ?>
                                                            <img src="../../../assets/images/trainings/<?php echo $training['featured_image']; ?>" 
                                                                 class="rounded me-3" 
                                                                 style="width: 60px; height: 40px; object-fit: cover;">
                                                        <?php else: ?>
                                                            <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center" 
                                                                 style="width: 60px; height: 40px;">
                                                                <i class="fas fa-graduation-cap text-muted"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($training['title']); ?></strong>
                                                            <div class="text-muted small">
                                                                <?php echo htmlspecialchars(substr($training['short_description'], 0, 60)); ?>...
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        <?php echo ucfirst($training['course_type']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php 
                                                        $color = $statusColors[$training['status']] ?? 'secondary';
                                                    ?>
                                                    <span class="badge bg-<?php echo $color; ?>">
                                                        <?php echo ucfirst($training['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($training['start_date']): ?>
                                                        <small class="text-muted">
                                                            <?php echo date('M j', strtotime($training['start_date'])); ?>
                                                            <?php if ($training['end_date']): ?>
                                                                - <?php echo date('M j', strtotime($training['end_date'])); ?>
                                                            <?php endif; ?>
                                                        </small>
                                                    <?php else: ?>
                                                        <span class="text-muted small">Not set</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php echo date('M j, Y', strtotime($training['created_at'])); ?><br>
                                                        <small>by <?php echo htmlspecialchars($training['author_name'] ?? 'Admin'); ?></small>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <a href="../pages/training-detail.php?slug=<?php echo $training['slug']; ?>" 
                                                           target="_blank" 
                                                           class="btn btn-sm btn-info" 
                                                           title="Preview">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="edit.php?id=<?php echo $training['id']; ?>" 
                                                           class="btn btn-sm btn-warning" 
                                                           title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="delete.php?id=<?php echo $training['id']; ?>" 
                                                           class="btn btn-sm btn-danger" 
                                                           title="Delete"
                                                           onclick="return confirm('Are you sure you want to delete this training?');">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
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
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" 
                                       href="?page=<?php echo $page - 1; ?>&type=<?php echo $filterType; ?>&status=<?php echo $filterStatus; ?>&search=<?php echo urlencode($search); ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                                
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" 
                                               href="?page=<?php echo $i; ?>&type=<?php echo $filterType; ?>&status=<?php echo $filterStatus; ?>&search=<?php echo urlencode($search); ?>">
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
                                       href="?page=<?php echo $page + 1; ?>&type=<?php echo $filterType; ?>&status=<?php echo $filterStatus; ?>&search=<?php echo urlencode($search); ?>">
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
    const checkboxes = document.querySelectorAll('.training-checkbox');
    
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
        const selectedTrainings = Array.from(checkboxes).filter(cb => cb.checked);
        
        if (!selectedAction) {
            alert('Please select a bulk action.');
            return;
        }
        
        if (selectedTrainings.length === 0) {
            alert('Please select at least one training.');
            return;
        }
        
        // Confirm dangerous actions
        if (selectedAction === 'delete') {
            if (!confirm(`Are you sure you want to delete ${selectedTrainings.length} training(s)? This action cannot be undone.`)) {
                return;
            }
        } else {
            if (!confirm(`Are you sure you want to ${selectedAction.replace('_', ' ')} ${selectedTrainings.length} training(s)?`)) {
                return;
            }
        }
        
        bulkActionInput.value = selectedAction;
        bulkForm.submit();
    });
    
    // DataTable initialization (if DataTables is included)
    if ($.fn.DataTable) {
        $('table').DataTable({
            pageLength: 10,
            order: [[5, 'desc']],
            columnDefs: [
                { orderable: false, targets: [0, 6] }
            ]
        });
    }
});
</script>

<?php include '../../admin/includes/admin_footer.php'; ?>