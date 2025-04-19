<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']==0)) {
    header('location:logout.php');
} else {
    // Handle grade submission
    if(isset($_POST['grade'])) {
        try {
            $submissionId = intval($_POST['submissionId']);
            $grade = intval($_POST['grade']);
            $comments = $_POST['comments'];
            
            if($grade < 0 || $grade > 100) {
                throw new Exception("Grade must be between 0 and 100");
            }
            
            $sql = "UPDATE tblhomeworksubmissions 
                    SET Grade = :grade, 
                        TeacherComments = :comments,
                        Status = 'Graded'
                    WHERE ID = :id";
            $query = $dbh->prepare($sql);
            $query->bindParam(':grade', $grade, PDO::PARAM_INT);
            $query->bindParam(':comments', $comments, PDO::PARAM_STR);
            $query->bindParam(':id', $submissionId, PDO::PARAM_INT);
            $query->execute();
            
            $_SESSION['success'] = "Submission graded successfully";
        } catch(Exception $e) {
            $_SESSION['error'] = "Error grading submission: " . $e->getMessage();
        }
        header('location: ' . $_SERVER['PHP_SELF'] . '?hwid=' . $_GET['hwid']);
        exit();
    }

    // Get homework details
    $hwid = intval($_GET['hwid']);
    $sql = "SELECT h.*, s.SubjectName, c.ClassName, c.Section, t.Name as TeacherName,
                   COUNT(DISTINCT hs.ID) as SubmissionCount,
                   COUNT(DISTINCT CASE WHEN hs.Status = 'Graded' THEN hs.ID END) as GradedCount,
                   ROUND(AVG(CASE WHEN hs.Status = 'Graded' THEN hs.Grade END), 2) as AvgGrade
            FROM tblhomework h
            LEFT JOIN tblsubjects s ON h.SubjectID = s.ID
            LEFT JOIN tblclass c ON h.ClassID = c.ID
            LEFT JOIN tblteacher t ON h.TeacherID = t.ID
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
    <title>Student Management System | View Submissions</title>
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
                        <h3 class="page-title">View Submissions</h3>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="manage-homework.php">Manage Homework</a></li>
                                <li class="breadcrumb-item active" aria-current="page">View Submissions</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="row">
                        <div class="col-md-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h4 class="card-title mb-0">Homework Details</h4>
                                        <a href="manage-homework.php" class="btn btn-secondary">
                                            <i class="icon-arrow-left-circle"></i> Back to List
                                        </a>
                                    </div>
                                    <?php
                                    if(isset($_SESSION['success'])) {
                                        echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
                                        unset($_SESSION['success']);
                                    }
                                    if(isset($_SESSION['error'])) {
                                        echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
                                        unset($_SESSION['error']);
                                    }
                                    ?>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <table class="table">
                                                <tr>
                                                    <th>Title:</th>
                                                    <td><?php echo htmlspecialchars($homework->Title);?></td>
                                                </tr>
                                                <tr>
                                                    <th>Subject:</th>
                                                    <td><?php echo htmlspecialchars($homework->SubjectName);?></td>
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
                                                        ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Max Grade:</th>
                                                    <td><?php echo htmlspecialchars($homework->MaxGrade);?></td>
                                                </tr>
                                                <tr>
                                                    <th>Submissions:</th>
                                                    <td>
                                                        <?php echo $homework->SubmissionCount;?> Total
                                                        <br>
                                                        <small class="text-muted">
                                                            <?php echo $homework->GradedCount;?> Graded
                                                        </small>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Average Grade:</th>
                                                    <td><?php echo $homework->AvgGrade ?: 'N/A';?></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>

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

                                    <div class="mt-4">
                                        <h5>Student Submissions</h5>
                                        <?php
                                        $sql = "SELECT hs.*, s.StudentName, s.StuID
                                               FROM tblhomeworksubmissions hs
                                               JOIN tblstudent s ON hs.StudentID = s.ID
                                               WHERE hs.HomeworkID = :hwid
                                               ORDER BY hs.SubmissionDate DESC";
                                        $query = $dbh->prepare($sql);
                                        $query->bindParam(':hwid', $hwid, PDO::PARAM_INT);
                                        $query->execute();
                                        $submissions = $query->fetchAll(PDO::FETCH_OBJ);
                                        
                                        if($submissions) {
                                        ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Student ID</th>
                                                        <th>Name</th>
                                                        <th>Submitted</th>
                                                        <th>Status</th>
                                                        <th>Grade</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach($submissions as $sub) { 
                                                        $isLate = strtotime($sub->SubmissionDate) > strtotime($homework->DueDate);
                                                    ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($sub->StuID);?></td>
                                                        <td><?php echo htmlspecialchars($sub->StudentName);?></td>
                                                        <td>
                                                            <?php 
                                                            echo date('Y-m-d', strtotime($sub->SubmissionDate));
                                                            echo '<br><small class="text-muted">';
                                                            echo date('h:i A', strtotime($sub->SubmissionDate));
                                                            if($isLate) {
                                                                echo ' <span class="badge badge-warning">Late</span>';
                                                            }
                                                            echo '</small>';
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $statusClass = 'secondary';
                                                            if($sub->Status == 'Graded') {
                                                                $statusClass = 'success';
                                                            }
                                                            ?>
                                                            <span class="badge badge-<?php echo $statusClass;?>">
                                                                <?php echo htmlspecialchars($sub->Status);?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php 
                                                            if($sub->Status == 'Graded') {
                                                                echo $sub->Grade . '/' . $homework->MaxGrade;
                                                                if($sub->TeacherComments) {
                                                                    echo '<br><small class="text-muted">';
                                                                    echo htmlspecialchars($sub->TeacherComments);
                                                                    echo '</small>';
                                                                }
                                                            } else {
                                                                echo 'Not graded';
                                                            }
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group">
                                                                <?php if($sub->AttachmentURL) { ?>
                                                                    <a href="../uploads/homework-submissions/<?php echo htmlspecialchars($sub->AttachmentURL);?>" 
                                                                       target="_blank" class="btn btn-info btn-sm" title="View Submission">
                                                                        <i class="icon-eye"></i>
                                                                    </a>
                                                                <?php } ?>
                                                                <button type="button" class="btn btn-primary btn-sm" 
                                                                        data-toggle="modal" 
                                                                        data-target="#gradeModal<?php echo $sub->ID;?>"
                                                                        title="Grade Submission">
                                                                    <i class="icon-pencil"></i>
                                                                </button>
                                                            </div>

                                                            <!-- Grade Modal -->
                                                            <div class="modal fade" id="gradeModal<?php echo $sub->ID;?>" tabindex="-1" role="dialog">
                                                                <div class="modal-dialog" role="document">
                                                                    <div class="modal-content">
                                                                        <form method="post">
                                                                            <div class="modal-header">
                                                                                <h5 class="modal-title">Grade Submission</h5>
                                                                                <button type="button" class="close" data-dismiss="modal">
                                                                                    <span>&times;</span>
                                                                                </button>
                                                                            </div>
                                                                            <div class="modal-body">
                                                                                <input type="hidden" name="submissionId" value="<?php echo $sub->ID;?>">
                                                                                <div class="form-group">
                                                                                    <label>Grade (out of <?php echo $homework->MaxGrade;?>)</label>
                                                                                    <input type="number" name="grade" class="form-control" 
                                                                                           min="0" max="<?php echo $homework->MaxGrade;?>" 
                                                                                           value="<?php echo $sub->Grade;?>" required>
                                                                                </div>
                                                                                <div class="form-group">
                                                                                    <label>Comments</label>
                                                                                    <textarea name="comments" class="form-control" rows="3"><?php echo htmlspecialchars($sub->TeacherComments);?></textarea>
                                                                                </div>
                                                                            </div>
                                                                            <div class="modal-footer">
                                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                                                <button type="submit" name="grade" class="btn btn-primary">Save Grade</button>
                                                                            </div>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <?php } else { ?>
                                        <div class="alert alert-info">
                                            No submissions yet.
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
