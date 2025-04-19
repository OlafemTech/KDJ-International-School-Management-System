<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsuid']) == 0) {
  header('location:logout.php');
} else {
  if (isset($_POST['submit'])) {
    try {
      $studentId = $_SESSION['sturecmsuid'];
      $homeworkId = intval($_POST['homeworkId']);
      $submissionText = $_POST['submissionText'];

      // Handle file upload if present
      $attachmentURL = null;
      if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
        $allowed = array('pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png');
        $filename = $_FILES['attachment']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
          throw new Exception("Invalid file type. Allowed types: " . implode(', ', $allowed));
        }

        $uploadDir = '../uploads/homework/';
        if (!file_exists($uploadDir)) {
          mkdir($uploadDir, 0777, true);
        }

        $newFilename = uniqid('hw_') . '.' . $ext;
        $destination = $uploadDir . $newFilename;

        if (!move_uploaded_file($_FILES['attachment']['tmp_name'], $destination)) {
          throw new Exception("Error uploading file");
        }

        $attachmentURL = $newFilename;
      }

      // Check if submission already exists
      $checkSql = "SELECT ID FROM tblhomeworksubmissions 
                    WHERE HomeworkID = :homeworkId AND StudentId = :studentId";
      $checkStmt = $dbh->prepare($checkSql);
      $checkStmt->bindParam(':homeworkId', $homeworkId, PDO::PARAM_INT);
      $checkStmt->bindParam(':studentId', $studentId, PDO::PARAM_INT);
      $checkStmt->execute();

      if ($checkStmt->rowCount() > 0) {
        // Update existing submission
        $sql = "UPDATE tblhomeworksubmissions 
                   SET SubmissionText = :submissionText, 
                       AttachmentURL = COALESCE(:attachmentUrl, AttachmentURL),
                       SubmissionDate = CURRENT_TIMESTAMP,
                       Status = 'Pending'
                   WHERE HomeworkID = :homeworkId AND StudentId = :studentId";
      } else {
        // Insert new submission
        $sql = "INSERT INTO tblhomeworksubmissions 
                   (HomeworkID, StudentId, SubmissionText, AttachmentURL, Status) 
                   VALUES (:homeworkId, :studentId, :submissionText, :attachmentUrl, 'Pending')";
      }

      $query = $dbh->prepare($sql);
      $query->bindParam(':homeworkId', $homeworkId, PDO::PARAM_INT);
      $query->bindParam(':studentId', $studentId, PDO::PARAM_INT);
      $query->bindParam(':submissionText', $submissionText, PDO::PARAM_STR);
      $query->bindParam(':attachmentUrl', $attachmentURL, PDO::PARAM_STR);
      $query->execute();

      $msg = "Homework submitted successfully";
      echo "<script>alert('$msg');</script>";
      echo "<script>window.location.href='homework.php';</script>";
    } catch (Exception $e) {
      echo "<script>alert('" . htmlspecialchars($e->getMessage()) . "');</script>";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Submit Homework</title>
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
                    if (!isset($_GET['id'])) {
                        echo '<div class="alert alert-danger">Invalid homework ID</div>';
                    } else {
                        try {
                            $studentId = $_SESSION['sturecmsuid'];
                            $homeworkId = intval($_GET['id']);

                            // Get homework details and check if student can submit
                            $sql = "SELECT h.*, s.SubjectName, c.ClassName, c.Level,
                                   hs.SubmissionText, hs.AttachmentURL, hs.Status,
                                   hs.SubmissionDate, hs.Grade, hs.Feedback
                           FROM tblhomework h
                           JOIN tblsubjects s ON h.SubjectID = s.ID
                           JOIN tblclass c ON h.ClassID = c.ID
                           LEFT JOIN tblhomeworksubmissions hs ON h.ID = hs.HomeworkID 
                                AND hs.StudentId = :studentId
                           WHERE h.ID = :homeworkId 
                           AND h.ClassID = (SELECT StudentClass FROM tblstudent WHERE ID = :studentId)";
                            $query = $dbh->prepare($sql);
                            $query->bindParam(':homeworkId', $homeworkId, PDO::PARAM_INT);
                            $query->bindParam(':studentId', $studentId, PDO::PARAM_INT);
                            $query->execute();

                            if ($query->rowCount() == 0) {
                                throw new Exception("Invalid homework or you don't have permission to submit");
                            }

                            $homework = $query->fetch(PDO::FETCH_OBJ);

                            // Check if submission is still allowed
                            if ($homework->DueDate < date('Y-m-d') && !$homework->AllowLateSubmission) {
                                throw new Exception("Submission deadline has passed");
                            }
                    ?>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                      <div>
                        <h4 class="card-title mb-0"><?php echo htmlentities($homework->Title);?></h4>
                        <small class="text-muted">
                          <?php echo htmlentities($homework->SubjectName);?> | 
                          <?php echo htmlentities($homework->ClassName . ' ' . $homework->Level);?>
                        </small>
                      </div>
                      <a href="homework.php" class="btn btn-secondary">Back to List</a>
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
                      <div class="card-body">
                        <h5 class="card-title">Assignment Details</h5>
                        <p class="card-text"><?php echo nl2br(htmlentities($homework->Description));?></p>
                        <?php if($homework->AttachmentURL) { ?>
                        <a href="../teacher/homework-files/<?php echo $homework->AttachmentURL;?>" 
                           class="btn btn-outline-primary btn-sm" target="_blank">
                          <i class="icon-paper-clip"></i> View Assignment File
                        </a>
                        <?php } ?>
                        <div class="mt-3">
                          <strong class="text-danger">
                            Due Date: <?php echo date('d M Y', strtotime($homework->DueDate));?>
                          </strong>
                          <?php if($homework->DueDate < date('Y-m-d')) { ?>
                          <div class="alert alert-warning mt-2">
                            <i class="icon-exclamation"></i> 
                            This homework is past due. Your submission may be marked as late.
                          </div>
                          <?php } ?>
                        </div>
                      </div>
                    </div>
                    
                    <?php if($homework->Status == 'Graded') { ?>
                    <div class="alert alert-success">
                      <h5 class="alert-heading">Homework Graded</h5>
                      <p>Your submission has been graded.</p>
                      <hr>
                      <strong>Grade: <?php echo htmlentities($homework->Grade);?></strong>
                    </div>
                    <?php } ?>
                    
                    <form class="forms-sample" method="post" enctype="multipart/form-data">
                      <input type="hidden" name="homeworkId" value="<?php echo $homeworkId;?>">
                      
                      <div class="form-group">
                        <label for="submissionText">Your Answer</label>
                        <textarea class="form-control" id="submissionText" name="submissionText" 
                                rows="6" required><?php echo $homework->SubmissionText;?></textarea>
                      </div>
                      
                      <div class="form-group">
                        <label for="attachment">Attachment</label>
                        <?php if($homework->AttachmentURL) { ?>
                        <div class="mb-2">
                          <a href="../teacher/homework-files/submissions/<?php echo $homework->AttachmentURL;?>" 
                             class="btn btn-outline-primary btn-sm" target="_blank">
                            <i class="icon-paper-clip"></i> Current Submission File
                          </a>
                        </div>
                        <?php } ?>
                        <input type="file" class="form-control-file" id="attachment" name="attachment">
                        <small class="form-text text-muted">
                          Allowed file types: PDF, DOC, DOCX, TXT, JPG, JPEG, PNG
                          <?php if($homework->AttachmentURL) { ?>
                          <br>
                          Leave empty to keep current file. Upload new file to replace.
                          <?php } ?>
                        </small>
                      </div>
                      
                      <button type="submit" name="submit" class="btn btn-primary mr-2">
                        <?php echo $homework->SubmissionDate ? 'Update Submission' : 'Submit Homework';?>
                      </button>
                      <a href="homework.php" class="btn btn-light">Cancel</a>
                    </form>
                    <?php 
                        } catch (Exception $e) {
                            echo '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>';
                        }
                    }
                    ?>
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
