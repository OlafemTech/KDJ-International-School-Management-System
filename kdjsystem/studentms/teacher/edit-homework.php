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
    $homeworkId = intval($_POST['homeworkId']);
    $title = $_POST['title'];
    $description = $_POST['description'];
    $submissionDate = $_POST['submissionDate'];
    
    try {
      // Verify teacher's access to this homework
      $sql = "SELECT h.*, s.SubjectCode 
              FROM tblhomework h
              JOIN tblsubjectteacherclass stc ON h.SubjectID = stc.SubjectID AND h.ClassID = stc.ClassID
              JOIN tblsubjects s ON h.SubjectID = s.ID
              WHERE h.ID = :homeworkId AND stc.TeacherID = :teacherId";
      $query = $dbh->prepare($sql);
      $query->bindParam(':homeworkId', $homeworkId, PDO::PARAM_INT);
      $query->bindParam(':teacherId', $teacherId, PDO::PARAM_INT);
      $query->execute();
      $homework = $query->fetch(PDO::FETCH_OBJ);
      
      if($homework) {
        $attachmentURL = $homework->AttachmentURL;
        
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
              // Delete old attachment if exists
              if($homework->AttachmentURL) {
                $oldFile = 'homework-files/' . $homework->AttachmentURL;
                if(file_exists($oldFile)) {
                  unlink($oldFile);
                }
              }
              $attachmentURL = $newName;
            }
          }
        }
        
        // Update homework
        $sql = "UPDATE tblhomework 
                SET Title = :title, Description = :description, 
                    SubmissionDate = :submissionDate, AttachmentURL = :attachmentURL
                WHERE ID = :homeworkId";
        $query = $dbh->prepare($sql);
        $query->bindParam(':title', $title, PDO::PARAM_STR);
        $query->bindParam(':description', $description, PDO::PARAM_STR);
        $query->bindParam(':submissionDate', $submissionDate, PDO::PARAM_STR);
        $query->bindParam(':attachmentURL', $attachmentURL, PDO::PARAM_STR);
        $query->bindParam(':homeworkId', $homeworkId, PDO::PARAM_INT);
        $query->execute();
        
        $msg = "Homework updated successfully";
      } else {
        $error = "You are not authorized to edit this homework";
      }
    } catch (PDOException $e) {
      $error = "Error updating homework: " . $e->getMessage();
    }
  }
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Edit Homework</title>
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
                           JOIN tblsubjectteacherclass stc ON h.SubjectID = stc.SubjectID 
                           AND h.ClassID = stc.ClassID
                           WHERE h.ID = :homeworkId AND stc.TeacherID = :teacherId";
                    $query = $dbh->prepare($sql);
                    $query->bindParam(':homeworkId', $homeworkId, PDO::PARAM_INT);
                    $query->bindParam(':teacherId', $teacherId, PDO::PARAM_INT);
                    $query->execute();
                    $homework = $query->fetch(PDO::FETCH_OBJ);
                    
                    if($homework) {
                    ?>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                      <div>
                        <h4 class="card-title mb-0">Edit Homework</h4>
                        <small class="text-muted">
                          Subject: <?php echo htmlentities($homework->SubjectName);?>
                          <br>
                          Class: <?php echo htmlentities($homework->ClassName . ' ' . $homework->Section);?>
                        </small>
                      </div>
                      <a href="manage-homework.php" class="btn btn-secondary">Back to List</a>
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
                      <input type="hidden" name="homeworkId" value="<?php echo $homeworkId;?>">
                      
                      <div class="form-group">
                        <label for="title">Homework Title</label>
                        <input type="text" class="form-control" id="title" name="title" 
                               value="<?php echo htmlentities($homework->Title);?>" required>
                      </div>
                      
                      <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" 
                                rows="4" required><?php echo htmlentities($homework->Description);?></textarea>
                      </div>
                      
                      <div class="form-group">
                        <label for="submissionDate">Submission Date</label>
                        <input type="date" class="form-control" id="submissionDate" 
                               name="submissionDate" required
                               value="<?php echo $homework->SubmissionDate;?>">
                      </div>
                      
                      <div class="form-group">
                        <label for="attachment">Attachment</label>
                        <?php if($homework->AttachmentURL) { ?>
                        <div class="mb-2">
                          <a href="homework-files/<?php echo $homework->AttachmentURL;?>" 
                             class="btn btn-outline-primary btn-sm" target="_blank">
                            <i class="icon-paper-clip"></i> Current Attachment
                          </a>
                        </div>
                        <?php } ?>
                        <input type="file" class="form-control-file" id="attachment" name="attachment">
                        <small class="form-text text-muted">
                          Leave empty to keep current attachment. Upload new file to replace.
                          <br>
                          Allowed file types: PDF, DOC, DOCX, TXT, ZIP, RAR
                        </small>
                      </div>
                      
                      <button type="submit" name="submit" class="btn btn-primary mr-2">
                        Update Homework
                      </button>
                      <a href="manage-homework.php" class="btn btn-light">Cancel</a>
                    </form>
                    <?php } else { ?>
                    <div class="alert alert-warning">
                      <h4 class="alert-heading">Access Denied</h4>
                      <p>You are not authorized to edit this homework assignment.</p>
                      <hr>
                      <a href="manage-homework.php" class="btn btn-secondary">Back to List</a>
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
