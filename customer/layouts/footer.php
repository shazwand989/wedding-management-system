<?php
// Ensure this file is included from customer pages
if (!defined('CUSTOMER_ACCESS')) {
    exit('Direct access not allowed');
}
?>

        </div>
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<!-- Main Footer -->
<footer class="main-footer">
    <strong>&copy; <?php echo date('Y'); ?> <a href="../index.php"><?php echo SITE_NAME; ?></a>.</strong>
    All rights reserved.
    <div class="float-right d-none d-sm-inline-block">
        <b>Version</b> 1.0.0
    </div>
</footer>

<!-- Control Sidebar -->
<aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
</aside>
<!-- /.control-sidebar -->

</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- DataTables  & Plugins -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/js/adminlte.min.js"></script>

<!-- Custom JS -->
<script src="../assets/js/main.js"></script>

<!-- Initialize DataTables and Dropdowns -->
<script>
$(document).ready(function() {
    // Initialize DataTables
    $('.table').DataTable({
        "responsive": true,
        "lengthChange": true,
        "autoWidth": false,
        "order": [[0, "desc"]],
        "pageLength": 25,
        "language": {
            "search": "Search:",
            "lengthMenu": "Show _MENU_ entries per page",
            "info": "Showing _START_ to _END_ of _TOTAL_ entries",
            "infoEmpty": "No entries available",
            "infoFiltered": "(filtered from _MAX_ total entries)",
            "zeroRecords": "No matching entries found",
            "emptyTable": "No data available in table",
            "paginate": {
                "first": "First",
                "last": "Last",
                "next": "Next",
                "previous": "Previous"
            }
        }
    });

    // Fix Bootstrap 4 dropdown issues
    $('.dropdown-toggle').dropdown();
    
    // Handle dropdown clicks
    $('.dropdown-menu a').on('click', function(e) {
        if ($(this).attr('href') !== '#') {
            window.location.href = $(this).attr('href');
        }
    });

    // Customer-specific logout function
    window.logoutCustomer = function() {
        Swal.fire({
            title: 'Are you sure?',
            text: "You will be logged out of your account",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e91e63',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, logout'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../includes/logout.php';
            }
        });
    };
});
</script>

<?php if (isset($additional_js)): ?>
    <?php foreach ($additional_js as $js): ?>
        <script src="<?php echo $js; ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (isset($inline_js)): ?>
    <script>
        <?php echo $inline_js; ?>
    </script>
<?php endif; ?>

</body>
</html>
