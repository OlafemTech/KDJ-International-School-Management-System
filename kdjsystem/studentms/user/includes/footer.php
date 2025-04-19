            </div>
            <!-- content-wrapper ends -->
            <!-- partial:partials/_footer.html -->
            <footer class="footer">
                <div class="d-sm-flex justify-content-center justify-content-sm-between">
                    <span class="text-muted text-center text-sm-left d-block d-sm-inline-block">
                        Copyright &copy; <?php echo date('Y'); ?> KDJ International School. All rights reserved.
                    </span>
                    <span class="float-none float-sm-right d-block mt-1 mt-sm-0 text-center">
                        Student Management System <i class="mdi mdi-heart text-danger"></i>
                    </span>
                </div>
            </footer>
            <!-- partial -->
        </div>
        <!-- main-panel ends -->
    </div>
    <!-- page-body-wrapper ends -->
</div>
<!-- container-scroller -->

<!-- plugins:js -->
<script src="../assets/vendors/js/vendor.bundle.base.js"></script>
<!-- endinject -->
<!-- Plugin js for this page -->
<script src="../assets/vendors/chart.js/Chart.min.js"></script>
<script src="../assets/vendors/datatables.net/jquery.dataTables.js"></script>
<script src="../assets/vendors/datatables.net-bs4/dataTables.bootstrap4.js"></script>
<!-- End plugin js for this page -->
<!-- inject:js -->
<script src="../assets/js/off-canvas.js"></script>
<script src="../assets/js/hoverable-collapse.js"></script>
<script src="../assets/js/misc.js"></script>
<script src="../assets/js/settings.js"></script>
<script src="../assets/js/todolist.js"></script>
<!-- endinject -->
<!-- Custom js for this page -->
<script src="../assets/js/dashboard.js"></script>
<script src="../assets/js/todolist.js"></script>
<!-- End custom js for this page -->

<!-- Initialize DataTables and other plugins -->
<script>
$(document).ready(function() {
    // Initialize DataTables
    $('.datatable').DataTable({
        "pageLength": 10,
        "ordering": true,
        "info": true,
        "lengthChange": true,
        "searching": true,
        "responsive": true
    });

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Handle sidebar toggle
    $('.sidebar-toggler').click(function() {
        $('body').toggleClass('sidebar-icon-only');
    });

    // Handle active menu items
    $('.nav-link').each(function() {
        if ($(this).attr('href') === window.location.pathname.split('/').pop()) {
            $(this).addClass('active');
            $(this).closest('.collapse').addClass('show');
            $(this).closest('.nav-item').addClass('active');
        }
    });
});
</script>
</body>
</html>

<!-- Initialize DataTables -->
<script>
$(document).ready(function() {
    // Initialize DataTables with common options
    $('.datatable').DataTable({
        "pageLength": 10,
        "ordering": true,
        "info": true,
        "lengthChange": true,
        "searching": true,
        "responsive": true
    });
});
</script>
</body>
</html>