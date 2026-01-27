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

// Fetch training data
$training = $db->fetchOne("SELECT * FROM trainings WHERE id = ?", [$trainingId]);

if (!$training) {
    header('Location: view.php');
    exit();
}

// Fetch related data
$modules = $db->fetchAll(
    "SELECT * FROM course_modules WHERE training_id = ? ORDER BY module_order",
    [$trainingId]
);

$audience = $db->fetchAll(
    "SELECT * FROM target_audience WHERE training_id = ?",
    [$trainingId]
);

// Initialize variables
$errors = [];
$success = false;

// Form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = trim($_POST['title']);
    $slug = $training['slug']; // Keep original slug
    $short_description = trim($_POST['short_description']);
    $full_description = trim($_POST['full_description']);
    $course_type = $_POST['course_type'];
    $duration = trim($_POST['duration']);
    $format = trim($_POST['format']);
    $price = !empty($_POST['price']) ? floatval($_POST['price']) : null;
    $status = $_POST['status'];
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $registration_deadline = !empty($_POST['registration_deadline']) ? $_POST['registration_deadline'] : null;
    $max_participants = !empty($_POST['max_participants']) ? intval($_POST['max_participants']) : null;
    
    // Validate required fields
    if (empty($title)) {
        $errors[] = 'Title is required';
    }
    
    if (empty($short_description)) {
        $errors[] = 'Short description is required';
    }
    
    // Handle file upload
    $featured_image = $training['featured_image'];
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === 0) {
        try {
            $uploadDir = '../../assets/images/trainings/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Delete old image if exists
            if ($featured_image && file_exists('../../' . $featured_image)) {
                unlink('../../' . $featured_image);
            }
            
            $featured_image = CPAFunctions::uploadFile(
                $_FILES['featured_image'],
                $uploadDir
            );
        } catch (Exception $e) {
            $errors[] = 'File upload error: ' . $e->getMessage();
        }
    }
    
    // Handle delete image request
    if (isset($_POST['delete_image']) && $_POST['delete_image'] == '1') {
        if ($featured_image && file_exists('../../' . $featured_image)) {
            unlink('../../' . $featured_image);
        }
        $featured_image = null;
    }
    
    // If no errors, save to database
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Update training
            $trainingData = [
                'title' => $title,
                'short_description' => $short_description,
                'full_description' => $full_description,
                'course_type' => $course_type,
                'duration' => $duration,
                'format' => $format,
                'price' => $price,
                'featured_image' => $featured_image,
                'status' => $status,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'registration_deadline' => $registration_deadline,
                'max_participants' => $max_participants,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $db->update('trainings', $trainingData, 'id = ?', [$trainingId]);
            
            // Delete existing modules and audience
            $db->delete('course_modules', 'training_id = ?', [$trainingId]);
            $db->delete('target_audience', 'training_id = ?', [$trainingId]);
            
            // Save new modules if provided
            if (!empty($_POST['module_title'])) {
                foreach ($_POST['module_title'] as $index => $moduleTitle) {
                    if (!empty(trim($moduleTitle))) {
                        $moduleData = [
                            'training_id' => $trainingId,
                            'module_title' => trim($moduleTitle),
                            'module_description' => trim($_POST['module_description'][$index] ?? ''),
                            'module_order' => $index + 1
                        ];
                        $db->insert('course_modules', $moduleData);
                    }
                }
            }
            
            // Save target audience if provided
            if (!empty($_POST['audience'])) {
                foreach ($_POST['audience'] as $audienceText) {
                    if (!empty(trim($audienceText))) {
                        $audienceData = [
                            'training_id' => $trainingId,
                            'audience_text' => trim($audienceText)
                        ];
                        $db->insert('target_audience', $audienceData);
                    }
                }
            }
            
            $db->commit();
            
            $_SESSION['flash_message'] = [
                'text' => 'Training updated successfully!',
                'type' => 'success'
            ];
            header('Location: view.php?id=' . $trainingId);
            exit();
            
        } catch (Exception $e) {
            $db->rollback();
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

$pageTitle = "Edit Training: " . htmlspecialchars($training['title']);
?>
<?php include '../../admin/includes/admin_header.php'; ?>
<?php include '../../admin/includes/admin_sidebar.php'; ?>

            <!-- Page Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Edit Training</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="view.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to List
                    </a>
                </div>
            </div>

            <!-- Error Messages -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <h5><i class="fas fa-exclamation-triangle me-2"></i> Please fix the following errors:</h5>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Training Form -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-edit me-2"></i> Edit Training Details</h5>
                    <a href="../pages/training-detail.php?slug=<?php echo $training['slug']; ?>" 
                       target="_blank" 
                       class="btn btn-sm btn-info">
                        <i class="fas fa-external-link-alt me-1"></i> Preview
                    </a>
                </div>
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data" id="trainingForm">
                        <div class="row">
                            <!-- Basic Information -->
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Training Title *</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="title" 
                                           name="title" 
                                           required
                                           value="<?php echo htmlspecialchars($training['title']); ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="short_description" class="form-label">Short Description *</label>
                                    <textarea class="form-control" 
                                              id="short_description" 
                                              name="short_description" 
                                              rows="3" 
                                              required><?php echo htmlspecialchars($training['short_description']); ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="full_description" class="form-label">Full Description</label>
                                    <textarea class="form-control" 
                                              id="full_description" 
                                              name="full_description" 
                                              rows="6"><?php echo htmlspecialchars($training['full_description']); ?></textarea>
                                </div>

                                <!-- Modules -->
                                <div class="mb-4">
                                    <label class="form-label">Course Modules</label>
                                    <div id="modulesContainer">
                                        <?php if (empty($modules)): ?>
                                            <div class="module-item card mb-2">
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <input type="text" 
                                                                   class="form-control mb-2" 
                                                                   name="module_title[]" 
                                                                   placeholder="Module Title">
                                                        </div>
                                                        <div class="col-md-5">
                                                            <input type="text" 
                                                                   class="form-control mb-2" 
                                                                   name="module_description[]" 
                                                                   placeholder="Module Description">
                                                        </div>
                                                        <div class="col-md-1">
                                                            <button type="button" class="btn btn-danger btn-sm remove-module" disabled>
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($modules as $index => $module): ?>
                                                <div class="module-item card mb-2">
                                                    <div class="card-body">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <input type="text" 
                                                                       class="form-control mb-2" 
                                                                       name="module_title[]" 
                                                                       value="<?php echo htmlspecialchars($module['module_title']); ?>"
                                                                       placeholder="Module Title">
                                                            </div>
                                                            <div class="col-md-5">
                                                                <input type="text" 
                                                                       class="form-control mb-2" 
                                                                       name="module_description[]" 
                                                                       value="<?php echo htmlspecialchars($module['module_description']); ?>"
                                                                       placeholder="Module Description">
                                                            </div>
                                                            <div class="col-md-1">
                                                                <button type="button" class="btn btn-danger btn-sm remove-module">
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="addModule">
                                        <i class="fas fa-plus me-1"></i> Add Module
                                    </button>
                                </div>

                                <!-- Target Audience -->
                                <div class="mb-4">
                                    <label class="form-label">Target Audience</label>
                                    <div id="audienceContainer">
                                        <?php if (empty($audience)): ?>
                                            <div class="input-group mb-2">
                                                <input type="text" 
                                                       class="form-control" 
                                                       name="audience[]" 
                                                       placeholder="e.g., Cardiac physiologists">
                                                <button class="btn btn-outline-danger remove-audience" type="button" disabled>
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($audience as $item): ?>
                                                <div class="input-group mb-2">
                                                    <input type="text" 
                                                           class="form-control" 
                                                           name="audience[]" 
                                                           value="<?php echo htmlspecialchars($item['audience_text']); ?>"
                                                           placeholder="e.g., Cardiac physiologists">
                                                    <button class="btn btn-outline-danger remove-audience" type="button">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="addAudience">
                                        <i class="fas fa-plus me-1"></i> Add Audience
                                    </button>
                                </div>
                            </div>

                            <!-- Sidebar -->
                            <div class="col-md-4">
                                <!-- Current Image -->
                                <?php if ($training['featured_image']): ?>
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h6 class="mb-0"><i class="fas fa-image me-2"></i> Current Image</h6>
                                    </div>
                                    <div class="card-body text-center">
                                        <img src="../../<?php echo $training['featured_image']; ?>" 
                                             class="img-fluid rounded mb-3" 
                                             style="max-height: 200px;">
                                        <div class="form-check">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   id="delete_image" 
                                                   name="delete_image" 
                                                   value="1">
                                            <label class="form-check-label text-danger" for="delete_image">
                                                <i class="fas fa-trash me-1"></i> Delete this image
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- New Image -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h6 class="mb-0"><i class="fas fa-upload me-2"></i> <?php echo $training['featured_image'] ? 'Replace Image' : 'Upload Image'; ?></h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="featured_image" class="form-label">Upload New Image</label>
                                            <input type="file" 
                                                   class="form-control" 
                                                   id="featured_image" 
                                                   name="featured_image" 
                                                   accept="image/*">
                                        </div>
                                        <div id="imagePreview" class="mt-2 text-center" style="display: none;">
                                            <img id="previewImage" class="img-fluid rounded" style="max-height: 150px;">
                                        </div>
                                    </div>
                                </div>

                                <!-- Course Details -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h6 class="mb-0"><i class="fas fa-cog me-2"></i> Course Details</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="course_type" class="form-label">Course Type</label>
                                            <select class="form-select" id="course_type" name="course_type">
                                                <option value="echocardiography" <?php echo $training['course_type'] == 'echocardiography' ? 'selected' : ''; ?>>Echocardiography Training</option>
                                                <option value="ecg_masterclass" <?php echo $training['course_type'] == 'ecg_masterclass' ? 'selected' : ''; ?>>ECG Interpretation Masterclass</option>
                                                <option value="other" <?php echo $training['course_type'] == 'other' ? 'selected' : ''; ?>>Other Course</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="duration" class="form-label">Duration</label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="duration" 
                                                   name="duration" 
                                                   value="<?php echo htmlspecialchars($training['duration']); ?>">
                                        </div>

                                        <div class="mb-3">
                                            <label for="format" class="form-label">Format</label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="format" 
                                                   name="format" 
                                                   value="<?php echo htmlspecialchars($training['format']); ?>">
                                        </div>

                                        <div class="mb-3">
                                            <label for="price" class="form-label">Price (₦)</label>
                                            <input type="number" 
                                                   step="0.01" 
                                                   class="form-control" 
                                                   id="price" 
                                                   name="price" 
                                                   value="<?php echo htmlspecialchars($training['price']); ?>">
                                        </div>

                                        <div class="mb-3">
                                            <label for="max_participants" class="form-label">Max Participants</label>
                                            <input type="number" 
                                                   class="form-control" 
                                                   id="max_participants" 
                                                   name="max_participants" 
                                                   value="<?php echo htmlspecialchars($training['max_participants']); ?>">
                                        </div>

                                        <div class="mb-3">
                                            <label for="status" class="form-label">Status</label>
                                            <select class="form-select" id="status" name="status">
                                                <option value="draft" <?php echo $training['status'] == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                                <option value="upcoming" <?php echo $training['status'] == 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                                                <option value="active" <?php echo $training['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                                <option value="completed" <?php echo $training['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Dates -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h6 class="mb-0"><i class="fas fa-calendar me-2"></i> Dates</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="start_date" class="form-label">Start Date</label>
                                            <input type="date" 
                                                   class="form-control" 
                                                   id="start_date" 
                                                   name="start_date"
                                                   value="<?php echo htmlspecialchars($training['start_date']); ?>">
                                        </div>

                                        <div class="mb-3">
                                            <label for="end_date" class="form-label">End Date</label>
                                            <input type="date" 
                                                   class="form-control" 
                                                   id="end_date" 
                                                   name="end_date"
                                                   value="<?php echo htmlspecialchars($training['end_date']); ?>">
                                        </div>

                                        <div class="mb-3">
                                            <label for="registration_deadline" class="form-label">Registration Deadline</label>
                                            <input type="date" 
                                                   class="form-control" 
                                                   id="registration_deadline" 
                                                   name="registration_deadline"
                                                   value="<?php echo htmlspecialchars($training['registration_deadline']); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <a href="view.php" class="btn btn-secondary">
                                        <i class="fas fa-times me-1"></i> Cancel
                                    </a>
                                    <div>
                                        <a href="delete.php?id=<?php echo $trainingId; ?>" 
                                           class="btn btn-danger me-2"
                                           onclick="return confirm('Are you sure you want to delete this training? This action cannot be undone.');">
                                            <i class="fas fa-trash me-1"></i> Delete
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i> Update Training
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add Module
    document.getElementById('addModule').addEventListener('click', function() {
        const container = document.getElementById('modulesContainer');
        
        const newModule = document.createElement('div');
        newModule.className = 'module-item card mb-2';
        newModule.innerHTML = `
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <input type="text" 
                               class="form-control mb-2" 
                               name="module_title[]" 
                               placeholder="Module Title">
                    </div>
                    <div class="col-md-5">
                        <input type="text" 
                               class="form-control mb-2" 
                               name="module_description[]" 
                               placeholder="Module Description">
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-danger btn-sm remove-module">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        container.appendChild(newModule);
        enableRemoveButtons();
    });
    
    // Add Audience
    document.getElementById('addAudience').addEventListener('click', function() {
        const container = document.getElementById('audienceContainer');
        
        const newAudience = document.createElement('div');
        newAudience.className = 'input-group mb-2';
        newAudience.innerHTML = `
            <input type="text" 
                   class="form-control" 
                   name="audience[]" 
                   placeholder="e.g., Cardiac physiologists">
            <button class="btn btn-outline-danger remove-audience" type="button">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        container.appendChild(newAudience);
        enableRemoveButtons();
    });
    
    // Enable remove buttons
    function enableRemoveButtons() {
        // Module remove buttons
        document.querySelectorAll('.remove-module').forEach(btn => {
            btn.addEventListener('click', function() {
                if (document.querySelectorAll('.module-item').length > 1) {
                    this.closest('.module-item').remove();
                }
            });
        });
        
        // Audience remove buttons
        document.querySelectorAll('.remove-audience').forEach(btn => {
            btn.addEventListener('click', function() {
                if (document.querySelectorAll('#audienceContainer .input-group').length > 1) {
                    this.closest('.input-group').remove();
                }
            });
        });
    }
    
    // Image preview
    const featuredImage = document.getElementById('featured_image');
    const previewContainer = document.getElementById('imagePreview');
    const previewImage = document.getElementById('previewImage');
    
    featuredImage.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                previewContainer.style.display = 'block';
            }
            reader.readAsDataURL(file);
        } else {
            previewContainer.style.display = 'none';
        }
    });
    
    // Enable initial remove buttons
    enableRemoveButtons();
});
</script>

<?php include '../../admin/includes/admin_footer.php'; ?>