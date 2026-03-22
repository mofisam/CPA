<?php
require_once '../../config/environment.php';
require_once '../../includes/core/Database.php';
require_once '../../includes/core/Functions.php';
require_once '../../includes/core/Auth.php';
require_once '../../includes/core/Mailer.php';
require_once '../../includes/core/EmailTemplate.php';

use includes\core\Auth;
use includes\core\Database;

// Check admin authentication
Auth::requireAdmin();

$db = Database::getInstance();
$emailTemplate = new EmailTemplate();

// Get templates for dropdown
$templates = $emailTemplate->getAllTemplates();
$courses = $db->fetchAll("SELECT id, title FROM trainings WHERE status IN ('active', 'upcoming')");

// Handle AJAX preview request
if (isset($_POST['preview_email']) && $_POST['preview_email'] == '1') {
    header('Content-Type: application/json');
    
    $templateId = intval($_POST['template_id']);
    $subject = trim($_POST['subject']);
    $content = $_POST['content'];
    
    if ($templateId > 0) {
        $template = $emailTemplate->getTemplate($templateId);
        if ($template) {
            $processed = $emailTemplate->processTemplate($template, [
                'recipient_name' => 'John Doe',
                'recipient_email' => 'john@example.com',
                'unsubscribe_token' => 'preview_token_123'
            ]);
            $subject = $processed['subject'];
            $content = $processed['content'];
        }
    }
    
    echo json_encode([
        'subject' => $subject,
        'content' => $content
    ]);
    exit();
}

// Handle AJAX recipient count
if (isset($_POST['get_recipient_count'])) {
    header('Content-Type: application/json');
    
    $recipientsType = $_POST['recipients_type'];
    $filter = null;
    
    if ($recipientsType == 'specific_course' && isset($_POST['course_id'])) {
        $filter = ['course_id' => intval($_POST['course_id'])];
    }
    
    $recipients = $emailTemplate->getRecipients($recipientsType, $filter);
    
    echo json_encode([
        'count' => count($recipients),
        'recipients' => array_slice($recipients, 0, 10)
    ]);
    exit();
}

