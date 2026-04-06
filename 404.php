<?php
require_once 'config/environment.php';
require_once 'includes/core/Functions.php';

$pageTitle = "404 - Page Not Found | " . SITE_NAME;
$pageDescription = "The page you're looking for cannot be found.";
http_response_code(404);
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
    color: #ffc107;
    margin-bottom: 1.5rem;
}

.error-icon i {
    animation: swing 2s ease-in-out infinite;
}

@keyframes swing {
    0% {
        transform: rotate(0deg);
    }
    50% {
        transform: rotate(15deg);
    }
    100% {
        transform: rotate(0deg);
    }
}

.error-code {
    font-size: 6rem;
    font-weight: 700;
    color: #ffc107;
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

.search-box {
    max-width: 400px;
    margin: 0 auto 2rem;
}

.search-box .input-group {
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.search-box .form-control {
    border-right: none;
}

.search-box .btn {
    border-left: none;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: 2rem;
}

.popular-links {
    background-color: #f8f9fa;
    padding: 1.5rem;
    border-radius: 0.5rem;
    margin-top: 1rem;
}

.popular-links h4 {
    font-size: 1.1rem;
    margin-bottom: 1rem;
    color: #2c3e50;
}

.popular-links .row {
    margin-top: 0.5rem;
}

.popular-links a {
    text-decoration: none;
    display: inline-block;
    margin: 0.25rem 0;
}

.popular-links a:hover {
    text-decoration: underline;
}
</style>

<section class="error-page">
    <div class="container">
        <div class="error-content">
            <div class="error-icon">
                <i class="fas fa-search"></i>
            </div>
            <div class="error-code">404</div>
            <h1 class="error-title">Page Not Found</h1>
            <p class="error-message">
                Oops! The page you're looking for doesn't exist or has been moved. 
                Let's get you back on track.
            </p>
            
            <!-- Search Box -->
            <div class="search-box">
                <form action="courses.php" method="GET">
                    <div class="input-group">
                        <input type="text" 
                               class="form-control" 
                               name="search" 
                               placeholder="Search for courses or content..."
                               aria-label="Search">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="action-buttons">
                <a href="javascript:history.back()" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i> Go Back
                </a>
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home me-2"></i> Return Home
                </a>
                <a href="courses.php" class="btn btn-outline-primary">
                    <i class="fas fa-graduation-cap me-2"></i> Browse Courses
                </a>
            </div>
            
            <div class="popular-links">
                <h4><i class="fas fa-link me-2"></i> Popular Pages You Might Be Looking For:</h4>
                <div class="row">
                    <div class="col-md-4">
                        <ul class="list-unstyled">
                            <li><a href="index.php"><i class="fas fa-home me-2"></i> Home</a></li>
                            <li><a href="courses.php"><i class="fas fa-graduation-cap me-2"></i> All Courses</a></li>
                            <li><a href="about.php"><i class="fas fa-info-circle me-2"></i> About Us</a></li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <ul class="list-unstyled">
                            <li><a href="contact.php"><i class="fas fa-envelope me-2"></i> Contact Us</a></li>
                            <li><a href="register-interest.php"><i class="fas fa-user-plus me-2"></i> Register Interest</a></li>
                            <li><a href="subscribe.php"><i class="fas fa-newspaper me-2"></i> Newsletter</a></li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <ul class="list-unstyled">
                            <li><a href="admin/login.php"><i class="fas fa-user-shield me-2"></i> Admin Login</a></li>
                            <li><a href="privacy.php"><i class="fas fa-lock me-2"></i> Privacy Policy</a></li>
                            <li><a href="terms.php"><i class="fas fa-file-contract me-2"></i> Terms of Service</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <hr>
                <p class="text-muted small">
                    If you continue to experience issues, please <a href="contact.php">contact our support team</a>.
                </p>
            </div>
        </div>
    </div>
</section>

<script>
// Track 404 errors for analytics (optional)
if (typeof gtag !== 'undefined') {
    gtag('event', '404', {
        'event_category': 'error',
        'event_label': window.location.pathname,
        'value': 1
    });
}
</script>

<?php include 'includes/footer.php'; ?>