<?php
require_once 'config/environment.php';
require_once 'includes/core/Database.php';
require_once 'includes/core/Functions.php';

use includes\core\Database;

$db = Database::getInstance();

// Handle form submission
$success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $profession = trim($_POST['profession']);
    $qualification = trim($_POST['qualification']);
    $exam_prep = isset($_POST['exam_prep']) ? $_POST['exam_prep'] : [];
    $interest = $_POST['interest'] ?? [];
    $message = trim($_POST['message']);
    
    // Validation
    if (empty($full_name)) {
        $errors[] = 'Full name is required';
    }
    
    if (empty($email)) {
        $errors[] = 'Email address is required';
    } elseif (!CPAFunctions::isValidEmail($email)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    if (empty($profession)) {
        $errors[] = 'Profession is required';
    }
    
    if (empty($interest)) {
        $errors[] = 'Please select at least one area of interest';
    }
    
    // Check if email already exists in course_interests (recent submissions)
    $existing = $db->fetchOne(
        "SELECT id FROM course_interests 
         WHERE email = ? AND submitted_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
        [$email]
    );
    
    if ($existing) {
        $errors[] = 'You have already registered your interest recently. We will contact you soon.';
    }
    
    // If no errors, save to database
    if (empty($errors)) {
        try {
            // Convert arrays to JSON for storage
            $exam_prep_json = !empty($exam_prep) ? json_encode($exam_prep) : null;
            $interests_json = json_encode($interest);
            
            // Save to course_interests table
            $interestData = [
                'full_name' => $full_name,
                'email' => $email,
                'phone' => $phone,
                'profession' => $profession,
                'qualification' => $qualification,
                'exam_preparation' => $exam_prep_json,
                'interests' => $interests_json,
                'additional_message' => $message,
                'status' => 'pending',
                'submitted_at' => date('Y-m-d H:i:s')
            ];
            
            $interestId = $db->insert('course_interests', $interestData);
            
            // Also add to subscribers table for newsletter (optional)
            $existingSubscriber = $db->fetchOne(
                "SELECT id FROM subscribers WHERE email = ?",
                [$email]
            );
            
            if (!$existingSubscriber) {
                $subscriberData = [
                    'email' => $email,
                    'full_name' => $full_name,
                    'subscribed_at' => date('Y-m-d H:i:s'),
                    'is_active' => 1
                ];
                $db->insert('subscribers', $subscriberData);
            }
            
            $success = true;
            
            // You could send confirmation email here
            
        } catch (Exception $e) {
            $errors[] = 'Error saving registration. Please try again.';
            if (DEBUG_MODE) {
                $errors[] = 'Error: ' . $e->getMessage();
            }
        }
    }
}

// Get available courses for interest selection
$courses = $db->fetchAll(
    "SELECT id, title, course_type FROM trainings WHERE status IN ('active', 'upcoming')"
);

$pageTitle = "Register Interest - Clinical Physiology Academy";
$pageDescription = "Express your interest in our clinical physiology courses. Get notified about upcoming trainings and special offers.";
?>
<?php include 'includes/header.php'; ?>

<!-- Hero -->
<section class="py-5 bg-primary text-white">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="display-4 fw-bold mb-3">Register Your Interest</h1>
                <p class="lead mb-0">
                    Be the first to know about new courses, special offers, and training opportunities
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
                <li class="breadcrumb-item active">Register Interest</li>
            </ol>
        </nav>
    </div>
</section>

