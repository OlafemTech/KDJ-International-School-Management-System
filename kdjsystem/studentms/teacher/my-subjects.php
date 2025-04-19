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
    <title>My Assigned Subjects</title>
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
                    <h4 class="card-title">My Assigned Subjects</h4>
                    <div class="table-responsive">
                      <table class="table">
                        <thead>
                          <tr>
                            <th>#</th>
                            <th>Subject Code</th>
                            <th>Subject Name</th>
                            <th>Class</th>
                            <th>Total Students</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php
                          $teacherId = $_SESSION['sturecmsteachid'];
                          
                          // Get subjects and classes assigned to the teacher
                          $sql = "SELECT s.SubjectCode, s.SubjectName, 
                                 c.ClassName, c.Section,
                                 COUNT(st.ID) as StudentCount
                                 FROM tblsubjects s
                                 JOIN tblsubjectteacherclass stc ON s.ID = stc.SubjectID
                                 JOIN tblclass c ON stc.ClassID = c.ID
                                 LEFT JOIN tblstudent st ON st.StudentClass = c.ID
                                 WHERE stc.TeacherID = :teacherId
                                 GROUP BY s.ID, c.ID
                                 ORDER BY s.SubjectName, c.ClassName, c.Section";
                          
                          $query = $dbh->prepare($sql);
                          $query->bindParam(':teacherId', $teacherId, PDO::PARAM_INT);
                          $query->execute();
                          $results = $query->fetchAll(PDO::FETCH_OBJ);
                          $cnt = 1;
                          
                          if($query->rowCount() > 0) {
                              foreach($results as $row) {
                          ?>
                          <tr>
                            <td><?php echo htmlentities($cnt);?></td>
                            <td><?php echo htmlentities($row->SubjectCode);?></td>
                            <td><?php echo htmlentities($row->SubjectName);?></td>
                            <td><?php echo htmlentities($row->ClassName) . ' ' . htmlentities($row->Section);?></td>
                            <td>
                                <a href="class-students.php?class=<?php echo $row->ClassName;?>&section=<?php echo $row->Section;?>" 
                                   class="text-info">
                                    <?php echo htmlentities($row->StudentCount);?> Students
                                </a>
                            </td>
                          </tr>
                          <?php 
                              $cnt++;
                              }
                          } else { ?>
                          <tr>
                            <td colspan="5" class="text-center">No subjects assigned yet</td>
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
