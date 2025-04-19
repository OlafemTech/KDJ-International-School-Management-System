<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']) == 0) {
  header('location:logout.php');
} else {
    $viewid = isset($_GET['viewid']) ? intval($_GET['viewid']) : 0;
    
    if ($viewid == 0) {
        $_SESSION['error'] = "Invalid class ID";
        header('location: manage-class.php');
        exit();
    }

    try {
        // Get class details with student and subject counts
        $sql = "SELECT c.*, 
                (SELECT COUNT(*) FROM tblstudent s 
                 WHERE s.StudentClass = c.ClassName 
                 AND s.Level = c.Level 
                 AND s.Session = c.Session 
                 AND s.Term = c.Term) as StudentCount,
                (SELECT COUNT(*) FROM tblsubjects s 
                 WHERE s.ClassID = c.ID) as SubjectCount,
                CASE c.ClassName
                    WHEN 'SS' THEN 'Senior Secondary'
                    WHEN 'JS' THEN 'Junior Secondary'
                    WHEN 'Basic' THEN 'Basic'
                    WHEN 'Nursery' THEN 'Nursery'
                    WHEN 'PG' THEN 'Play Group'
                END as ClassNameFull
                FROM tblclass c
                WHERE c.ID = :viewid AND c.ClassName IN ('SS', 'JS', 'Basic', 'Nursery', 'PG')";
        $query = $dbh->prepare($sql);
        $query->bindParam(':viewid', $viewid, PDO::PARAM_INT);
        $query->execute();
        $class = $query->fetch(PDO::FETCH_OBJ);

        if (!$class) {
            $_SESSION['error'] = "Class not found";
            header('location: manage-class.php');
            exit();
        }

        // Get students in class
        $sql = "SELECT s.* FROM tblstudent s 
                WHERE s.StudentClass = :className 
                AND s.Level = :level 
                AND s.Session = :session 
                AND s.Term = :term 
                ORDER BY s.StudentName";
        $query = $dbh->prepare($sql);
        $query->bindParam(':className', $class->ClassName, PDO::PARAM_STR);
        $query->bindParam(':level', $class->Level, PDO::PARAM_STR);
        $query->bindParam(':session', $class->Session, PDO::PARAM_STR);
        $query->bindParam(':term', $class->Term, PDO::PARAM_STR);
        $query->execute();
        $students = $query->fetchAll(PDO::FETCH_OBJ);

        // Get subjects in class with teacher names
        $sql = "SELECT s.*, t.FullName as TeacherName,
                UPPER(s.SubjectCode) as SubjectCode 
                FROM tblsubjects s
                LEFT JOIN tblteacher t ON t.ID = s.TeacherID
                WHERE s.ClassID = :viewid 
                ORDER BY s.SubjectName";
        $query = $dbh->prepare($sql);
        $query->bindParam(':viewid', $viewid, PDO::PARAM_INT);
        $query->execute();
        $subjects = $query->fetchAll(PDO::FETCH_OBJ);
    } catch (PDOException $e) {
        error_log("Error in view-class.php: " . $e->getMessage());
        $_SESSION['error'] = "An error occurred while fetching class details";
        header('location: manage-class.php');
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Student Management System | View Class</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="vendors/datatables.net-bs4/dataTables.bootstrap4.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/custom-header.css">
    <link rel="stylesheet" href="css/custom-buttons.css">
  </head>
  <body>
    <div class="container-scroller">
      <?php include('includes/header.php');?>
      <div class="container-fluid page-body-wrapper">
        <?php include('includes/sidebar.php');?>
        <div class="main-panel">
          <div class="content-wrapper">
            <div class="page-header">
              <h3 class="page-title">View Class Details</h3>
              <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                  <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                  <li class="breadcrumb-item"><a href="manage-class.php">Manage Classes</a></li>
                  <li class="breadcrumb-item active" aria-current="page">View Class</li>
                </ol>
              </nav>
            </div>
            <div class="row">
              <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
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
                    
                    <div class="d-sm-flex align-items-center mb-4">
                      <h4 class="card-title mb-sm-0">Class Information</h4>
                      <a href="edit-class.php?editid=<?php echo $viewid; ?>" class="btn btn-action btn-edit ml-auto">
                        <i class="icon-pencil"></i> Edit Class
                      </a>
                    </div>
                    
                    <!-- Class Details -->
                    <div class="table-responsive border rounded p-1 mb-4">
                      <table class="table">
                        <tbody>
                          <tr>
                            <th width="200">Class Name</th>
                            <td><?php echo htmlentities($class->ClassNameFull);?></td>
                          </tr>
                          <tr>
                            <th>Level</th>
                            <td><?php echo htmlentities($class->Level);?></td>
                          </tr>
                          <tr>
                            <th>Session</th>
                            <td><?php echo htmlentities($class->Session);?></td>
                          </tr>
                          <tr>
                            <th>Term</th>
                            <td><?php echo htmlentities($class->Term);?></td>
                          </tr>
                          <tr>
                            <th>Number of Students</th>
                            <td><?php echo htmlentities($class->StudentCount);?></td>
                          </tr>
                          <tr>
                            <th>Number of Subjects</th>
                            <td><?php echo htmlentities($class->SubjectCount);?></td>
                          </tr>
                          <tr>
                            <th>Creation Date</th>
                            <td><?php echo htmlentities($class->CreationDate);?></td>
                          </tr>
                        </tbody>
                      </table>
                    </div>

                    <!-- Subjects List -->
                    <div class="d-sm-flex align-items-center mb-4">
                      <h4 class="card-title mb-sm-0">Subjects</h4>
                      <a href="add-subject.php?class_id=<?php echo $viewid; ?>" class="btn btn-action btn-edit ml-auto">
                        <i class="icon-plus"></i> Add Subject
                      </a>
                    </div>
                    <div class="table-responsive border rounded p-1 mb-4">
                      <table class="table">
                        <thead>
                          <tr>
                            <th>Subject Name</th>
                            <th>Subject Code</th>
                            <th>Teacher</th>
                            <th>Action</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php if($subjects && count($subjects) > 0) {
                            foreach($subjects as $subject) { ?>
                            <tr>
                              <td><?php echo htmlentities($subject->SubjectName);?></td>
                              <td><?php echo htmlentities($subject->SubjectCode);?></td>
                              <td><?php echo $subject->TeacherName ? htmlentities($subject->TeacherName) : 'Not Assigned';?></td>
                              <td>
                                <div class="action-buttons">
                                  <a href="edit-subject.php?id=<?php echo htmlentities($subject->ID);?>" 
                                     class="btn btn-action btn-edit" title="Edit Subject">
                                     <i class="icon-pencil"></i>
                                  </a>
                                  <a href="view-subject.php?id=<?php echo htmlentities($subject->ID);?>" 
                                     class="btn btn-action btn-view" title="View Subject">
                                     <i class="icon-eye"></i>
                                  </a>
                                </div>
                              </td>
                            </tr>
                            <?php }
                          } else { ?>
                            <tr>
                              <td colspan="4" class="text-center">No subjects assigned to this class</td>
                            </tr>
                          <?php } ?>
                        </tbody>
                      </table>
                    </div>

                    <!-- Students List -->
                    <div class="d-sm-flex align-items-center mb-4">
                      <h4 class="card-title mb-sm-0">Students</h4>
                      <a href="add-student.php?class=<?php echo urlencode($class->ClassName); ?>&level=<?php echo urlencode($class->Level); ?>&session=<?php echo urlencode($class->Session); ?>&term=<?php echo urlencode($class->Term); ?>" 
                         class="btn btn-action btn-edit ml-auto">
                        <i class="icon-plus"></i> Add Student
                      </a>
                    </div>
                    <div class="table-responsive border rounded p-1">
                      <table class="table">
                        <thead>
                          <tr>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Gender</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Action</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php if($students && count($students) > 0) {
                            foreach($students as $student) { ?>
                            <tr>
                              <td><?php echo htmlentities($student->StudentId);?></td>
                              <td><?php echo htmlentities($student->StudentName);?></td>
                              <td><?php echo htmlentities($student->Gender);?></td>
                              <td><?php echo htmlentities($student->ContactNumber);?></td>
                              <td>
                                <span class="badge <?php echo $student->Status ? 'badge-success' : 'badge-danger'; ?>">
                                  <?php echo $student->Status ? 'Active' : 'Inactive'; ?>
                                </span>
                              </td>
                              <td>
                                <div class="action-buttons">
                                  <a href="edit-student.php?editid=<?php echo htmlentities($student->ID);?>" 
                                     class="btn btn-action btn-edit" title="Edit Student">
                                     <i class="icon-pencil"></i>
                                  </a>
                                  <a href="view-student.php?viewid=<?php echo htmlentities($student->ID);?>" 
                                     class="btn btn-action btn-view" title="View Student">
                                     <i class="icon-eye"></i>
                                  </a>
                                </div>
                              </td>
                            </tr>
                            <?php }
                          } else { ?>
                            <tr>
                              <td colspan="6" class="text-center">No students in this class</td>
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
