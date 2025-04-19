<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
if (!defined('DB_HOST')) {
    include('includes/dbconnection.php');
}

if (strlen($_SESSION['sturecmsaid'])==0) {
    header('location:logout.php');
    exit();
}

try {
    // Code for deletion
    if(isset($_GET['delid'])) {
        $rid = intval($_GET['delid']);
        $sql = "DELETE FROM tblnotice WHERE ID=:rid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':rid', $rid, PDO::PARAM_INT);
        if($query->execute()) {
            $_SESSION['success'] = "Notice deleted successfully";
        } else {
            $_SESSION['error'] = "Failed to delete notice";
        }
        header('Location: manage-notice.php');
        exit();
    }
    
    // Display messages if any
    $msg = '';
    if(isset($_SESSION['success'])) {
        $msg = '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
        unset($_SESSION['success']);
    } elseif(isset($_SESSION['error'])) {
        $msg = '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
        unset($_SESSION['error']);
    }

    // Pagination setup
    $page_no = isset($_GET['page_no']) && !empty($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
    $total_records_per_page = 10;
    $offset = ($page_no-1) * $total_records_per_page;

    // Get total records for pagination
    $count_query = "SELECT COUNT(*) as total FROM tblnotice";
    $count_stmt = $dbh->prepare($count_query);
    $count_stmt->execute();
    $total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_no_of_pages = ceil($total_records / $total_records_per_page);

    // Get notices with class info
    $sql = "SELECT n.ID as nid, n.Title, n.Message, n.CreationDate, 
                   c.ID as ClassID, c.ClassName, c.Level 
            FROM tblnotice n 
            LEFT JOIN tblclass c ON c.ID = n.ClassID 
            ORDER BY n.CreationDate DESC 
            LIMIT :offset, :records";

    $query = $dbh->prepare($sql);
    $query->bindParam(':offset', $offset, PDO::PARAM_INT);
    $query->bindParam(':records', $total_records_per_page, PDO::PARAM_INT);
    $query->execute();
    $notices = $query->fetchAll(PDO::FETCH_OBJ);

    // Get total records for pagination
    $count_query = "SELECT COUNT(*) as total FROM tblnotice";
    $count_stmt = $dbh->prepare($count_query);
    $count_stmt->execute();
    $total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_no_of_pages = ceil($total_records / $total_records_per_page);
    
} catch(PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $error_message = "An error occurred while fetching notices. Please try again later.";
}

// Handle notice deletion
if(isset($_GET['delid'])) {
    $rid = intval($_GET['delid']);
    try {
        $sql = "delete from tblnotice where ID=:rid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':rid',$rid,PDO::PARAM_INT);
        $query->execute();
        $msg = "Notice deleted successfully";
    } catch(PDOException $e) {
        $error_message = "Error deleting notice: " . $e->getMessage();
    }
}

// Pagination setup
$page_no = isset($_GET['page_no']) && !empty($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
$total_records_per_page = 10;
$offset = ($page_no-1) * $total_records_per_page;

try {
    // Get total number of records
    $total_records = $dbh->query("SELECT COUNT(*) FROM tblnotice")->fetchColumn();
    $total_no_of_pages = ceil($total_records / $total_records_per_page);

    // Main query to get notices with class info
    $sql = "SELECT n.ID as nid, n.Title, n.Message, n.CreationDate, 
                   c.ID as ClassID, c.ClassName, c.Level 
            FROM tblnotice n 
            LEFT JOIN tblclass c ON c.ID = n.ClassId 
            ORDER BY n.CreationDate DESC 
            LIMIT :offset, :limit";

    $query = $dbh->prepare($sql);
    $query->bindParam(':offset', $offset, PDO::PARAM_INT);
    $query->bindParam(':limit', $total_records_per_page, PDO::PARAM_INT);
    $query->execute();
    $notices = $query->fetchAll(PDO::FETCH_OBJ);
} catch(PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Student Management System | Manage Notice</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <!-- Layout styles -->
    <link rel="stylesheet" href="./css/style.css">
    <style>
        .action-buttons .btn { margin: 0 2px; }
        .pagination { margin-bottom: 0; }
        .table td { vertical-align: middle; }
    </style>
</head>
<body>
    <div class="container-scroller">
        <?php include_once('includes/header.php');?>
        <div class="container-fluid page-body-wrapper">
            <?php include_once('includes/sidebar.php');?>
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="row">
                        <div class="col-md-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-sm-flex align-items-center mb-4">
                                        <h4 class="card-title mb-sm-0">Manage Notice</h4>
                                        <a href="add-notice.php" class="btn btn-primary ml-auto mb-3 mb-sm-0">Add New Notice</a>
                                    </div>
                                    <?php if(!empty($msg)): ?>
                                        <div class="mb-4"><?php echo $msg; ?></div>
                                    <?php endif; ?>
                                    <div class="table-responsive border rounded p-1">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th class="font-weight-bold">S.No</th>
                                                    <th class="font-weight-bold">Notice Title</th>
                                                    <th class="font-weight-bold">Class</th>
                                                    <th class="font-weight-bold">Level</th>
                                                    <th class="font-weight-bold">Notice Date</th>
                                                    <th class="font-weight-bold">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if(isset($error_message)) { ?>
                                                    <tr>
                                                        <td colspan="6" class="text-center text-danger"><?php echo $error_message; ?></td>
                                                    </tr>
                                                <?php } else { 
                                                    if(!empty($notices)) { 
                                                        $cnt = 1;
                                                        foreach($notices as $notice) { ?>
                                                            <tr>
                                                                <td><?php echo $cnt++; ?></td>
                                                                <td><?php echo htmlentities($notice->Title); ?></td>
                                                                <td><?php echo is_null($notice->ClassID) ? 'All Classes' : htmlentities($notice->ClassName); ?></td>
                                                                <td><?php echo is_null($notice->ClassID) ? '-' : htmlentities($notice->Level); ?></td>
                                                                <td><?php echo date('d-m-Y', strtotime($notice->CreationDate)); ?></td>
                                                                <td>
                                                                    <div class="action-buttons">
                                                                        <a href="edit-notice-detail.php?editid=<?php echo $notice->nid; ?>" 
                                                                           class="btn btn-primary btn-sm" title="Edit">
                                                                            <i class="icon-pencil"></i>
                                                                        </a>
                                                                        <a href="view-notice-detail.php?viewid=<?php echo $notice->nid; ?>" 
                                                                           class="btn btn-info btn-sm" title="View">
                                                                            <i class="icon-eye"></i>
                                                                        </a>
                                                                        <a href="manage-notice.php?delid=<?php echo $notice->nid; ?>" 
                                                                           onclick="return confirm('Do you really want to delete this notice?');" 
                                                                           class="btn btn-danger btn-sm" title="Delete">
                                                                            <i class="icon-trash"></i>
                                                                        </a>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        <?php }
                                                    } else { ?>
                                                        <tr>
                                                            <td colspan="6" class="text-center">No notices found</td>
                                                        </tr>
                                                    <?php }
                                                } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php if(!isset($error_message) && $total_no_of_pages > 1) { ?>
                                    <div class="pagination-wrapper mt-4">
                                        <ul class="pagination justify-content-center">
                                            <li class="page-item <?php echo ($page_no <= 1) ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="?page_no=1">First</a>
                                            </li>
                                            <li class="page-item <?php echo ($page_no <= 1) ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="<?php echo ($page_no <= 1) ? '#' : '?page_no='.($page_no - 1); ?>">Previous</a>
                                            </li>
                                            <li class="page-item <?php echo ($page_no >= $total_no_of_pages) ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="<?php echo ($page_no >= $total_no_of_pages) ? '#' : '?page_no='.($page_no + 1); ?>">Next</a>
                                            </li>
                                            <li class="page-item <?php echo ($page_no >= $total_no_of_pages) ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="?page_no=<?php echo $total_no_of_pages; ?>">Last</a>
                                            </li>
                                        </ul>
                                    </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- content-wrapper ends -->
                <!-- partial:partials/_footer.html -->
                <?php include_once('includes/footer.php');?>
                <!-- partial -->
            </div>
            <!-- main-panel ends -->
        </div>
        <!-- page-body-wrapper ends -->
    </div>
    <!-- container-scroller -->
    <!-- plugins:js -->
    <script src="vendors/js/vendor.bundle.base.js"></script>
    <!-- endinject -->
    <!-- inject:js -->
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
    <!-- endinject -->
    <!-- Custom js for this page -->
    <script src="./js/dashboard.js"></script>
    <!-- End custom js for this page -->
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
</body>
</html>