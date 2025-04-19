<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsteachid']==0)) {
  header('location:logout.php');
} else {
  if(isset($_POST['submit'])) {
    $teacherId = $_SESSION['sturecmsteachid'];
    $subjectId = intval($_POST['subjectId']);
    $classId = intval($_POST['classId']);
    $title = $_POST['title'];
    $description = $_POST['description'];
    $submissionDate = $_POST['submissionDate'];
    
    try {
      // Verify teacher has access to this subject and class
      $sql = "SELECT 1 FROM tblsubjectteacherclass 
              WHERE SubjectID = :subjectId 
              AND ClassID = :classId 
              AND TeacherID = :teacherId";
      $query = $dbh->prepare($sql);
      $query->bindParam(':subjectId', $subjectId, PDO::PARAM_INT);
      $query->bindParam(':classId', $classId, PDO::PARAM_INT);
      $query->bindParam(':teacherId', $teacherId, PDO::PARAM_INT);
      $query->execute();
      
      if($query->rowCount() > 0) {
        $attachmentURL = '';
        
        // Handle file upload if present
        if(isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
          $allowed = array('pdf', 'doc', 'docx', 'txt', 'zip', 'rar');
          $filename = $_FILES['attachment']['name'];
          $ext = pathinfo($filename, PATHINFO_EXTENSION);
          
          if(in_array(strtolower($ext), $allowed)) {
            $newName = time() . '_' . $filename;
            $path = 'homework-files/' . $newName;
            
            if(!file_exists('homework-files')) {
              mkdir('homework-files', 0777, true);
            }
            
            if(move_uploaded_file($_FILES['attachment']['tmp_name'], $path)) {
              $attachmentURL = $newName;
            }
          }
        }
        
        // Insert homework
        $sql = "INSERT INTO tblhomework (SubjectID, ClassID, Title, Description, SubmissionDate, AttachmentURL) 
                VALUES (:subjectId, :classId, :title, :description, :submissionDate, :attachmentURL)";
        $query = $dbh->prepare($sql);
        $query->bindParam(':subjectId', $subjectId, PDO::PARAM_INT);
        $query->bindParam(':classId', $classId, PDO::PARAM_INT);
        $query->bindParam(':title', $title, PDO::PARAM_STR);
        $query->bindParam(':description', $description, PDO::PARAM_STR);
        $query->bindParam(':submissionDate', $submissionDate, PDO::PARAM_STR);
        $query->bindParam(':attachmentURL', $attachmentURL, PDO::PARAM_STR);
        $query->execute();
        
        $msg = "Homework assigned successfully";
      } else {
        $error = "You are not authorized to assign homework for this subject and class";
      }
    } catch (PDOException $e) {
      $error = "Error assigning homework: " . $e->getMessage();
    }
  }
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Add Homework</title>
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
                    $subjectId = isset($_GET['subject']) ? intval($_GET['subject']) : 0;
                    $classId = isset($_GET['class']) ? intval($_GET['class']) : 0;
                    
                    // Get subject and class info
                    $sql = "SELECT s.*, c.ClassName, c.Section 
                           FROM tblsubjects s
                           JOIN tblsubjectteacherclass stc ON s.ID = stc.SubjectID
                           JOIN tblclass c ON stc.ClassID = c.ID
                           WHERE s.ID = :subjectId 
                           AND c.ID = :classId
                           AND stc.TeacherID = :teacherId";
                    $query = $dbh->prepare($sql);
                    $query->bindParam(':subjectId', $subjectId, PDO::PARAM_INT);
                    $query->bindParam(':classId', $classId, PDO::PARAM_INT);
                    $query->bindParam(':teacherId', $teacherId, PDO::PARAM_INT);
                    $query->execute();
                    $subjectInfo = $query->fetch(PDO::FETCH_OBJ);
                    
                    if($subjectInfo) {
                    ?>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                      <div>
                        <h4 class="card-title mb-0">Add Homework</h4>
                        <small class="text-muted">
                          Subject: <?php echo htmlentities($subjectInfo->SubjectName);?>
                          <br>
                          Class: <?php echo htmlentities($subjectInfo->ClassName . ' ' . $subjectInfo->Section);?>
                        </small>
                      </div>
                      <a href="my-schedule.php" class="btn btn-secondary">Back to Schedule</a>
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
                    
                    <form class="forms-sample" method="post" enctype="multipart/form-data">
                      <input type="hidden" name="subjectId" value="<?php echo $subjectId;?>">
                      <input type="hidden" name="classId" value="<?php echo $classId;?>">
                      
                      <div class="form-group">
                        <label for="title">Homework Title</label>
                        <input type="text" class="form-control" id="title" name="title" 
                               placeholder="Enter homework title" required>
                      </div>
                      
                      <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" 
                                rows="4" placeholder="Enter homework description" required></textarea>
                      </div>
                      
                      <div class="form-group">
                        <label for="submissionDate">Submission Date</label>
                        <input type="date" class="form-control" id="submissionDate" 
                               name="submissionDate" required
                               min="<?php echo date('Y-m-d');?>">
                      </div>
                      
                      <div class="form-group">
                        <label for="attachment">Attachment (Optional)</label>
                        <input type="file" class="form-control-file" id="attachment" name="attachment">
                        <small class="form-text text-muted">
                          Allowed file types: PDF, DOC, DOCX, TXT, ZIP, RAR
                        </small>
                      </div>
                      
                      <button type="submit" name="submit" class="btn btn-primary mr-2">
                        Assign Homework
                      </button>
                      <a href="my-schedule.php" class="btn btn-light">Cancel</a>
                    </form>
                    <?php } else { ?>
                    <div class="alert alert-warning">
                      <h4 class="alert-heading">Access Denied</h4>
                      <p>You are not authorized to assign homework for this subject and class.</p>
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
