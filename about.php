<?php
require_once 'config/environment.php';
require_once 'includes/core/Database.php';
require_once 'includes/core/Functions.php';

use includes\core\Database;

$db = Database::getInstance();

// Get stats for about page
$stats = [
    'trainings_completed' => $db->fetchOne("SELECT COUNT(*) as count FROM trainings WHERE status = 'completed'")['count'],
    'active_students' => $db->fetchOne("SELECT COUNT(*) as count FROM subscribers WHERE is_active = 1")['count'],
    'instructors' => 12, // Assuming 12 instructors
    'success_rate' => 98 // 98% success rate
];

$pageTitle = "About Us - Clinical Physiology Academy";
$pageDescription = "Learn about our mission, team, and commitment to providing cutting-edge clinical physiology education in Nigeria and beyond.";
?>
<?php include 'includes/header.php'; ?>

<!-- Hero -->
<section class="py-5 bg-primary text-white">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="display-4 fw-bold mb-3">About Clinical Physiology Academy</h1>
                <p class="lead mb-0">
                    Leading the way in clinical physiology education and professional training in Nigeria
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
                <li class="breadcrumb-item active">About Us</li>
            </ol>
        </nav>
    </div>
</section>

<!-- Mission & Vision -->
<section class="py-5">
    <div class="container">
        <div class="row align-items-center mb-5">
            <div class="col-lg-6">
                <h2 class="display-5 fw-bold mb-4">Our Mission</h2>
                <p class="lead mb-4">
                    To provide <strong>cutting-edge clinical physiology education</strong> that equips 
                    healthcare professionals with practical skills and knowledge to excel in cardiac 
                    diagnostics and patient care.
                </p>
                <p class="text-muted">
                    We believe in transforming healthcare through quality education, bridging the gap 
                    between theoretical knowledge and practical application in clinical settings.
                </p>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-lg">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-bullseye text-primary display-1"></i>
                        </div>
                        <h3 class="h4 text-center mb-3">Our Vision</h3>
                        <p class="text-center text-muted mb-0">
                            To become Nigeria's leading platform for clinical physiology education, 
                            recognized for excellence in training healthcare professionals and 
                            contributing to improved cardiac care nationwide.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Stats -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="row text-center">
                            <div class="col-md-3 col-6 mb-3">
                                <div class="display-3 fw-bold text-primary"><?php echo $stats['trainings_completed']; ?>+</div>
                                <p class="text-muted mb-0">Trainings Completed</p>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <div class="display-3 fw-bold text-primary"><?php echo $stats['active_students']; ?>+</div>
                                <p class="text-muted mb-0">Healthcare Professionals Trained</p>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <div class="display-3 fw-bold text-primary"><?php echo $stats['instructors']; ?>+</div>
                                <p class="text-muted mb-0">Expert Instructors</p>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <div class="display-3 fw-bold text-primary"><?php echo $stats['success_rate']; ?>%</div>
                                <p class="text-muted mb-0">Success Rate</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Our Story -->
        <div class="row mb-5">
            <div class="col-lg-8 mx-auto">
                <h2 class="display-5 fw-bold mb-4 text-center">Our Story</h2>
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-5">
                        <p class="lead mb-4">
                            Clinical Physiology Academy was founded by a team of registered clinical 
                            physiologists practicing in both the UK and Nigeria, who identified a 
                            critical need for practical, hands-on training in cardiac diagnostics.
                        </p>
                        <p class="mb-4">
                            Recognizing the gap between academic knowledge and practical application 
                            in clinical settings, we established the academy to provide healthcare 
                            professionals with the skills needed to excel in their practice.
                        </p>
                        <p class="mb-4">
                            Starting with our flagship Echocardiography Training program, we have 
                            expanded to offer comprehensive courses in ECG interpretation and other 
                            specialized areas of clinical physiology.
                        </p>
                        <p class="mb-0">
                            Today, we serve healthcare professionals across Nigeria and beyond, 
                            delivering high-quality, practical education through innovative online 
                            platforms while maintaining our commitment to excellence.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Core Values -->
        <div class="row mb-5">
            <div class="col-12">
                <h2 class="display-5 fw-bold mb-4 text-center">Our Core Values</h2>
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-4 text-center">
                                <div class="mb-3">
                                    <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center" 
                                         style="width: 70px; height: 70px;">
                                        <i class="fas fa-graduation-cap fa-2x text-white"></i>
                                    </div>
                                </div>
                                <h4 class="h5 mb-3">Excellence in Education</h4>
                                <p class="text-muted mb-0">
                                    We maintain the highest standards in curriculum development, 
                                    instruction, and student support.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-4 text-center">
                                <div class="mb-3">
                                    <div class="bg-success rounded-circle d-inline-flex align-items-center justify-content-center" 
                                         style="width: 70px; height: 70px;">
                                        <i class="fas fa-hands-helping fa-2x text-white"></i>
                                    </div>
                                </div>
                                <h4 class="h5 mb-3">Practical Application</h4>
                                <p class="text-muted mb-0">
                                    Our training emphasizes hands-on skills that can be immediately 
                                    applied in clinical practice.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-4 text-center">
                                <div class="mb-3">
                                    <div class="bg-info rounded-circle d-inline-flex align-items-center justify-content-center" 
                                         style="width: 70px; height: 70px;">
                                        <i class="fas fa-users fa-2x text-white"></i>
                                    </div>
                                </div>
                                <h4 class="h5 mb-3">Student-Centered</h4>
                                <p class="text-muted mb-0">
                                    We prioritize our students' success through personalized support 
                                    and responsive teaching methods.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Teaching Methodology -->
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <div class="card border-0 shadow-lg">
                    <div class="card-header bg-primary text-white py-3">
                        <h3 class="h4 mb-0"><i class="fas fa-chalkboard-teacher me-2"></i> Our Teaching Methodology</h3>
                    </div>
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-video text-primary fa-2x"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="mb-2">Live Interactive Sessions</h5>
                                        <p class="text-muted mb-0">
                                            Real-time training via Zoom with Q&A sessions, 
                                            allowing direct interaction with instructors.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-file-video text-primary fa-2x"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="mb-2">Recorded Lectures</h5>
                                        <p class="text-muted mb-0">
                                            Access recorded sessions for review and flexible learning 
                                            at your own pace.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-tasks text-primary fa-2x"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="mb-2">Practical Assignments</h5>
                                        <p class="text-muted mb-0">
                                            Hands-on exercises and case studies to apply theoretical 
                                            knowledge to real-world scenarios.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-comments text-primary fa-2x"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="mb-2">Continuous Support</h5>
                                        <p class="text-muted mb-0">
                                            24/7 access to instructors via Telegram for questions and 
                                            clarifications throughout the program.
                                        </p>
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

<!-- CTA -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <h2 class="display-5 fw-bold mb-3">Join Our Growing Community</h2>
                <p class="lead text-muted mb-4">
                    Be part of Nigeria's leading clinical physiology education platform. 
                    Enhance your skills and advance your career with our expert-led training.
                </p>
                <div class="d-flex flex-wrap justify-content-center gap-3">
                    <a href="courses.php" class="btn btn-primary btn-lg px-4">
                        <i class="fas fa-graduation-cap me-2"></i> View Our Courses
                    </a>
                    <a href="register-interest.php" class="btn btn-outline-primary btn-lg px-4">
                        <i class="fas fa-user-plus me-2"></i> Register Interest
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>