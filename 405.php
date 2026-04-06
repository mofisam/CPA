<?php
require_once 'config/environment.php';
require_once 'includes/core/Functions.php';

$pageTitle = "405 - Request Not Supported | " . SITE_NAME;
$pageDescription = "The requested action is not supported. Please try a different approach.";
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
    max-width: 500px;
    margin: 0 auto;
}

.error-icon {
    font-size: 5rem;
    color: #fd7e14;
    margin-bottom: 1.5rem;
}

.error-icon i {
    animation: gentleShake 0.5s ease-in-out;
}

@keyframes gentleShake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

.error-code {
    font-size: 5rem;
    font-weight: 700;
    color: #fd7e14;
    line-height: 1;
    margin-bottom: 0.5rem;
    font-family: monospace;
}

.error-title {
    font-size: 1.75rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: #2c3e50;
}

.error-message {
    font-size: 1rem;
    color: #6c757d;
    margin-bottom: 2rem;
    line-height: 1.6;
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
    margin-top: 1rem;
}

.suggestions h4 {
    font-size: 1rem;
    margin-bottom: 0.75rem;
    color: #2c3e50;
}

.suggestions ul {
    margin-bottom: 0;
    padding-left: 1.2rem;
}

.suggestions li {
    margin-bottom: 0.5rem;
    color: #6c757d;
    font-size: 0.9rem;
}

.suggestions li i {
    color: #fd7e14;
    margin-right: 0.5rem;
    width: 1.25rem;
}
</style>

<section class="error-page">
    <div class="container">
        <div class="error-content">
            <div class="error-icon">
                <i class="fas fa-hand-paper"></i>
            </div>
            <div class="error-code">405</div>
            <h1 class="error-title">Oops! That Didn't Work</h1>
            <p class="error-message">
                The way you tried to access this page isn't supported. 
                Don't worry though - there's usually a simpler way to do what you're trying to accomplish.
            </p>
            
            <div class="action-buttons">
                <a href="javascript:history.back()" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i> Go Back
                </a>
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home me-2"></i> Home Page
                </a>
            </div>
            
            <div class="suggestions">
                <h4><i class="fas fa-lightbulb me-2"></i> Here's What You Can Try:</h4>
                <ul>
                    <li><i class="fas fa-arrow-left"></i> Use the back button and try again</li>
                    <li><i class="fas fa-home"></i> Start fresh from our <a href="index.php">homepage</a></li>
                    <li><i class="fas fa-search"></i> Use the search to find what you need</li>
                    <li><i class="fas fa-envelope"></i> <a href="contact.php">Contact us</a> if you need help</li>
                </ul>
            </div>
            
            <div class="mt-4">
                <hr>
                <p class="text-muted small">
                    Need immediate assistance? Our support team is here to help.
                    <a href="contact.php">Get in touch</a>
                </p>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>