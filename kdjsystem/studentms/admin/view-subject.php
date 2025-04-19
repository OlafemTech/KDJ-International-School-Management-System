<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']==0)) {
  header('location:logout.php');
} else {
    try {
        if(!isset($_GET['viewid'])) {
            throw new Exception("No subject ID provided");
        }
        
        $vid = intval($_GET['viewid']);
        if($vid <= 0) {
            throw new Exception("Invalid subject ID");
        }
        
        // Main query to get subject details
        $sql = "SELECT s.*, c.ClassName, c.Level, c.Session, c.Term 
                FROM tblsubjects s
                LEFT JOIN tblclass c ON s.ClassID = c.ID 
                WHERE s.ID = :vid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':vid', $vid, PDO::PARAM_INT);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_OBJ);
        
        // Get teacher details if assigned
        $teacher = null;
        if($result && $result->TeacherID) {
            $teacherSql = "SELECT FullName, TeacherID, Email, MobileNumber 
                          FROM tblteachers 
                          WHERE ID = :teacherId";
            $teacherQuery = $dbh->prepare($teacherSql);
            $teacherQuery->bindParam(':teacherId', $result->TeacherID, PDO::PARAM_INT);
            $teacherQuery->execute();
            $teacher = $teacherQuery->fetch(PDO::FETCH_OBJ);
        }
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Student Management System | View Subject</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
      .subject-details {
        background: #f8f9fa;
        border-radius: 5px;
        padding: 20px;
        margin-bottom: 20px;
      }
      .detail-row {
        margin-bottom: 15px;
        border-bottom: 1px solid #e9ecef;
        padding-bottom: 15px;
      }
      .detail-row:last-child {
        border-bottom: none;
      }
      .detail-label {
        font-weight: bold;
        color: #4B49AC;
      }
      .btn-action {
        margin-right: 8px;
      }
      .status-badge {
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: 500;
      }
      .status-active {
        background: #e8f5e9;
        color: #43a047;
      }
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
                    <div class="d-flex justify-content-between align-items-center mb-4">
                      <h4 class="card-title mb-0">Subject Details</h4>
                      <div>
                        <a href="manage-subjects.php" class="btn btn-secondary btn-action">
                          <i class="icon-arrow-left"></i> Back to List
                        </a>
                        <a href="edit-subject.php?editid=<?php echo $vid; ?>" class="btn btn-primary btn-action">
                          <i class="icon-pencil"></i> Edit Subject
                        </a>
                      </div>
                    </div>

                    <?php if($result) { ?>
                    <div class="subject-details">
                      <div class="row detail-row">
                        <div class="col-md-6">
                          <p class="detail-label">Subject Code</p>
                          <h5><?php echo htmlentities($result->SubjectCode);?></h5>
                        </div>
                        <div class="col-md-6">
                          <p class="detail-label">Subject Name</p>
                          <h5><?php echo htmlentities($result->SubjectName);?></h5>
                        </div>
                      </div>

                      <div class="row detail-row">
                        <div class="col-md-6">
                          <p class="detail-label">Class</p>
                          <h5><?php echo htmlentities($result->ClassName . ' - Level ' . $result->Level);?></h5>
                        </div>
                        <div class="col-md-6">
                          <p class="detail-label">Academic Period</p>
                          <h5><?php echo htmlentities($result->Session . ' - ' . $result->Term);?></h5>
                        </div>
                      </div>

                      <div class="row detail-row">
                        <div class="col-md-12">
                          <p class="detail-label">Teacher Information</p>
                          <?php if($teacher): ?>
                          <div class="row">
                            <div class="col-md-6">
                              <p><strong>Name:</strong> <?php echo htmlentities($teacher->FullName);?></p>
                              <p><strong>Teacher ID:</strong> <?php echo htmlentities($teacher->TeacherID);?></p>
                            </div>
                            <div class="col-md-6">
                              <p><strong>Email:</strong> <?php echo htmlentities($teacher->Email);?></p>
                              <p><strong>Phone:</strong> <?php echo htmlentities($teacher->MobileNumber);?></p>
                            </div>
                          </div>
                          <?php else: ?>
                          <p class="text-muted">No teacher assigned to this subject.</p>
                          <?php endif; ?>
                        </div>
                      </div>

                      <div class="row">
                        <div class="col-md-6">
                          <p class="detail-label">Creation Date</p>
                          <h5><?php echo htmlentities($result->CreationDate);?></h5>
                        </div>
                        <div class="col-md-6">
                          <p class="detail-label">Status</p>
                          <span class="status-badge status-active">Active</span>
                        </div>
                      </div>
                    </div>

                    <?php } else { ?>
                    <div class="alert alert-warning">
                      <i class="icon-exclamation"></i> Subject not found or has been deleted.
                    </div>
                    <?php } ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <?php include_once('includes/footer.php');?>
        </div>
      </div>
    </div>
    <script src="vendors/js/vendor.bundle.base.js"></script>
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
  </body>
</html>
<?php 
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
} ?>
