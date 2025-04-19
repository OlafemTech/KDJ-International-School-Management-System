<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsteachid']==0)) {
  header('location:logout.php');
} else {
  if(isset($_POST['grade'])) {
    $teacherId = $_SESSION['sturecmsteachid'];
    $submissionId = intval($_POST['submissionId']);
    $grade = $_POST['grade'];
    $comments = $_POST['comments'];
    
    try {
      // Verify teacher's access to this submission
      $sql = "SELECT hs.ID 
              FROM tblhomeworksubmissions hs
              JOIN tblhomework h ON hs.HomeworkID = h.ID
              JOIN tblsubjectteacherclass stc ON h.SubjectID = stc.SubjectID AND h.ClassID = stc.ClassID
              WHERE hs.ID = :submissionId 
              AND stc.TeacherID = :teacherId";
      $query = $dbh->prepare($sql);
      $query->bindParam(':submissionId', $submissionId, PDO::PARAM_INT);
      $query->bindParam(':teacherId', $teacherId, PDO::PARAM_INT);
      $query->execute();
      
      if($query->rowCount() > 0) {
        // Update submission with grade
        $sql = "UPDATE tblhomeworksubmissions 
                SET Grade = :grade, TeacherComments = :comments, Status = 'Graded'
                WHERE ID = :submissionId";
        $query = $dbh->prepare($sql);
        $query->bindParam(':grade', $grade, PDO::PARAM_STR);
        $query->bindParam(':comments', $comments, PDO::PARAM_STR);
        $query->bindParam(':submissionId', $submissionId, PDO::PARAM_INT);
        $query->execute();
        
        $msg = "Submission graded successfully";
      } else {
        $error = "You are not authorized to grade this submission";
      }
    } catch (PDOException $e) {
      $error = "Error grading submission: " . $e->getMessage();
    }
  }
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>View Submissions</title>
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
                    $homeworkId = isset($_GET['id']) ? intval($_GET['id']) : 0;
                    
                    // Get homework details
                    $sql = "SELECT h.*, s.SubjectName, c.ClassName, c.Section
                           FROM tblhomework h
                           JOIN tblsubjects s ON h.SubjectID = s.ID
                           JOIN tblclass c ON h.ClassID = c.ID
                           JOIN tblsubjectteacherclass stc ON h.SubjectID = stc.SubjectID AND h.ClassID = stc.ClassID
                           WHERE h.ID = :homeworkId 
                           AND stc.TeacherID = :teacherId";
                    $query = $dbh->prepare($sql);
                    $query->bindParam(':homeworkId', $homeworkId, PDO::PARAM_INT);
                    $query->bindParam(':teacherId', $teacherId, PDO::PARAM_INT);
                    $query->execute();
                    $homework = $query->fetch(PDO::FETCH_OBJ);
                    
                    if($homework) {
                    ?>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                      <div>
                        <h4 class="card-title mb-0">Homework Submissions</h4>
                        <small class="text-muted">
                          Subject: <?php echo htmlentities($homework->SubjectName);?>
                          <br>
                          Class: <?php echo htmlentities($homework->ClassName . ' ' . $homework->Section);?>
                        </small>
                      </div>
                      <a href="manage-homework.php" class="btn btn-secondary">Back to Homework</a>
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
                    
                    <div class="card mb-4">
                      <div class="card-body bg-light">
                        <h5 class="card-title"><?php echo htmlentities($homework->Title);?></h5>
                        <p class="card-text"><?php echo nl2br(htmlentities($homework->Description));?></p>
                        <div class="d-flex justify-content-between align-items-center">
                          <small class="text-muted">
                            Due Date: <?php echo date('d M Y', strtotime($homework->SubmissionDate));?>
                          </small>
                          <?php if($homework->AttachmentURL) { ?>
                          <a href="homework-files/<?php echo $homework->AttachmentURL;?>" 
                             class="btn btn-outline-primary btn-sm" target="_blank">
                            <i class="icon-paper-clip"></i> View Assignment File
                          </a>
                          <?php } ?>
                        </div>
                      </div>
                    </div>
                    
                    <div class="table-responsive">
                      <table class="table">
                        <thead>
                          <tr>
                            <th>Student</th>
                            <th>Submission Date</th>
                            <th>Status</th>
                            <th>Grade</th>
                            <th>Actions</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php
                          // Get all students in class and their submissions
                          $sql = "SELECT s.ID as StudentID, s.StudentName, s.StuID,
                                 hs.ID as SubmissionID, hs.SubmissionDate, hs.Status,
                                 hs.Grade, hs.SubmissionText, hs.AttachmentURL
                                 FROM tblstudent s
                                 LEFT JOIN tblhomeworksubmissions hs ON s.ID = hs.StudentID 
                                 AND hs.HomeworkID = :homeworkId
                                 WHERE s.StudentClass = :classId
                                 ORDER BY s.StudentName";
                          $query = $dbh->prepare($sql);
                          $query->bindParam(':homeworkId', $homeworkId, PDO::PARAM_INT);
                          $query->bindParam(':classId', $homework->ClassID, PDO::PARAM_INT);
                          $query->execute();
                          $submissions = $query->fetchAll(PDO::FETCH_OBJ);
                          
                          foreach($submissions as $sub) {
                          ?>
                          <tr>
                            <td>
                              <?php echo htmlentities($sub->StudentName);?>
                              <br>
                              <small class="text-muted">ID: <?php echo $sub->StuID;?></small>
                            </td>
                            <td>
                              <?php 
                              if($sub->SubmissionDate) {
                                  echo date('d M Y H:i', strtotime($sub->SubmissionDate));
                              } else {
                                  echo '<span class="text-danger">Not Submitted</span>';
                              }
                              ?>
                            </td>
                            <td>
                              <?php
                              if($sub->Status == 'Graded') {
                                  echo '<span class="badge badge-success">Graded</span>';
                              } else if($sub->SubmissionDate) {
                                  echo '<span class="badge badge-warning">Pending</span>';
                              } else {
                                  echo '<span class="badge badge-danger">Missing</span>';
                              }
                              ?>
                            </td>
                            <td>
                              <?php echo $sub->Grade ? htmlentities($sub->Grade) : '-';?>
                            </td>
                            <td>
                              <?php if($sub->SubmissionDate) { ?>
                              <button type="button" class="btn btn-info btn-sm" 
                                      data-toggle="modal" 
                                      data-target="#viewModal<?php echo $sub->SubmissionID;?>">
                                View
                              </button>
                              
                              <!-- View Modal -->
                              <div class="modal fade" id="viewModal<?php echo $sub->SubmissionID;?>" 
                                   tabindex="-1" role="dialog">
                                <div class="modal-dialog modal-lg" role="document">
                                  <div class="modal-content">
                                    <div class="modal-header">
                                      <h5 class="modal-title">
                                        <?php echo htmlentities($sub->StudentName);?>'s Submission
                                      </h5>
                                      <button type="button" class="close" data-dismiss="modal">
                                        <span aria-hidden="true">&times;</span>
                                      </button>
                                    </div>
                                    <div class="modal-body">
                                      <div class="submission-content mb-3">
                                        <?php echo nl2br(htmlentities($sub->SubmissionText));?>
                                      </div>
                                      
                                      <?php if($sub->AttachmentURL) { ?>
                                      <div class="mb-3">
                                        <a href="homework-submissions/<?php echo $sub->AttachmentURL;?>" 
                                           class="btn btn-outline-primary btn-sm" target="_blank">
                                          <i class="icon-paper-clip"></i> View Attachment
                                        </a>
                                      </div>
                                      <?php } ?>
                                      
                                      <form method="post">
                                        <input type="hidden" name="submissionId" 
                                               value="<?php echo $sub->SubmissionID;?>">
                                        
                                        <div class="form-group">
                                          <label>Grade</label>
                                          <input type="text" name="grade" class="form-control" 
                                                 value="<?php echo $sub->Grade;?>" required>
                                        </div>
                                        
                                        <div class="form-group">
                                          <label>Comments</label>
                                          <textarea name="comments" class="form-control" rows="3"><?php 
                                          if(isset($sub->TeacherComments)) {
                                              echo htmlentities($sub->TeacherComments);
                                          }
                                          ?></textarea>
                                        </div>
                                        
                                        <button type="submit" name="grade" class="btn btn-primary">
                                          Save Grade
                                        </button>
                                      </form>
                                    </div>
                                  </div>
                                </div>
                              </div>
                              <?php } ?>
                            </td>
                          </tr>
                          <?php } ?>
                        </tbody>
                      </table>
                    </div>
                    <?php } else { ?>
                    <div class="alert alert-warning">
                      <h4 class="alert-heading">Access Denied</h4>
                      <p>You are not authorized to view submissions for this homework assignment.</p>
                      <hr>
                      <a href="my-schedule.php" class="btn btn-secondary">Back to Schedule</a>
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
