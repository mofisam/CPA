<?php
require_once 'config/environment.php';
require_once 'includes/core/Database.php';
require_once 'includes/core/Functions.php';

use includes\core\Database;

$db = Database::getInstance();

// Handle AJAX subscription
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    header('Content-Type: application/json');
    
    $email = trim($_POST['email']);
    $name = trim($_POST['name'] ?? '');
    $source = trim($_POST['source'] ?? 'website');
    
    $response = ['success' => false, 'message' => ''];
    
    // Validation
    if (empty($email)) {
        $response['message'] = 'Email address is required';
        echo json_encode($response);
        exit();
    }
    
    if (!CPAFunctions::isValidEmail($email)) {
        $response['message'] = 'Please enter a valid email address';
        echo json_encode($response);
        exit();
    }
    
    // Check if already subscribed
    $existing = $db->fetchOne(
        "SELECT id, is_active FROM subscribers WHERE email = ?",
        [$email]
    );
    
    if ($existing) {
        if ($existing['is_active']) {
            $response['success'] = true;
            $response['message'] = 'You are already subscribed to our newsletter!';
        } else {
            // Reactivate subscription
            $db->update('subscribers', 
                ['is_active' => 1, 'subscribed_at' => date('Y-m-d H:i:s')], 
                'id = ?', 
                [$existing['id']]
            );
            $response['success'] = true;
            $response['message'] = 'Welcome back! Your subscription has been reactivated.';
        }
    } else {
        // New subscription
        $subscriberData = [
            'email' => $email,
            'full_name' => $name,
            'subscribed_at' => date('Y-m-d H:i:s'),
            'is_active' => 1,
            'source' => $source
        ];
        
        try {
            $db->insert('subscribers', $subscriberData);
            $response['success'] = true;
            $response['message'] = 'Thank you for subscribing to our newsletter!';
        } catch (Exception $e) {
            $response['message'] = 'Error subscribing. Please try again.';
        }
    }
    
    echo json_encode($response);
    exit();
}

// Handle regular form submission (fallback)
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subscribe'])) {
    $email = trim($_POST['email']);
    $name = trim($_POST['name'] ?? '');
    
    if (empty($email)) {
        $error = 'Email address is required';
    } elseif (!CPAFunctions::isValidEmail($email)) {
        $error = 'Please enter a valid email address';
    } else {
        // Check if already subscribed
        $existing = $db->fetchOne(
            "SELECT id, is_active FROM subscribers WHERE email = ?",
            [$email]
        );
        
        if ($existing) {
            if ($existing['is_active']) {
                $success = true;
                $message = 'You are already subscribed to our newsletter!';
            } else {
                $db->update('subscribers', 
                    ['is_active' => 1, 'subscribed_at' => date('Y-m-d H:i:s')], 
                    'id = ?', 
                    [$existing['id']]
                );
                $success = true;
                $message = 'Welcome back! Your subscription has been reactivated.';
            }
        } else {
            // New subscription
            $subscriberData = [
                'email' => $email,
                'full_name' => $name,
                'subscribed_at' => date('Y-m-d H:i:s'),
                'is_active' => 1,
                'source' => 'website'
            ];
            
            try {
                $db->insert('subscribers', $subscriberData);
                $success = true;
                $message = 'Thank you for subscribing to our newsletter!';
            } catch (Exception $e) {
                $error = 'Error subscribing. Please try again.';
            }
        }
    }
}

$pageTitle = "Subscribe to Newsletter - Clinical Physiology Academy";
$pageDescription = "Subscribe to our newsletter for updates on courses, training programs, and clinical physiology insights.";
?>
<?php include 'includes/header.php'; ?>

<!-- Hero -->
<section class="py-5 bg-primary text-white">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="display-4 fw-bold mb-3">Stay Updated</h1>
                <p class="lead mb-0">
                    Subscribe to our newsletter for the latest in clinical physiology education
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Breadcrumb -->
<section class="py-3 bg-light">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active">Subscribe</li>
            </ol>
        </nav>
    </div>
</section>

