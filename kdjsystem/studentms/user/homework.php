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
    <title>My Homework</title>
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
                      <h4 class="card-title mb-0">My Homework</h4>
                      <a href="my-grades.php" class="btn btn-primary">
                        <i class="icon-graduation"></i> View My Grades
                      </a>
                    </div>
                    
                    <?php
                    $studentId = $_SESSION['sturecmsuid'];
                    
                    // Get student's class info
                    $sql = "SELECT s.*, c.ClassName, c.Section 
                           FROM tblstudent s
                           JOIN tblclass c ON s.StudentClass = c.ID
                           WHERE s.ID = :studentId";
                    $query = $dbh->prepare($sql);
                    $query->bindParam(':studentId', $studentId, PDO::PARAM_INT);
                    $query->execute();
                    $studentInfo = $query->fetch(PDO::FETCH_OBJ);
                    
                    // Get homework assignments
                    $sql = "SELECT h.*, s.SubjectName, s.SubjectCode,
                           t.FullName as TeacherName,
                           hs.ID as SubmissionID, hs.Status as SubmissionStatus,
                           hs.Grade, hs.SubmissionDate
                           FROM tblhomework h
                           JOIN tblsubjects s ON h.SubjectID = s.ID
                           JOIN tblsubjectteacherclass stc ON h.SubjectID = stc.SubjectID 
                           AND h.ClassID = stc.ClassID
                           LEFT JOIN tblteacher t ON stc.TeacherID = t.ID
                           LEFT JOIN tblhomeworksubmissions hs ON h.ID = hs.HomeworkID 
                           AND hs.StudentID = :studentId
                           WHERE h.ClassID = :classId
                           ORDER BY h.SubmissionDate DESC";
                    $query = $dbh->prepare($sql);
                    $query->bindParam(':studentId', $studentId, PDO::PARAM_INT);
                    $query->bindParam(':classId', $studentInfo->StudentClass, PDO::PARAM_INT);
                    $query->execute();
                    $homeworks = $query->fetchAll(PDO::FETCH_OBJ);
                    
                    if($query->rowCount() > 0) {
                        // Group homework by status
                        $pending = array();
                        $submitted = array();
                        $graded = array();
                        $past = array();
                        
                        foreach($homeworks as $hw) {
                            if(!$hw->SubmissionID && strtotime($hw->SubmissionDate) >= strtotime(date('Y-m-d'))) {
                                $pending[] = $hw;
                            } else if($hw->SubmissionID && $hw->SubmissionStatus != 'Graded') {
                                $submitted[] = $hw;
                            } else if($hw->SubmissionStatus == 'Graded') {
                                $graded[] = $hw;
                            } else {
                                $past[] = $hw;
                            }
                        }
                        
                        // Display pending homework first
                        if(!empty($pending)) {
                    ?>
                    <div class="alert alert-warning">
                      <h5 class="alert-heading">
                        <i class="icon-clock"></i> Pending Homework
                      </h5>
                      <p class="mb-0">You have <?php echo count($pending);?> pending assignments</p>
                    </div>
                    
                    <?php foreach($pending as $hw) { ?>
                    <div class="card mb-3 border-warning">
                      <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                          <h5 class="card-title mb-0">
                            <?php echo htmlentities($hw->Title);?>
                            <br>
                            <small class="text-muted">
                              <?php echo htmlentities($hw->SubjectName);?> | 
                              Teacher: <?php echo $hw->TeacherName ? htmlentities($hw->TeacherName) : 'Not Assigned';?>
                            </small>
                          </h5>
                          <span class="badge badge-warning">Due: <?php echo date('d M Y', strtotime($hw->SubmissionDate));?></span>
                        </div>
                        <p class="card-text"><?php echo nl2br(htmlentities($hw->Description));?></p>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                          <?php if($hw->AttachmentURL) { ?>
                          <a href="../teacher/homework-files/<?php echo $hw->AttachmentURL;?>" 
                             class="btn btn-outline-primary btn-sm" target="_blank">
                            <i class="icon-paper-clip"></i> View Assignment
                          </a>
                          <?php } else { ?>
                          <div></div>
                          <?php } ?>
                          <a href="submit-homework.php?id=<?php echo $hw->ID;?>" 
                             class="btn btn-primary btn-sm">
                            Submit Homework
                          </a>
                        </div>
                      </div>
                    </div>
                    <?php }
                    }
                    
                    // Display submitted but not graded
                    if(!empty($submitted)) {
                    ?>
                    <div class="alert alert-info mt-4">
                      <h5 class="alert-heading">
                        <i class="icon-check"></i> Submitted Homework
                      </h5>
                      <p class="mb-0">
                        You have <?php echo count($submitted);?> submissions awaiting grading
                      </p>
                    </div>
                    
                    <?php foreach($submitted as $hw) { ?>
                    <div class="card mb-3 border-info">
                      <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                          <h5 class="card-title mb-0">
                            <?php echo htmlentities($hw->Title);?>
                            <br>
                            <small class="text-muted">
                              <?php echo htmlentities($hw->SubjectName);?> | 
                              Teacher: <?php echo $hw->TeacherName ? htmlentities($hw->TeacherName) : 'Not Assigned';?>
                            </small>
                          </h5>
                          <div class="text-right">
                            <span class="badge badge-info">Submitted</span>
                            <br>
                            <small class="text-muted">
                              on <?php echo date('d M Y H:i', strtotime($hw->SubmissionDate));?>
                            </small>
                          </div>
                        </div>
                        <p class="card-text"><?php echo nl2br(htmlentities($hw->Description));?></p>
                      </div>
                    </div>
                    <?php }
                    }
                    
                    // Display graded homework
                    if(!empty($graded)) {
                    ?>
                    <div class="alert alert-success mt-4">
                      <h5 class="alert-heading">
                        <i class="icon-badge"></i> Graded Homework
                      </h5>
                      <p class="mb-0">
                        You have <?php echo count($graded);?> graded assignments
                      </p>
                    </div>
                    
                    <?php foreach($graded as $hw) { ?>
                    <div class="card mb-3 border-success">
                      <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                          <h5 class="card-title mb-0">
                            <?php echo htmlentities($hw->Title);?>
                            <br>
                            <small class="text-muted">
                              <?php echo htmlentities($hw->SubjectName);?> | 
                              Teacher: <?php echo $hw->TeacherName ? htmlentities($hw->TeacherName) : 'Not Assigned';?>
                            </small>
                          </h5>
                          <div class="text-right">
                            <strong class="text-success">Grade: <?php echo htmlentities($hw->Grade);?></strong>
                            <br>
                            <small class="text-muted">
                              Submitted on <?php echo date('d M Y', strtotime($hw->SubmissionDate));?>
                            </small>
                          </div>
                        </div>
                        <p class="card-text"><?php echo nl2br(htmlentities($hw->Description));?></p>
                      </div>
                    </div>
                    <?php }
                    }
                    
                    // Display past due homework
                    if(!empty($past)) {
                    ?>
                    <div class="alert alert-secondary mt-4">
                      <h5 class="alert-heading">
                        <i class="icon-clock"></i> Past Due Homework
                      </h5>
                      <p class="mb-0">
                        You have <?php echo count($past);?> past due assignments
                      </p>
                    </div>
                    
                    <?php foreach($past as $hw) { ?>
                    <div class="card mb-3 border-secondary">
                      <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                          <h5 class="card-title mb-0">
                            <?php echo htmlentities($hw->Title);?>
                            <br>
                            <small class="text-muted">
                              <?php echo htmlentities($hw->SubjectName);?> | 
                              Teacher: <?php echo $hw->TeacherName ? htmlentities($hw->TeacherName) : 'Not Assigned';?>
                            </small>
                          </h5>
                          <span class="badge badge-danger">Due Date Passed</span>
                        </div>
                        <p class="card-text"><?php echo nl2br(htmlentities($hw->Description));?></p>
                      </div>
                    </div>
                    <?php }
                    }
                    } else { ?>
                    <div class="alert alert-info">
                      <h5 class="alert-heading">No Homework</h5>
                      <p class="mb-0">You don't have any homework assignments yet.</p>
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