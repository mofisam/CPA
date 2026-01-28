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

// Handle registration actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $registrationId = intval($_POST['registration_id']);
        $status = $_POST['status'];
        $notes = trim($_POST['notes'] ?? '');
        
        $updateData = ['status' => $status];
        if ($status === 'contacted') {
            $updateData['contacted_at'] = date('Y-m-d H:i:s');
        }
        if ($notes) {
            $updateData['notes'] = $notes;
        }
        
        $db->update('course_interests', $updateData, 'id = ?', [$registrationId]);
        
        $_SESSION['flash_message'] = [
            'text' => 'Registration status updated successfully!',
            'type' => 'success'
        ];
        header('Location: registrations.php' . (isset($_GET['training']) ? '?training=' . $_GET['training'] : ''));
        exit();
    }
    
    if (isset($_POST['delete_registration'])) {
        $registrationId = intval($_POST['registration_id']);
        $db->delete('course_interests', 'id = ?', [$registrationId]);
        
        $_SESSION['flash_message'] = [
            'text' => 'Registration deleted successfully',
            'type' => 'success'
        ];
        header('Location: registrations.php');
        exit();
    }
    
    if (isset($_POST['export_registrations'])) {
        // Get filter parameters for export
        $trainingId = $_GET['training'] ?? null;
        $filterStatus = $_GET['status'] ?? 'all';
        
        // Build query for export
        $query = "SELECT ci.*, t.title as training_title 
                  FROM course_interests ci 
                  LEFT JOIN trainings t ON ci.training_id = t.id 
                  WHERE 1=1";
        $params = [];
        
        if ($trainingId) {
            $query .= " AND ci.training_id = ?";
            $params[] = $trainingId;
        }
        
        if ($filterStatus !== 'all') {
            $query .= " AND ci.status = ?";
            $params[] = $filterStatus;
        }
        
        $query .= " ORDER BY ci.submitted_at DESC";
        
        $registrations = $db->fetchAll($query, $params);
        
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=registrations_' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        
        // Add CSV headers
        fputcsv($output, [
            'ID', 'Name', 'Email', 'Phone', 'Profession', 
            'Qualification', 'Training', 'Status', 'Submitted', 
            'Contacted', 'Notes'
        ]);
        
        // Add data
        foreach ($registrations as $reg) {
            $examPrep = $reg['exam_preparation'] ? json_decode($reg['exam_preparation'], true) : [];
            $examPrepText = is_array($examPrep) ? implode(', ', $examPrep) : '';
            
            fputcsv($output, [
                $reg['id'],
                $reg['full_name'],
                $reg['email'],
                $reg['phone'] ?? '',
                $reg['profession'],
                $reg['qualification'] ?? '',
                $reg['training_title'] ?? 'General Interest',
                $reg['status'],
                $reg['submitted_at'],
                $reg['contacted_at'] ?? '',
                $reg['notes'] ?? ''
            ]);
        }
        
        fclose($output);
        exit();
    }
    
    if (isset($_POST['bulk_action'])) {
        $selectedRegistrations = $_POST['selected_registrations'] ?? [];
        $action = $_POST['bulk_action'];
        
        if (!empty($selectedRegistrations)) {
            switch ($action) {
                case 'mark_contacted':
                    $db->update('course_interests', 
                        ['status' => 'contacted', 'contacted_at' => date('Y-m-d H:i:s')], 
                        'id IN (' . implode(',', array_fill(0, count($selectedRegistrations), '?')) . ')', 
                        $selectedRegistrations
                    );
                    $message = count($selectedRegistrations) . ' registration(s) marked as contacted';
                    break;
                    
                case 'mark_registered':
                    $db->update('course_interests', 
                        ['status' => 'registered'], 
                        'id IN (' . implode(',', array_fill(0, count($selectedRegistrations), '?')) . ')', 
                        $selectedRegistrations
                    );
                    $message = count($selectedRegistrations) . ' registration(s) marked as registered';
                    break;
                    
                case 'mark_not_interested':
                    $db->update('course_interests', 
                        ['status' => 'not_interested'], 
                        'id IN (' . implode(',', array_fill(0, count($selectedRegistrations), '?')) . ')', 
                        $selectedRegistrations
                    );
                    $message = count($selectedRegistrations) . ' registration(s) marked as not interested';
                    break;
                    
                case 'delete':
                    $db->delete('course_interests', 
                        'id IN (' . implode(',', array_fill(0, count($selectedRegistrations), '?')) . ')', 
                        $selectedRegistrations
                    );
                    $message = count($selectedRegistrations) . ' registration(s) deleted';
                    break;
            }
            
            $_SESSION['flash_message'] = [
                'text' => $message,
                'type' => 'success'
            ];
            header('Location: registrations.php' . (isset($_GET['training']) ? '?training=' . $_GET['training'] : ''));
            exit();
        }
    }
}

