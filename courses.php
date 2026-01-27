<?php
require_once 'config/environment.php';
require_once 'includes/core/Database.php';
require_once 'includes/core/Functions.php';

use includes\core\Database;

$db = Database::getInstance();

// Get filter parameters
$filterType = $_GET['type'] ?? 'all';
$filterStatus = $_GET['status'] ?? 'active';
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = 9;
$offset = ($page - 1) * $perPage;

// Build query for trainings
$query = "SELECT * FROM trainings WHERE status IN ('active', 'upcoming')";
$countQuery = "SELECT COUNT(*) as count FROM trainings WHERE status IN ('active', 'upcoming')";
$where = [];
$params = [];

if ($filterType !== 'all') {
    $where[] = "course_type = ?";
    $params[] = $filterType;
}

if ($filterStatus !== 'all') {
    $where[] = "status = ?";
    $params[] = $filterStatus;
}

if (!empty($search)) {
    $where[] = "(title LIKE ? OR short_description LIKE ? OR full_description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($where)) {
    $query .= " AND " . implode(" AND ", $where);
    $countQuery .= " AND " . implode(" AND ", $where);
}

$query .= " ORDER BY 
    CASE status 
        WHEN 'upcoming' THEN 1 
        WHEN 'active' THEN 2 
        ELSE 3 
    END,
    created_at DESC 
    LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;

// Execute queries
$courses = $db->fetchAll($query, $params);
$totalCount = $db->fetchOne($countQuery, array_slice($params, 0, -2))['count'];
$totalPages = ceil($totalCount / $perPage);

// Get course type counts
$typeCounts = $db->fetchAll(
    "SELECT course_type, COUNT(*) as count 
     FROM trainings 
     WHERE status IN ('active', 'upcoming') 
     GROUP BY course_type"
);

$pageTitle = "Our Courses - Clinical Physiology Academy";
$pageDescription = "Browse our specialized courses in echocardiography, ECG interpretation, and clinical physiology training for healthcare professionals.";
?>
<?php include 'includes/header.php'; ?>

