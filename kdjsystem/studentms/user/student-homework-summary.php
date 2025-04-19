<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsuid']==0)) {
  header('location:logout.php');
} else {
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Homework Summary</title>
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
                      <h4 class="card-title mb-0">Homework Summary</h4>
                      <a href="homework.php" class="btn btn-primary">View All Homework</a>
                    </div>

                    <?php
                    $studentId = $_SESSION['sturecmsuid'];
                    
                    // Get student's class info and subjects
                    $sql = "SELECT s.*, c.ClassName, c.Section,
                           GROUP_CONCAT(DISTINCT sub.SubjectName ORDER BY sub.SubjectName SEPARATOR ', ') as Subjects
                           FROM tblstudent s
                           JOIN tblclass c ON s.StudentClass = c.ID
                           JOIN tblsubjectteacherclass stc ON stc.ClassID = c.ID
                           JOIN tblsubjects sub ON stc.SubjectID = sub.ID
                           WHERE s.ID = :studentId
                           GROUP BY s.ID";
                    $query = $dbh->prepare($sql);
                    $query->bindParam(':studentId', $studentId, PDO::PARAM_INT);
                    $query->execute();
                    $studentInfo = $query->fetch(PDO::FETCH_OBJ);
                    ?>

                    <div class="row mb-4">
                      <div class="col-md-6">
                        <div class="card bg-light">
                          <div class="card-body">
                            <h5 class="card-title">Class Information</h5>
                            <p class="mb-1">
                              <strong>Class:</strong> <?php echo htmlentities($studentInfo->ClassName);?>
                            </p>
                            <p class="mb-1">
                              <strong>Section:</strong> <?php echo htmlentities($studentInfo->Section);?>
                            </p>
                            <p class="mb-0">
                              <strong>Subjects:</strong> <?php echo htmlentities($studentInfo->Subjects);?>
                            </p>
                          </div>
                        </div>
                      </div>

                      <?php
                      // Get homework statistics
                      $sql = "SELECT 
                             COUNT(DISTINCT h.ID) as TotalHomework,
                             COUNT(DISTINCT CASE WHEN hs.ID IS NOT NULL THEN h.ID END) as Submitted,
                             COUNT(DISTINCT CASE WHEN hs.Status = 'Graded' THEN h.ID END) as Graded,
                             COUNT(DISTINCT CASE WHEN h.SubmissionDate >= CURDATE() THEN h.ID END) as Pending,
                             ROUND(AVG(CASE WHEN hs.Grade IS NOT NULL THEN hs.Grade END), 2) as AverageGrade
                             FROM tblhomework h
                             JOIN tblsubjectteacherclass stc ON h.SubjectID = stc.SubjectID
                             LEFT JOIN tblhomeworksubmissions hs ON h.ID = hs.HomeworkID AND hs.StudentID = :studentId
                             WHERE stc.ClassID = :classId";
                      $query = $dbh->prepare($sql);
                      $query->bindParam(':studentId', $studentId, PDO::PARAM_INT);
                      $query->bindParam(':classId', $studentInfo->StudentClass, PDO::PARAM_INT);
                      $query->execute();
                      $stats = $query->fetch(PDO::FETCH_OBJ);
                      ?>

                      <div class="col-md-6">
                        <div class="card bg-primary text-white">
                          <div class="card-body">
                            <h5 class="card-title">Homework Statistics</h5>
                            <div class="row">
                              <div class="col-6 mb-3">
                                <h3 class="mb-0"><?php echo $stats->TotalHomework;?></h3>
                                <small>Total Assignments</small>
                              </div>
                              <div class="col-6 mb-3">
                                <h3 class="mb-0"><?php echo $stats->Pending;?></h3>
                                <small>Pending</small>
                              </div>
                              <div class="col-6">
                                <h3 class="mb-0"><?php echo $stats->Submitted;?></h3>
                                <small>Submitted</small>
                              </div>
                              <div class="col-6">
                                <h3 class="mb-0"><?php echo $stats->AverageGrade ?: 'N/A';?></h3>
                                <small>Average Grade</small>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <?php
                    // Get recent homework by subject
                    $sql = "SELECT s.SubjectName, s.SubjectCode,
                           COUNT(DISTINCT h.ID) as TotalHomework,
                           COUNT(DISTINCT CASE WHEN hs.ID IS NOT NULL THEN h.ID END) as Submitted,
                           COUNT(DISTINCT CASE WHEN h.SubmissionDate >= CURDATE() THEN h.ID END) as Pending,
                           MAX(h.SubmissionDate) as NextDueDate
                           FROM tblsubjects s
                           JOIN tblsubjectteacherclass stc ON s.ID = stc.SubjectID
                           LEFT JOIN tblhomework h ON s.ID = h.SubjectID
                           LEFT JOIN tblhomeworksubmissions hs ON h.ID = hs.HomeworkID AND hs.StudentID = :studentId
                           WHERE stc.ClassID = :classId
                           GROUP BY s.ID
                           ORDER BY s.SubjectName";
                    $query = $dbh->prepare($sql);
                    $query->bindParam(':studentId', $studentId, PDO::PARAM_INT);
                    $query->bindParam(':classId', $studentInfo->StudentClass, PDO::PARAM_INT);
                    $query->execute();
                    $subjects = $query->fetchAll(PDO::FETCH_OBJ);
                    ?>

                    <h5 class="mb-3">Subject-wise Summary</h5>
                    <div class="table-responsive">
                      <table class="table table-hover">
                        <thead>
                          <tr>
                            <th>Subject</th>
                            <th>Total</th>
                            <th>Submitted</th>
                            <th>Pending</th>
                            <th>Next Due Date</th>
                            <th>Action</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach($subjects as $subject) { ?>
                          <tr>
                            <td><?php echo htmlentities($subject->SubjectName);?></td>
                            <td><?php echo $subject->TotalHomework;?></td>
                            <td>
                              <span class="badge badge-success">
                                <?php echo $subject->Submitted;?> / <?php echo $subject->TotalHomework;?>
                              </span>
                            </td>
                            <td>
                              <?php if($subject->Pending > 0) { ?>
                              <span class="badge badge-warning"><?php echo $subject->Pending;?> Due</span>
                              <?php } else { ?>
                              <span class="badge badge-secondary">None</span>
                              <?php } ?>
                            </td>
                            <td>
                              <?php 
                              if($subject->NextDueDate && $subject->NextDueDate >= date('Y-m-d')) {
                                  echo date('d M Y', strtotime($subject->NextDueDate));
                              } else {
                                  echo 'No upcoming';
                              }
                              ?>
                            </td>
                            <td>
                              <a href="view-homework.php?subject=<?php echo $subject->SubjectCode;?>" 
                                 class="btn btn-outline-primary btn-sm">
                                View Homework
                              </a>
                            </td>
                          </tr>
                          <?php } ?>
                        </tbody>
                      </table>
                    </div>

                    <?php
                    // Get upcoming homework
                    $sql = "SELECT h.*, s.SubjectName, 
                           DATEDIFF(h.SubmissionDate, CURDATE()) as DaysLeft
                           FROM tblhomework h
                           JOIN tblsubjects s ON h.SubjectID = s.ID
                           LEFT JOIN tblhomeworksubmissions hs ON h.ID = hs.HomeworkID 
                           AND hs.StudentID = :studentId
                           WHERE h.ClassID = :classId
                           AND h.SubmissionDate >= CURDATE()
                           AND hs.ID IS NULL
                           ORDER BY h.SubmissionDate ASC
                           LIMIT 5";
                    $query = $dbh->prepare($sql);
                    $query->bindParam(':studentId', $studentId, PDO::PARAM_INT);
                    $query->bindParam(':classId', $studentInfo->StudentClass, PDO::PARAM_INT);
                    $query->execute();
                    $upcoming = $query->fetchAll(PDO::FETCH_OBJ);
                    
                    if($upcoming) {
                    ?>
                    <h5 class="mt-4 mb-3">Upcoming Homework</h5>
                    <div class="list-group">
                      <?php foreach($upcoming as $hw) { ?>
                      <div class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between align-items-center">
                          <div>
                            <h6 class="mb-1"><?php echo htmlentities($hw->Title);?></h6>
                            <small class="text-muted">
                              <?php echo htmlentities($hw->SubjectName);?> | 
                              Due: <?php echo date('d M Y', strtotime($hw->SubmissionDate));?>
                            </small>
                          </div>
                          <?php if($hw->DaysLeft <= 3) { ?>
                          <span class="badge badge-danger">
                            <?php echo $hw->DaysLeft;?> days left
                          </span>
                          <?php } else { ?>
                          <span class="badge badge-info">
                            <?php echo $hw->DaysLeft;?> days left
                          </span>
                          <?php } ?>
                        </div>
                      </div>
                      <?php } ?>
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
