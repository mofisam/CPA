<?php
require_once 'config/environment.php';
require_once 'includes/core/Database.php';
require_once 'includes/core/Functions.php';

use includes\core\Database;

$db = Database::getInstance();
$success = false;
$message = '';

// Get token from URL
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $message = 'Invalid unsubscribe link.';
} else {
    // Find subscriber by token
    $subscriber = $db->fetchOne(
        "SELECT id, email, full_name FROM subscribers WHERE unsubscribe_token = ? AND is_active = 1",
        [$token]
    );
    
    if ($subscriber) {
        // Unsubscribe
        $db->update('subscribers', 
            ['is_active' => 0, 'unsubscribed_at' => date('Y-m-d H:i:s')], 
            'id = ?', 
            [$subscriber['id']]
        );
        
        $success = true;
        $message = 'You have been successfully unsubscribed from our newsletter.';
    } else {
        $message = 'Invalid or already processed unsubscribe link.';
    }
}

$pageTitle = "Unsubscribe - Clinical Physiology Academy";
?>
<?php include 'includes/header.php'; ?>

<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 mx-auto">
                <div class="card border-0 shadow-lg">
                    <div class="card-header bg-<?php echo $success ? 'success' : 'danger'; ?> text-white">
                        <h3 class="h4 mb-0">
                            <i class="fas fa-<?php echo $success ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                            <?php echo $success ? 'Unsubscribed' : 'Unsubscribe Failed'; ?>
                        </h3>
                    </div>
                    <div class="card-body p-5 text-center">
                        <div class="mb-4">
                            <i class="fas fa-<?php echo $success ? 'envelope-open-text' : 'envelope'; ?> fa-3x text-<?php echo $success ? 'success' : 'danger'; ?> mb-3"></i>
                            <p class="lead"><?php echo htmlspecialchars($message); ?></p>
                        </div>
                        
                        <?php if ($success): ?>
                            <p class="text-muted mb-4">
                                You will no longer receive marketing emails from Clinical Physiology Academy.
                                If you change your mind, you can <a href="subscribe.php">subscribe again</a> at any time.
                            </p>
                            <div class="d-grid gap-2">
                                <a href="index.php" class="btn btn-primary">
                                    <i class="fas fa-home me-2"></i> Back to Home
                                </a>
                                <a href="courses.php" class="btn btn-outline-primary">
                                    <i class="fas fa-graduation-cap me-2"></i> Browse Courses
                                </a>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-4">
                                If you need help with unsubscribing, please <a href="contact.php">contact our support team</a>.
                            </p>
                            <div class="d-grid gap-2">
                                <a href="index.php" class="btn btn-primary">
                                    <i class="fas fa-home me-2"></i> Back to Home
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>