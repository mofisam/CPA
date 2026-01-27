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

// Check if slug is provided
if (!isset($_GET['slug']) || empty($_GET['slug'])) {
    header('Location: ../trainings/view.php');
    exit();
}

$slug = $_GET['slug'];

// Get training details
$training = $db->fetchOne(
    "SELECT t.*, a.full_name as instructor_name 
     FROM trainings t 
     LEFT JOIN admins a ON t.created_by = a.id 
     WHERE t.slug = ?",
    [$slug]
);

if (!$training) {
    header('Location: ../trainings/view.php');
    exit();
}

// Get related modules
$modules = $db->fetchAll(
    "SELECT * FROM course_modules 
     WHERE training_id = ? 
     ORDER BY module_order ASC",
    [$training['id']]
);

// Get target audience
$audience = $db->fetchAll(
    "SELECT * FROM target_audience 
     WHERE training_id = ?",
    [$training['id']]
);

// Get training features
$features = $db->fetchAll(
    "SELECT * FROM training_features 
     WHERE training_id = ?",
    [$training['id']]
);

// Get registrations for this training
$registrations = $db->fetchAll(
    "SELECT ci.* 
     FROM course_interests ci 
     WHERE ci.training_id = ? 
     ORDER BY ci.submitted_at DESC 
     LIMIT 10",
    [$training['id']]
);

$registrationCount = $db->fetchOne(
    "SELECT COUNT(*) as count FROM course_interests WHERE training_id = ?",
    [$training['id']]
)['count'];

