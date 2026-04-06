<?php
require_once 'config/environment.php';
require_once 'includes/core/Functions.php';

$pageTitle = "405 - Method Not Allowed | " . SITE_NAME;
$pageDescription = "The requested method is not supported for this resource.";
http_response_code(405);
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
    color: #fd7e14;
    margin-bottom: 1.5rem;
}

.error-icon i {
    animation: shake 0.5s ease-in-out;
}

@keyframes shake {
    0%, 100% {
        transform: translateX(0);
    }
    25% {
        transform: translateX(-10px);
    }
    75% {
        transform: translateX(10px);
    }
}

.error-code {
    font-size: 6rem;
    font-weight: 700;
    color: #fd7e14;
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

.method-info {
    background-color: #fff3cd;
    border: 1px solid #ffc107;
    padding: 1rem;
    border-radius: 0.5rem;
    margin-bottom: 2rem;
    text-align: left;
}

.method-info h5 {
    color: #856404;
    margin-bottom: 0.75rem;
}

.method-info code {
    background-color: #fff;
    padding: 0.2rem 0.4rem;
    border-radius: 0.25rem;
    font-size: 0.875rem;
}

.allowed-methods {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
    flex-wrap: wrap;
    margin: 1rem 0;
}

.allowed-method {
    background-color: #28a745;
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 2rem;
    font-size: 0.875rem;
    font-family: monospace;
}

.current-method {
    background-color: #dc3545;
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 2rem;
    font-size: 0.875rem;
    font-family: monospace;
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
</style>

<section class="error-page">
    <div class="container">
        <div class="error-content">
            <div class="error-icon">
                <i class="fas fa-ban"></i>
            </div>
            <div class="error-code">405</div>
            <h1 class="error-title">Method Not Allowed</h1>
            <p class="error-message">
                The HTTP method used to access this page is not supported.
            </p>
            
            <?php
            // Get request method if available
            $request_method = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
            ?>
            
            <div class="method-info">
                <h5><i class="fas fa-info-circle me-2"></i> Technical Details:</h5>
                <p class="mb-2">The request was made using <strong><code><?php echo htmlspecialchars($request_method); ?></code></strong> method, which is not allowed for this resource.</p>
                <p class="mb-0">This page expects one of the following HTTP methods:</p>
                <div class="allowed-methods">
                    <span class="allowed-method">GET</span>
                    <span class="allowed-method">POST</span>
                </div>
                <p class="text-muted small mt-2 mb-0">Your request used: <span class="current-method"><?php echo htmlspecialchars($request_method); ?></span></p>
            </div>
            
            <div class="action-buttons">
                <a href="javascript:history.back()" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i> Go Back
                </a>
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home me-2"></i> Return Home
                </a>
                <a href="contact.php" class="btn btn-outline-primary">
                    <i class="fas fa-envelope me-2"></i> Report Issue
                </a>
            </div>
            
            <div class="suggestions">
                <h4><i class="fas fa-lightbulb me-2"></i> What Could Have Happened:</h4>
                <ul>
                    <li>You may have submitted a form incorrectly</li>
                    <li>A bookmark or link might be outdated</li>
                    <li>You might have tried to directly access an API endpoint</li>
                    <li>A plugin or extension may be causing conflicts</li>
                </ul>
            </div>
            
            <div class="mt-4">
                <hr>
                <p class="text-muted small">
                    If you believe this is an error, please <a href="contact.php">contact our support team</a> 
                    and include the method you were trying to use.
                </p>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>