<?php
require_once 'config/environment.php';
require_once 'includes/core/Functions.php';

$pageTitle = "403 - Access Forbidden | " . SITE_NAME;
$pageDescription = "You don't have permission to access this resource.";
http_response_code(403);
?>
<?php include 'includes/header.php'; ?>

<style>
.error-page {
    min-height: 60vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 3rem 0;
}

.error-content {
    text-align: center;
    max-width: 600px;
    margin: 0 auto;
}

.error-icon {
    font-size: 6rem;
    color: #dc3545;
    margin-bottom: 1.5rem;
}

.error-icon i {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.1);
        opacity: 0.8;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

.error-code {
    font-size: 6rem;
    font-weight: 700;
    color: #dc3545;
    line-height: 1;
    margin-bottom: 1rem;
    font-family: monospace;
}

.error-title {
    font-size: 2rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: #2c3e50;
}

.error-message {
    font-size: 1.1rem;
    color: #6c757d;
    margin-bottom: 2rem;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: 2rem;
}

.suggestions {
    background-color: #f8f9fa;
    padding: 1.5rem;
    border-radius: 0.5rem;
    text-align: left;
    margin-top: 2rem;
}

.suggestions h4 {
    font-size: 1.1rem;
    margin-bottom: 1rem;
    color: #2c3e50;
}

.suggestions ul {
    margin-bottom: 0;
    padding-left: 1.2rem;
}

.suggestions li {
    margin-bottom: 0.5rem;
    color: #6c757d;
}

.suggestions li a {
    text-decoration: none;
}

.suggestions li a:hover {
    text-decoration: underline;
}
</style>

<section class="error-page">
    <div class="container">
        <div class="error-content">
            <div class="error-icon">
                <i class="fas fa-ban"></i>
            </div>
            <div class="error-code">403</div>
            <h1 class="error-title">Access Forbidden</h1>
            <p class="error-message">
                You don't have permission to access this page or resource. 
                This could be due to insufficient privileges or authentication requirements.
            </p>
            
            <div class="action-buttons">
                <a href="javascript:history.back()" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i> Go Back
                </a>
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home me-2"></i> Return Home
                </a>
                <a href="contact.php" class="btn btn-outline-primary">
                    <i class="fas fa-envelope me-2"></i> Contact Support
                </a>
            </div>
            
            <div class="suggestions">
                <h4><i class="fas fa-lightbulb me-2"></i> Possible Reasons:</h4>
                <ul>
                    <li>You may need to <a href="admin/login.php">login</a> to access this resource</li>
                    <li>Your account may not have the required permissions</li>
                    <li>The page might be restricted to certain user roles</li>
                    <li>You may have attempted to access an admin-only area</li>
                    <li>Your IP address might be blocked</li>
                </ul>
            </div>
            
            <div class="mt-4">
                <hr>
                <p class="text-muted small">
                    If you believe this is an error, please contact our support team at 
                    <a href="mailto:<?php echo ADMIN_EMAIL; ?>"><?php echo ADMIN_EMAIL; ?></a>
                </p>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>