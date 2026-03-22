<?php
require_once '../../config/environment.php';
require_once '../../includes/core/Database.php';
require_once '../../includes/core/Functions.php';
require_once '../../includes/core/Auth.php';
require_once '../../includes/core/EmailTemplate.php';

use includes\core\Auth;

// Check admin authentication
Auth::requireAdmin();

$emailTemplate = new EmailTemplate();
$db = Database::getInstance();

// Handle AJAX template fetch
if (isset($_POST['get_template'])) {
    header('Content-Type: application/json');
    $template = $emailTemplate->getTemplate(intval($_POST['get_template']));
    echo json_encode(['success' => true, 'template' => $template]);
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_template'])) {
        $data = [
            'name' => trim($_POST['name']),
            'subject' => trim($_POST['subject']),
            'content' => $_POST['content'],
            'category' => $_POST['category'],
            'variables' => $_POST['variables'] ?? ''
        ];
        
        $emailTemplate->createTemplate($data);
        $_SESSION['flash_message'] = ['text' => 'Template created successfully!', 'type' => 'success'];
        header('Location: templates.php');
        exit();
    }
    
    if (isset($_POST['update_template'])) {
        $id = intval($_POST['template_id']);
        $data = [
            'name' => trim($_POST['name']),
            'subject' => trim($_POST['subject']),
            'content' => $_POST['content'],
            'category' => $_POST['category'],
            'variables' => $_POST['variables'] ?? ''
        ];
        
        $emailTemplate->updateTemplate($id, $data);
        $_SESSION['flash_message'] = ['text' => 'Template updated successfully!', 'type' => 'success'];
        header('Location: templates.php');
        exit();
    }
    
    if (isset($_POST['delete_template'])) {
        $id = intval($_POST['template_id']);
        $emailTemplate->deleteTemplate($id);
        $_SESSION['flash_message'] = ['text' => 'Template deleted successfully!', 'type' => 'success'];
        header('Location: templates.php');
        exit();
    }
}

// Get templates
$templates = $emailTemplate->getAllTemplates($_GET['category'] ?? null);
$categories = ['newsletter', 'course_promo', 'registration', 'follow_up', 'custom'];