// Handle sending emails
$sendSuccess = false;
$sendError = '';
$sendStats = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_email'])) {
    $recipientsType = $_POST['recipients_type'];
    $templateId = intval($_POST['template_id']);
    $customSubject = trim($_POST['custom_subject']);
    $customContent = $_POST['custom_content'];
    $courseId = isset($_POST['course_id']) ? intval($_POST['course_id']) : null;
    
    // Get template if selected
    $template = null;
    if ($templateId > 0) {
        $template = $emailTemplate->getTemplate($templateId);
    }
    
    // Get recipients
    $filter = null;
    if ($recipientsType == 'specific_course' && $courseId) {
        $filter = ['course_id' => $courseId];
    }
    
    $recipients = $emailTemplate->getRecipients($recipientsType, $filter);
    
    if (empty($recipients)) {
        $sendError = 'No recipients found for the selected criteria.';
    } else {
        // Create campaign
        $campaignData = [
            'template_id' => $templateId ?: null,
            'campaign_name' => 'Email Campaign - ' . date('Y-m-d H:i:s'),
            'recipients_type' => $recipientsType,
            'recipients_filter' => json_encode(['course_id' => $courseId])
        ];
        $campaignId = $emailTemplate->createCampaign($campaignData);
        
        // Initialize mailer
        $mailer = new Mailer();
        $sentCount = 0;
        $failedCount = 0;
        
        // Set debug mode for testing
        if (DEBUG_MODE) {
            $mailer->setDebug(true);
        }
        
        // Send emails in batches
        $batchSize = EMAIL_MAX_PER_BATCH;
        $recipientChunks = array_chunk($recipients, $batchSize);
        
        foreach ($recipientChunks as $chunkIndex => $chunk) {
            foreach ($chunk as $recipient) {
                // Process template or use custom content
                if ($template) {
                    $processed = $emailTemplate->processTemplate($template, [
                        'recipient_name' => $recipient['full_name'] ?: 'Valued Professional',
                        'recipient_email' => $recipient['email'],
                        'unsubscribe_token' => $recipient['unsubscribe_token'],
                        'dynamic_content' => $customContent ?: ''
                    ]);
                    $subject = $processed['subject'];
                    $content = $processed['content'];
                } else {
                    $subject = $customSubject;
                    $content = $customContent;
                    // Add unsubscribe link to custom content
                    $unsubscribeLink = SITE_URL . 'unsubscribe.php?token=' . $recipient['unsubscribe_token'];
                    $content .= "\n\n<hr>\n<p style='font-size:12px; color:#666;'>";
                    $content .= "If you no longer wish to receive these emails, you can ";
                    $content .= "<a href='{$unsubscribeLink}'>unsubscribe here</a>.";
                    $content .= "</p>";
                }
                
                // Send email
                try {
                    if ($mailer->send($recipient['email'], $subject, $content)) {
                        $sentCount++;
                        $emailTemplate->logEmail($campaignId, $recipient, $subject, $content, 'sent');
                    } else {
                        $failedCount++;
                        $emailTemplate->logEmail($campaignId, $recipient, $subject, $content, 'failed', 'SMTP send failed');
                    }
                } catch (Exception $e) {
                    $failedCount++;
                    $emailTemplate->logEmail($campaignId, $recipient, $subject, $content, 'failed', $e->getMessage());
                }
                
                // Delay between emails to avoid rate limiting
                if (EMAIL_SEND_DELAY > 0) {
                    usleep(EMAIL_SEND_DELAY * 1000000);
                }
            }
            
            // Update campaign status after each batch
            $emailTemplate->updateCampaignStatus($campaignId, 'sending', $sentCount);
        }
        
        // Close mailer connection
        $mailer->close();
        
        // Update campaign status
        $finalStatus = $failedCount > 0 ? 'completed' : 'completed';
        $emailTemplate->updateCampaignStatus($campaignId, $finalStatus, $sentCount);
        
        $sendSuccess = true;
        $sendStats = [
            'total' => count($recipients),
            'sent' => $sentCount,
            'failed' => $failedCount,
            'campaign_id' => $campaignId
        ];
        
        $_SESSION['flash_message'] = [
            'text' => "Email campaign completed! Sent: $sentCount, Failed: $failedCount",
            'type' => $failedCount > 0 ? 'warning' : 'success'
        ];
        
        header('Location: campaigns.php');
        exit();
    }
}

$pageTitle = "Send Email Campaign";
?>
<?php include '../../admin/includes/admin_header.php'; ?>
<?php include '../../admin/includes/admin_sidebar.php'; ?>

<style>
.email-preview {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    padding: 1rem;
    max-height: 400px;
    overflow-y: auto;
    font-family: Arial, sans-serif;
}

.email-preview h1, .email-preview h2, .email-preview h3 {
    margin-top: 0.5rem;
    margin-bottom: 0.5rem;
}

.variable-badge {
    display: inline-block;
    background-color: #e9ecef;
    padding: 0.25rem 0.5rem;
    margin: 0.25rem;
    border-radius: 0.25rem;
    font-family: monospace;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s;
}

.variable-badge:hover {
    background-color: #0d6efd;
    color: white;
}

.recipient-preview {
    max-height: 200px;
    overflow-y: auto;
    font-size: 0.875rem;
}

