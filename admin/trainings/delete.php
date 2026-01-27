<?php
require_once '../../config/environment.php';
require_once '../../includes/core/Database.php';
require_once '../../includes/core/Functions.php';
require_once '../../includes/core/Auth.php';

use includes\core\Auth;
use includes\core\Database;

// Check admin authentication
Auth::requireAdmin();

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: view.php');
    exit();
}

$db = Database::getInstance();
$trainingId = intval($_GET['id']);

// Check if training exists
$training = $db->fetchOne("SELECT * FROM trainings WHERE id = ?", [$trainingId]);

if (!$training) {
    header('Location: view.php');
    exit();
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
        try {
            $db->beginTransaction();
            
            // Delete modules
            $db->delete('course_modules', 'training_id = ?', [$trainingId]);
            
            // Delete target audience
            $db->delete('target_audience', 'training_id = ?', [$trainingId]);
            
            // Delete training features
            $db->delete('training_features', 'training_id = ?', [$trainingId]);
            
            // Delete training
            $db->delete('trainings', 'id = ?', [$trainingId]);
            
            // Delete featured image if exists
            if ($training['featured_image'] && file_exists('../../' . $training['featured_image'])) {
                unlink('../../' . $training['featured_image']);
            }
            
            $db->commit();
            
            $_SESSION['flash_message'] = [
                'text' => 'Training deleted successfully!',
                'type' => 'success'
            ];
            header('Location: view.php');
            exit();
            
        } catch (Exception $e) {
            $db->rollback();
            $error = 'Error deleting training: ' . $e->getMessage();
        }
    } else {
        header('Location: view.php');
        exit();
    }
}

$pageTitle = "Delete Training";
?>
<?php include '../../admin/includes/admin_header.php'; ?>
<?php include '../../admin/includes/admin_sidebar.php'; ?>

            <!-- Page Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Delete Training</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="view.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to List
                    </a>
                </div>
            </div>

            <!-- Error Message -->
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Confirmation Card -->
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i> Confirm Deletion</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <h5 class="alert-heading">Warning!</h5>
                        <p class="mb-0">You are about to delete the following training. This action cannot be undone.</p>
                    </div>

                    <!-- Training Details -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($training['title']); ?></h5>
                            <p class="card-text text-muted"><?php echo htmlspecialchars($training['short_description']); ?></p>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Type:</strong> 
                                        <span class="badge bg-secondary">
                                            <?php echo ucfirst($training['course_type']); ?>
                                        </span>
                                    </p>
                                    <p class="mb-1"><strong>Status:</strong> 
                                        <span class="badge bg-<?php 
                                            $statusColors = [
                                                'draft' => 'secondary',
                                                'active' => 'success',
                                                'upcoming' => 'info',
                                                'completed' => 'dark'
                                            ];
                                            echo $statusColors[$training['status']] ?? 'secondary';
                                        ?>">
                                            <?php echo ucfirst($training['status']); ?>
                                        </span>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Created:</strong> 
                                        <?php echo date('F j, Y', strtotime($training['created_at'])); ?>
                                    </p>
                                    <p class="mb-1"><strong>Duration:</strong> 
                                        <?php echo htmlspecialchars($training['duration'] ?: 'Not set'); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Related Data -->
                    <div class="alert alert-info">
                        <h6 class="alert-heading"><i class="fas fa-info-circle me-2"></i> This will also delete:</h6>
                        <ul class="mb-0">
                            <li>All course modules (<?php 
                                $moduleCount = $db->fetchOne("SELECT COUNT(*) as count FROM course_modules WHERE training_id = ?", [$trainingId])['count'];
                                echo $moduleCount;
                            ?> modules)</li>
                            <li>Target audience information</li>
                            <li>Training features</li>
                            <?php if ($training['featured_image']): ?>
                                <li>Featured image file</li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <!-- Confirmation Form -->
                    <form method="POST" action="" id="deleteForm">
                        <div class="mb-3">
                            <label for="confirm" class="form-label">Type "DELETE" to confirm</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="confirm" 
                                   name="confirm" 
                                   placeholder="Type DELETE here"
                                   required>
                            <div class="form-text">This is a safety measure to prevent accidental deletions.</div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="view.php" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-danger" id="deleteButton" disabled>
                                <i class="fas fa-trash me-1"></i> Delete Training Permanently
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const confirmInput = document.getElementById('confirm');
    const deleteButton = document.getElementById('deleteButton');
    const deleteForm = document.getElementById('deleteForm');
    
    // Enable/disable delete button based on input
    confirmInput.addEventListener('input', function() {
        deleteButton.disabled = this.value.toUpperCase() !== 'DELETE';
    });
    
    // Form validation
    deleteForm.addEventListener('submit', function(e) {
        if (confirmInput.value.toUpperCase() !== 'DELETE') {
            e.preventDefault();
            alert('Please type "DELETE" to confirm deletion.');
            confirmInput.focus();
            return false;
        }
        
        if (!confirm('Are you absolutely sure you want to delete this training? This action cannot be undone.')) {
            e.preventDefault();
            return false;
        }
    });
});
</script>

<?php include '../../admin/includes/admin_footer.php'; ?>