$pageTitle = "Email Templates";
?>
<?php include '../../admin/includes/admin_header.php'; ?>
<?php include '../../admin/includes/admin_sidebar.php'; ?>

            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-file-alt me-2"></i> Email Templates</h1>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTemplateModal">
                    <i class="fas fa-plus me-1"></i> Create Template
                </button>
            </div>

            <!-- Category Filters -->
            <div class="mb-4">
                <div class="btn-group" role="group">
                    <a href="templates.php" class="btn btn-outline-secondary <?php echo !isset($_GET['category']) ? 'active' : ''; ?>">All</a>
                    <?php foreach ($categories as $cat): ?>
                    <a href="?category=<?php echo $cat; ?>" class="btn btn-outline-secondary <?php echo ($_GET['category'] ?? '') == $cat ? 'active' : ''; ?>">
                        <?php echo ucfirst($cat); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Templates Grid -->
            <div class="row">
                <?php if (empty($templates)): ?>
                    <div class="col-12">
                        <div class="card text-center py-5">
                            <div class="card-body">
                                <i class="fas fa-envelope-open-text fa-3x text-muted mb-3"></i>
                                <h4>No Templates Found</h4>
                                <p class="text-muted">Create your first email template to get started.</p>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTemplateModal">
                                    <i class="fas fa-plus me-1"></i> Create Template
                                </button>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($templates as $template): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span class="badge bg-<?php 
                                    $colors = ['newsletter' => 'info', 'course_promo' => 'primary', 'registration' => 'success', 'follow_up' => 'warning', 'custom' => 'secondary'];
                                    echo $colors[$template['category']] ?? 'secondary';
                                ?>">
                                    <?php echo ucfirst($template['category']); ?>
                                </span>
                                <div>
                                    <button class="btn btn-sm btn-outline-primary edit-template" 
                                            data-id="<?php echo $template['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($template['name']); ?>"
                                            data-subject="<?php echo htmlspecialchars($template['subject']); ?>"
                                            data-content="<?php echo htmlspecialchars($template['content']); ?>"
                                            data-category="<?php echo $template['category']; ?>"
                                            data-variables="<?php echo htmlspecialchars($template['variables']); ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger delete-template" 
                                            data-id="<?php echo $template['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($template['name']); ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($template['name']); ?></h5>
                                <p class="card-text text-muted small">
                                    <strong>Subject:</strong> <?php echo htmlspecialchars($template['subject']); ?>
                                </p>
                                <div class="email-preview-small mt-2 p-2 bg-light rounded" style="max-height: 100px; overflow: hidden;">
                                    <?php echo htmlspecialchars(substr(strip_tags($template['content']), 0, 100)); ?>...
                                </div>
                                <?php if ($template['variables']): ?>
                                <div class="mt-2">
                                    <small class="text-muted">Variables: <?php echo htmlspecialchars($template['variables']); ?></small>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer bg-transparent">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">Created: <?php echo date('M j, Y', strtotime($template['created_at'])); ?></small>
                                    <a href="send.php?template=<?php echo $template['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-paper-plane me-1"></i> Use Template
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Create Template Modal -->
            <div class="modal fade" id="createTemplateModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Create Email Template</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST" action="">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Template Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="category" class="form-label">Category *</label>
                                    <select class="form-select" id="category" name="category" required>
                                        <option value="custom">Custom</option>
                                        <option value="newsletter">Newsletter</option>
                                        <option value="course_promo">Course Promotion</option>
                                        <option value="registration">Registration</option>
                                        <option value="follow_up">Follow Up</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="subject" class="form-label">Subject *</label>
                                    <input type="text" class="form-control" id="subject" name="subject" required>
                                    <small class="text-muted">You can use variables like {recipient_name}</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="variables" class="form-label">Variables (comma separated)</label>
                                    <input type="text" class="form-control" id="variables" name="variables" placeholder="e.g., recipient_name, course_name, date">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="content" class="form-label">Email Content *</label>
                                    <textarea class="form-control" id="content" name="content" rows="10" required></textarea>
                                    <small class="text-muted">
                                        Available variables: {recipient_name}, {recipient_email}, {unsubscribe_link}, {date}, {site_name}, {dynamic_content}
                                    </small>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="create_template" class="btn btn-primary">Create Template</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Template Modal -->
            <div class="modal fade" id="editTemplateModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Email Template</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST" action="">
                            <input type="hidden" name="template_id" id="edit_template_id">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="edit_name" class="form-label">Template Name *</label>
                                    <input type="text" class="form-control" id="edit_name" name="name" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="edit_category" class="form-label">Category *</label>
                                    <select class="form-select" id="edit_category" name="category" required>
                                        <option value="custom">Custom</option>
                                        <option value="newsletter">Newsletter</option>
                                        <option value="course_promo">Course Promotion</option>
                                        <option value="registration">Registration</option>
                                        <option value="follow_up">Follow Up</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="edit_subject" class="form-label">Subject *</label>
                                    <input type="text" class="form-control" id="edit_subject" name="subject" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="edit_variables" class="form-label">Variables (comma separated)</label>
                                    <input type="text" class="form-control" id="edit_variables" name="variables">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="edit_content" class="form-label">Email Content *</label>
                                    <textarea class="form-control" id="edit_content" name="content" rows="10" required></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="update_template" class="btn btn-primary">Update Template</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div class="modal fade" id="deleteTemplateModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Delete Template</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST" action="">
                            <input type="hidden" name="template_id" id="delete_template_id">
                            <div class="modal-body">
                                <p>Are you sure you want to delete the template "<span id="delete_template_name"></span>"?</p>
                                <p class="text-danger">This action cannot be undone.</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="delete_template" class="btn btn-danger">Delete Template</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Edit template
    document.querySelectorAll('.edit-template').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            const subject = this.dataset.subject;
            const content = this.dataset.content;
            const category = this.dataset.category;
            const variables = this.dataset.variables;
            
            document.getElementById('edit_template_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_subject').value = subject;
            document.getElementById('edit_content').value = content;
            document.getElementById('edit_category').value = category;
            document.getElementById('edit_variables').value = variables;
            
            new bootstrap.Modal(document.getElementById('editTemplateModal')).show();
        });
    });
    
    // Delete template
    document.querySelectorAll('.delete-template').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            
            document.getElementById('delete_template_id').value = id;
            document.getElementById('delete_template_name').textContent = name;
            
            new bootstrap.Modal(document.getElementById('deleteTemplateModal')).show();
        });
    });
});
</script>

<?php include '../../admin/includes/admin_footer.php'; ?>