<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']==0)) {
  header('location:logout.php');
} else {
    if(isset($_POST['submit'])) {
        try {
            $subjectId = intval($_GET['editid']);
            $subjectName = $_POST['subjectName'];
            $subjectCode = strtoupper($_POST['subjectCode']);
            $classId = $_POST['classId'];
            $teacherId = !empty($_POST['teacherId']) ? $_POST['teacherId'] : null;
            
            // Check if subject code exists for other subjects in the same class
            $sql = "SELECT ID FROM tblsubjects WHERE SubjectCode = :subjectCode AND ClassID = :classId AND ID != :subjectId";
            $query = $dbh->prepare($sql);
            $query->bindParam(':subjectCode', $subjectCode, PDO::PARAM_STR);
            $query->bindParam(':classId', $classId, PDO::PARAM_INT);
            $query->bindParam(':subjectId', $subjectId, PDO::PARAM_INT);
            $query->execute();
            
            if($query->rowCount() > 0) {
                throw new Exception("Subject code already exists for this class. Please choose a different code.");
            }
            
            // Update subject
            $sql = "UPDATE tblsubjects SET 
                    SubjectName=:subjectName, 
                    SubjectCode=:subjectCode,
                    ClassID=:classId,
                    TeacherID=:teacherId 
                    WHERE ID=:subjectId";
            $query = $dbh->prepare($sql);
            $query->bindParam(':subjectName', $subjectName, PDO::PARAM_STR);
            $query->bindParam(':subjectCode', $subjectCode, PDO::PARAM_STR);
            $query->bindParam(':classId', $classId, PDO::PARAM_INT);
            $query->bindParam(':teacherId', $teacherId, PDO::PARAM_INT);
            $query->bindParam(':subjectId', $subjectId, PDO::PARAM_INT);
            $query->execute();
            
            $_SESSION['success'] = "Subject updated successfully";
            header('location: manage-subjects.php');
            exit();
            
        } catch(Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
    }
    
    // Get subject details
    $subjectId = intval($_GET['editid']);
    $sql = "SELECT s.*, c.ClassName, c.Level, c.Session, c.Term, t.FullName as TeacherName 
            FROM tblsubjects s
            LEFT JOIN tblclass c ON s.ClassID = c.ID
            LEFT JOIN tblteachers t ON s.TeacherID = t.ID
            WHERE s.ID = :subjectId";
    $query = $dbh->prepare($sql);
    $query->bindParam(':subjectId', $subjectId, PDO::PARAM_INT);
    $query->execute();
    $subject = $query->fetch(PDO::FETCH_OBJ);
    
    if(!$subject) {
        $_SESSION['error'] = "Subject not found";
        header('location: manage-subjects.php');
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Edit Subject</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="vendors/select2/select2.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .subject-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .current-details {
            margin-bottom: 20px;
            padding: 15px;
            border-left: 3px solid #4B49AC;
        }
        .btn-action {
            margin-right: 5px;
        }
        .button-group {
            display: flex;
            gap: 10px;
        }
        .form-actions {
            border-top: 1px solid #ebedf2;
            padding-top: 20px;
        }
        .btn-lg {
            padding: 12px 25px;
            font-size: 14px;
        }
        .alert ul {
            padding-left: 20px;
        }
        .spin {
            animation: spin 1s infinite linear;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
  </head>
  <body>
    <div class="container-scroller">
      <?php include_once('includes/header.php');?>
      <div class="container-fluid page-body-wrapper">
        <?php include_once('includes/sidebar.php');?>
        <div class="main-panel">
          <div class="content-wrapper">
            <div class="row">
              <div class="col-12 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="card-title">Edit Subject</h4>
                        <div class="button-group">
                            <a href="manage-subjects.php" class="btn btn-secondary btn-action">
                                <i class="icon-arrow-left"></i> Back to List
                            </a>
                            <a href="view-subject.php?viewid=<?php echo $subjectId; ?>" class="btn btn-info btn-action">
                                <i class="icon-eye"></i> View Details
                            </a>
                        </div>
                    </div>

                    <!-- Current Subject Information -->
                    <div class="current-details">
                        <h5>Current Subject Information</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Subject Name:</strong> <?php echo htmlentities($subject->SubjectName); ?></p>
                                <p><strong>Subject Code:</strong> <?php echo htmlentities($subject->SubjectCode); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Class:</strong> <?php echo htmlentities($subject->ClassName . ' - Level ' . $subject->Level); ?></p>
                                <p><strong>Teacher:</strong> <?php echo $subject->TeacherName ? htmlentities($subject->TeacherName) : 'Not Assigned'; ?></p>
                            </div>
                        </div>
                    </div>

                    <?php if(isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php 
                            echo htmlentities($_SESSION['error']); 
                            unset($_SESSION['error']);
                            ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <form class="forms-sample" method="post">
                      <div class="form-group">
                        <label for="subjectName">Subject Name <span class="text-danger">*</span></label>
                        <input type="text" name="subjectName" class="form-control" required
                               value="<?php echo htmlentities($subject->SubjectName); ?>"
                               placeholder="e.g., Mathematics">
                        <small class="form-text text-muted">Maximum 100 characters</small>
                      </div>

                      <div class="form-group">
                        <label for="subjectCode">Subject Code <span class="text-danger">*</span></label>
                        <input type="text" name="subjectCode" class="form-control" required
                               value="<?php echo htmlentities($subject->SubjectCode); ?>"
                               placeholder="e.g., MTH" maxlength="20" pattern="[A-Za-z0-9]+"
                               title="Only letters and numbers allowed">
                        <small class="form-text text-muted">Maximum 20 characters, letters and numbers only. Will be converted to uppercase.</small>
                      </div>

                      <div class="form-group">
                        <label for="classId">Class <span class="text-danger">*</span></label>
                        <select name="classId" class="form-control" required>
                          <option value="">Select Class</option>
                          <?php 
                          $sql = "SELECT ID, ClassName, Level, Session, Term FROM tblclass ORDER BY ClassName, Level";
                          $query = $dbh->prepare($sql);
                          $query->execute();
                          $classes = $query->fetchAll(PDO::FETCH_OBJ);
                          foreach($classes as $class) {
                              $selected = ($subject->ClassID == $class->ID) ? 'selected' : '';
                              echo "<option value='" . $class->ID . "' " . $selected . ">" . 
                                   htmlentities($class->ClassName . ' - Level ' . $class->Level . 
                                   ' (' . $class->Session . ' - ' . $class->Term . ')') . "</option>";
                          }
                          ?>
                        </select>
                      </div>

                      <div class="form-group">
                        <label for="teacherId">Assign Teacher</label>
                        <select name="teacherId" class="form-control">
                          <option value="">Select Teacher</option>
                          <?php 
                          $sql = "SELECT ID, FullName, TeacherID FROM tblteachers ORDER BY FullName";
                          $query = $dbh->prepare($sql);
                          $query->execute();
                          $teachers = $query->fetchAll(PDO::FETCH_OBJ);
                          foreach($teachers as $teacher) {
                              $selected = ($subject->TeacherID == $teacher->ID) ? 'selected' : '';
                              echo "<option value='" . $teacher->ID . "' " . $selected . ">" . 
                                   htmlentities($teacher->FullName) . " (" . htmlentities($teacher->TeacherID) . ")</option>";
                          }
                          ?>
                        </select>
                        <small class="form-text text-muted">Optional. Leave blank to remove teacher assignment.</small>
                      </div>

                      <div class="form-actions mt-4">
                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" name="submit" class="btn btn-primary btn-lg mr-2">
                                    <i class="icon-refresh"></i> Update Subject
                                </button>
                                <button type="reset" class="btn btn-warning btn-lg mr-2">
                                    <i class="icon-reload"></i> Reset Form
                                </button>
                                <a href="manage-subjects.php" class="btn btn-danger btn-lg">
                                    <i class="icon-close"></i> Cancel
                                </a>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <i class="icon-info"></i> 
                                    <strong>Note:</strong> 
                                    <ul class="mb-0">
                                        <li>All fields marked with <span class="text-danger">*</span> are required</li>
                                        <li>Subject Code must be unique within each class</li>
                                        <li>Teacher assignment is optional</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                      </div>
                    </form>
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
    <script src="vendors/select2/select2.min.js"></script>
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
    <script>
      $(document).ready(function() {
          // Initialize select2
          $('select').select2();

          // Form validation
          $('form').on('submit', function(e) {
              let subjectCode = $('input[name="subjectCode"]').val();
              if(!/^[A-Za-z0-9]+$/.test(subjectCode)) {
                  e.preventDefault();
                  alert('Subject code can only contain letters and numbers');
                  return false;
              }
              
              // Show loading state
              $(this).find('button[type="submit"]')
                    .html('<i class="icon-refresh spin"></i> Updating...')
                    .prop('disabled', true);
          });

          // Reset form button handler
          $('button[type="reset"]').click(function(e) {
              e.preventDefault();
              if(confirm('Are you sure you want to reset the form? All changes will be lost.')) {
                  $('form')[0].reset();
                  $('select').trigger('change'); // Update Select2 dropdowns
              }
          });
      });
    </script>
  </body>
</html>
<?php } ?>
