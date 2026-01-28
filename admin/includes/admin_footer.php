</div>
            </div>
        </main>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery (for DataTables if used) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    
    <!-- Custom Admin JS -->
    <script src="<?php echo SITE_URL; ?>/assets/js/admin.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const closeSidebar = document.getElementById('closeSidebar');
            const mainContent = document.querySelector('main');
            
            // Check if mobile
            function isMobile() {
                return window.innerWidth < 992;
            }
            
            // Initialize sidebar state
            function initSidebar() {
                if (isMobile()) {
                    sidebar.style.transform = 'translateX(-100%)';
                    mainContent.style.marginLeft = '0';
                    sidebarOverlay.classList.remove('show');
                } else {
                    sidebar.style.transform = 'translateX(0)';
                    mainContent.style.marginLeft = 'var(--sidebar-width)';
                }
            }
            
            // Toggle sidebar
            function toggleSidebar() {
                if (isMobile()) {
                    if (sidebar.style.transform === 'translateX(0px)') {
                        sidebar.style.transform = 'translateX(-100%)';
                        sidebarOverlay.classList.remove('show');
                    } else {
                        sidebar.style.transform = 'translateX(0)';
                        sidebarOverlay.classList.add('show');
                    }
                } else {
                    if (sidebar.style.transform === 'translateX(0px)') {
                        sidebar.style.transform = 'translateX(-100%)';
                        mainContent.style.marginLeft = '0';
                    } else {
                        sidebar.style.transform = 'translateX(0)';
                        mainContent.style.marginLeft = 'var(--sidebar-width)';
                    }
                }
            }
            
            // Close sidebar on mobile
            function closeSidebarMobile() {
                if (isMobile()) {
                    sidebar.style.transform = 'translateX(-100%)';
                    sidebarOverlay.classList.remove('show');
                }
            }
            
            // Initialize
            initSidebar();
            
            // Event listeners
            sidebarToggle.addEventListener('click', toggleSidebar);
            
            if (closeSidebar) {
                closeSidebar.addEventListener('click', closeSidebarMobile);
            }
            
            sidebarOverlay.addEventListener('click', closeSidebarMobile);
            
            // Close sidebar when clicking a link on mobile
            const sidebarLinks = sidebar.querySelectorAll('a');
            sidebarLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (isMobile()) {
                        closeSidebarMobile();
                    }
                });
            });
            
            // Handle window resize
            let resizeTimer;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function() {
                    initSidebar();
                }, 250);
            });
            
            // Auto-hide alerts after 5 seconds
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
                alerts.forEach(alert => {
                    const closeBtn = alert.querySelector('.btn-close');
                    if (closeBtn) closeBtn.click();
                });
            }, 5000);
            
            // Initialize DataTables with responsive settings
            if ($.fn.DataTable) {
                $('table').DataTable({
                    responsive: true,
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                    language: {
                        search: "_INPUT_",
                        searchPlaceholder: "Search...",
                        lengthMenu: "Show _MENU_ entries",
                        info: "Showing _START_ to _END_ of _TOTAL_ entries",
                        infoEmpty: "Showing 0 to 0 of 0 entries",
                        infoFiltered: "(filtered from _MAX_ total entries)",
                        zeroRecords: "No matching records found",
                        paginate: {
                            first: "First",
                            last: "Last",
                            next: "Next",
                            previous: "Previous"
                        }
                    },
                    dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                         '<"row"<"col-sm-12"tr>>' +
                         '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                    initComplete: function() {
                        // Add custom classes for mobile
                        if (isMobile()) {
                            $('.dataTables_length select, .dataTables_filter input').addClass('form-control-sm');
                        }
                    }
                });
            }
            
            // Touch-friendly dropdowns on mobile
            if (isMobile()) {
                document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
                    toggle.addEventListener('touchstart', function(e) {
                        e.preventDefault();
                        const dropdown = new bootstrap.Dropdown(this);
                        dropdown.toggle();
                    }, { passive: false });
                });
            }
            
            // Prevent body scroll when sidebar is open on mobile
            function preventBodyScroll(prevent) {
                if (isMobile()) {
                    if (prevent) {
                        document.body.style.overflow = 'hidden';
                        document.body.style.height = '100vh';
                    } else {
                        document.body.style.overflow = '';
                        document.body.style.height = '';
                    }
                }
            }
            
            // Observe sidebar state changes
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.attributeName === 'style') {
                        const isOpen = sidebar.style.transform === 'translateX(0px)';
                        preventBodyScroll(isOpen && isMobile());
                    }
                });
            });
            
            observer.observe(sidebar, { attributes: true });
            
            // Add touch feedback to buttons
            document.querySelectorAll('.btn').forEach(button => {
                button.addEventListener('touchstart', function() {
                    this.classList.add('active');
                }, { passive: true });
                
                button.addEventListener('touchend', function() {
                    this.classList.remove('active');
                }, { passive: true });
            });
            
            // Improve form input experience on mobile
            document.querySelectorAll('input, select, textarea').forEach(input => {
                input.addEventListener('focus', function() {
                    if (isMobile()) {
                        // Scroll to input on focus
                        setTimeout(() => {
                            this.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }, 300);
                    }
                });
            });
        });
    </script>
</body>
</html>