<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsteachid']==0)) {
  header('location:logout.php');
} else {
  if(isset($_GET['delete'])) {
    $teacherId = $_SESSION['sturecmsteachid'];
    $homeworkId = intval($_GET['delete']);
    
    try {
      // Verify teacher's access to this homework
      $sql = "SELECT 1 FROM tblhomework h
              JOIN tblsubjectteacherclass stc ON h.SubjectID = stc.SubjectID AND h.ClassID = stc.ClassID
              WHERE h.ID = :homeworkId AND stc.TeacherID = :teacherId";
      $query = $dbh->prepare($sql);
      $query->bindParam(':homeworkId', $homeworkId, PDO::PARAM_INT);
      $query->bindParam(':teacherId', $teacherId, PDO::PARAM_INT);
      $query->execute();
      
      if($query->rowCount() > 0) {
        // Get attachment URL before deleting
        $sql = "SELECT AttachmentURL FROM tblhomework WHERE ID = :homeworkId";
        $query = $dbh->prepare($sql);
        $query->bindParam(':homeworkId', $homeworkId, PDO::PARAM_INT);
        $query->execute();
        $attachment = $query->fetch(PDO::FETCH_OBJ);
        
        // Delete homework (submissions will be deleted by foreign key cascade)
        $sql = "DELETE FROM tblhomework WHERE ID = :homeworkId";
        $query = $dbh->prepare($sql);
        $query->bindParam(':homeworkId', $homeworkId, PDO::PARAM_INT);
        $query->execute();
        
        // Delete attachment file if exists
        if($attachment && $attachment->AttachmentURL) {
          $file = 'homework-files/' . $attachment->AttachmentURL;
          if(file_exists($file)) {
            unlink($file);
          }
        }
        
        $msg = "Homework deleted successfully";
      } else {
        $error = "You are not authorized to delete this homework";
      }
    } catch (PDOException $e) {
      $error = "Error deleting homework: " . $e->getMessage();
    }
  }
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Manage Homework</title>
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
                      <h4 class="card-title mb-0">Manage Homework</h4>
                      <a href="my-schedule.php" class="btn btn-primary">
                        <i class="icon-plus"></i> Add New Homework
                      </a>
                    </div>
                    
                    <?php if(isset($msg)) { ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                      <?php echo $msg;?>
                      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                      </button>
                    </div>
                    <?php } ?>
                    
                    <?php if(isset($error)) { ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                      <?php echo $error;?>
                      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                      </button>
                    </div>
                    <?php } ?>
                    
                    <div class="table-responsive">
                      <table class="table">
                        <thead>
                          <tr>
                            <th>Title</th>
                            <th>Subject</th>
                            <th>Class</th>
                            <th>Due Date</th>
                            <th>Submissions</th>
                            <th>Status</th>
                            <th>Actions</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php
                          $teacherId = $_SESSION['sturecmsteachid'];
                          
                          $sql = "SELECT h.*, s.SubjectName, c.ClassName, c.Section,
                                 COUNT(hs.ID) as SubmissionCount,
                                 SUM(CASE WHEN hs.Status = 'Graded' THEN 1 ELSE 0 END) as GradedCount
                                 FROM tblhomework h
                                 JOIN tblsubjects s ON h.SubjectID = s.ID
                                 JOIN tblclass c ON h.ClassID = c.ID
                                 JOIN tblsubjectteacherclass stc ON h.SubjectID = stc.SubjectID 
                                 AND h.ClassID = stc.ClassID
                                 LEFT JOIN tblhomeworksubmissions hs ON h.ID = hs.HomeworkID
                                 WHERE stc.TeacherID = :teacherId
                                 GROUP BY h.ID
                                 ORDER BY h.SubmissionDate DESC";
                          $query = $dbh->prepare($sql);
                          $query->bindParam(':teacherId', $teacherId, PDO::PARAM_INT);
                          $query->execute();
                          $homeworks = $query->fetchAll(PDO::FETCH_OBJ);
                          
                          if($query->rowCount() > 0) {
                              foreach($homeworks as $hw) {
                                  $isPending = strtotime($hw->SubmissionDate) >= strtotime(date('Y-m-d'));
                          ?>
                          <tr>
                            <td>
                              <strong><?php echo htmlentities($hw->Title);?></strong>
                              <?php if($hw->AttachmentURL) { ?>
                              <br>
                              <a href="homework-files/<?php echo $hw->AttachmentURL;?>" 
                                 class="text-muted small" target="_blank">
                                <i class="icon-paper-clip"></i> View Attachment
                              </a>
                              <?php } ?>
                            </td>
                            <td><?php echo htmlentities($hw->SubjectName);?></td>
                            <td><?php echo htmlentities($hw->ClassName . ' ' . $hw->Section);?></td>
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
                              echo $hw->SubmissionCount . ' Submitted';
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
                              <a href="edit-homework.php?id=<?php echo $hw->ID;?>" 
                                 class="btn btn-primary btn-sm mt-1">
                                Edit
                              </a>
                              <a href="?delete=<?php echo $hw->ID;?>" 
                                 class="btn btn-danger btn-sm mt-1"
                                 onclick="return confirm('Are you sure you want to delete this homework?');">
                                Delete
                              </a>
                            </td>
                          </tr>
                          <?php }
                          } else { ?>
                          <tr>
                            <td colspan="7" class="text-center">
                              <div class="alert alert-info">
                                You haven't assigned any homework yet.
                                <br>
                                <a href="my-schedule.php" class="btn btn-primary mt-2">
                                  Add Your First Homework
                                </a>
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