// Get filter parameters
$trainingId = $_GET['training'] ?? null;
$filterStatus = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build query
$query = "SELECT ci.*, t.title as training_title 
          FROM course_interests ci 
          LEFT JOIN trainings t ON ci.training_id = t.id";
$countQuery = "SELECT COUNT(*) as count 
               FROM course_interests ci 
               LEFT JOIN trainings t ON ci.training_id = t.id";
$where = [];
$params = [];

if ($trainingId) {
    $where[] = "ci.training_id = ?";
    $params[] = $trainingId;
}

if ($filterStatus !== 'all') {
    $where[] = "ci.status = ?";
    $params[] = $filterStatus;
}

if (!empty($search)) {
    $where[] = "(ci.full_name LIKE ? OR ci.email LIKE ? OR ci.profession LIKE ? OR t.title LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
    $countQuery .= " WHERE " . implode(" AND ", $where);
}

$query .= " ORDER BY ci.submitted_at DESC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;

$registrations = $db->fetchAll($query, $params);
$totalCount = $db->fetchOne($countQuery, array_slice($params, 0, -2))['count'];
$totalPages = ceil($totalCount / $perPage);

// Get training details if filtering by training
$training = null;
if ($trainingId) {
    $training = $db->fetchOne("SELECT * FROM trainings WHERE id = ?", [$trainingId]);
}

// Get stats
$stats = [
    'total' => $db->fetchOne("SELECT COUNT(*) as count FROM course_interests")['count'],
    'pending' => $db->fetchOne("SELECT COUNT(*) as count FROM course_interests WHERE status = 'pending'")['count'],
    'contacted' => $db->fetchOne("SELECT COUNT(*) as count FROM course_interests WHERE status = 'contacted'")['count'],
    'registered' => $db->fetchOne("SELECT COUNT(*) as count FROM course_interests WHERE status = 'registered'")['count']
];

// Get training-specific stats if filtering
if ($trainingId) {
    $trainingStats = $db->fetchOne(
        "SELECT COUNT(*) as total, 
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'contacted' THEN 1 ELSE 0 END) as contacted,
                SUM(CASE WHEN status = 'registered' THEN 1 ELSE 0 END) as registered
         FROM course_interests 
         WHERE training_id = ?",
        [$trainingId]
    );
    $stats = array_merge($stats, $trainingStats);
}

// Get status counts
$statusCounts = $db->fetchAll(
    "SELECT status, COUNT(*) as count FROM course_interests GROUP BY status ORDER BY count DESC"
);

