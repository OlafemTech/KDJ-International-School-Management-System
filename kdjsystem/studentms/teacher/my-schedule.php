<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsteachid']==0)) {
  header('location:logout.php');
} else {
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>My Teaching Schedule</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="css/style.css">
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
                    <h4 class="card-title">My Teaching Schedule</h4>
                    <div class="table-responsive">
                      <table class="table table-bordered">
                        <thead>
                          <tr>
                            <th>Subject</th>
                            <th>Class</th>
                            <th>Total Students</th>
                            <th>Actions</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php
                          $teacherId = $_SESSION['sturecmsteachid'];
                          
                          // Get teacher's subjects and classes
                          $sql = "SELECT DISTINCT 
                                 s.ID as SubjectID,
                                 s.SubjectName,
                                 s.SubjectCode,
                                 c.ID as ClassID,
                                 c.ClassName,
                                 c.Section,
                                 COUNT(st.ID) as StudentCount
                                 FROM tblsubjects s
                                 JOIN tblsubjectteacherclass stc ON s.ID = stc.SubjectID
                                 JOIN tblclass c ON stc.ClassID = c.ID
                                 LEFT JOIN tblstudent st ON st.StudentClass = c.ID
                                 WHERE stc.TeacherID = :teacherId
                                 GROUP BY s.ID, c.ID
                                 ORDER BY c.ClassName, c.Section, s.SubjectName";
                          
                          $query = $dbh->prepare($sql);
                          $query->bindParam(':teacherId', $teacherId, PDO::PARAM_INT);
                          $query->execute();
                          $results = $query->fetchAll(PDO::FETCH_OBJ);
                          
                          if($query->rowCount() > 0) {
                              foreach($results as $row) {
                          ?>
                          <tr>
                            <td>
                              <strong><?php echo htmlentities($row->SubjectName);?></strong>
                              <br>
                              <small class="text-muted">Code: <?php echo htmlentities($row->SubjectCode);?></small>
                            </td>
                            <td>
                              <?php echo htmlentities($row->ClassName . ' ' . $row->Section);?>
                            </td>
                            <td>
                              <?php echo htmlentities($row->StudentCount);?> Students
                            </td>
                            <td>
                              <a href="class-students.php?class=<?php echo htmlentities($row->ClassName);?>&section=<?php echo htmlentities($row->Section);?>" 
                                 class="btn btn-info btn-sm">
                                <i class="icon-people"></i> View Students
                              </a>
                              <a href="add-homework.php?subject=<?php echo htmlentities($row->SubjectID);?>&class=<?php echo htmlentities($row->ClassID);?>" 
                                 class="btn btn-primary btn-sm mt-1">
                                <i class="icon-doc"></i> Add Homework
                              </a>
                            </td>
                          </tr>
                          <?php }
                          } else { ?>
                          <tr>
                            <td colspan="4" class="text-center">
                              <div class="alert alert-info">
                                No subjects have been assigned to you yet.
                                <br>
                                Please contact the administrator for subject assignments.
                              </div>
                            </td>
                          </tr>
                          <?php } ?>
                        </tbody>
                      </table>
                    </div>
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
<?php } ?>
