<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']==0)) {
    header('location:logout.php');
} else {
    $hwid = intval($_GET['id']);

    // Get homework details with subject, class, and teacher info
    $sql = "SELECT h.*, s.SubjectName, s.SubjectCode, 
                   c.ClassName, c.Section,
                   t.Name as TeacherName,
                   COUNT(DISTINCT hs.ID) as SubmissionCount,
                   COUNT(DISTINCT CASE WHEN hs.Status = 'Graded' THEN hs.ID END) as GradedCount,
                   ROUND(AVG(CASE WHEN hs.Status = 'Graded' THEN hs.Grade END), 2) as AvgGrade,
                   ROUND(AVG(CASE WHEN hs.Status = 'Graded' THEN (hs.Grade / h.MaxGrade * 100) END), 2) as AvgPercentage
            FROM tblhomework h
            JOIN tblsubjects s ON h.SubjectID = s.ID
            JOIN tblclass c ON h.ClassID = c.ID
            JOIN tblteacher t ON h.TeacherID = t.ID
            LEFT JOIN tblhomeworksubmissions hs ON h.ID = hs.HomeworkID
            WHERE h.ID = :hwid
            GROUP BY h.ID";
    $query = $dbh->prepare($sql);
    $query->bindParam(':hwid', $hwid, PDO::PARAM_INT);
    $query->execute();
    $homework = $query->fetch(PDO::FETCH_OBJ);

    if(!$homework) {
        $_SESSION['error'] = "Homework not found";
        header('location: manage-homework.php');
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Student Management System | View Homework</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>
    <div class="container-scroller">
        <?php include_once('includes/header.php');?>
        <div class="container-fluid page-body-wrapper">
            <?php include_once('includes/sidebar.php');?>
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="page-header">
                        <h3 class="page-title">View Homework</h3>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="manage-homework.php">Manage Homework</a></li>
                                <li class="breadcrumb-item active" aria-current="page">View Homework</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="row">
                        <div class="col-md-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h4 class="card-title mb-0">Homework Details</h4>
                                        <div>
                                            <a href="manage-homework.php" class="btn btn-secondary">
                                                <i class="icon-arrow-left-circle"></i> Back to List
                                            </a>
                                            <a href="edit-homework.php?id=<?php echo $homework->ID;?>" class="btn btn-info">
                                                <i class="icon-pencil"></i> Edit
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Homework Details -->
                                    <div class="row">
                                        <div class="col-md-6">
                                            <table class="table">
                                                <tr>
                                                    <th>Title:</th>
                                                    <td><?php echo htmlspecialchars($homework->Title);?></td>
                                                </tr>
                                                <tr>
                                                    <th>Subject:</th>
                                                    <td>
                                                        <?php echo htmlspecialchars($homework->SubjectName);?>
                                                        <small class="text-muted">(<?php echo htmlspecialchars($homework->SubjectCode);?>)</small>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Class:</th>
                                                    <td><?php echo htmlspecialchars($homework->ClassName . ' ' . $homework->Section);?></td>
                                                </tr>
                                                <tr>
                                                    <th>Teacher:</th>
                                                    <td><?php echo htmlspecialchars($homework->TeacherName);?></td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            <table class="table">
                                                <tr>
                                                    <th>Due Date:</th>
                                                    <td>
                                                        <?php 
                                                        echo date('Y-m-d', strtotime($homework->DueDate));
                                                        echo '<br><small class="text-muted">';
                                                        echo date('h:i A', strtotime($homework->DueDate));
                                                        echo '</small>';
                                                        
                                                        $isOverdue = strtotime($homework->DueDate) < time();
                                                        if($isOverdue && $homework->Status == 'Active') {
                                                            echo ' <span class="badge badge-warning">Overdue</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Max Grade:</th>
                                                    <td><?php echo htmlspecialchars($homework->MaxGrade);?></td>
                                                </tr>
                                                <tr>
                                                    <th>Status:</th>
                                                    <td>
                                                        <?php
                                                        $statusClass = $homework->Status == 'Active' ? 
                                                                     ($isOverdue ? 'badge-warning' : 'badge-success') : 
                                                                     'badge-danger';
                                                        ?>
                                                        <span class="badge <?php echo $statusClass;?>">
                                                            <?php echo htmlspecialchars($homework->Status);?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Created:</th>
                                                    <td>
                                                        <?php 
                                                        echo date('Y-m-d', strtotime($homework->CreatedAt));
                                                        echo '<br><small class="text-muted">';
                                                        echo date('h:i A', strtotime($homework->CreatedAt));
                                                        echo '</small>';
                                                        ?>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Description and Attachment -->
                                    <div class="mt-4">
                                        <h5>Description</h5>
                                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($homework->Description));?></p>
                                        <?php if($homework->AttachmentURL) { ?>
                                            <p>
                                                <a href="../uploads/homework/<?php echo htmlspecialchars($homework->AttachmentURL);?>" 
                                                   target="_blank" class="btn btn-outline-primary btn-sm">
                                                    <i class="icon-paper-clip"></i> View Attachment
                                                </a>
                                            </p>
                                        <?php } ?>
                                    </div>

                                    <!-- Submission Statistics -->
                                    <div class="mt-4">
                                        <h5>Submission Statistics</h5>
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="card bg-primary text-white">
                                                    <div class="card-body text-center">
                                                        <h3 class="mb-2"><?php echo $homework->SubmissionCount;?></h3>
                                                        <p class="mb-0">Total Submissions</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="card bg-success text-white">
                                                    <div class="card-body text-center">
                                                        <h3 class="mb-2"><?php echo $homework->GradedCount;?></h3>
                                                        <p class="mb-0">Graded</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="card bg-info text-white">
                                                    <div class="card-body text-center">
                                                        <h3 class="mb-2"><?php echo $homework->AvgGrade ?: 'N/A';?></h3>
                                                        <p class="mb-0">Average Grade</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="card bg-warning text-white">
                                                    <div class="card-body text-center">
                                                        <h3 class="mb-2"><?php echo $homework->AvgPercentage ? $homework->AvgPercentage . '%' : 'N/A';?></h3>
                                                        <p class="mb-0">Average Percentage</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Student Submissions -->
                                    <div class="mt-4">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="mb-0">Student Submissions</h5>
                                            <a href="view-submissions.php?hwid=<?php echo $homework->ID;?>" class="btn btn-primary btn-sm">
                                                <i class="icon-eye"></i> View All Submissions
                                            </a>
                                        </div>
                                        <?php
                                        // Get class roster with submission status
                                        $sql = "SELECT s.ID, s.StuID, s.StudentName,
                                               hs.ID as SubmissionID, hs.SubmissionDate, hs.Status as SubmissionStatus,
                                               hs.Grade, hs.TeacherComments
                                               FROM tblstudent s
                                               LEFT JOIN tblhomeworksubmissions hs ON s.ID = hs.StudentID AND hs.HomeworkID = :hwid
                                               WHERE s.StudentClass = :classId
                                               ORDER BY s.StudentName";
                                        $query = $dbh->prepare($sql);
                                        $query->bindParam(':hwid', $hwid, PDO::PARAM_INT);
                                        $query->bindParam(':classId', $homework->ClassID, PDO::PARAM_INT);
                                        $query->execute();
                                        $students = $query->fetchAll(PDO::FETCH_OBJ);
                                        
                                        if($students) {
                                        ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Student ID</th>
                                                        <th>Name</th>
                                                        <th>Status</th>
                                                        <th>Submitted</th>
                                                        <th>Grade</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach($students as $student) { 
                                                        $isLate = $student->SubmissionDate && 
                                                                 strtotime($student->SubmissionDate) > strtotime($homework->DueDate);
                                                    ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($student->StuID);?></td>
                                                        <td><?php echo htmlspecialchars($student->StudentName);?></td>
                                                        <td>
                                                            <?php if($student->SubmissionID): ?>
                                                                <?php
                                                                $statusClass = 'secondary';
                                                                if($student->SubmissionStatus == 'Graded') {
                                                                    $statusClass = 'success';
                                                                } elseif($student->SubmissionStatus == 'Submitted') {
                                                                    $statusClass = 'info';
                                                                }
                                                                ?>
                                                                <span class="badge badge-<?php echo $statusClass;?>">
                                                                    <?php echo htmlspecialchars($student->SubmissionStatus);?>
                                                                </span>
                                                            <?php else: ?>
                                                                <?php if(strtotime($homework->DueDate) < time()): ?>
                                                                    <span class="badge badge-danger">Not Submitted</span>
                                                                <?php else: ?>
                                                                    <span class="badge badge-secondary">Pending</span>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if($student->SubmissionDate): ?>
                                                                <?php 
                                                                echo date('Y-m-d', strtotime($student->SubmissionDate));
                                                                echo '<br><small class="text-muted">';
                                                                echo date('h:i A', strtotime($student->SubmissionDate));
                                                                if($isLate) {
                                                                    echo ' <span class="badge badge-warning">Late</span>';
                                                                }
                                                                echo '</small>';
                                                                ?>
                                                            <?php else: ?>
                                                                -
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if($student->SubmissionStatus == 'Graded'): ?>
                                                                <?php echo $student->Grade . '/' . $homework->MaxGrade;?>
                                                                <?php if($student->TeacherComments): ?>
                                                                    <br>
                                                                    <small class="text-muted">
                                                                        <?php echo htmlspecialchars($student->TeacherComments);?>
                                                                    </small>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                -
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <?php } else { ?>
                                        <div class="alert alert-info">
                                            No students found in this class.
                                        </div>
                                        <?php } ?>
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
