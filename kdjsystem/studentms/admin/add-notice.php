<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid'])==0) {
    header('location:logout.php');
    exit();
}

if(isset($_POST['submit'])) {
    try {
        // Validate inputs
        $nottitle = trim($_POST['nottitle']);
        $classid = trim($_POST['classid']);
        $notmsg = trim($_POST['notmsg']);
        
        if(empty($nottitle) || $classid === "" || empty($notmsg)) {
            throw new Exception("All fields are required");
        }
        
        // Convert classid to NULL if "All Classes" is selected
        if($classid === "0") {
            $classid = null;
        }
        
        // Insert notice
        $sql = "INSERT INTO tblnotice(Title, ClassID, Message) VALUES (:title, :classid, :message)";
        $query = $dbh->prepare($sql);
        $query->bindParam(':title', $nottitle, PDO::PARAM_STR);
        $query->bindParam(':classid', $classid, PDO::PARAM_INT);
        $query->bindParam(':message', $notmsg, PDO::PARAM_STR);
        
        $query->execute();
        $LastInsertId = $dbh->lastInsertId();
        
        if ($LastInsertId > 0) {
            echo '<script>alert("Notice has been added successfully.");</script>';
            echo "<script>window.location.href ='manage-notice.php'</script>";
            exit();
        } else {
            throw new Exception("Failed to add notice");
        }
        
    } catch(Exception $e) {
        error_log("Error adding notice: " . $e->getMessage());
        echo '<script>alert("Error: ' . addslashes($e->getMessage()) . '");</script>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Student Management System|| Add Notice</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <!-- Plugin css for this page -->
    <link rel="stylesheet" href="vendors/select2/select2.min.css">
    <link rel="stylesheet" href="vendors/select2-bootstrap-theme/select2-bootstrap.min.css">
    <!-- Layout styles -->
    <link rel="stylesheet" href="css/style.css" />
</head>
<body>
    <div class="container-scroller">
        <!-- partial:partials/_navbar.html -->
        <?php include_once('includes/header.php');?>
        <!-- partial -->
        <div class="container-fluid page-body-wrapper">
            <!-- partial:partials/_sidebar.html -->
            <?php include_once('includes/sidebar.php');?>
            <!-- partial -->
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="page-header">
                        <h3 class="page-title">Add Notice</h3>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Add Notice</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="row">
                        <div class="col-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title" style="text-align: center;">Add Notice</h4>
                                    <form class="forms-sample" method="post">
                                        <div class="form-group">
                                            <label for="nottitle">Notice Title</label>
                                            <input type="text" name="nottitle" class="form-control" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="classid">Notice For</label>
                                            <select name="classid" class="form-control" required>
                                                <option value="">Select Class</option>
                                                <option value="0" <?php echo isset($_POST['classid']) && $_POST['classid'] === '0' ? 'selected' : ''; ?>>All Classes</option>
                                                <?php 
                                                $sql2 = "SELECT * FROM tblclass ORDER BY ClassName, Level";
                                                $query2 = $dbh->prepare($sql2);
                                                $query2->execute();
                                                $result2 = $query2->fetchAll(PDO::FETCH_OBJ);
                                                
                                                foreach($result2 as $row1) {          
                                                    ?>  
                                                    <option value="<?php echo htmlentities($row1->ID);?>">
                                                        <?php echo htmlentities($row1->ClassName);?> 
                                                        <?php echo htmlentities($row1->Level);?>
                                                    </option>
                                                <?php } ?> 
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="notmsg">Notice Message</label>
                                            <textarea name="notmsg" class="form-control" rows="4" required></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary mr-2" name="submit">Add Notice</button>
                                        <a href="manage-notice.php" class="btn btn-light">Cancel</a>
                                    </form>
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
    <script src="vendors/select2/select2.min.js"></script>
    <!-- inject:js -->
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
    <!-- Custom js for this page -->
    <script src="js/select2.js"></script>
</body>
</html>