$pageTitle = "Preview: " . $training['title'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - CPA Admin</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background-color: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }
        
        .admin-preview-header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        
        .preview-alert {
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .course-image {
            max-height: 400px;
            object-fit: cover;
            width: 100%;
        }
        
        .badge-status {
            font-size: 0.9em;
        }
        
        .registration-item {
            border-left: 3px solid #3498db;
            padding-left: 15px;
            margin-bottom: 15px;
        }
        
        .sidebar-card {
            position: sticky;
            top: 100px;
        }
    </style>
</head>
<body>
    <!-- Preview Alert -->
    <div class="preview-alert">
        <div class="alert alert-warning rounded-0 mb-0">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-eye me-2"></i>
                        <strong>Admin Preview Mode</strong> - This is how the training page appears to visitors
                    </div>
                    <div>
                        <a href="../trainings/edit.php?id=<?php echo $training['id']; ?>" class="btn btn-sm btn-warning me-2">
                            <i class="fas fa-edit me-1"></i> Edit
                        </a>
                        <a href="../trainings/view.php" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back to List
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Header -->
    <div class="admin-preview-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-2"><?php echo htmlspecialchars($training['title']); ?></h1>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-<?php 
                            $statusColors = [
                                'active' => 'success',
                                'upcoming' => 'info',
                                'draft' => 'secondary',
                                'completed' => 'dark'
                            ];
                            echo $statusColors[$training['status']] ?? 'secondary';
                        ?> badge-status">
                            <?php echo ucfirst($training['status']); ?>
                        </span>
                        <span class="badge bg-primary badge-status">
                            <?php 
                                $typeNames = [
                                    'echocardiography' => 'Echocardiography',
                                    'ecg_masterclass' => 'ECG Masterclass',
                                    'other' => 'Other Course'
                                ];
                                echo $typeNames[$training['course_type']] ?? ucfirst($training['course_type']);
                            ?>
                        </span>
                        <?php if ($training['price'] && $training['price'] > 0): ?>
                            <span class="badge bg-success badge-status">
                                ₦<?php echo number_format($training['price'], 2); ?>
                            </span>
                        <?php else: ?>
                            <span class="badge bg-success badge-status">Free</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="text-end">
                    <div class="text-white-50 small">Created by</div>
                    <div class="fw-bold"><?php echo htmlspecialchars($training['instructor_name'] ?? 'Admin'); ?></div>
                    <div class="text-white-50 small">
                        <?php echo date('F j, Y', strtotime($training['created_at'])); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="container py-4">
        <div class="row">
            <!-- Left Column - Course Content -->
            <div class="col-lg-8">
                <!-- Course Image -->
                <?php if ($training['featured_image']): ?>
                <div class="card mb-4">
                    <div class="card-body p-0">
                        <img src="../../../assets/images/trainings/<?php echo htmlspecialchars($training['featured_image']); ?>" 
                             class="course-image rounded" 
                             alt="<?php echo htmlspecialchars($training['title']); ?>">
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Course Details -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i> Course Details</h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled mb-0">
                                    <?php if ($training['duration']): ?>
                                    <li class="mb-2">
                                        <i class="fas fa-clock text-primary me-2"></i>
                                        <strong>Duration:</strong> <?php echo htmlspecialchars($training['duration']); ?>
                                    </li>
                                    <?php endif; ?>
                                    
                                    <?php if ($training['format']): ?>
                                    <li class="mb-2">
                                        <i class="fas fa-laptop text-primary me-2"></i>
                                        <strong>Format:</strong> <?php echo htmlspecialchars($training['format']); ?>
                                    </li>
                                    <?php endif; ?>
                                    
                                    <?php if ($training['start_date']): ?>
                                    <li class="mb-2">
                                        <i class="fas fa-calendar-alt text-primary me-2"></i>
                                        <strong>Start Date:</strong> <?php echo date('F j, Y', strtotime($training['start_date'])); ?>
                                    </li>
                                    <?php endif; ?>
                                    
                                    <?php if ($training['end_date']): ?>
                                    <li class="mb-2">
                                        <i class="fas fa-calendar-check text-primary me-2"></i>
                                        <strong>End Date:</strong> <?php echo date('F j, Y', strtotime($training['end_date'])); ?>
                                    </li>
                                    <?php endif; ?>
                                    
                                    <?php if ($training['registration_deadline']): ?>
                                    <li>
                                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                        <strong>Registration Deadline:</strong> <?php echo date('F j, Y', strtotime($training['registration_deadline'])); ?>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i> Registration Stats</h6>
                            </div>
                            <div class="card-body">
                                <div class="text-center mb-3">
                                    <div class="display-4 fw-bold text-primary"><?php echo $registrationCount; ?></div>
                                    <div class="text-muted">Total Interest Registrations</div>
                                </div>
                                
                                <?php if ($training['max_participants']): ?>
                                <div class="progress mb-2" style="height: 10px;">
                                    <?php 
                                    $percentage = $registrationCount > 0 ? min(100, ($registrationCount / $training['max_participants']) * 100) : 0;
                                    $color = $percentage >= 90 ? 'danger' : ($percentage >= 75 ? 'warning' : 'success');
                                    ?>
                                    <div class="progress-bar bg-<?php echo $color; ?>" 
                                         role="progressbar" 
                                         style="width: <?php echo $percentage; ?>%">
                                    </div>
                                </div>
                                <div class="text-center">
                                    <small class="text-muted">
                                        <?php echo $registrationCount; ?> of <?php echo $training['max_participants']; ?> seats filled
                                        (<?php echo number_format($percentage, 1); ?>%)
                                    </small>
                                </div>
                                <?php else: ?>
                                <div class="text-center">
                                    <small class="text-muted">No participant limit set</small>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Short Description -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-quote-left me-2"></i> Short Description</h6>
                    </div>
                    <div class="card-body">
                        <p class="lead mb-0"><?php echo htmlspecialchars($training['short_description']); ?></p>
                    </div>
                </div>
                
                <!-- Full Description -->
                <?php if ($training['full_description']): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-align-left me-2"></i> Full Description</h6>
                    </div>
                    <div class="card-body">
                        <div class="course-content">
                            <?php echo nl2br(htmlspecialchars($training['full_description'])); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Course Modules -->
                <?php if (!empty($modules)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-list-ol me-2"></i> Course Modules (<?php echo count($modules); ?>)</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th width="60">#</th>
                                        <th>Module Title</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($modules as $module): ?>
                                    <tr>
                                        <td class="fw-bold"><?php echo $module['module_order']; ?></td>
                                        <td><?php echo htmlspecialchars($module['module_title']); ?></td>
                                        <td class="text-muted"><?php echo htmlspecialchars($module['module_description']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Target Audience -->
                <?php if (!empty($audience)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-users me-2"></i> Target Audience</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($audience as $item): ?>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                <?php echo htmlspecialchars($item['audience_text']); ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Course Features -->
                <?php if (!empty($features)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-star me-2"></i> Course Features (<?php echo count($features); ?>)</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($features as $feature): ?>
                            <div class="col-md-6 mb-2">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-check-circle text-success mt-1"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <?php echo htmlspecialchars($feature['feature_text']); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Right Column - Admin Actions & Registrations -->
            <div class="col-lg-4">
                <!-- Admin Actions -->
                <div class="card sidebar-card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="fas fa-cog me-2"></i> Admin Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="../trainings/edit.php?id=<?php echo $training['id']; ?>" class="btn btn-warning">
                                <i class="fas fa-edit me-2"></i> Edit Training
                            </a>
                            <a href="../users/registrations.php?training=<?php echo $training['id']; ?>" class="btn btn-info">
                                <i class="fas fa-users me-2"></i> View Registrations (<?php echo $registrationCount; ?>)
                            </a>
                            <button type="button" class="btn btn-secondary" onclick="window.print()">
                                <i class="fas fa-print me-2"></i> Print Preview
                            </button>
                            <a href="../trainings/delete.php?id=<?php echo $training['id']; ?>" 
                               class="btn btn-danger"
                               onclick="return confirm('Are you sure you want to delete this training?');">
                                <i class="fas fa-trash me-2"></i> Delete Training
                            </a>
                        </div>
                        
                        <hr>
                        
                        <div class="text-center">
                            <a href="../../pages/training-detail.php?slug=<?php echo $training['slug']; ?>" 
                               target="_blank" 
                               class="btn btn-outline-primary">
                                <i class="fas fa-external-link-alt me-2"></i> View Live Page
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Registrations -->
                <?php if (!empty($registrations)): ?>
                <div class="card sidebar-card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-user-plus me-2"></i> Recent Registrations</h6>
                    </div>
                    <div class="card-body">
                        <?php foreach ($registrations as $registration): ?>
                        <div class="registration-item">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <strong><?php echo htmlspecialchars($registration['full_name']); ?></strong>
                                <span class="badge bg-<?php 
                                    $statusColors = [
                                        'pending' => 'warning',
                                        'contacted' => 'info',
                                        'registered' => 'success',
                                        'not_interested' => 'secondary'
                                    ];
                                    echo $statusColors[$registration['status']] ?? 'secondary';
                                ?> small">
                                    <?php echo ucfirst($registration['status']); ?>
                                </span>
                            </div>
                            <div class="text-muted small mb-1">
                                <?php echo htmlspecialchars($registration['email']); ?>
                                <?php if ($registration['phone']): ?>
                                    <br><?php echo htmlspecialchars($registration['phone']); ?>
                                <?php endif; ?>
                            </div>
                            <div class="small">
                                <i class="far fa-clock me-1"></i>
                                <?php echo date('M j, g:i A', strtotime($registration['submitted_at'])); ?>
                            </div>
                            <div class="mt-2">
                                <a href="../users/registrations.php?view=<?php echo $registration['id']; ?>" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if ($registrationCount > 10): ?>
                        <div class="text-center mt-3">
                            <a href="../users/registrations.php?training=<?php echo $training['id']; ?>" 
                               class="btn btn-sm btn-outline-secondary">
                                View All <?php echo $registrationCount; ?> Registrations
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Course Metadata -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-database me-2"></i> Course Metadata</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <td><strong>ID:</strong></td>
                                <td><?php echo $training['id']; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Slug:</strong></td>
                                <td><code><?php echo htmlspecialchars($training['slug']); ?></code></td>
                            </tr>
                            <tr>
                                <td><strong>Created:</strong></td>
                                <td><?php echo date('Y-m-d H:i:s', strtotime($training['created_at'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Updated:</strong></td>
                                <td><?php echo date('Y-m-d H:i:s', strtotime($training['updated_at'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Created By:</strong></td>
                                <td><?php echo htmlspecialchars($training['instructor_name'] ?? 'Admin'); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Print styling
    const style = document.createElement('style');
    style.textContent = `
        @media print {
            .preview-alert,
            .admin-preview-header,
            .sidebar-card,
            .btn,
            .card-header .btn,
            .registration-item .btn {
                display: none !important;
            }
            
            .card {
                border: 1px solid #dee2e6 !important;
                box-shadow: none !important;
            }
            
            .card-header {
                background-color: #f8f9fa !important;
                color: #212529 !important;
                border-bottom: 1px solid #dee2e6 !important;
            }
        }
    `;
    document.head.appendChild(style);
    </script>
</body>
</html>