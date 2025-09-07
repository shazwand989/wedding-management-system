<?php
// Ensure this file is included from admin pages
if (!defined('ADMIN_ACCESS')) {
    exit('Direct access not allowed');
}
?>
</div>
</section>
</div>

<!-- Footer -->
<footer class="main-footer">
    <div class="float-right d-none d-sm-block">
        <b>Version</b> 1.0.0
    </div>
    <strong>Copyright &copy; <?php echo date('Y'); ?> <a href="../index.php" class="golden-link">Wedding Management System</a>.</strong>
    All rights reserved.
</footer>
</div>

<!-- jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables JS -->
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>

<!-- AdminLTE Scripts -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/js/adminlte.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Chart -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Main JS -->
<script src="../assets/js/main.js"></script>

<!-- Custom Scripts -->
<script>
    $(document).ready(function() {
        // Initialize AdminLTE
        window.AdminLTE = window.AdminLTE || {};

        // Initialize Bootstrap 4 dropdowns
        $('.dropdown-toggle').dropdown();

        // Custom golden theme adjustments
        $('.sidebar').addClass('sidebar-golden');

        // Handle active menu states
        $('.nav-sidebar .nav-link').on('click', function() {
            $('.nav-sidebar .nav-link').removeClass('active');
            $(this).addClass('active');
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);

        // Smooth scrolling for internal links
        $('a[href^="#"]').on('click', function(e) {
            e.preventDefault();
            const target = $(this.getAttribute('href'));
            if (target.length) {
                $('html, body').stop().animate({
                    scrollTop: target.offset().top - 70
                }, 1000);
            }
        });

        // Fix dropdown positioning for Bootstrap 4
        $(document).on('show.bs.dropdown', function(e) {
            const dropdown = $(e.target);
            const menu = dropdown.find('.dropdown-menu');

            // Ensure dropdown doesn't go off screen
            setTimeout(function() {
                const rect = menu[0].getBoundingClientRect();
                if (rect.right > window.innerWidth) {
                    menu.addClass('dropdown-menu-right');
                }
            }, 10);
        });

        // Initialize DataTables if present
        if ($.fn.DataTable) {
            // Initialize all tables with class 'data-table'
            $('.data-table').DataTable({
                responsive: true,
                lengthChange: true,
                autoWidth: false,
                pageLength: 25,
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                }
            });
        }
    });

    function logout() {
        Swal.fire({
            title: 'Are you sure you want to logout?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, logout',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#d33',
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../includes/logout.php';
            }
        });
    }
</script>

</body>

</html>