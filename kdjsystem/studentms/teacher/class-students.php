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
    <title>Class Students</title>
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
                    <?php
                    $teacherId = $_SESSION['sturecmsteachid'];
                    $className = $_GET['class'];
                    $section = $_GET['section'];
                    
                    // Verify teacher has access to this class
                    $sql = "SELECT DISTINCT c.ID as ClassID, c.ClassName, c.Section,
                           GROUP_CONCAT(DISTINCT s.SubjectName SEPARATOR ', ') as Subjects
                           FROM tblclass c
                           JOIN tblsubjectteacherclass stc ON c.ID = stc.ClassID
                           JOIN tblsubjects s ON stc.SubjectID = s.ID
                           WHERE stc.TeacherID = :teacherId 
                           AND c.ClassName = :className 
                           AND c.Section = :section
                           GROUP BY c.ID";
                    $query = $dbh->prepare($sql);
                    $query->bindParam(':teacherId', $teacherId, PDO::PARAM_INT);
                    $query->bindParam(':className', $className, PDO::PARAM_STR);
                    $query->bindParam(':section', $section, PDO::PARAM_STR);
                    $query->execute();
                    $classInfo = $query->fetch(PDO::FETCH_OBJ);
                    
                    if($classInfo) {
                    ?>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                      <div>
                        <h4 class="card-title mb-0">Students of <?php echo htmlentities($classInfo->ClassName . ' ' . $classInfo->Section);?></h4>
                        <small class="text-muted">Subjects: <?php echo htmlentities($classInfo->Subjects);?></small>
                      </div>
                      <a href="my-subjects.php" class="btn btn-secondary">Back to Subjects</a>
                    </div>
                    <div class="table-responsive">
                      <table class="table">
                        <thead>
                          <tr>
                            <th>#</th>
                            <th>Student ID</th>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Gender</th>
                            <th>Contact</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php
                          $sql = "SELECT * FROM tblstudent 
                                 WHERE StudentClass = :classId 
                                 ORDER BY StudentName";
                          $query = $dbh->prepare($sql);
                          $query->bindParam(':classId', $classInfo->ClassID, PDO::PARAM_INT);
                          $query->execute();
                          $results = $query->fetchAll(PDO::FETCH_OBJ);
                          $cnt = 1;
                          
                          if($query->rowCount() > 0) {
                              foreach($results as $row) {
                          ?>
                          <tr>
                            <td><?php echo htmlentities($cnt);?></td>
                            <td><?php echo htmlentities($row->StuID);?></td>
                            <td>
                              <?php if($row->Image && file_exists("../admin/images/".$row->Image)) { ?>
                                <img src="../admin/images/<?php echo $row->Image;?>" 
                                     alt="" class="img-sm rounded-circle">
                              <?php } else { ?>
                                <div class="img-sm rounded-circle bg-light text-center" 
                                     style="line-height: 40px; width: 40px; height: 40px;">
                                  <?php echo strtoupper(substr($row->StudentName, 0, 1));?>
                                </div>
                              <?php } ?>
                            </td>
                            <td><?php echo htmlentities($row->StudentName);?></td>
                            <td><?php echo htmlentities($row->StudentEmail);?></td>
                            <td><?php echo htmlentities($row->Gender);?></td>
                            <td><?php echo htmlentities($row->ContactNumber);?></td>
                          </tr>
                          <?php 
                              $cnt++;
                              }
                          } else { ?>
                          <tr>
                            <td colspan="7" class="text-center">No students found in this class</td>
                          </tr>
                          <?php } ?>
                        </tbody>
                      </table>
                    </div>
                    <?php } else { ?>
                    <div class="alert alert-warning">
                      <h4 class="alert-heading">Access Denied</h4>
                      <p>You are not assigned to teach this class. Please check your subject assignments.</p>
                      <hr>
                      <a href="my-subjects.php" class="btn btn-secondary">Back to My Subjects</a>
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
<?php } ?>