<!-- Subscription Section -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <?php if ($success): ?>
                    <div class="card border-success">
                        <div class="card-header bg-success text-white">
                            <h3 class="h4 mb-0"><i class="fas fa-check-circle me-2"></i> Subscription Successful!</h3>
                        </div>
                        <div class="card-body p-5 text-center">
                            <div class="mb-4">
                                <i class="fas fa-envelope-open-text text-success display-1"></i>
                            </div>
                            <h4 class="mb-3">Thank You for Subscribing!</h4>
                            <p class="lead mb-4">
                                <?php echo htmlspecialchars($message); ?>
                            </p>
                            <div class="alert alert-info">
                                <h5 class="alert-heading"><i class="fas fa-info-circle me-2"></i> What to Expect</h5>
                                <ul class="mb-0">
                                    <li>Monthly newsletter with course updates</li>
                                    <li>Early access to new training programs</li>
                                    <li>Exclusive offers and discounts</li>
                                    <li>Clinical tips and industry insights</li>
                                </ul>
                            </div>
                            <div class="mt-4">
                                <a href="index.php" class="btn btn-primary me-2">
                                    <i class="fas fa-home me-1"></i> Back to Home
                                </a>
                                <a href="courses.php" class="btn btn-outline-primary">
                                    <i class="fas fa-graduation-cap me-1"></i> Browse Courses
                                </a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card border-0 shadow-lg">
                        <div class="card-header bg-primary text-white py-3">
                            <h3 class="h4 mb-0"><i class="fas fa-newspaper me-2"></i> Newsletter Subscription</h3>
                        </div>
                        <div class="card-body p-4">
                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <?php echo htmlspecialchars($error); ?>
                                </div>
                            <?php endif; ?>
                            
                            <p class="text-muted mb-4">
                                Join our community of healthcare professionals and stay informed about:
                            </p>
                            
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="d-flex mb-3">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-calendar-alt text-primary"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-1">New Course Dates</h6>
                                            <small class="text-muted">Be first to know about upcoming sessions</small>
                                        </div>
                                    </div>
                                    <div class="d-flex mb-3">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-gift text-primary"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-1">Special Offers</h6>
                                            <small class="text-muted">Exclusive discounts for subscribers</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex mb-3">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-lightbulb text-primary"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-1">Clinical Insights</h6>
                                            <small class="text-muted">Tips and updates in clinical physiology</small>
                                        </div>
                                    </div>
                                    <div class="d-flex mb-3">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-book-open text-primary"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-1">Learning Resources</h6>
                                            <small class="text-muted">Free resources and study materials</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <form method="POST" action="" id="subscribeForm">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="form-label">Your Name (Optional)</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="name" 
                                               name="name" 
                                               placeholder="Enter your name"
                                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email Address *</label>
                                        <input type="email" 
                                               class="form-control" 
                                               id="email" 
                                               name="email" 
                                               placeholder="your.email@example.com"
                                               required
                                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <div class="form-check">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="terms" 
                                               name="terms" 
                                               required>
                                        <label class="form-check-label" for="terms">
                                            I agree to receive newsletters and updates from Clinical Physiology Academy. 
                                            I can unsubscribe at any time using the link in the emails.
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" name="subscribe" class="btn btn-primary btn-lg py-3">
                                        <i class="fas fa-paper-plane me-2"></i> Subscribe Now
                                    </button>
                                </div>
                                
                                <div class="text-center mt-3">
                                    <small class="text-muted">
                                        <i class="fas fa-lock me-1"></i>
                                        We respect your privacy. Your email is safe with us.
                                    </small>
                                </div>
                            </form>
                            
                            <!-- AJAX Subscription Form (for footer) -->
                            <div class="mt-4" style="display: none;">
                                <h6 class="text-muted mb-2">Quick Subscribe</h6>
                                <form id="quickSubscribeForm" class="d-flex">
                                    <input type="email" 
                                           class="form-control me-2" 
                                           placeholder="Enter email" 
                                           required>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </form>
                                <div id="subscribeResponse" class="mt-2"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Already Subscribed? -->
                    <div class="card border-0 shadow-sm mt-4">
                        <div class="card-body p-4 text-center">
                            <h5 class="mb-3">Already a subscriber?</h5>
                            <p class="text-muted mb-3">
                                If you're not receiving our emails, check your spam folder or 
                                <a href="contact.php">contact us</a> for assistance.
                            </p>
                            <a href="contact.php" class="btn btn-outline-primary">
                                <i class="fas fa-envelope me-1"></i> Contact Support
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('subscribeForm');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const terms = document.getElementById('terms');
            
            if (!email) {
                e.preventDefault();
                alert('Email address is required.');
                return false;
            }
            
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address.');
                return false;
            }
            
            if (!terms.checked) {
                e.preventDefault();
                alert('Please agree to receive newsletters.');
                return false;
            }
        });
    }
    
    // AJAX subscription for quick form
    const quickForm = document.getElementById('quickSubscribeForm');
    if (quickForm) {
        quickForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = this.querySelector('input[type="email"]').value.trim();
            const responseDiv = document.getElementById('subscribeResponse');
            
            if (!email) {
                responseDiv.innerHTML = '<div class="alert alert-danger">Email is required</div>';
                return;
            }
            
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                responseDiv.innerHTML = '<div class="alert alert-danger">Invalid email address</div>';
                return;
            }
            
            // Send AJAX request
            fetch('subscribe.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'email=' + encodeURIComponent(email)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    responseDiv.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
                    quickForm.reset();
                } else {
                    responseDiv.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
                }
            })
            .catch(error => {
                responseDiv.innerHTML = '<div class="alert alert-danger">Error subscribing. Please try again.</div>';
            });
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>