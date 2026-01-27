<?php
require_once 'config/environment.php';
require_once 'includes/core/Database.php';
require_once 'includes/core/Functions.php';

use includes\core\Database;

$pageTitle = "Contact Us | " . SITE_NAME;
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($message)) {
        $error = 'Please fill in all required fields.';
    } elseif (!CPAFunctions::isValidEmail($email)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            $db = Database::getInstance();
            
            $data = [
                'name' => $name,
                'email' => $email,
                'subject' => $subject,
                'message' => $message,
                'submitted_at' => date('Y-m-d H:i:s')
            ];
            
            $db->insert('contact_messages', $data);
            $success = true;
            
            // You can add email sending functionality here
            
        } catch (Exception $e) {
            $error = 'Sorry, there was an error submitting your message. Please try again.';
            if (DEBUG_MODE) {
                $error .= ' Error: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo CPAFunctions::safeOutput($pageTitle); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo CPAFunctions::asset('css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/">Home</a></li>
                            <li class="breadcrumb-item active">Contact Us</li>
                        </ol>
                    </nav>
                </div>
            </div>
            
            <?php if ($success): ?>
                <div class="row justify-content-center">
                    <div class="col-lg-6">
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            Thank you for your message! We'll get back to you soon.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="row justify-content-center">
                    <div class="col-lg-6">
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo CPAFunctions::safeOutput($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h2 class="h4 mb-0">Send us a Message</h2>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST" action="" novalidate>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="form-label">Full Name *</label>
                                        <input type="text" class="form-control" id="name" name="name" required
                                               value="<?php echo isset($_POST['name']) ? CPAFunctions::safeOutput($_POST['name']) : ''; ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email Address *</label>
                                        <input type="email" class="form-control" id="email" name="email" required
                                               value="<?php echo isset($_POST['email']) ? CPAFunctions::safeOutput($_POST['email']) : ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="subject" class="form-label">Subject</label>
                                    <input type="text" class="form-control" id="subject" name="subject"
                                           value="<?php echo isset($_POST['subject']) ? CPAFunctions::safeOutput($_POST['subject']) : ''; ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="message" class="form-label">Message *</label>
                                    <textarea class="form-control" id="message" name="message" rows="6" required><?php echo isset($_POST['message']) ? CPAFunctions::safeOutput($_POST['message']) : ''; ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="fas fa-paper-plane me-2"></i> Send Message
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 mt-4 mt-lg-0">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-primary text-white">
                            <h2 class="h4 mb-0">Contact Information</h2>
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-4">
                                <h3 class="h5 mb-3"><i class="fas fa-map-marker-alt text-primary me-2"></i> Location</h3>
                                <p>Based in Nigeria<br>Online training available worldwide</p>
                            </div>
                            
                            <div class="mb-4">
                                <h3 class="h5 mb-3"><i class="fas fa-envelope text-primary me-2"></i> Email</h3>
                                <p>
                                    <a href="mailto:<?php echo ADMIN_EMAIL; ?>" class="text-decoration-none">
                                        <?php echo ADMIN_EMAIL; ?>
                                    </a>
                                </p>
                            </div>
                            
                            <div class="mb-4">
                                <h3 class="h5 mb-3"><i class="fas fa-clock text-primary me-2"></i> Response Time</h3>
                                <p>We typically respond within 24-48 hours during business days.</p>
                            </div>
                            
                            <div class="mt-4">
                                <h3 class="h5 mb-3">Follow Us</h3>
                                <div class="d-flex gap-3">
                                    <a href="#" class="text-primary fs-5"><i class="fab fa-facebook"></i></a>
                                    <a href="#" class="text-primary fs-5"><i class="fab fa-twitter"></i></a>
                                    <a href="#" class="text-primary fs-5"><i class="fab fa-linkedin"></i></a>
                                    <a href="#" class="text-primary fs-5"><i class="fab fa-instagram"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo CPAFunctions::asset('js/main.js'); ?>"></script>
</body>
</html>