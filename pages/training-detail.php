<?php
require_once '../config/environment.php';
require_once '../includes/core/Database.php';
require_once '../includes/core/Functions.php';

use includes\core\Database;

$db = Database::getInstance();

// Check if slug is provided
if (!isset($_GET['slug']) || empty($_GET['slug'])) {
    header('Location: ../courses.php');
    exit();
}

$slug = $_GET['slug'];

// Get training details
$training = $db->fetchOne(
    "SELECT t.*, a.full_name as instructor_name 
     FROM trainings t 
     LEFT JOIN admins a ON t.created_by = a.id 
     WHERE t.slug = ? AND t.status IN ('active', 'upcoming')",
    [$slug]
);

if (!$training) {
    header('Location: ../courses.php');
    exit();
}

// Get related modules
$modules = $db->fetchAll(
    "SELECT * FROM course_modules 
     WHERE training_id = ? 
     ORDER BY module_order ASC",
    [$training['id']]
);

// Get target audience
$audience = $db->fetchAll(
    "SELECT * FROM target_audience 
     WHERE training_id = ?",
    [$training['id']]
);

// Get training features
$features = $db->fetchAll(
    "SELECT * FROM training_features 
     WHERE training_id = ?",
    [$training['id']]
);

// Get related trainings (same type)
$related_trainings = $db->fetchAll(
    "SELECT id, title, slug, short_description, featured_image, price, duration, status 
     FROM trainings 
     WHERE course_type = ? 
       AND id != ? 
       AND status IN ('active', 'upcoming') 
     ORDER BY created_at DESC 
     LIMIT 3",
    [$training['course_type'], $training['id']]
);

// Handle interest registration
$registration_success = false;
$registration_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_interest'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $profession = trim($_POST['profession']);
    $message = trim($_POST['message']);
    
    // Validation
    if (empty($full_name) || empty($email) || empty($profession)) {
        $registration_error = 'Please fill in all required fields.';
    } elseif (!CPAFunctions::isValidEmail($email)) {
        $registration_error = 'Please enter a valid email address.';
    } else {
        try {
            // Check for duplicate recent registration
            $existing = $db->fetchOne(
                "SELECT id FROM course_interests 
                 WHERE email = ? AND training_id = ? 
                 AND submitted_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
                [$email, $training['id']]
            );
            
            if ($existing) {
                $registration_error = 'You have already registered interest for this training recently.';
            } else {
                // Register interest
                $interestData = [
                    'full_name' => $full_name,
                    'email' => $email,
                    'phone' => $phone,
                    'profession' => $profession,
                    'interests' => json_encode(['course_' . $training['id']]),
                    'additional_message' => $message . "\n\nTraining: " . $training['title'],
                    'training_id' => $training['id'],
                    'status' => 'pending',
                    'submitted_at' => date('Y-m-d H:i:s')
                ];
                
                $db->insert('course_interests', $interestData);
                
                // Also add to subscribers if not already
                $existingSubscriber = $db->fetchOne(
                    "SELECT id FROM subscribers WHERE email = ?",
                    [$email]
                );
                
                if (!$existingSubscriber) {
                    $subscriberData = [
                        'email' => $email,
                        'full_name' => $full_name,
                        'subscribed_at' => date('Y-m-d H:i:s'),
                        'is_active' => 1,
                        'source' => 'training_detail'
                    ];
                    $db->insert('subscribers', $subscriberData);
                }
                
                $registration_success = true;
            }
        } catch (Exception $e) {
            $registration_error = 'Error registering interest. Please try again.';
        }
    }
}

$pageTitle = $training['title'] . " - Clinical Physiology Academy";
$pageDescription = $training['short_description'];
?>
<?php include '../includes/header.php'; ?>

<!-- Hero Section -->
<section class="py-4 bg-primary text-white">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="../index.php" class="text-white">Home</a></li>
                        <li class="breadcrumb-item"><a href="../courses.php" class="text-white">Courses</a></li>
                        <li class="breadcrumb-item active text-white" aria-current="page"><?php echo htmlspecialchars($training['title']); ?></li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</section>

