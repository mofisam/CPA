<?php
require_once 'config/environment.php';
require_once 'includes/core/Database.php';
require_once 'includes/core/Functions.php';

use includes\core\Database;

$db = Database::getInstance();

// Get featured/active trainings
$featured_trainings = $db->fetchAll(
    "SELECT * FROM trainings 
     WHERE status IN ('active', 'upcoming') 
     ORDER BY created_at DESC 
     LIMIT 3"
);

// Get statistics
$stats = [
    'trainings' => $db->fetchOne("SELECT COUNT(*) as count FROM trainings WHERE status IN ('active', 'upcoming')")['count'],
    'students' => $db->fetchOne("SELECT COUNT(*) as count FROM subscribers WHERE is_active = 1")['count'],
    'courses' => $db->fetchOne("SELECT COUNT(DISTINCT course_type) as count FROM trainings")['count'],
    'years' => 2 // Assuming 2 years of operation
];

$pageTitle = "Clinical Physiology Academy - Cutting-edge Clinical Physiology Education";
$pageDescription = "Professional training in echocardiography, ECG interpretation, and cardiac diagnostics. Online and practical courses for healthcare professionals.";
?>
<?php include 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="hero-section py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">Master Clinical Physiology with Expert-Led Training</h1>
                <p class="lead mb-4">
                    Join Nigeria's premier platform for cutting-edge clinical physiology education. 
                    Gain practical skills in cardiac diagnostics through our specialized courses.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="#courses" class="btn btn-primary btn-lg px-4">
                        <i class="fas fa-graduation-cap me-2"></i> View Courses
                    </a>
                    <a href="register-interest.php" class="btn btn-outline-primary btn-lg px-4 text-white">
                        <i class="fas fa-user-plus me-2"></i> Register Interest
                    </a>
                </div>
                <div class="mt-4">
                    <div class="d-flex align-items-center text-white">
                        <i class="fas fa-shield-alt text-primary me-2"></i>
                        <small>Certified training • Expert instructors • Practical focus</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-lg mt-4 mt-lg-0">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <i class="fas fa-heartbeat text-primary display-1"></i>
                            <h3 class="h4 mt-3">Why Choose us?</h3>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-check-circle text-success"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-1">Expert Faculty</h6>
                                        <p class="small text-muted mb-0">UK & Nigeria registered physiologists</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-check-circle text-success"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-1">Practical Training</h6>
                                        <p class="small text-muted mb-0">Hands-on, real-world skills</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-check-circle text-success"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-1">Flexible Learning</h6>
                                        <p class="small text-muted mb-0">Online via Zoom & Telegram</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-check-circle text-success"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-1">Certification</h6>
                                        <p class="small text-muted mb-0">Professional certificates awarded</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-3 col-6 mb-4">
                <div class="display-4 fw-bold text-primary"><?php echo $stats['trainings']; ?>+</div>
                <p class="text-muted">Active Trainings</p>
            </div>
            <div class="col-md-3 col-6 mb-4">
                <div class="display-4 fw-bold text-primary"><?php echo $stats['students']; ?>+</div>
                <p class="text-muted">Healthcare Professionals</p>
            </div>
            <div class="col-md-3 col-6 mb-4">
                <div class="display-4 fw-bold text-primary"><?php echo $stats['courses']; ?></div>
                <p class="text-muted">Specialized Courses</p>
            </div>
            <div class="col-md-3 col-6 mb-4">
                <div class="display-4 fw-bold text-primary"><?php echo $stats['years']; ?>+</div>
                <p class="text-muted">Years Excellence</p>
            </div>
        </div>
    </div>
</section>

<!-- Featured Courses -->
<section id="courses" class="py-5">
    <div class="container">
        <div class="row mb-5">
            <div class="col-lg-8 mx-auto text-center">
                <h2 class="display-5 fw-bold mb-3">Our Featured Courses</h2>
                <p class="lead text-muted">
                    Specialized training programs designed for healthcare professionals seeking to 
                    enhance their skills in cardiac diagnostics.
                </p>
            </div>
        </div>
        
        <div class="row">
            <?php if (empty($featured_trainings)): ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-graduation-cap fa-3x text-muted mb-3"></i>
                    <h4>No courses available at the moment</h4>
                    <p class="text-muted">New courses coming soon. Register your interest to be notified.</p>
                    <a href="register-interest.php" class="btn btn-primary">Register Interest</a>
                </div>
            <?php else: ?>
                <?php foreach ($featured_trainings as $training): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card course-card h-100 border-0 shadow-sm">
                            <?php if ($training['featured_image']): ?>
                                <img src="assets/images/trainings/<?php echo htmlspecialchars($training['featured_image']); ?>" 
                                     class="card-img-top" 
                                     alt="<?php echo htmlspecialchars($training['title']); ?>"
                                     style="height: 200px; object-fit: cover;">
                            <?php else: ?>
                                <div class="card-img-top bg-primary text-white d-flex align-items-center justify-content-center" 
                                     style="height: 200px;">
                                    <i class="fas fa-graduation-cap fa-3x"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge bg-primary">
                                        <?php echo ucfirst($training['course_type']); ?>
                                    </span>
                                    <?php if ($training['price'] && $training['price'] > 0): ?>
                                        <span class="fw-bold text-primary">
                                            ₦<?php echo number_format($training['price'], 2); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Free</span>
                                    <?php endif; ?>
                                </div>
                                
                                <h5 class="card-title"><?php echo htmlspecialchars($training['title']); ?></h5>
                                <p class="card-text text-muted">
                                    <?php echo htmlspecialchars(substr($training['short_description'], 0, 100)); ?>...
                                </p>
                                
                                <div class="row g-2 mb-3">
                                    <?php if ($training['duration']): ?>
                                        <div class="col-6">
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                <?php echo htmlspecialchars($training['duration']); ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($training['format']): ?>
                                        <div class="col-6">
                                            <small class="text-muted">
                                                <i class="fas fa-laptop me-1"></i>
                                                <?php echo htmlspecialchars($training['format']); ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="card-footer bg-transparent border-top-0">
                                <div class="d-grid">
                                    <a href="pages/training-detail.php?slug=<?php echo $training['slug']; ?>" 
                                       class="btn btn-outline-primary">
                                        View Course Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="text-center mt-4">
            <a href="courses.php" class="btn btn-primary">
                <i class="fas fa-list me-2"></i> View All Courses
            </a>
        </div>
    </div>
