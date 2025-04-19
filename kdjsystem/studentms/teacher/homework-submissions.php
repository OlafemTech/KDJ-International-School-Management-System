<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsteacherId']==0)) {
  header('location:logout.php');
} else {
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Homework Submissions</title>
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
                    <div class="d-flex justify-content-between align-items-center mb-4">
                      <h4 class="card-title mb-0">Homework Submissions</h4>
                      <div>
                        <a href="homework-summary.php" class="btn btn-primary">
                          <i class="icon-chart"></i> View Summary
                        </a>
                        <a href="manage-homework.php" class="btn btn-secondary ml-2">
                          <i class="icon-notebook"></i> Manage Homework
                        </a>
                      </div>
                    </div>

                    <?php
                    $teacherId = $_SESSION['sturecmsteacherId'];
                    
                    // Get filter values
                    $subjectFilter = isset($_GET['subject']) ? $_GET['subject'] : '';
                    $classFilter = isset($_GET['class']) ? $_GET['class'] : '';
                    $statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
                    
                    // Get teacher's subjects and classes
                    $sql = "SELECT DISTINCT s.ID as SubjectID, s.SubjectName,
                           c.ID as ClassID, c.ClassName, c.Section
                           FROM tblsubjectteacherclass stc
                           JOIN tblsubjects s ON stc.SubjectID = s.ID
                           JOIN tblclass c ON stc.ClassID = c.ID
                           WHERE stc.TeacherID = :teacherId
                           ORDER BY s.SubjectName, c.ClassName, c.Section";
                    $query = $dbh->prepare($sql);
                    $query->bindParam(':teacherId', $teacherId, PDO::PARAM_INT);
                    $query->execute();
                    $teacherClasses = $query->fetchAll(PDO::FETCH_OBJ);
                    ?>
                    
                    <form method="get" class="mb-4">
                      <div class="row">
                        <div class="col-md-3">
                          <div class="form-group">
                            <label for="subject">Subject</label>
                            <select class="form-control" id="subject" name="subject">
                              <option value="">All Subjects</option>
                              <?php
                              $subjects = array();
                              foreach($teacherClasses as $tc) {
                                  if(!in_array($tc->SubjectID, $subjects)) {
                                      $subjects[] = $tc->SubjectID;
                                      $selected = $tc->SubjectID == $subjectFilter ? 'selected' : '';
                                      echo "<option value='{$tc->SubjectID}' {$selected}>{$tc->SubjectName}</option>";
                                  }
                              }
                              ?>
                            </select>
                          </div>
                        </div>
                        <div class="col-md-3">
                          <div class="form-group">
                            <label for="class">Class</label>
                            <select class="form-control" id="class" name="class">
                              <option value="">All Classes</option>
                              <?php
                              $classes = array();
                              foreach($teacherClasses as $tc) {
                                  if(!in_array($tc->ClassID, $classes)) {
                                      $classes[] = $tc->ClassID;
                                      $selected = $tc->ClassID == $classFilter ? 'selected' : '';
                                      echo "<option value='{$tc->ClassID}' {$selected}>";
                                      echo htmlentities($tc->ClassName . ' ' . $tc->Section);
                                      echo "</option>";
                                  }
                              }
                              ?>
                            </select>
                          </div>
                        </div>
                        <div class="col-md-3">
                          <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control" id="status" name="status">
                              <option value="">All Status</option>
                              <option value="Pending" <?php echo $statusFilter == 'Pending' ? 'selected' : '';?>>
                                Pending
                              </option>
                              <option value="Graded" <?php echo $statusFilter == 'Graded' ? 'selected' : '';?>>
                                Graded
                              </option>
                            </select>
                          </div>
                        </div>
                        <div class="col-md-3">
                          <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-block">
                              <i class="icon-magnifier"></i> Filter
                            </button>
                          </div>
                        </div>
                      </div>
                    </form>

                    <?php
                    // Build the submissions query with filters
                    $sql = "SELECT hs.*, h.Title as HomeworkTitle, h.SubmissionDate as DueDate,
                           s.StudentName, s.RollNumber, c.ClassName, c.Section,
                           sub.SubjectName
                           FROM tblhomeworksubmissions hs
                           JOIN tblhomework h ON hs.HomeworkID = h.ID
                           JOIN tblstudent s ON hs.StudentID = s.ID
                           JOIN tblclass c ON s.StudentClass = c.ID
                           JOIN tblsubjects sub ON h.SubjectID = sub.ID
                           JOIN tblsubjectteacherclass stc ON sub.ID = stc.SubjectID
                           WHERE stc.TeacherID = :teacherId";
                    
                    $params = array(':teacherId' => $teacherId);
                    
                    if($subjectFilter) {
                        $sql .= " AND sub.ID = :subjectId";
                        $params[':subjectId'] = $subjectFilter;
                    }
                    
                    if($classFilter) {
                        $sql .= " AND c.ID = :classId";
                        $params[':classId'] = $classFilter;
                    }
                    
                    if($statusFilter) {
                        $sql .= " AND hs.Status = :status";
                        $params[':status'] = $statusFilter;
                    }
                    
                    $sql .= " ORDER BY hs.SubmissionDate DESC";
                    
                    $query = $dbh->prepare($sql);
                    foreach($params as $key => &$val) {
                        $query->bindParam($key, $val);
                    }
                    $query->execute();
                    $submissions = $query->fetchAll(PDO::FETCH_OBJ);
                    
                    if($query->rowCount() > 0) {
                    ?>
                    <div class="table-responsive">
                      <table class="table table-hover">
                        <thead>
                          <tr>
                            <th>Student</th>
                            <th>Subject</th>
                            <th>Homework</th>
                            <th>Submission Date</th>
                            <th>Status</th>
                            <th>Grade</th>
                            <th>Action</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach($submissions as $sub) {
                              $isLate = strtotime($sub->SubmissionDate) > strtotime($sub->DueDate);
                          ?>
                          <tr>
                            <td>
                              <div><?php echo htmlentities($sub->StudentName);?></div>
                              <small class="text-muted">
                                Roll: <?php echo htmlentities($sub->RollNumber);?>
                                <br>
                                Class: <?php echo htmlentities($sub->ClassName . ' ' . $sub->Section);?>
                              </small>
                            </td>
                            <td><?php echo htmlentities($sub->SubjectName);?></td>
                            <td>
                              <div><?php echo htmlentities($sub->HomeworkTitle);?></div>
                              <small class="text-muted">
                                Due: <?php echo date('d M Y', strtotime($sub->DueDate));?>
                              </small>
                            </td>
                            <td>
                              <?php echo date('d M Y H:i', strtotime($sub->SubmissionDate));?>
                              <?php if($isLate) { ?>
                              <br>
                              <span class="badge badge-danger">Late</span>
                              <?php } ?>
                            </td>
                            <td>
                              <?php if($sub->Status == 'Graded') { ?>
                              <span class="badge badge-success">Graded</span>
                              <?php } else { ?>
                              <span class="badge badge-warning">Pending</span>
                              <?php } ?>
                            </td>
                            <td>
                              <?php 
                              if($sub->Grade) {
                                  echo htmlentities($sub->Grade);
                              } else {
                                  echo '-';
                              }
                              ?>
                            </td>
                            <td>
                              <a href="grade-homework.php?id=<?php echo $sub->ID;?>" 
                                 class="btn btn-primary btn-sm">
                                <?php echo $sub->Status == 'Graded' ? 'Update Grade' : 'Grade';?>
                              </a>
                            </td>
                          </tr>
                          <?php } ?>
                        </tbody>
                      </table>
                    </div>
                    <?php } else { ?>
                    <div class="alert alert-info">
                      <h5 class="alert-heading">No Submissions Found</h5>
                      <p class="mb-0">No homework submissions match your filter criteria.</p>
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