</style>

            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-envelope me-2"></i> Send Email Campaign</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="campaigns.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-history me-1"></i> View Campaigns
                    </a>
                    <a href="templates.php" class="btn btn-sm btn-outline-primary ms-2">
                        <i class="fas fa-file-alt me-1"></i> Manage Templates
                    </a>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-paper-plane me-2"></i> Compose Email</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($sendError): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <?php echo htmlspecialchars($sendError); ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="" id="emailForm">
                                <!-- Recipients Selection -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Recipients *</label>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <select class="form-select" id="recipients_type" name="recipients_type" required>
                                                <option value="">Select recipient group...</option>
                                                <option value="subscribers">All Newsletter Subscribers</option>
                                                <option value="course_interests">All Course Interest Registrants</option>
                                                <option value="specific_course">Specific Course Interest</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3" id="course_select_container" style="display: none;">
                                            <select class="form-select" id="course_id" name="course_id">
                                                <option value="">Select Course</option>
                                                <?php foreach ($courses as $course): ?>
                                                <option value="<?php echo $course['id']; ?>">
                                                    <?php echo htmlspecialchars($course['title']); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div id="recipient_info" class="alert alert-info mt-2" style="display: none;">
                                        <i class="fas fa-users me-2"></i>
                                        <span id="recipient_count">0</span> recipients found
                                        <button type="button" class="btn btn-sm btn-link" id="showRecipientsBtn">
                                            <i class="fas fa-eye"></i> Show Preview
                                        </button>
                                    </div>
                                    <div id="recipient_preview" class="recipient-preview border rounded p-3 mt-2" style="display: none;">
                                        <strong>Sample Recipients:</strong>
                                        <ul id="recipient_list" class="mt-2 mb-0"></ul>
                                    </div>
                                </div>
                                
                                <!-- Template Selection -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Email Template</label>
                                    <select class="form-select" id="template_id" name="template_id">
                                        <option value="0">-- Custom Email (No Template) --</option>
                                        <?php foreach ($templates as $template): ?>
                                        <option value="<?php echo $template['id']; ?>">
                                            <?php echo htmlspecialchars($template['name']); ?> (<?php echo $template['category']; ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <!-- Subject -->
                                <div class="mb-3">
                                    <label for="subject" class="form-label fw-bold">Subject *</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="subject" 
                                           name="custom_subject" 
                                           placeholder="Enter email subject"
                                           required>
                                </div>
                                
                                <!-- Available Variables -->
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Available Variables</label>
                                    <div>
                                        <span class="variable-badge" data-var="{recipient_name}">{recipient_name}</span>
                                        <span class="variable-badge" data-var="{recipient_email}">{recipient_email}</span>
                                        <span class="variable-badge" data-var="{unsubscribe_link}">{unsubscribe_link}</span>
                                        <span class="variable-badge" data-var="{date}">{date}</span>
                                        <span class="variable-badge" data-var="{site_name}">{site_name}</span>
                                        <span class="variable-badge" data-var="{dynamic_content}">{dynamic_content}</span>
                                    </div>
                                    <small class="text-muted">Click on a variable to insert it into the email content</small>
                                </div>
                                
                                <!-- Email Content -->
                                <div class="mb-3">
                                    <label for="content" class="form-label fw-bold">Email Content *</label>
                                    <textarea class="form-control" 
                                              id="content" 
                                              name="custom_content" 
                                              rows="12"
                                              placeholder="Write your email content here..."></textarea>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <button type="button" class="btn btn-secondary" id="previewBtn">
                                        <i class="fas fa-eye me-1"></i> Preview Email
                                    </button>
                                    <button type="submit" name="send_email" class="btn btn-primary" id="sendBtn">
                                        <i class="fas fa-paper-plane me-1"></i> Send Campaign
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Preview Panel -->
                <div class="col-lg-4">
                    <div class="card shadow-sm sticky-top" style="top: 80px;">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0"><i class="fas fa-eye me-2"></i> Live Preview</h5>
                        </div>
                        <div class="card-body">
                            <div id="preview_subject" class="alert alert-info">
                                <strong>Subject:</strong> <span id="preview_subject_text">No subject yet</span>
                            </div>
                            <div id="preview_content" class="email-preview">
                                <p class="text-muted text-center">Select a template or enter content to see preview</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const recipientsType = document.getElementById('recipients_type');
    const courseSelectContainer = document.getElementById('course_select_container');
    const courseId = document.getElementById('course_id');
    const recipientInfo = document.getElementById('recipient_info');
    const recipientCount = document.getElementById('recipient_count');
    const recipientPreview = document.getElementById('recipient_preview');
    const recipientList = document.getElementById('recipient_list');
    const showRecipientsBtn = document.getElementById('showRecipientsBtn');
    const templateSelect = document.getElementById('template_id');
    const subjectInput = document.getElementById('subject');
    const contentTextarea = document.getElementById('content');
    const previewBtn = document.getElementById('previewBtn');
    const sendBtn = document.getElementById('sendBtn');
    const emailForm = document.getElementById('emailForm');
    
    // Update recipient count when selection changes
    function updateRecipientCount() {
        const type = recipientsType.value;
        const course = courseId.value;
        
        if (!type) {
            recipientInfo.style.display = 'none';
            return;
        }
        
        const formData = new FormData();
        formData.append('get_recipient_count', '1');
        formData.append('recipients_type', type);
        if (type === 'specific_course' && course) {
            formData.append('course_id', course);
        }
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            recipientCount.textContent = data.count;
            recipientInfo.style.display = 'block';
            
            // Populate recipient list for preview
            if (data.recipients && data.recipients.length > 0) {
                recipientList.innerHTML = '';
                data.recipients.forEach(recipient => {
                    const li = document.createElement('li');
                    li.textContent = recipient.email + (recipient.full_name ? ' (' + recipient.full_name + ')' : '');
                    recipientList.appendChild(li);
                });
            }
        });
    }
    
    // Show/hide course select based on recipient type
    recipientsType.addEventListener('change', function() {
        if (this.value === 'specific_course') {
            courseSelectContainer.style.display = 'block';
        } else {
            courseSelectContainer.style.display = 'none';
        }
        updateRecipientCount();
    });
    
    courseId.addEventListener('change', updateRecipientCount);
    
    // Show recipients preview
    showRecipientsBtn.addEventListener('click', function() {
        if (recipientPreview.style.display === 'none') {
            recipientPreview.style.display = 'block';
            this.textContent = 'Hide Preview';
        } else {
            recipientPreview.style.display = 'none';
            this.textContent = 'Show Preview';
        }
    });
    
    // Load template content
    templateSelect.addEventListener('change', function() {
        const templateId = this.value;
        
        if (templateId == 0) {
            return;
        }
        
        fetch('templates.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'get_template=' + templateId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                subjectInput.value = data.template.subject;
                contentTextarea.value = data.template.content;
                updatePreview();
            }
        });
    });
    
    // Update live preview
    function updatePreview() {
        const templateId = templateSelect.value;
        const subject = subjectInput.value;
        const content = contentTextarea.value;
        
        const formData = new FormData();
        formData.append('preview_email', '1');
        formData.append('template_id', templateId);
        formData.append('subject', subject);
        formData.append('content', content);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('preview_subject_text').textContent = data.subject || 'No subject';
            document.getElementById('preview_content').innerHTML = data.content || '<p class="text-muted">No content to preview</p>';
        });
    }
    
    // Manual preview button
    previewBtn.addEventListener('click', updatePreview);
    
    // Auto-update on input changes
    subjectInput.addEventListener('input', updatePreview);
    contentTextarea.addEventListener('input', updatePreview);
    
    // Variable insertion
    document.querySelectorAll('.variable-badge').forEach(badge => {
        badge.addEventListener('click', function() {
            const variable = this.getAttribute('data-var');
            const cursorPos = contentTextarea.selectionStart;
            const text = contentTextarea.value;
            const before = text.substring(0, cursorPos);
            const after = text.substring(cursorPos);
            contentTextarea.value = before + variable + after;
            contentTextarea.focus();
            updatePreview();
        });
    });
    
    // Form validation
    emailForm.addEventListener('submit', function(e) {
        const type = recipientsType.value;
        const subject = subjectInput.value.trim();
        const content = contentTextarea.value.trim();
        
        if (!type) {
            e.preventDefault();
            alert('Please select recipient group.');
            return false;
        }
        
        if (type === 'specific_course' && !courseId.value) {
            e.preventDefault();
            alert('Please select a course.');
            return false;
        }
        
        if (!subject) {
            e.preventDefault();
            alert('Please enter an email subject.');
            return false;
        }
        
        if (!content) {
            e.preventDefault();
            alert('Please enter email content.');
            return false;
        }
        
        const confirmMsg = 'Are you sure you want to send this email to the selected recipients?\n\n' +
                          'This action cannot be undone and emails will be sent immediately.';
        if (!confirm(confirmMsg)) {
            e.preventDefault();
            return false;
        }
    });
});
</script>

<?php include '../../admin/includes/admin_footer.php'; ?>