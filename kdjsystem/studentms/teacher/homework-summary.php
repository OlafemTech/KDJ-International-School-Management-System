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
                      <a href="manage-homework.php" class="btn btn-primary">
                        <i class="icon-list"></i> Manage Homework
                      </a>
                    </div>
                    
                    <?php
                    $teacherId = $_SESSION['sturecmsteachid'];
                    
                    // Get all classes and subjects taught by the teacher
                    $sql = "SELECT DISTINCT c.ID as ClassID, c.ClassName, c.Section,
                           s.ID as SubjectID, s.SubjectName, s.SubjectCode,
                           (SELECT COUNT(*) FROM tblstudent WHERE StudentClass = c.ID) as StudentCount
                           FROM tblsubjectteacherclass stc
                           JOIN tblclass c ON stc.ClassID = c.ID
                           JOIN tblsubjects s ON stc.SubjectID = s.ID
                           WHERE stc.TeacherID = :teacherId
                           ORDER BY c.ClassName, c.Section, s.SubjectName";
                    $query = $dbh->prepare($sql);
                    $query->bindParam(':teacherId', $teacherId, PDO::PARAM_INT);
                    $query->execute();
                    $classes = $query->fetchAll(PDO::FETCH_OBJ);
                    
                    if($query->rowCount() > 0) {
                        foreach($classes as $class) {
                            // Get homework statistics for this class and subject
                            $sql = "SELECT 
                                   COUNT(*) as TotalHomework,
                                   SUM(CASE WHEN CURDATE() <= h.SubmissionDate THEN 1 ELSE 0 END) as PendingHomework,
                                   (SELECT COUNT(*) FROM tblhomeworksubmissions hs
                                    JOIN tblstudent st ON hs.StudentID = st.ID
                                    WHERE hs.HomeworkID = h.ID 
                                    AND st.StudentClass = :classId) as TotalSubmissions,
                                   (SELECT COUNT(*) FROM tblhomeworksubmissions hs
                                    JOIN tblstudent st ON hs.StudentID = st.ID
                                    WHERE hs.HomeworkID = h.ID 
                                    AND st.StudentClass = :classId
                                    AND hs.Status = 'Graded') as GradedSubmissions
                                   FROM tblhomework h
                                   WHERE h.ClassID = :classId
                                   AND h.SubjectID = :subjectId";
                            $query = $dbh->prepare($sql);
                            $query->bindParam(':classId', $class->ClassID, PDO::PARAM_INT);
                            $query->bindParam(':subjectId', $class->SubjectID, PDO::PARAM_INT);
                            $query->execute();
                            $stats = $query->fetch(PDO::FETCH_OBJ);
                            
                            // Get recent homework for this class and subject
                            $sql = "SELECT h.*, 
                                   (SELECT COUNT(*) FROM tblhomeworksubmissions hs
                                    JOIN tblstudent st ON hs.StudentID = st.ID
                                    WHERE hs.HomeworkID = h.ID 
                                    AND st.StudentClass = :classId) as SubmissionCount,
                                   (SELECT COUNT(*) FROM tblhomeworksubmissions hs
                                    JOIN tblstudent st ON hs.StudentID = st.ID
                                    WHERE hs.HomeworkID = h.ID 
                                    AND st.StudentClass = :classId
                                    AND hs.Status = 'Graded') as GradedCount
                                   FROM tblhomework h
                                   WHERE h.ClassID = :classId
                                   AND h.SubjectID = :subjectId
                                   ORDER BY h.SubmissionDate DESC
                                   LIMIT 5";
                            $query = $dbh->prepare($sql);
                            $query->bindParam(':classId', $class->ClassID, PDO::PARAM_INT);
                            $query->bindParam(':subjectId', $class->SubjectID, PDO::PARAM_INT);
                            $query->execute();
                            $recentHomework = $query->fetchAll(PDO::FETCH_OBJ);
                    ?>
                    <div class="card mb-4">
                      <div class="card-header bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                          <div>
                            <h5 class="mb-0">
                              <?php echo htmlentities($class->ClassName . ' ' . $class->Section);?>
                              <small class="text-muted ml-2">
                                <?php echo htmlentities($class->SubjectName);?>
                              </small>
                            </h5>
                            <small class="text-muted">
                              <?php echo $class->StudentCount;?> Students Enrolled
                            </small>
                          </div>
                          <div class="text-right">
                            <a href="class-students.php?class=<?php echo $class->ClassID;?>" 
                               class="btn btn-outline-primary btn-sm">
                              View Students
                            </a>
                          </div>
                        </div>
                      </div>
                      <div class="card-body">
                        <div class="row">
                          <div class="col-md-3">
                            <div class="card bg-light">
                              <div class="card-body text-center">
                                <h3 class="mb-0"><?php echo $stats->TotalHomework;?></h3>
                                <small class="text-muted">Total Homework</small>
                              </div>
                            </div>
                          </div>
                          <div class="col-md-3">
                            <div class="card bg-warning text-white">
                              <div class="card-body text-center">
                                <h3 class="mb-0"><?php echo $stats->PendingHomework;?></h3>
                                <small>Pending Homework</small>
                              </div>
                            </div>
                          </div>
                          <div class="col-md-3">
                            <div class="card bg-info text-white">
                              <div class="card-body text-center">
                                <h3 class="mb-0"><?php echo $stats->TotalSubmissions;?></h3>
                                <small>Total Submissions</small>
                              </div>
                            </div>
                          </div>
                          <div class="col-md-3">
                            <div class="card bg-success text-white">
                              <div class="card-body text-center">
                                <h3 class="mb-0"><?php echo $stats->GradedSubmissions;?></h3>
                                <small>Graded Submissions</small>
                              </div>
                            </div>
                          </div>
                        </div>
                        
                        <?php if($query->rowCount() > 0) { ?>
                        <h6 class="mt-4 mb-3">Recent Homework</h6>
                        <div class="table-responsive">
                          <table class="table">
                            <thead>
                              <tr>
                                <th>Title</th>
                                <th>Due Date</th>
                                <th>Submissions</th>
                                <th>Status</th>
                                <th>Actions</th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php foreach($recentHomework as $hw) {
                                  $isPending = strtotime($hw->SubmissionDate) >= strtotime(date('Y-m-d'));
                              ?>
                              <tr>
                                <td>
                                  <?php echo htmlentities($hw->Title);?>
                                  <?php if($hw->AttachmentURL) { ?>
                                  <br>
                                  <a href="homework-files/<?php echo $hw->AttachmentURL;?>" 
                                     class="text-muted small" target="_blank">
                                    <i class="icon-paper-clip"></i> View Attachment
                                  </a>
                                  <?php } ?>
                                </td>
                                <td>
                                  <?php echo date('d M Y', strtotime($hw->SubmissionDate));?>
                                  <?php if($isPending) { ?>
                                  <span class="badge badge-warning">Pending</span>
                                  <?php } else { ?>
                                  <span class="badge badge-secondary">Closed</span>
                                  <?php } ?>
                                </td>
                                <td>
                                  <?php 
                                  echo $hw->SubmissionCount . '/' . $class->StudentCount . ' Submitted';
                                  if($hw->SubmissionCount > 0) {
                                      echo '<br>';
                                      echo '<small class="text-muted">';
                                      echo $hw->GradedCount . ' Graded';
                                      echo '</small>';
                                  }
                                  ?>
                                </td>
                                <td>
                                  <?php
                                  if($hw->SubmissionCount == 0) {
                                      echo '<span class="badge badge-danger">No Submissions</span>';
                                  } else if($hw->SubmissionCount == $hw->GradedCount) {
                                      echo '<span class="badge badge-success">All Graded</span>';
                                  } else {
                                      echo '<span class="badge badge-warning">Needs Grading</span>';
                                  }
                                  ?>
                                </td>
                                <td>
                                  <a href="view-submissions.php?id=<?php echo $hw->ID;?>" 
                                     class="btn btn-info btn-sm">
                                    View Submissions
                                  </a>
                                </td>
                              </tr>
                              <?php } ?>
                            </tbody>
                          </table>
                        </div>
                        <?php } ?>
                      </div>
                    </div>
                    <?php }
                    } else { ?>
                    <div class="alert alert-info">
                      <h5 class="alert-heading">No Classes Assigned</h5>
                      <p class="mb-0">You haven't been assigned to any classes yet.</p>
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