$pageTitle = $training ? "Registrations for: " . $training['title'] : "Course Registrations";
?>
<?php include '../../admin/includes/admin_header.php'; ?>
<?php include '../../admin/includes/admin_sidebar.php'; ?>

            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <?php if ($training): ?>
                        <i class="fas fa-users me-2"></i> Registrations for: <?php echo htmlspecialchars($training['title']); ?>
                    <?php else: ?>
                        <i class="fas fa-users me-2"></i> Course Registrations
                    <?php endif; ?>
                </h1>
                <form method="POST" action="" class="d-inline">
                    <button type="submit" name="export_registrations" class="btn btn-success">
                        <i class="fas fa-file-export me-1"></i> Export CSV
                    </button>
                </form>
            </div>

            <!-- Training Info (if filtering) -->
            <?php if ($training): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="mb-2"><?php echo htmlspecialchars($training['title']); ?></h5>
                                    <p class="text-muted mb-2"><?php echo htmlspecialchars($training['short_description']); ?></p>
                                    <div class="d-flex gap-2">
                                        <span class="badge bg-primary">
                                            <?php echo ucfirst($training['course_type']); ?>
                                        </span>
                                        <span class="badge bg-<?php echo $training['status'] == 'active' ? 'success' : 'info'; ?>">
                                            <?php echo ucfirst($training['status']); ?>
                                        </span>
                                        <?php if ($training['price'] && $training['price'] > 0): ?>
                                            <span class="badge bg-success">
                                                ₦<?php echo number_format($training['price'], 2); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                    <a href="../pages/training-detail.php?slug=<?php echo $training['slug']; ?>" 
                                       class="btn btn-sm btn-primary me-2">
                                        <i class="fas fa-eye me-1"></i> Preview
                                    </a>
                                    <a href="../trainings/edit.php?id=<?php echo $training['id']; ?>" 
                                       class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit me-1"></i> Edit
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-3 mb-3">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs fw-bold text-primary text-uppercase mb-1">
                                        Total Registrations
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
                
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs fw-bold text-warning text-uppercase mb-1">
                                        Pending
                                    </div>
                                    <div class="h5 mb-0 fw-bold text-gray-800"><?php echo $stats['pending']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clock fa-2x text-gray-300"></i>
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
                                        Contacted
                                    </div>
                                    <div class="h5 mb-0 fw-bold text-gray-800"><?php echo $stats['contacted']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-comments fa-2x text-gray-300"></i>
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
                                        Registered
                                    </div>
                                    <div class="h5 mb-0 fw-bold text-gray-800"><?php echo $stats['registered']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-user-check fa-2x text-gray-300"></i>
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
                        <?php if (!$trainingId): ?>
                        <div class="col-md-3">
                            <label for="training" class="form-label">Training</label>
                            <select class="form-select" id="training" name="training">
                                <option value="">All Trainings</option>
                                <?php 
                                $trainings = $db->fetchAll(
                                    "SELECT id, title FROM trainings ORDER BY title"
                                );
                                foreach ($trainings as $t):
                                ?>
                                <option value="<?php echo $t['id']; ?>" <?php echo $trainingId == $t['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($t['title']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php else: ?>
                            <input type="hidden" name="training" value="<?php echo $trainingId; ?>">
                        <?php endif; ?>
                        
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="all" <?php echo $filterStatus == 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="pending" <?php echo $filterStatus == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="contacted" <?php echo $filterStatus == 'contacted' ? 'selected' : ''; ?>>Contacted</option>
                                <option value="registered" <?php echo $filterStatus == 'registered' ? 'selected' : ''; ?>>Registered</option>
                                <option value="not_interested" <?php echo $filterStatus == 'not_interested' ? 'selected' : ''; ?>>Not Interested</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="search" 
                                   name="search" 
                                   placeholder="Search by name, email, profession, or training..."
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="d-grid gap-2 d-md-flex">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter me-1"></i> Filter
                                </button>
                                <a href="registrations.php" class="btn btn-secondary">
                                    <i class="fas fa-redo me-1"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Registrations Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Registrations (<?php echo $totalCount; ?>)</h5>
                    
                    <!-- Bulk Actions -->
                    <div class="d-flex align-items-center">
                        <select class="form-select form-select-sm me-2" id="bulkAction" style="width: auto;">
                            <option value="">Bulk Actions</option>
                            <option value="mark_contacted">Mark as Contacted</option>
                            <option value="mark_registered">Mark as Registered</option>
                            <option value="mark_not_interested">Mark as Not Interested</option>
                            <option value="delete">Delete</option>
                        </select>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="applyBulkAction">
                            Apply
                        </button>
                    </div>
                </div>
                
                <div class="card-body">
                    <?php if (empty($registrations)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5>No registrations found</h5>
                            <p class="text-muted">
                                <?php if ($trainingId || $filterStatus !== 'all' || !empty($search)): ?>
                                    Try changing your filters or search terms.
                                <?php else: ?>
                                    No one has registered interest in courses yet.
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
                                            <th>Registrant</th>
                                            <?php if (!$trainingId): ?>
                                            <th>Training</th>
                                            <?php endif; ?>
                                            <th>Profession</th>
                                            <th>Status</th>
                                            <th>Submitted</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($registrations as $reg): 
                                            $statusColors = [
                                                'pending' => 'warning',
                                                'contacted' => 'info',
                                                'registered' => 'success',
                                                'not_interested' => 'secondary'
                                            ];
                                            $statusColor = $statusColors[$reg['status']] ?? 'secondary';
                                        ?>
                                            <tr>
                                                <td>
                                                    <div class="form-check">
                                                        <input class="form-check-input registration-checkbox" 
                                                               type="checkbox" 
                                                               name="selected_registrations[]" 
                                                               value="<?php echo $reg['id']; ?>">
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($reg['full_name']); ?></strong>
                                                        <div class="text-muted small">
                                                            <?php echo htmlspecialchars($reg['email']); ?>
                                                            <?php if ($reg['phone']): ?>
                                                                <br><?php echo htmlspecialchars($reg['phone']); ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <?php if (!$trainingId): ?>
                                                <td>
                                                    <?php if ($reg['training_title']): ?>
                                                        <a href="?training=<?php echo $reg['training_id']; ?>" class="text-decoration-none">
                                                            <?php echo htmlspecialchars($reg['training_title']); ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">General Interest</span>
                                                    <?php endif; ?>
                                                </td>
                                                <?php endif; ?>
                                                <td><?php echo htmlspecialchars($reg['profession']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $statusColor; ?>">
                                                        <?php echo ucfirst($reg['status']); ?>
                                                    </span>
                                                    <?php if ($reg['contacted_at']): ?>
                                                        <div class="text-muted small">
                                                            <?php echo date('M j', strtotime($reg['contacted_at'])); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php echo date('M j, Y', strtotime($reg['submitted_at'])); ?><br>
                                                        <?php echo date('g:i A', strtotime($reg['submitted_at'])); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <button type="button" 
                                                                class="btn btn-sm btn-primary" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#viewModal<?php echo $reg['id']; ?>">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure?');">
                                                            <input type="hidden" name="registration_id" value="<?php echo $reg['id']; ?>">
                                                            <button type="submit" name="delete_registration" class="btn btn-sm btn-danger" title="Delete">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                            
                                            <!-- View Modal -->
                                            <div class="modal fade" id="viewModal<?php echo $reg['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Registration Details</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="row mb-4">
                                                                <div class="col-md-6">
                                                                    <p><strong>Name:</strong> <?php echo htmlspecialchars($reg['full_name']); ?></p>
                                                                    <p><strong>Email:</strong> 
                                                                        <a href="mailto:<?php echo htmlspecialchars($reg['email']); ?>">
                                                                            <?php echo htmlspecialchars($reg['email']); ?>
                                                                        </a>
                                                                    </p>
                                                                    <?php if ($reg['phone']): ?>
                                                                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($reg['phone']); ?></p>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <p><strong>Profession:</strong> <?php echo htmlspecialchars($reg['profession']); ?></p>
                                                                    <?php if ($reg['qualification']): ?>
                                                                        <p><strong>Qualification:</strong> <?php echo htmlspecialchars($reg['qualification']); ?></p>
                                                                    <?php endif; ?>
                                                                    <p><strong>Submitted:</strong> <?php echo date('F j, Y g:i A', strtotime($reg['submitted_at'])); ?></p>
                                                                    <?php if ($reg['training_title']): ?>
                                                                        <p><strong>Training:</strong> <?php echo htmlspecialchars($reg['training_title']); ?></p>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            
                                                            <?php 
                                                            $examPrep = $reg['exam_preparation'] ? json_decode($reg['exam_preparation'], true) : [];
                                                            $interests = $reg['interests'] ? json_decode($reg['interests'], true) : [];
                                                            ?>
                                                            
                                                            <?php if (!empty($examPrep)): ?>
                                                            <div class="mb-3">
                                                                <strong>Exam Preparation:</strong>
                                                                <div class="mt-1">
                                                                    <?php foreach ($examPrep as $exam): ?>
                                                                        <span class="badge bg-info me-1 mb-1"><?php echo htmlspecialchars($exam); ?></span>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            </div>
                                                            <?php endif; ?>
                                                            
                                                            <?php if (!empty($interests)): ?>
                                                            <div class="mb-3">
                                                                <strong>Interests:</strong>
                                                                <div class="mt-1">
                                                                    <?php foreach ($interests as $interest): ?>
                                                                        <span class="badge bg-primary me-1 mb-1"><?php echo htmlspecialchars($interest); ?></span>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            </div>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($reg['additional_message']): ?>
                                                            <div class="mb-4">
                                                                <strong>Additional Message:</strong>
                                                                <div class="mt-1 p-3 bg-light rounded">
                                                                    <?php echo nl2br(htmlspecialchars($reg['additional_message'])); ?>
                                                                </div>
                                                            </div>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($reg['notes']): ?>
                                                            <div class="mb-4">
                                                                <strong>Admin Notes:</strong>
                                                                <div class="mt-1 p-3 bg-light rounded">
                                                                    <?php echo nl2br(htmlspecialchars($reg['notes'])); ?>
                                                                </div>
                                                            </div>
                                                            <?php endif; ?>
                                                            
                                                            <!-- Update Status Form -->
                                                            <form method="POST" action="">
                                                                <input type="hidden" name="registration_id" value="<?php echo $reg['id']; ?>">
                                                                
                                                                <div class="row">
                                                                    <div class="col-md-6 mb-3">
                                                                        <label for="status<?php echo $reg['id']; ?>" class="form-label">Update Status</label>
                                                                        <select class="form-select" id="status<?php echo $reg['id']; ?>" name="status">
                                                                            <option value="pending" <?php echo $reg['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                                            <option value="contacted" <?php echo $reg['status'] == 'contacted' ? 'selected' : ''; ?>>Contacted</option>
                                                                            <option value="registered" <?php echo $reg['status'] == 'registered' ? 'selected' : ''; ?>>Registered</option>
                                                                            <option value="not_interested" <?php echo $reg['status'] == 'not_interested' ? 'selected' : ''; ?>>Not Interested</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label for="notes<?php echo $reg['id']; ?>" class="form-label">Add Notes</label>
                                                                    <textarea class="form-control" 
                                                                              id="notes<?php echo $reg['id']; ?>" 
                                                                              name="notes" 
                                                                              rows="3"
                                                                              placeholder="Add notes about contact or follow-up..."><?php echo htmlspecialchars($reg['notes'] ?? ''); ?></textarea>
                                                                </div>
                                                                
                                                                <div class="d-flex justify-content-between">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                    <button type="submit" name="update_status" class="btn btn-primary">
                                                                        <i class="fas fa-save me-1"></i> Update Status
                                                                    </button>
                                                                </div>
                                                            </form>
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
                                       href="?page=<?php echo $page - 1; ?>&training=<?php echo $trainingId; ?>&status=<?php echo $filterStatus; ?>&search=<?php echo urlencode($search); ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                                
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" 
                                               href="?page=<?php echo $i; ?>&training=<?php echo $trainingId; ?>&status=<?php echo $filterStatus; ?>&search=<?php echo urlencode($search); ?>">
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
                                       href="?page=<?php echo $page + 1; ?>&training=<?php echo $trainingId; ?>&status=<?php echo $filterStatus; ?>&search=<?php echo urlencode($search); ?>">
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
    const checkboxes = document.querySelectorAll('.registration-checkbox');
    
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
        const selectedRegistrations = Array.from(checkboxes).filter(cb => cb.checked);
        
        if (!selectedAction) {
            alert('Please select a bulk action.');
            return;
        }
        
        if (selectedRegistrations.length === 0) {
            alert('Please select at least one registration.');
            return;
        }
        
        if (selectedAction === 'delete') {
            if (!confirm(`Are you sure you want to delete ${selectedRegistrations.length} registration(s)? This action cannot be undone.`)) {
                return;
            }
        } else {
            if (!confirm(`Are you sure you want to ${selectedAction.replace('_', ' ')} ${selectedRegistrations.length} registration(s)?`)) {
                return;
            }
        }
        
        bulkActionInput.value = selectedAction;
        bulkForm.submit();
    });
});
</script>

<?php include '../../admin/includes/admin_footer.php'; ?>