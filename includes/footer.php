</main>
    
    <!-- Footer -->
    <footer class="bg-dark text-white pt-5 pb-4">
        <div class="container">
            <div class="row">
                <!-- Company Info -->
                <div class="col-lg-4 mb-4">
                    <a class="navbar-brand d-flex align-items-center mb-3" href="/">
                        <div class="logo-wrapper me-2">
                            <img src="assets/images/logo.jpg" alt="Clinical PhysiologyAcademy logo" style="height:40px; width:auto; margin-right:8px; border-radius: 20px">
                        </div>
                        <div>
                            <span class="fw-bold fs-4 text-white">Clinical Physiology</span>
                            <small class="d-block text-light" style="line-height: 1; margin-top: -2px;">Academy</small>
                        </div>
                    </a>
                    <p class="text-light mb-3">
                        Providing cutting-edge clinical physiology education to raise future healthcare professionals.
                    </p>
                    <div class="social-icons">
                        <a href="#" class="text-white me-3" aria-label="Facebook"><i class="fab fa-facebook-f fa-lg"></i></a>
                        <a href="#" class="text-white me-3" aria-label="Twitter"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="#" class="text-white me-3" aria-label="LinkedIn"><i class="fab fa-linkedin-in fa-lg"></i></a>
                        <a href="#" class="text-white" aria-label="Instagram"><i class="fab fa-instagram fa-lg"></i></a>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5 class="text-white mb-3">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="/" class="text-light text-decoration-none">Home</a></li>
                        <li class="mb-2"><a href="about.php" class="text-light text-decoration-none">About Us</a></li>
                        <li class="mb-2"><a href="courses.php" class="text-light text-decoration-none">Courses</a></li>
                        <li class="mb-2"><a href="contact.php" class="text-light text-decoration-none">Contact</a></li>
                        <li class="mb-2"><a href="register-interest.php" class="text-light text-decoration-none">Register Interest</a></li>
                    </ul>
                </div>
                
                <!-- Courses -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5 class="text-white mb-3">Our Courses</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="courses.php?type=echocardiography" class="text-light text-decoration-none">
                                <i class="fas fa-heartbeat text-danger me-2"></i> Echocardiography Training
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="courses.php?type=ecg" class="text-light text-decoration-none">
                                <i class="fas fa-heart text-success me-2"></i> ECG Interpretation Masterclass
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="courses.php?type=other" class="text-light text-decoration-none">
                                <i class="fas fa-stethoscope text-info me-2"></i> Other Specialized Courses
                            </a>
                        </li>
                    </ul>
                </div>
                
                <!-- Contact Info -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5 class="text-white mb-3">Contact Us</h5>
                    <ul class="list-unstyled">
                        <li class="mb-3">
                            <div class="d-flex">
                                <i class="fas fa-map-marker-alt text-primary me-3 mt-1"></i>
                                <span class="text-light">Based in Nigeria<br>Online training worldwide</span>
                            </div>
                        </li>
                        <li class="mb-3">
                            <div class="d-flex">
                                <i class="fas fa-envelope text-primary me-3 mt-1"></i>
                                <a href="mailto:<?php echo defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'info@clinicalphysiologyacademy.com'; ?>" class="text-light text-decoration-none">
                                    <?php echo defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'info@clinicalphysiologyacademy.com'; ?>
                                </a>
                            </div>
                        </li>
                        <li class="mb-3">
                            <div class="d-flex">
                                <i class="fas fa-clock text-primary me-3 mt-1"></i>
                                <span class="text-light">Mon - Fri: 9:00 AM - 5:00 PM</span>
                            </div>
                        </li>
                    </ul>
                    
                    <!-- Newsletter Signup -->
                    <div class="mt-4">
                        <h6 class="text-white mb-2">Subscribe to Newsletter</h6>
                        <form action="subscribe.php" method="POST" class="d-flex">
                            <input type="email" name="email" class="form-control form-control-sm me-2" placeholder="Your email" required>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <hr class="bg-light my-4">
            
            <!-- Copyright -->
            <div class="row">
                <div class="col-md-6">
                    <p class="text-light mb-0">
                        &copy; <?php echo date('Y'); ?> Clinical Physiology Academy. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-light mb-0">
                        <a href="privacy.php" class="text-light text-decoration-none me-3">Privacy Policy</a>
                        <a href="terms.php" class="text-light text-decoration-none">Terms of Service</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Back to Top Button -->
    <button type="button" class="btn btn-primary btn-floating btn-lg" id="back-to-top">
        <i class="fas fa-arrow-up"></i>
    </button>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="<?php echo isset($base_url) ? $base_url : ''; ?>assets/js/main.js"></script>
    
    <script>
        // Back to top button
        document.addEventListener('DOMContentLoaded', function() {
            const backToTopButton = document.getElementById('back-to-top');
            
            // Show/hide button on scroll
            window.addEventListener('scroll', function() {
                if (window.pageYOffset > 300) {
                    backToTopButton.style.display = 'block';
                } else {
                    backToTopButton.style.display = 'none';
                }
            });
            
            // Smooth scroll to top
            backToTopButton.addEventListener('click', function(e) {
                e.preventDefault();
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
            
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Auto-hide flash messages after 5 seconds
            const flashAlert = document.querySelector('.alert.alert-dismissible');
            if (flashAlert) {
                setTimeout(() => {
                    const closeBtn = flashAlert.querySelector('.btn-close');
                    if (closeBtn) closeBtn.click();
                }, 5000);
            }
        });
    </script>
</body>
</html>