<!-- Hero -->
<section class="py-5 bg-primary text-white">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="display-4 fw-bold mb-3">Our Specialized Courses</h1>
                <p class="lead mb-0">
                    Professional training programs designed to enhance your clinical skills in cardiac diagnostics
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Courses Section -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <!-- Sidebar Filters -->
            <div class="col-lg-3 mb-4">
                <div class="card border-0 shadow-sm sticky-top" style="top: 100px;">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-filter me-2"></i> Filters</h5>
                    </div>
                    <div class="card-body">
                        <!-- Search -->
                        <div class="mb-4">
                            <label for="search" class="form-label">Search Courses</label>
                            <div class="input-group">
                                <input type="text" 
                                       class="form-control" 
                                       id="searchInput" 
                                       placeholder="Search courses..."
                                       value="<?php echo htmlspecialchars($search); ?>">
                                <button class="btn btn-outline-primary" type="button" id="searchButton">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Course Type -->
                        <div class="mb-4">
                            <label class="form-label">Course Type</label>
                            <div class="list-group list-group-flush">
                                <a href="?type=all&status=<?php echo $filterStatus; ?>&search=<?php echo urlencode($search); ?>" 
                                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo $filterType == 'all' ? 'active' : ''; ?>">
                                    All Courses
                                    <span class="badge bg-primary rounded-pill">
                                        <?php echo array_sum(array_column($typeCounts, 'count')); ?>
                                    </span>
                                </a>
                                <?php foreach ($typeCounts as $type): 
                                    $typeNames = [
                                        'echocardiography' => 'Echocardiography',
                                        'ecg_masterclass' => 'ECG Masterclass',
                                        'other' => 'Other Courses'
                                    ];
                                ?>
                                <a href="?type=<?php echo $type['course_type']; ?>&status=<?php echo $filterStatus; ?>&search=<?php echo urlencode($search); ?>" 
                                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo $filterType == $type['course_type'] ? 'active' : ''; ?>">
                                    <?php echo $typeNames[$type['course_type']] ?? ucfirst($type['course_type']); ?>
                                    <span class="badge bg-primary rounded-pill"><?php echo $type['count']; ?></span>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Status -->
                        <div class="mb-4">
                            <label class="form-label">Status</label>
                            <div class="list-group list-group-flush">
                                <a href="?type=<?php echo $filterType; ?>&status=all&search=<?php echo urlencode($search); ?>" 
                                   class="list-group-item list-group-item-action <?php echo $filterStatus == 'all' ? 'active' : ''; ?>">
                                    All Status
                                </a>
                                <a href="?type=<?php echo $filterType; ?>&status=active&search=<?php echo urlencode($search); ?>" 
                                   class="list-group-item list-group-item-action <?php echo $filterStatus == 'active' ? 'active' : ''; ?>">
                                    <i class="fas fa-play-circle text-success me-2"></i>Active
                                </a>
                                <a href="?type=<?php echo $filterType; ?>&status=upcoming&search=<?php echo urlencode($search); ?>" 
                                   class="list-group-item list-group-item-action <?php echo $filterStatus == 'upcoming' ? 'active' : ''; ?>">
                                    <i class="fas fa-calendar-alt text-info me-2"></i>Upcoming
                                </a>
                            </div>
                        </div>
                        
                        <!-- Quick Links -->
                        <div class="mt-4">
                            <h6 class="text-muted mb-3">Quick Links</h6>
                            <div class="d-grid gap-2">
                                <a href="register-interest.php" class="btn btn-outline-primary">
                                    <i class="fas fa-user-plus me-2"></i> Register Interest
                                </a>
                                <a href="contact.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-question-circle me-2"></i> Contact for Info
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Courses Grid -->
            <div class="col-lg-9">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="h3 mb-1">Available Courses</h2>
                        <p class="text-muted mb-0">
                            Showing <?php echo count($courses); ?> of <?php echo $totalCount; ?> courses
                        </p>
                    </div>
                    
                </div>
                
                <!-- No Courses Message -->
                <?php if (empty($courses)): ?>
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-graduation-cap fa-3x text-muted mb-3"></i>
                            <h4>No courses found</h4>
                            <p class="text-muted mb-4">
                                <?php if ($filterType !== 'all' || $filterStatus !== 'all' || !empty($search)): ?>
                                    Try changing your filters or search terms.
                                <?php else: ?>
                                    No courses are available at the moment. Check back soon or register your interest to be notified.
                                <?php endif; ?>
                            </p>
                            <div class="d-flex justify-content-center gap-3">
                                <a href="courses.php" class="btn btn-outline-primary">Clear Filters</a>
                                <a href="register-interest.php" class="btn btn-primary">Register Interest</a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Courses Grid -->
                    <div class="row">
                        <?php foreach ($courses as $course): 
                            $statusColors = [
                                'active' => 'success',
                                'upcoming' => 'info',
                                'draft' => 'secondary',
                                'completed' => 'dark'
                            ];
                            $statusColor = $statusColors[$course['status']] ?? 'secondary';
                        ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card course-card h-100 border-0 shadow-sm">
                                <!-- Course Image -->
                                <?php if ($course['featured_image']): ?>
                                    <img src="assets/images/trainings/<?php echo htmlspecialchars($course['featured_image']); ?>" 
                                         class="card-img-top" 
                                         alt="<?php echo htmlspecialchars($course['title']); ?>"
                                         style="height: 180px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="card-img-top bg-primary text-white d-flex align-items-center justify-content-center" 
                                         style="height: 180px;">
                                        <i class="fas fa-graduation-cap fa-3x"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Status Badge -->
                                <div class="position-absolute top-0 end-0 m-3">
                                    <span class="badge bg-<?php echo $statusColor; ?>">
                                        <?php echo ucfirst($course['status']); ?>
                                    </span>
                                </div>
                                
                                <div class="card-body">
                                    <!-- Course Type -->
                                    <div class="mb-2">
                                        <span class="badge bg-secondary">
                                            <?php 
                                                $typeNames = [
                                                    'echocardiography' => 'Echocardiography',
                                                    'ecg_masterclass' => 'ECG Masterclass',
                                                    'other' => 'Other Course'
                                                ];
                                                echo $typeNames[$course['course_type']] ?? ucfirst($course['course_type']);
                                            ?>
                                        </span>
                                    </div>
                                    
                                    <!-- Title -->
                                    <h5 class="card-title">
                                        <a href="pages/training-detail.php?slug=<?php echo $course['slug']; ?>" 
                                           class="text-decoration-none text-dark">
                                            <?php echo htmlspecialchars($course['title']); ?>
                                        </a>
                                    </h5>
                                    
                                    <!-- Description -->
                                    <p class="card-text text-muted small">
                                        <?php echo htmlspecialchars(substr($course['short_description'], 0, 100)); ?>...
                                    </p>
                                    
                                    <!-- Course Details -->
                                    <div class="row g-2 mb-3">
                                        <?php if ($course['duration']): ?>
                                        <div class="col-12">
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                <?php echo htmlspecialchars($course['duration']); ?>
                                            </small>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($course['format']): ?>
                                        <div class="col-12">
                                            <small class="text-muted">
                                                <i class="fas fa-laptop me-1"></i>
                                                <?php echo htmlspecialchars($course['format']); ?>
                                            </small>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($course['start_date']): ?>
                                        <div class="col-12">
                                            <small class="text-muted">
                                                <i class="fas fa-calendar me-1"></i>
                                                Starts: <?php echo date('M j, Y', strtotime($course['start_date'])); ?>
                                            </small>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Price -->
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <?php if ($course['price'] && $course['price'] > 0): ?>
                                            <span class="h5 mb-0 text-primary">
                                                ₦<?php echo number_format($course['price'], 2); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Free</span>
                                        <?php endif; ?>
                                        
                                        <span class="text-muted small">
                                            <i class="fas fa-users me-1"></i>
                                            <?php echo $course['max_participants'] ? $course['max_participants'] . ' seats' : 'Unlimited'; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="card-footer bg-transparent border-top-0 pt-0">
                                    <div class="d-grid">
                                        <a href="pages/training-detail.php?slug=<?php echo $course['slug']; ?>" 
                                           class="btn btn-outline-primary">
                                            <i class="fas fa-info-circle me-2"></i> View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" 
                                   href="?page=<?php echo $page - 1; ?>&type=<?php echo $filterType; ?>&status=<?php echo $filterStatus; ?>&search=<?php echo urlencode($search); ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" 
                                           href="?page=<?php echo $i; ?>&type=<?php echo $filterType; ?>&status=<?php echo $filterStatus; ?>&search=<?php echo urlencode($search); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" 
                                   href="?page=<?php echo $page + 1; ?>&type=<?php echo $filterType; ?>&status=<?php echo $filterStatus; ?>&search=<?php echo urlencode($search); ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                <?php endif; ?>
                
                <!-- CTA -->
                <div class="card border-primary mt-5">
                    <div class="card-body text-center p-4">
                        <h3 class="h4 mb-3">Can't find what you're looking for?</h3>
                        <p class="text-muted mb-4">
                            Register your interest and we'll notify you when new courses become available 
                            or create a custom training program for your needs.
                        </p>
                        <a href="register-interest.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-user-plus me-2"></i> Register Your Interest
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const searchButton = document.getElementById('searchButton');
    
    function performSearch() {
        const searchTerm = searchInput.value.trim();
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('search', searchTerm);
        urlParams.set('page', 1); // Reset to first page
        window.location.href = 'courses.php?' + urlParams.toString();
    }
    
    searchButton.addEventListener('click', performSearch);
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            performSearch();
        }
    });
    
    // Clear search
    const clearSearch = document.getElementById('clearSearch');
    if (clearSearch) {
        clearSearch.addEventListener('click', function() {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.delete('search');
            window.location.href = 'courses.php?' + urlParams.toString();
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>