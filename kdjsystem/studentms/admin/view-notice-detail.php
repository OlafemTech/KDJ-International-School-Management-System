<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid'])==0) {
    header('location:logout.php');
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Student Management System | View Notice</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <!-- Plugin css for this page -->
    <link rel="stylesheet" href="./vendors/daterangepicker/daterangepicker.css">
    <link rel="stylesheet" href="./vendors/chartist/chartist.min.css">
    <!-- Layout styles -->
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>
    <div class="container-scroller">
        <?php include_once('includes/header.php');?>
        <div class="container-fluid page-body-wrapper">
            <?php include_once('includes/sidebar.php');?>
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="page-header">
                        <h3 class="page-title">View Notice</h3>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="manage-notice.php">Manage Notice</a></li>
                                <li class="breadcrumb-item active" aria-current="page">View Notice</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="row">
                        <div class="col-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <?php
                                    $vid = $_GET['viewid'];
                                    $sql = "SELECT n.ID as nid, n.Title, n.Message, n.CreationDate,
                                                  c.ID as ClassID, c.ClassName, c.Level
                                           FROM tblnotice n
                                           LEFT JOIN tblclass c ON c.ID = n.ClassID
                                           WHERE n.ID = :vid";
                                    $query = $dbh->prepare($sql);
                                    $query->bindParam(':vid', $vid, PDO::PARAM_INT);
                                    $query->execute();
                                    $notice = $query->fetch(PDO::FETCH_OBJ);

                                    if($notice) {
                                    ?>
                                    <div class="notice-details">
                                        <h4 class="mb-4"><?php echo htmlentities($notice->Title); ?></h4>
                                        
                                        <div class="notice-meta mb-4">
                                            <p><strong>Class:</strong> <?php echo is_null($notice->ClassID) ? 'All Classes' : htmlentities($notice->ClassName); ?></p>
                                            <p><strong>Level:</strong> <?php echo is_null($notice->ClassID) ? '-' : htmlentities($notice->Level); ?></p>
                                            <p><strong>Date:</strong> <?php echo date('d-m-Y', strtotime($notice->CreationDate)); ?></p>
                                        </div>
                                        
                                        <div class="notice-content">
                                            <h5>Notice Content:</h5>
                                            <div class="p-3 bg-light rounded">
                                                <?php echo nl2br(htmlentities($notice->Message)); ?>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-4">
                                            <a href="edit-notice-detail.php?editid=<?php echo $notice->nid; ?>" 
                                               class="btn btn-primary">Edit Notice</a>
                                            <a href="manage-notice.php" class="btn btn-secondary">Back to List</a>
                                        </div>
                                    </div>
                                    <?php 
                                    } else {
                                        echo '<div class="alert alert-danger">Notice not found.</div>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php include_once('includes/footer.php');?>
            </div>
        </div>
    </div>
    <!-- plugins:js -->
    <script src="vendors/js/vendor.bundle.base.js"></script>
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
</body>
</html>
