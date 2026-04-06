<?php
require_once 'config/environment.php';
require_once 'includes/core/Functions.php';

$pageTitle = "503 - Service Unavailable | " . SITE_NAME;
$pageDescription = "Our website is temporarily unavailable. Please check back later.";
http_response_code(503);
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
    color: #6c757d;
    margin-bottom: 1.5rem;
}

.error-icon i {
    animation: spin 2s linear infinite;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.error-code {
    font-size: 6rem;
    font-weight: 700;
    color: #6c757d;
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

.maintenance-info {
    background-color: #d1ecf1;
    border: 1px solid #bee5eb;
    padding: 1rem;
    border-radius: 0.5rem;
    margin-bottom: 2rem;
}

.maintenance-info h5 {
    color: #0c5460;
    margin-bottom: 0.5rem;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.social-links {
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid #dee2e6;
}

.social-links a {
    margin: 0 0.5rem;
    font-size: 1.5rem;
    color: #6c757d;
    transition: color 0.3s;
}

.social-links a:hover {
    color: #0d6efd;
}
</style>

<section class="error-page">
    <div class="container">
        <div class="error-content">
            <div class="error-icon">
                <i class="fas fa-tools"></i>
            </div>
            <div class="error-code">503</div>
            <h1 class="error-title">Service Unavailable</h1>
            <p class="error-message">
                Our website is temporarily down for maintenance or experiencing high traffic.
                Please check back soon.
            </p>
            
            <div class="maintenance-info">
                <h5><i class="fas fa-clock me-2"></i> Estimated Downtime:</h5>
                <p class="mb-0">We expect to be back online shortly. Thank you for your patience!</p>
            </div>
            
            <div class="action-buttons">
                <button onclick="location.reload()" class="btn btn-primary">
                    <i class="fas fa-sync-alt me-2"></i> Try Again
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-home me-2"></i> Return Later
                </a>
            </div>
            
            <div class="social-links">
                <p class="text-muted small mb-2">Follow us for updates:</p>
                <a href="#" target="_blank"><i class="fab fa-facebook"></i></a>
                <a href="#" target="_blank"><i class="fab fa-twitter"></i></a>
                <a href="#" target="_blank"><i class="fab fa-linkedin"></i></a>
                <a href="#" target="_blank"><i class="fab fa-instagram"></i></a>
            </div>
            
            <div class="mt-4">
                <hr>
                <p class="text-muted small">
                    Need immediate assistance? <a href="contact.php">Contact our support team</a>
                </p>
            </div>
        </div>
    </div>
</section>

<script>
// Auto-refresh every 30 seconds
setTimeout(function() {
    location.reload();
}, 30000);
</script>

<?php include 'includes/footer.php'; ?>