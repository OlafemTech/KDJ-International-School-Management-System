<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']) == 0) {
  header('location:logout.php');
} else {
  if (isset($_POST['submit'])) {
    try {
      // Validate and sanitize inputs
      $subjectName = trim(filter_input(INPUT_POST, 'subjectName', FILTER_SANITIZE_STRING));
      $subjectCode = strtoupper(trim(filter_input(INPUT_POST, 'subjectCode', FILTER_SANITIZE_STRING)));
      $classIds = isset($_POST['classIds']) ? $_POST['classIds'] : [];
      $teacherId = !empty($_POST['teacherId']) ? filter_var($_POST['teacherId'], FILTER_VALIDATE_INT) : null;

      // Validate required fields
      if (empty($subjectName) || empty($subjectCode) || empty($classIds)) {
        throw new Exception("Subject Name, Code and at least one Class are required fields.");
      }

      // Validate subject code format (letters and numbers only)
      if (!preg_match("/^[A-Za-z0-9]+$/", $subjectCode)) {
        throw new Exception("Subject code can only contain letters and numbers.");
      }

      // Validate field lengths
      if (strlen($subjectCode) > 20) {
        throw new Exception("Subject code cannot exceed 20 characters.");
      }

      if (strlen($subjectName) > 100) {
        throw new Exception("Subject name cannot exceed 100 characters.");
      }

      // Begin transaction
      $dbh->beginTransaction();

      // Check if subject code exists for any of the selected classes
      $placeholders = str_repeat('?,', count($classIds) - 1) . '?';
      $sql = "SELECT s.ID, s.ClassID, c.ClassName, c.Level, c.Session, c.Term 
              FROM tblsubjects s 
              JOIN tblclass c ON s.ClassID = c.ID 
              WHERE s.SubjectCode = ? AND s.ClassID IN ($placeholders)";
      
      $query = $dbh->prepare($sql);
      $params = array_merge([$subjectCode], $classIds);
      $query->execute($params);
      
      if ($query->rowCount() > 0) {
        $conflicts = $query->fetchAll(PDO::FETCH_OBJ);
        $conflictClasses = array_map(function($class) {
          return "{$class->ClassName} Level {$class->Level} ({$class->Session} - {$class->Term})";
        }, $conflicts);
        throw new Exception("This subject code already exists for the following classes: " . implode(", ", $conflictClasses));
      }

      // Insert subject for each class
      $sql = "INSERT INTO tblsubjects (SubjectName, SubjectCode, ClassID, TeacherID) VALUES (?, ?, ?, ?)";
      $query = $dbh->prepare($sql);
      
      foreach ($classIds as $classId) {
        $query->execute([$subjectName, $subjectCode, $classId, $teacherId]);
      }

      $dbh->commit();
      $_SESSION['success'] = "Subject has been added successfully to " . count($classIds) . " class(es).";
      header('location:add-subject.php');
      exit();

    } catch (Exception $e) {
      $dbh->rollBack();
      $_SESSION['error'] = $e->getMessage();
      header('location:add-subject.php');
      exit();
    }
  }
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Student Management System | Add Subject</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/custom-header.css">
    <link rel="stylesheet" href="css/custom-buttons.css">
    <style>
      .form-group {
        margin-bottom: 1.5rem;
      }
      .form-control {
        border: 1px solid #e4e9f0;
        padding: 0.875rem 1.375rem;
        height: 45px;
        border-radius: 4px;
        width: 100%;
        font-size: 0.875rem;
        background-color: #f8f9fc;
      }
      .form-control:focus {
        background-color: #fff;
        border-color: #00c8bf;
        box-shadow: none;
      }
      .select2-container--default .select2-selection--multiple {
        border: 1px solid #e4e9f0;
        min-height: 45px;
        background-color: #f8f9fc;
      }
      .select2-container--default.select2-container--focus .select2-selection--multiple {
        border-color: #00c8bf;
      }
      .help-text {
        color: #6c757d;
        font-size: 0.75rem;
        margin-top: 0.25rem;
      }
      .form-label {
        font-weight: 500;
        margin-bottom: 0.5rem;
        display: block;
        color: #344767;
      }
      .card {
        border: none;
        box-shadow: 0 0 10px rgba(0,0,0,0.05);
        background: #fff;
      }
      .card-body {
        padding: 2rem;
      }
      .card-title {
        margin-bottom: 1.5rem;
        font-size: 1.25rem;
        font-weight: 500;
        color: #344767;
      }
      .btn-primary {
        background-color: #00c8bf;
        border-color: #00c8bf;
        padding: 0.5rem 1.5rem;
        font-weight: 500;
      }
      .btn-primary:hover {
        background-color: #00b3a9;
        border-color: #00b3a9;
      }
      .alert {
        border: none;
        border-radius: 4px;
        padding: 1rem;
        margin-bottom: 1rem;
      }
      .alert-success {
        background-color: #00c8bf20;
        color: #00c8bf;
      }
      .alert-danger {
        background-color: #dc354520;
        color: #dc3545;
      }
    </style>
  </head>
  <body>
    <div class="container-scroller">
      <?php include('includes/header.php');?>
      <div class="container-fluid page-body-wrapper">
        <?php include('includes/sidebar.php');?>
        <div class="main-panel">
          <div class="content-wrapper">
            <div class="row">
              <div class="col-12 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title">Add Subject</h4>
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
                    <?php if(isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php 
                            echo htmlentities($_SESSION['success']); 
                            unset($_SESSION['success']);
                            ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    <form class="forms-sample" method="post" id="subjectForm">
                      <div class="form-group">
                        <label class="form-label" for="subjectName">Subject Name<span class="text-danger">*</span></label>
                        <input type="text" name="subjectName" class="form-control" id="subjectName" maxlength="100" required>
                        <small class="help-text">Maximum 100 characters</small>
                      </div>
                      <div class="form-group">
                        <label class="form-label" for="subjectCode">Subject Code<span class="text-danger">*</span></label>
                        <input type="text" name="subjectCode" class="form-control" id="subjectCode" maxlength="20" required>
                        <small class="help-text">Maximum 20 characters, letters and numbers only. Will be converted to uppercase.</small>
                      </div>
                      <div class="form-group">
                        <label class="form-label" for="teacherId">Assign Teacher (Optional)</label>
                        <select name="teacherId" class="form-control" id="teacherId">
                          <option value="">Select Teacher</option>
                          <?php
                          try {
                              $sql = "SELECT ID, FullName as TeacherName FROM tblteacher ORDER BY FullName";
                              $query = $dbh->prepare($sql);
                              $query->execute();
                              $teachers = $query->fetchAll(PDO::FETCH_OBJ);
                              foreach($teachers as $teacher) {
                                  echo "<option value='" . $teacher->ID . "'>" . htmlentities($teacher->TeacherName) . "</option>";
                              }
                          } catch (PDOException $e) {
                              error_log("Error fetching teachers: " . $e->getMessage());
                          }
                          ?>
                        </select>
                      </div>
                      <div class="form-group">
                        <label class="form-label" for="classIds">Assign Classes<span class="text-danger">*</span></label>
                        <select name="classIds[]" class="form-control select2-multiple" id="classIds" multiple required>
                          <?php
                          try {
                              $sql = "SELECT ID, CONCAT(ClassName, ' Level ', Level, ' (', Session, ' - ', Term, ')') as ClassInfo 
                                     FROM tblclass 
                                     ORDER BY ClassName, Level, Session, Term";
                              $query = $dbh->prepare($sql);
                              $query->execute();
                              $classes = $query->fetchAll(PDO::FETCH_OBJ);
                              if ($classes) {
                                  foreach($classes as $class) {
                                      echo "<option value='" . $class->ID . "'>" . htmlentities($class->ClassInfo) . "</option>";
                                  }
                              } else {
                                  echo "<option value='' disabled>No classes found</option>";
                              }
                          } catch (PDOException $e) {
                              error_log("Error fetching classes: " . $e->getMessage());
                              echo "<option value='' disabled>Error loading classes</option>";
                          }
                          ?>
                        </select>
                        <small class="help-text">You can select multiple classes</small>
                      </div>
                      <button type="submit" name="submit" class="btn btn-primary mr-2">Add Subject</button>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <script src="vendors/js/vendor.bundle.base.js"></script>
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
      $(document).ready(function() {
        // Initialize Select2 for multiple select
        $('.select2-multiple').select2({
          placeholder: "Select Classes",
          allowClear: true,
          width: '100%'
        });

        // Convert subject code to uppercase
        $('#subjectCode').on('input', function() {
          this.value = this.value.toUpperCase();
        });

        // Form validation
        $('#subjectForm').on('submit', function(e) {
          const subjectCode = $('#subjectCode').val();
          if (!/^[A-Za-z0-9]+$/.test(subjectCode)) {
            e.preventDefault();
            alert('Subject code can only contain letters and numbers.');
            return false;
          }
          
          const selectedClasses = $('#classIds').val();
          if (!selectedClasses || selectedClasses.length === 0) {
            e.preventDefault();
            alert('Please select at least one class.');
            return false;
          }
        });
      });
    </script>
  </body>
</html>
<?php } ?>
