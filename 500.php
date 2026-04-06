<?php
require_once 'config/environment.php';
require_once 'includes/core/Functions.php';

$pageTitle = "500 - Server Error | " . SITE_NAME;
$pageDescription = "Something went wrong on our end. Please try again later.";
http_response_code(500);
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
    animation: blink 1s ease-in-out infinite;
}

@keyframes blink {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
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
    margin-bottom: 1rem;
}

.refresh-timer {
    background-color: #e9ecef;
    padding: 0.5rem 1rem;
    border-radius: 2rem;
    display: inline-block;
    margin-bottom: 2rem;
    font-size: 0.875rem;
}

.refresh-timer i {
    margin-right: 0.5rem;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: 2rem;
}

.error-steps {
    background-color: #f8f9fa;
    padding: 1.5rem;
    border-radius: 0.5rem;
    text-align: left;
    margin-top: 2rem;
}

.error-steps h4 {
    font-size: 1.1rem;
    margin-bottom: 1rem;
    color: #2c3e50;
}

.error-steps ol {
    margin-bottom: 0;
    padding-left: 1.2rem;
}

.error-steps li {
    margin-bottom: 0.5rem;
    color: #6c757d;
}

.report-box {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    padding: 1rem;
    border-radius: 0.5rem;
    margin-top: 1rem;
    text-align: left;
}

.report-box h5 {
    color: #721c24;
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
}

.report-box p {
    margin-bottom: 0;
    font-size: 0.875rem;
    color: #721c24;
}
</style>

<section class="error-page">
    <div class="container">
        <div class="error-content">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="error-code">500</div>
            <h1 class="error-title">Internal Server Error</h1>
            <p class="error-message">
                Something went wrong on our end. We're working to fix the issue.
            </p>
            
            <div class="refresh-timer" id="refreshTimer">
                <i class="fas fa-sync-alt"></i>
                <span id="timerSeconds">10</span> seconds until auto-refresh...
            </div>
            
            <div class="action-buttons">
                <a href="javascript:location.reload()" class="btn btn-primary" id="refreshNowBtn">
                    <i class="fas fa-sync-alt me-2"></i> Refresh Now
                </a>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-home me-2"></i> Return Home
                </a>
                <a href="contact.php" class="btn btn-outline-primary">
                    <i class="fas fa-envelope me-2"></i> Contact Support
                </a>
            </div>
            
            <div class="error-steps">
                <h4><i class="fas fa-clipboard-list me-2"></i> What You Can Do:</h4>
                <ol>
                    <li><strong>Wait a moment</strong> - The issue might be temporary</li>
                    <li><strong>Refresh the page</strong> - Sometimes a simple refresh helps</li>
                    <li><strong>Clear your browser cache</strong> - Old cache might cause conflicts</li>
                    <li><strong>Try again later</strong> - We're likely working on the issue</li>
                    <li><strong>Contact support</strong> - If the problem persists</li>
                </ol>
            </div>
            
            <div class="report-box">
                <h5><i class="fas fa-bug me-2"></i> Error Reference:</h5>
                <p>
                    An error has been logged with reference ID: 
                    <code id="errorRef"></code>
                </p>
                <p class="mt-2 small mb-0">
                    Please include this reference if you contact support.
                </p>
            </div>
        </div>
    </div>
</section>

<script>
// Generate random error reference
function generateErrorRef() {
    return 'ERR_' + Date.now() + '_' + Math.random().toString(36).substr(2, 8).toUpperCase();
}

// Set error reference
document.getElementById('errorRef').textContent = generateErrorRef();

// Auto-refresh countdown
let seconds = 10;
const timerSpan = document.getElementById('timerSeconds');
const refreshTimer = document.getElementById('refreshTimer');

const countdown = setInterval(function() {
    seconds--;
    if (timerSpan) {
        timerSpan.textContent = seconds;
    }
    
    if (seconds <= 0) {
        clearInterval(countdown);
        location.reload();
    }
}, 1000);

// Manual refresh
document.getElementById('refreshNowBtn').addEventListener('click', function(e) {
    clearInterval(countdown);
    location.reload();
});

// Log error to console for debugging (optional)
console.error('500 Error: Internal Server Error occurred at ' + new Date().toISOString());
</script>

<?php include 'includes/footer.php'; ?>