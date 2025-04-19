<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
} else {
    $gradeId = intval($_GET['id']);

    if(isset($_POST['submit'])) {
        $ca1 = floatval($_POST['ca1']);
        $ca2 = floatval($_POST['ca2']);
        $exam = floatval($_POST['exam']);
        $comment = $_POST['comment'];

        // Calculate totals and performance
        $totalTest = $ca1 + $ca2;
        $totalScore = $totalTest + $exam;
        
        // Determine performance
        $performance = '';
        if ($totalScore >= 75) $performance = 'Excellent';
        else if ($totalScore >= 65) $performance = 'VeryGood';
        else if ($totalScore >= 55) $performance = 'Good';
        else if ($totalScore >= 45) $performance = 'Average';
        else $performance = 'NeedsImprovement';

        // Update grade
        $sql = "UPDATE tblgrades SET 
                CA1 = :ca1,
                CA2 = :ca2,
                TotalTest = :totalTest,
                Exam = :exam,
                TotalScore = :totalScore,
                Performance = :performance,
                TeacherComment = :comment,
                LastUpdated = CURRENT_TIMESTAMP
                WHERE ID = :gradeId";

        $query = $dbh->prepare($sql);
        $query->bindParam(':ca1', $ca1, PDO::PARAM_STR);
        $query->bindParam(':ca2', $ca2, PDO::PARAM_STR);
        $query->bindParam(':totalTest', $totalTest, PDO::PARAM_STR);
        $query->bindParam(':exam', $exam, PDO::PARAM_STR);
        $query->bindParam(':totalScore', $totalScore, PDO::PARAM_STR);
        $query->bindParam(':performance', $performance, PDO::PARAM_STR);
        $query->bindParam(':comment', $comment, PDO::PARAM_STR);
        $query->bindParam(':gradeId', $gradeId, PDO::PARAM_INT);

        if($query->execute()) {
            echo "<script>alert('Grade updated successfully');</script>";
            echo "<script>window.location.href='view-grades.php';</script>";
        } else {
            echo "<script>alert('Something went wrong. Please try again');</script>";
        }
    }

    // Get grade details
    $sql = "SELECT g.*, s.StudentName, sub.SubjectName, c.ClassName, c.Level, c.Term 
            FROM tblgrades g
            JOIN tblstudent s ON g.StudentID = s.ID
            JOIN tblsubjects sub ON g.SubjectID = sub.ID
            JOIN tblclass c ON g.ClassID = c.ID
            WHERE g.ID = :gradeId";
    $query = $dbh->prepare($sql);
    $query->bindParam(':gradeId', $gradeId, PDO::PARAM_INT);
    $query->execute();
    $gradeDetails = $query->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Grade</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <!-- Layout styles -->
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
                        <div class="col-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Edit Grade</h4>
                                    
                                    <div class="student-info mb-4">
                                        <p><strong>Student:</strong> <?php echo $gradeDetails['StudentName']; ?></p>
                                        <p><strong>Subject:</strong> <?php echo $gradeDetails['SubjectName']; ?></p>
                                        <p><strong>Class:</strong> <?php echo $gradeDetails['ClassName'].' '.$gradeDetails['Level']; ?></p>
                                        <p><strong>Term:</strong> <?php echo $gradeDetails['Term']; ?></p>
                                    </div>

                                    <form class="forms-sample" method="post" onsubmit="return validateForm()">
                                        <div class="form-group">
                                            <label for="ca1">CA1 (20%)</label>
                                            <input type="number" class="form-control" id="ca1" name="ca1" 
                                                value="<?php echo $gradeDetails['CA1']; ?>" 
                                                min="0" max="20" step="0.01" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="ca2">CA2 (20%)</label>
                                            <input type="number" class="form-control" id="ca2" name="ca2" 
                                                value="<?php echo $gradeDetails['CA2']; ?>" 
                                                min="0" max="20" step="0.01" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="exam">Exam (60%)</label>
                                            <input type="number" class="form-control" id="exam" name="exam" 
                                                value="<?php echo $gradeDetails['Exam']; ?>" 
                                                min="0" max="60" step="0.01" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="comment">Teacher's Comment</label>
                                            <textarea class="form-control" id="comment" name="comment" rows="4"><?php echo $gradeDetails['TeacherComment']; ?></textarea>
                                        </div>
                                        
                                        <button type="submit" name="submit" class="btn btn-primary mr-2">Update Grade</button>
                                        <a href="view-grades.php" class="btn btn-light">Cancel</a>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- plugins:js -->
    <script src="vendors/js/vendor.bundle.base.js"></script>
    <script>
        function validateForm() {
            const ca1 = parseFloat(document.getElementById('ca1').value);
            const ca2 = parseFloat(document.getElementById('ca2').value);
            const exam = parseFloat(document.getElementById('exam').value);
            
            if (ca1 < 0 || ca1 > 20) {
                alert('CA1 must be between 0 and 20');
                return false;
            }
            if (ca2 < 0 || ca2 > 20) {
                alert('CA2 must be between 0 and 20');
                return false;
            }
            if (exam < 0 || exam > 60) {
                alert('Exam score must be between 0 and 60');
                return false;
            }
            
            return true;
        }
    </script>
</body>
</html>
<?php } ?>