<!-- Training Detail -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Training Header -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <?php 
                                $typeNames = [
                                    'echocardiography' => 'Echocardiography Training',
                                    'ecg_masterclass' => 'ECG Interpretation Masterclass',
                                    'other' => 'Specialized Course'
                                ];
                                $typeName = $typeNames[$training['course_type']] ?? ucfirst($training['course_type']);
                                ?>
                                <span class="badge bg-primary fs-6 mb-2"><?php echo $typeName; ?></span>
                                
                                <?php if ($training['status'] == 'upcoming'): ?>
                                    <span class="badge bg-info fs-6 mb-2">Upcoming</span>
                                <?php elseif ($training['status'] == 'active'): ?>
                                    <span class="badge bg-success fs-6 mb-2">Active</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($training['price'] && $training['price'] > 0): ?>
                                <div class="text-end">
                                    <div class="h2 text-primary mb-0">₦<?php echo number_format($training['price'], 2); ?></div>
                                    <small class="text-muted">Full Course Fee</small>
                                </div>
                            <?php else: ?>
                                <div class="text-end">
                                    <span class="badge bg-success fs-6">Free</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <h1 class="display-5 fw-bold mb-3"><?php echo htmlspecialchars($training['title']); ?></h1>
                        
                        <div class="row mb-4">
                            <?php if ($training['duration']): ?>
                            <div class="col-md-3 col-6 mb-2">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-clock text-primary me-2"></i>
                                    <div>
                                        <div class="small text-muted">Duration</div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($training['duration']); ?></div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($training['format']): ?>
                            <div class="col-md-3 col-6 mb-2">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-laptop text-primary me-2"></i>
                                    <div>
                                        <div class="small text-muted">Format</div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($training['format']); ?></div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($training['start_date']): ?>
                            <div class="col-md-3 col-6 mb-2">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-calendar-alt text-primary me-2"></i>
                                    <div>
                                        <div class="small text-muted">Start Date</div>
                                        <div class="fw-bold"><?php echo date('F j, Y', strtotime($training['start_date'])); ?></div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($training['max_participants']): ?>
                            <div class="col-md-3 col-6 mb-2">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-users text-primary me-2"></i>
                                    <div>
                                        <div class="small text-muted">Seats Available</div>
                                        <div class="fw-bold"><?php echo $training['max_participants']; ?> seats</div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($training['registration_deadline']): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Registration Deadline:</strong> 
                            <?php echo date('F j, Y', strtotime($training['registration_deadline'])); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Course Image -->
                <?php if ($training['featured_image']): ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-0">
                        <img src="../<?php echo htmlspecialchars($training['featured_image']); ?>" 
                             class="img-fluid rounded" 
                             alt="<?php echo htmlspecialchars($training['title']); ?>"
                             style="max-height: 400px; width: 100%; object-fit: cover;">
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Course Description -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h4 class="mb-0"><i class="fas fa-info-circle me-2"></i> Course Description</h4>
                    </div>
                    <div class="card-body p-4">
                        <p class="lead mb-4"><?php echo htmlspecialchars($training['short_description']); ?></p>
                        
                        <?php if ($training['full_description']): ?>
                            <div class="course-content">
                                <?php echo nl2br(htmlspecialchars($training['full_description'])); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Course Modules -->
                <?php if (!empty($modules)): ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h4 class="mb-0"><i class="fas fa-list-ol me-2"></i> Course Curriculum</h4>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php foreach ($modules as $index => $module): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Module <?php echo $index + 1; ?>: <?php echo htmlspecialchars($module['module_title']); ?></h6>
                                        <?php if ($module['module_description']): ?>
                                            <p class="text-muted mb-0 small"><?php echo htmlspecialchars($module['module_description']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <span class="badge bg-secondary"><?php echo $index + 1; ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Target Audience -->
                <?php if (!empty($audience)): ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h4 class="mb-0"><i class="fas fa-users me-2"></i> Who Should Attend</h4>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($audience as $item): ?>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                <?php echo htmlspecialchars($item['audience_text']); ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Course Features -->
                <?php if (!empty($features)): ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h4 class="mb-0"><i class="fas fa-star me-2"></i> What You'll Learn</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($features as $feature): ?>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-check-circle text-success mt-1"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <?php echo htmlspecialchars($feature['feature_text']); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Related Courses -->
                <?php if (!empty($related_trainings)): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h4 class="mb-0"><i class="fas fa-graduation-cap me-2"></i> Related Courses</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($related_trainings as $related): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card h-100 border">
                                    <?php if ($related['featured_image']): ?>
                                        <img src="../<?php echo htmlspecialchars($related['featured_image']); ?>" 
                                             class="card-img-top" 
                                             alt="<?php echo htmlspecialchars($related['title']); ?>"
                                             style="height: 120px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="card-img-top bg-primary text-white d-flex align-items-center justify-content-center" 
                                             style="height: 120px;">
                                            <i class="fas fa-graduation-cap"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <a href="training-detail.php?slug=<?php echo $related['slug']; ?>" 
                                               class="text-decoration-none">
                                                <?php echo htmlspecialchars($related['title']); ?>
                                            </a>
                                        </h6>
                                        <p class="card-text small text-muted">
                                            <?php echo htmlspecialchars(substr($related['short_description'], 0, 60)); ?>...
                                        </p>
                                    </div>
                                    <div class="card-footer bg-transparent">
                                        <div class="d-flex justify-content-between">
                                            <?php if ($related['price'] && $related['price'] > 0): ?>
                                                <span class="text-primary fw-bold">₦<?php echo number_format($related['price'], 2); ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Free</span>
                                            <?php endif; ?>
                                            <a href="training-detail.php?slug=<?php echo $related['slug']; ?>" class="btn btn-sm btn-outline-primary">
                                                View
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Register Interest Card -->
                <div class="card border-0 shadow-sm sticky-top" style="top: 100px;">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-user-plus me-2"></i> Register Interest</h4>
                    </div>
                    
                    <div class="card-body">
                        <?php if ($registration_success): ?>
                            <div class="alert alert-success">
                                <h5 class="alert-heading"><i class="fas fa-check-circle me-2"></i> Thank You!</h5>
                                <p class="mb-0">Your interest has been registered successfully. We'll contact you soon with more details.</p>
                            </div>
                            <div class="text-center">
                                <a href="../courses.php" class="btn btn-outline-primary">Browse Other Courses</a>
                            </div>
                        <?php else: ?>
                            <?php if ($registration_error): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <?php echo htmlspecialchars($registration_error); ?>
                                </div>
                            <?php endif; ?>
                            
                            <p class="text-muted mb-4">
                                Register your interest in this course to receive detailed information, 
                                schedule, and special offers.
                            </p>
                            
                            <form method="POST" action="" id="interestForm">
                                <div class="mb-3">
                                    <label for="full_name" class="form-label">Full Name *</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="full_name" 
                                           name="full_name" 
                                           required
                                           value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" 
                                           class="form-control" 
                                           id="email" 
                                           name="email" 
                                           required
                                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" 
                                           class="form-control" 
                                           id="phone" 
                                           name="phone" 
                                           placeholder="+234 800 000 0000"
                                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="profession" class="form-label">Profession/Role *</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="profession" 
                                           name="profession" 
                                           placeholder="e.g., Cardiac Physiologist, Nurse"
                                           required
                                           value="<?php echo isset($_POST['profession']) ? htmlspecialchars($_POST['profession']) : ''; ?>">
                                </div>
                                
                                <div class="mb-4">
                                    <label for="message" class="form-label">Additional Questions</label>
                                    <textarea class="form-control" 
                                              id="message" 
                                              name="message" 
                                              rows="3"
                                              placeholder="Any specific questions about this course?"><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                                </div>
                                
                                <div class="mb-4">
                                    <div class="form-check">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="terms" 
                                               name="terms" 
                                               required>
                                        <label class="form-check-label" for="terms">
                                            I agree to receive information about this course and other updates
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" name="register_interest" class="btn btn-primary btn-lg">
                                        <i class="fas fa-paper-plane me-2"></i> Register Interest
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quick Info -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-question-circle me-2"></i> Have Questions?</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">
                            Need more information about this course? Contact our support team.
                        </p>
                        <div class="d-grid">
                            <a href="../contact.php" class="btn btn-outline-primary">
                                <i class="fas fa-envelope me-2"></i> Contact Us
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Course Stats -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-body">
                        <h6 class="mb-3"><i class="fas fa-chart-bar me-2"></i> Course Details</h6>
                        <ul class="list-unstyled mb-0">
                            <?php if ($training['instructor_name']): ?>
                            <li class="mb-2">
                                <small class="text-muted">Instructor:</small>
                                <div class="fw-bold"><?php echo htmlspecialchars($training['instructor_name']); ?></div>
                            </li>
                            <?php endif; ?>
                            
                            <li class="mb-2">
                                <small class="text-muted">Course Type:</small>
                                <div class="fw-bold"><?php echo $typeName; ?></div>
                            </li>
                            
                            <?php if ($training['duration']): ?>
                            <li class="mb-2">
                                <small class="text-muted">Duration:</small>
                                <div class="fw-bold"><?php echo htmlspecialchars($training['duration']); ?></div>
                            </li>
                            <?php endif; ?>
                            
                            <?php if ($training['format']): ?>
                            <li class="mb-2">
                                <small class="text-muted">Format:</small>
                                <div class="fw-bold"><?php echo htmlspecialchars($training['format']); ?></div>
                            </li>
                            <?php endif; ?>
                            
                            <li>
                                <small class="text-muted">Status:</small>
                                <div class="fw-bold">
                                    <span class="badge bg-<?php echo $training['status'] == 'active' ? 'success' : 'info'; ?>">
                                        <?php echo ucfirst($training['status']); ?>
                                    </span>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('interestForm');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            const fullName = document.getElementById('full_name').value.trim();
            const email = document.getElementById('email').value.trim();
            const profession = document.getElementById('profession').value.trim();
            const terms = document.getElementById('terms');
            
            if (!fullName || !email || !profession) {
                e.preventDefault();
                alert('Please fill in all required fields.');
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
                alert('Please agree to receive information about the course.');
                return false;
            }
            
            if (!confirm('Are you sure you want to register interest in this course?')) {
                e.preventDefault();
                return false;
            }
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>