</section>

<!-- Course Types -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row mb-5">
            <div class="col-lg-8 mx-auto text-center">
                <h2 class="display-5 fw-bold mb-3">Our Specializations</h2>
                <p class="lead text-muted">
                    Focused training in key areas of clinical physiology and cardiac diagnostics.
                </p>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card border-0 shadow-sm h-100 text-center p-4">
                    <div class="card-icon mb-3">
                        <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center" 
                             style="width: 80px; height: 80px;">
                            <i class="fas fa-heartbeat fa-2x text-white"></i>
                        </div>
                    </div>
                    <h4 class="card-title">Echocardiography Training</h4>
                    <p class="card-text text-muted">
                        56-day intensive program covering cardiac ultrasound measurements, 
                        interpretations, and diagnosis of various heart conditions.
                    </p>
                    <div class="mt-3">
                        <span class="badge bg-info">56 Days</span>
                        <span class="badge bg-secondary ms-2">Online</span>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card border-0 shadow-sm h-100 text-center p-4">
                    <div class="card-icon mb-3">
                        <div class="bg-success rounded-circle d-inline-flex align-items-center justify-content-center" 
                             style="width: 80px; height: 80px;">
                            <i class="fas fa-heart fa-2x text-white"></i>
                        </div>
                    </div>
                    <h4 class="card-title">ECG Interpretation Masterclass</h4>
                    <p class="card-text text-muted">
                        4-6 weeks training program focused on building expertise in reading 
                        and understanding electrocardiograms systematically.
                    </p>
                    <div class="mt-3">
                        <span class="badge bg-info">4-6 Weeks</span>
                        <span class="badge bg-secondary ms-2">Online</span>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card border-0 shadow-sm h-100 text-center p-4">
                    <div class="card-icon mb-3">
                        <div class="bg-info rounded-circle d-inline-flex align-items-center justify-content-center" 
                             style="width: 80px; height: 80px;">
                            <i class="fas fa-stethoscope fa-2x text-white"></i>
                        </div>
                    </div>
                    <h4 class="card-title">Specialized Short Courses</h4>
                    <p class="card-text text-muted">
                        Focused masterclasses and short courses in various aspects of 
                        clinical physiology and non-invasive cardiac diagnostics.
                    </p>
                    <div class="mt-3">
                        <span class="badge bg-info">Flexible</span>
                        <span class="badge bg-secondary ms-2">Custom</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials -->
<section class="py-5">
    <div class="container">
        <div class="row mb-5">
            <div class="col-lg-8 mx-auto text-center">
                <h2 class="display-5 fw-bold mb-3">What Our Students Say</h2>
                <p class="lead text-muted">
                    Healthcare professionals who transformed their skills with our training.
                </p>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" 
                                     style="width: 50px; height: 50px;">
                                    <i class="fas fa-user-md text-white"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0">Dr. Adeola Johnson</h6>
                                <small class="text-muted">Cardiac Physiologist, Lagos</small>
                            </div>
                        </div>
                        <p class="card-text">
                            "The echocardiography training was exceptional. The practical focus and 
                            expert guidance from UK-registered physiologists made all the difference 
                            in my clinical practice."
                        </p>
                        <div class="text-warning">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <div class="bg-success rounded-circle d-flex align-items-center justify-content-center" 
                                     style="width: 50px; height: 50px;">
                                    <i class="fas fa-user-nurse text-white"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0">Nurse Funke Adebayo</h6>
                                <small class="text-muted">ICU Nurse, Abuja</small>
                            </div>
                        </div>
                        <p class="card-text">
                            "The ECG Masterclass helped me systematically analyze ECGs with confidence. 
                            The online format was convenient and the support was outstanding."
                        </p>
                        <div class="text-warning">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <div class="bg-info rounded-circle d-flex align-items-center justify-content-center" 
                                     style="width: 50px; height: 50px;">
                                    <i class="fas fa-user-graduate text-white"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0">Chinedu Okoro</h6>
                                <small class="text-muted">Medical Student, Ibadan</small>
                            </div>
                        </div>
                        <p class="card-text">
                            "As a medical student preparing for professional exams, the BSE-focused 
                            training was invaluable. The practical approach gave me real-world skills."
                        </p>
                        <div class="text-warning">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-5 bg-primary text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h2 class="display-6 fw-bold mb-3">Ready to Advance Your Clinical Skills?</h2>
                <p class="lead mb-0">
                    Join healthcare professionals across Nigeria who are enhancing their 
                    diagnostic capabilities with our specialized training programs.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                <a href="register-interest.php" class="btn btn-light btn-lg px-4">
                    <i class="fas fa-user-plus me-2"></i> Register Now
                </a>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>