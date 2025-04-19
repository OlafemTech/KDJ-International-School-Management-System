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
    $title = $_POST['title'];
    $classid = $_POST['classid'];
    $message = $_POST['message'];
    $eid = intval($_GET['editid']);

    try {
        // Convert classid to NULL if "All Classes" is selected
        if($classid === "0") {
            $classid = null;
        }

        $sql = "UPDATE tblnotice SET Title=:title, ClassId=:classid, Message=:message WHERE ID=:eid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':title', $title, PDO::PARAM_STR);
        $query->bindParam(':classid', $classid, PDO::PARAM_INT);
        $query->bindParam(':message', $message, PDO::PARAM_STR);
        $query->bindParam(':eid', $eid, PDO::PARAM_INT);
        $query->execute();

        echo '<script>alert("Notice has been updated successfully");</script>';
        echo '<script>window.location.href="manage-notice.php";</script>';
    } catch(PDOException $e) {
        echo '<script>alert("Error updating notice: ' . $e->getMessage() . '");</script>';
    }
}

  ?>
<!DOCTYPE html>
<html lang="en">
  <head>
   
    <title>Student  Management System|| Update Notice</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <!-- endinject -->
    <!-- Plugin css for this page -->
    <link rel="stylesheet" href="vendors/select2/select2.min.css">
    <link rel="stylesheet" href="vendors/select2-bootstrap-theme/select2-bootstrap.min.css">
    <!-- End plugin css for this page -->
    <!-- inject:css -->
    <!-- endinject -->
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
              <h3 class="page-title">Update Notice </h3>
              <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                  <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                  <li class="breadcrumb-item active" aria-current="page"> Update Notice</li>
                </ol>
              </nav>
            </div>
            <div class="row">
          
              <div class="col-12 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title" style="text-align: center;">Update Notice</h4>
                   
                    <form class="forms-sample" method="post" enctype="multipart/form-data">
                      <?php
$eid = intval($_GET['editid']);

try {
    $sql = "SELECT n.Title, n.CreationDate, n.ClassId, n.Message, n.ID as nid,
                  c.ID, c.ClassName, c.Level
            FROM tblnotice n 
            LEFT JOIN tblclass c ON c.ID = n.ClassId 
            WHERE n.ID = :eid";
    $query = $dbh->prepare($sql);
    $query->bindParam(':eid', $eid, PDO::PARAM_INT);
    $query->execute();
    $notice = $query->fetch(PDO::FETCH_OBJ);

    if($notice) { ?>
                      <div class="form-group">
                        <label for="title">Notice Title</label>
                        <input type="text" name="title" id="title" value="<?php echo htmlentities($notice->Title); ?>" 
                               class="form-control" required>
                      </div>
                     
                      <div class="form-group">
                        <label for="classid">Notice For</label>
                        <select name="classid" id="classid" class="form-control" required>
                          <option value="0" <?php echo is_null($notice->ClassId) ? 'selected' : ''; ?>>All Classes</option>
                          <?php if(!is_null($notice->ClassId)) { ?>
                              <option value="<?php echo htmlentities($notice->ClassId); ?>" selected>
                                  <?php echo htmlentities($notice->ClassName); ?> - <?php echo htmlentities($notice->Level); ?>
                              </option>
                          <?php } ?>
                          <?php 
    $sql2 = "SELECT * FROM tblclass ORDER BY ClassName, Level";
    $query2 = $dbh->prepare($sql2);
    $query2->execute();
    $classes = $query2->fetchAll(PDO::FETCH_OBJ);

    foreach($classes as $class) {
        if($notice->ClassId != $class->ID) { ?>
            <option value="<?php echo htmlentities($class->ID); ?>">
                <?php echo htmlentities($class->ClassName); ?> - <?php echo htmlentities($class->Level); ?>
            </option>
        <?php }
    } ?>
                        </select>
                      </div>
                      <div class="form-group">
                        <label for="message">Notice Message</label>
                        <textarea name="message" id="message" class="form-control" rows="5" required><?php echo htmlentities($notice->Message); ?></textarea>
                      </div>
                      <div class="form-group">
                        <button type="submit" class="btn btn-primary" name="submit">Update Notice</button>
                        <a href="manage-notice.php" class="btn btn-secondary">Cancel</a>
                      </div>
                   <?php } else { ?>
                      <div class="alert alert-danger">Notice not found.</div>
                      <a href="manage-notice.php" class="btn btn-secondary">Back to List</a>
                   <?php }
} catch(PDOException $e) {
    echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
} ?>
                     
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
    <!-- endinject -->
    <!-- Plugin js for this page -->
    <script src="vendors/select2/select2.min.js"></script>
    <script src="vendors/typeahead.js/typeahead.bundle.min.js"></script>
    <!-- End plugin js for this page -->
    <!-- inject:js -->
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
    <!-- endinject -->
    <!-- Custom js for this page -->
    <script src="js/typeahead.js"></script>
    <script src="js/select2.js"></script>
    <!-- End custom js for this page -->
  </body>
</html>