<!-- Registration Form -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <?php if ($success): ?>
                    <div class="card border-success">
                        <div class="card-header bg-success text-white">
                            <h3 class="h4 mb-0"><i class="fas fa-check-circle me-2"></i> Registration Successful!</h3>
                        </div>
                        <div class="card-body p-5 text-center">
                            <div class="mb-4">
                                <i class="fas fa-check-circle text-success display-1"></i>
                            </div>
                            <h4 class="mb-3">Thank You, <?php echo htmlspecialchars($full_name); ?>!</h4>
                            <p class="lead mb-4">
                                Your interest has been registered successfully. We'll contact you soon 
                                with information about our courses and upcoming training sessions.
                            </p>
                            
                            <div class="alert alert-info">
                                <h5 class="alert-heading"><i class="fas fa-envelope me-2"></i> What's Next?</h5>
                                <ul class="mb-0">
                                    <li>Our team will review your interests</li>
                                    <li>We'll contact you within 48 hours via email or phone</li>
                                    <li>You'll receive personalized course recommendations</li>
                                    <li>Get early access to course schedules and special offers</li>
                                </ul>
                            </div>
                            
                            <div class="card mt-4 mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Your Registration Details</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row text-start">
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($full_name); ?></p>
                                            <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
                                            <?php if ($phone): ?>
                                                <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($phone); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Profession:</strong> <?php echo htmlspecialchars($profession); ?></p>
                                            <?php if ($qualification): ?>
                                                <p class="mb-1"><strong>Qualification:</strong> <?php echo htmlspecialchars($qualification); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
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
                            <h3 class="h4 mb-0"><i class="fas fa-user-plus me-2"></i> Express Your Interest</h3>
                        </div>
                        <div class="card-body p-4">
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger">
                                    <h5 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i> Please fix the following errors:</h5>
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <p class="text-muted mb-4">
                                Fill out this form to register your interest in our clinical physiology courses. 
                                We'll contact you with detailed information about upcoming training sessions, 
                                schedules, and special offers.
                            </p>
                            
                            <form method="POST" action="" id="interestForm">
                                <div class="row">
                                    <!-- Personal Information -->
                                    <div class="col-md-6 mb-3">
                                        <label for="full_name" class="form-label">Full Name *</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="full_name" 
                                               name="full_name" 
                                               required
                                               value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email Address *</label>
                                        <input type="email" 
                                               class="form-control" 
                                               id="email" 
                                               name="email" 
                                               required
                                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" 
                                               class="form-control" 
                                               id="phone" 
                                               name="phone" 
                                               placeholder="+234 800 000 0000"
                                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="profession" class="form-label">Profession/Current Role *</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="profession" 
                                               name="profession" 
                                               placeholder="e.g., Cardiac Physiologist, Nurse, Medical Student"
                                               required
                                               value="<?php echo isset($_POST['profession']) ? htmlspecialchars($_POST['profession']) : ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="qualification" class="form-label">Highest Qualification</label>
                                        <select class="form-select" id="qualification" name="qualification">
                                            <option value="">Select Qualification</option>
                                            <option value="OND/ND" <?php echo (isset($_POST['qualification']) && $_POST['qualification'] == 'OND/ND') ? 'selected' : ''; ?>>OND/ND</option>
                                            <option value="HND/BSc" <?php echo (isset($_POST['qualification']) && $_POST['qualification'] == 'HND/BSc') ? 'selected' : ''; ?>>HND/BSc</option>
                                            <option value="MSc" <?php echo (isset($_POST['qualification']) && $_POST['qualification'] == 'MSc') ? 'selected' : ''; ?>>MSc</option>
                                            <option value="MBBS/MD" <?php echo (isset($_POST['qualification']) && $_POST['qualification'] == 'MBBS/MD') ? 'selected' : ''; ?>>MBBS/MD</option>
                                            <option value="PhD" <?php echo (isset($_POST['qualification']) && $_POST['qualification'] == 'PhD') ? 'selected' : ''; ?>>PhD</option>
                                            <option value="Other" <?php echo (isset($_POST['qualification']) && $_POST['qualification'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Are you preparing for any professional exams?</label>
                                        <div class="form-check">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   id="bse_exam" 
                                                   name="exam_prep[]" 
                                                   value="BSE"
                                                   <?php echo (isset($_POST['exam_prep']) && in_array('BSE', $_POST['exam_prep'])) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="bse_exam">
                                                BSE (British Society of Echocardiography)
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   id="rcs_exam" 
                                                   name="exam_prep[]" 
                                                   value="RCS"
                                                   <?php echo (isset($_POST['exam_prep']) && in_array('RCS', $_POST['exam_prep'])) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="rcs_exam">
                                                RCS (Reporting of Chest Pain)
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   id="other_exam" 
                                                   name="exam_prep[]" 
                                                   value="Other"
                                                   <?php echo (isset($_POST['exam_prep']) && in_array('Other', $_POST['exam_prep'])) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="other_exam">
                                                Other Professional Exams
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Areas of Interest -->
                                <div class="mb-4">
                                    <label class="form-label">Areas of Interest *</label>
                                    <div class="alert alert-info py-2">
                                        <small><i class="fas fa-info-circle me-1"></i> Select all that apply</small>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       id="interest_echo" 
                                                       name="interest[]" 
                                                       value="echocardiography"
                                                       <?php echo (isset($_POST['interest']) && in_array('echocardiography', $_POST['interest'])) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="interest_echo">
                                                    <strong>Echocardiography Training</strong>
                                                    <small class="d-block text-muted">56-day intensive program</small>
                                                </label>
                                            </div>
                                            
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       id="interest_ecg" 
                                                       name="interest[]" 
                                                       value="ecg_masterclass"
                                                       <?php echo (isset($_POST['interest']) && in_array('ecg_masterclass', $_POST['interest'])) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="interest_ecg">
                                                    <strong>ECG Interpretation Masterclass</strong>
                                                    <small class="d-block text-muted">4-6 weeks training</small>
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <?php if (!empty($courses)): ?>
                                                <?php foreach ($courses as $course): ?>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" 
                                                               type="checkbox" 
                                                               id="interest_<?php echo $course['id']; ?>" 
                                                               name="interest[]" 
                                                               value="course_<?php echo $course['id']; ?>"
                                                               <?php echo (isset($_POST['interest']) && in_array('course_' . $course['id'], $_POST['interest'])) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="interest_<?php echo $course['id']; ?>">
                                                            <strong><?php echo htmlspecialchars($course['title']); ?></strong>
                                                            <small class="d-block text-muted"><?php echo ucfirst($course['course_type']); ?></small>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                            
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       id="interest_other" 
                                                       name="interest[]" 
                                                       value="other_courses"
                                                       <?php echo (isset($_POST['interest']) && in_array('other_courses', $_POST['interest'])) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="interest_other">
                                                    <strong>Other Specialized Courses</strong>
                                                    <small class="d-block text-muted">Short courses & masterclasses</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Additional Information -->
                                <div class="mb-4">
                                    <label for="message" class="form-label">Additional Information or Questions</label>
                                    <textarea class="form-control" 
                                              id="message" 
                                              name="message" 
                                              rows="4"
                                              placeholder="Tell us about your specific learning goals, questions about our courses, or any other information..."><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                                </div>
                                
                                <!-- Benefits -->
                                <div class="alert alert-success mb-4">
                                    <h5 class="alert-heading"><i class="fas fa-gift me-2"></i> Benefits of Registering</h5>
                                    <ul class="mb-0">
                                        <li>Early notification of new course dates</li>
                                        <li>Exclusive access to special offers</li>
                                        <li>Personalized course recommendations</li>
                                        <li>Priority support from our team</li>
                                        <li>Added to our newsletter for updates</li>
                                    </ul>
                                </div>
                                
                                <!-- Terms -->
                                <div class="mb-4">
                                    <div class="form-check">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="terms" 
                                               name="terms" 
                                               required>
                                        <label class="form-check-label" for="terms">
                                            I agree to receive emails about courses, offers, and updates from 
                                            Clinical Physiology Academy. I can unsubscribe at any time.
                                        </label>
                                    </div>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="privacy" 
                                               name="privacy" 
                                               required>
                                        <label class="form-check-label" for="privacy">
                                            I have read and agree to the <a href="privacy.php">Privacy Policy</a>
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- Submit -->
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg py-3">
                                        <i class="fas fa-paper-plane me-2"></i> Submit Interest Registration
                                    </button>
                                </div>
                                
                                <div class="text-center mt-3">
                                    <small class="text-muted">
                                        <i class="fas fa-lock me-1"></i>
                                        Your information is secure and will only be used to contact you about our courses.
                                    </small>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Contact Info -->
                    <div class="card border-0 shadow-sm mt-4">
                        <div class="card-body p-4">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="mb-3"><i class="fas fa-question-circle text-primary me-2"></i> Need Help?</h5>
                                    <p class="text-muted mb-0">
                                        Have questions about registration or our courses? 
                                        Contact our support team for assistance.
                                    </p>
                                </div>
                                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                                    <a href="contact.php" class="btn btn-outline-primary">
                                        <i class="fas fa-envelope me-1"></i> Contact Support
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('interestForm');
    
    if (form) {
        // Validate form on submit
        form.addEventListener('submit', function(e) {
            // Check required fields
            const fullName = document.getElementById('full_name').value.trim();
            const email = document.getElementById('email').value.trim();
            const profession = document.getElementById('profession').value.trim();
            const terms = document.getElementById('terms');
            const privacy = document.getElementById('privacy');
            
            // Check interests
            const interests = document.querySelectorAll('input[name="interest[]"]:checked');
            
            // Basic validation
            if (!fullName || !email || !profession) {
                e.preventDefault();
                alert('Please fill in all required fields (marked with *).');
                return false;
            }
            
            if (interests.length === 0) {
                e.preventDefault();
                alert('Please select at least one area of interest.');
                return false;
            }
            
            if (!terms.checked || !privacy.checked) {
                e.preventDefault();
                alert('Please agree to the terms and privacy policy.');
                return false;
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address.');
                return false;
            }
            
            // Phone validation (if provided)
            const phone = document.getElementById('phone').value.trim();
            if (phone) {
                const phoneRegex = /^[\+]?[0-9\s\-\(\)]+$/;
                if (!phoneRegex.test(phone.replace(/\s/g, ''))) {
                    e.preventDefault();
                    alert('Please enter a valid phone number.');
                    return false;
                }
            }
            
            // Show confirmation
            if (!confirm('Are you sure you want to submit your interest registration?')) {
                e.preventDefault();
                return false;
            }
        });
        
        // Real-time validation for email
        const emailInput = document.getElementById('email');
        emailInput.addEventListener('blur', function() {
            const email = this.value.trim();
            if (email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    this.classList.add('is-invalid');
                    this.classList.remove('is-valid');
                } else {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                }
            }
        });
        
        // Real-time validation for required fields
        const requiredFields = ['full_name', 'profession'];
        requiredFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.addEventListener('blur', function() {
                    if (this.value.trim()) {
                        this.classList.remove('is-invalid');
                        this.classList.add('is-valid');
                    } else {
                        this.classList.add('is-invalid');
                        this.classList.remove('is-valid');
                    }
                });
            }
        });
        
        // Check at least one interest is selected
        const interestCheckboxes = document.querySelectorAll('input[name="interest[]"]');
        const interestError = document.createElement('div');
        interestError.className = 'invalid-feedback d-block';
        interestError.textContent = 'Please select at least one area of interest';
        
        interestCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const checkedCount = document.querySelectorAll('input[name="interest[]"]:checked').length;
                if (checkedCount > 0) {
                    // Remove error from all checkboxes
                    interestCheckboxes.forEach(cb => {
                        cb.classList.remove('is-invalid');
                    });
                    if (interestError.parentNode) {
                        interestError.parentNode.removeChild(interestError);
                    }
                }
            });
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>