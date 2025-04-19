<?php
if(session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(0);

include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsstuid'])==0) {
    header('location:logout.php');
    exit();
}

$stuid = $_SESSION['sturecmsstuid'];

// Get student details
$sql = "SELECT s.*, c.ClassName, c.Level, c.Session, c.Term 
        FROM tblstudent s 
        LEFT JOIN tblclass c ON s.StudentClass = c.ID 
        WHERE s.UserName = :stuid";
$query = $dbh->prepare($sql);
$query->bindParam(':stuid', $stuid, PDO::PARAM_STR);
$query->execute();
$student = $query->fetch(PDO::FETCH_OBJ);

// Get total subjects
$sql = "SELECT COUNT(*) as total FROM tblsubjectcombination sc 
        WHERE sc.ClassId = :classId AND sc.Status = 1";
$query = $dbh->prepare($sql);
$query->bindParam(':classId', $student->StudentClass, PDO::PARAM_INT);
$query->execute();
$total_subjects = $query->fetch(PDO::FETCH_OBJ)->total;

// Get average marks
$sql = "SELECT AVG(Marks) as average FROM tblresult 
        WHERE StudentId = :studentId AND Term = :term";
$query = $dbh->prepare($sql);
$query->bindParam(':studentId', $student->ID, PDO::PARAM_INT);
$query->bindParam(':term', $student->Term, PDO::PARAM_STR);
$query->execute();
$average_marks = number_format($query->fetch(PDO::FETCH_OBJ)->average ?? 0, 2);

// Get unread notices
$sql = "SELECT COUNT(*) as count FROM tblnotice WHERE Status = 1";
$query = $dbh->prepare($sql);
$query->execute();
$unread_notices = $query->fetch(PDO::FETCH_OBJ)->count;

$pageTitle = "Dashboard";
include('includes/header.php');
?>

<div class="container-fluid page-body-wrapper">
    <?php include('includes/sidebar.php'); ?>
    <div class="main-panel">
        <div class="content-wrapper">
            <div class="row">
                <div class="col-md-12 grid-margin">
                    <div class="row">
                        <div class="col-12 col-xl-8 mb-4 mb-xl-0">
                            <h3 class="font-weight-bold">Welcome <?php echo htmlentities($student->StudentName); ?></h3>
                            <h6 class="font-weight-normal mb-0">All systems are running smoothly!</h6>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 grid-margin stretch-card">
                    <div class="card tale-bg">
                        <div class="card-people mt-auto">
                            <img src="../assets/images/dashboard/people.svg" alt="people">
                            <div class="weather-info">
                                <div class="d-flex">
                                    <div class="ml-2">
                                        <h4 class="location font-weight-normal"><?php echo htmlentities($student->ClassName); ?> Level <?php echo htmlentities($student->Level); ?></h4>
                                        <h6 class="font-weight-normal"><?php echo htmlentities($student->Session); ?> - <?php echo htmlentities($student->Term); ?></h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 grid-margin transparent">
                    <div class="row">
                        <div class="col-md-6 mb-4 stretch-card transparent">
                            <div class="card card-tale">
                                <div class="card-body">
                                    <p class="mb-4">Total Subjects</p>
                                    <p class="fs-30 mb-2"><?php echo $total_subjects; ?></p>
                                    <p>Current Term</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4 stretch-card transparent">
                            <div class="card card-dark-blue">
                                <div class="card-body">
                                    <p class="mb-4">Average Marks</p>
                                    <p class="fs-30 mb-2"><?php echo $average_marks; ?>%</p>
                                    <p>Current Term</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-4 mb-lg-0 stretch-card transparent">
                            <div class="card card-light-blue">
                                <div class="card-body">
                                    <p class="mb-4">Session</p>
                                    <p class="fs-30 mb-2"><?php echo htmlentities($student->Session); ?></p>
                                    <p><?php echo htmlentities($student->Term); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 stretch-card transparent">
                            <div class="card card-light-danger">
                                <div class="card-body">
                                    <p class="mb-4">Unread Notices</p>
                                    <p class="fs-30 mb-2"><?php echo $unread_notices; ?></p>
                                    <p>Pending Review</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <!-- Recent Results -->
                <div class="col-md-6 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Recent Results</h4>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Subject</th>
                                            <th>Marks</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = "SELECT r.*, s.SubjectName 
                                                FROM tblresult r 
                                                JOIN tblsubjects s ON r.SubjectId = s.ID 
                                                WHERE r.StudentId = :studentId 
                                                AND r.Term = :term 
                                                ORDER BY r.PostingDate DESC LIMIT 5";
                                        $query = $dbh->prepare($sql);
                                        $query->bindParam(':studentId', $student->ID, PDO::PARAM_INT);
                                        $query->bindParam(':term', $student->Term, PDO::PARAM_STR);
                                        $query->execute();
                                        $results = $query->fetchAll(PDO::FETCH_OBJ);
                                        if($query->rowCount() > 0) {
                                            foreach($results as $result) {
                                                $status = $result->Marks >= 50 ? 'Pass' : 'Fail';
                                                $statusClass = $result->Marks >= 50 ? 'text-success' : 'text-danger';
                                        ?>
                                        <tr>
                                            <td><?php echo htmlentities($result->SubjectName); ?></td>
                                            <td><?php echo htmlentities($result->Marks); ?>%</td>
                                            <td class="<?php echo $statusClass; ?>"><?php echo $status; ?></td>
                                        </tr>
                                        <?php }} else { ?>
                                        <tr>
                                            <td colspan="3" class="text-center">No results found</td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- My Subjects -->
                <div class="col-md-6 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">My Subjects</h4>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Subject Name</th>
                                            <th>Subject Code</th>
                                            <th>Teacher</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = "SELECT s.SubjectName, s.SubjectCode, t.FullName as TeacherName 
                                                FROM tblsubjectcombination sc 
                                                JOIN tblsubjects s ON sc.SubjectId = s.ID 
                                                LEFT JOIN tblteachers t ON s.TeacherID = t.ID 
                                                WHERE sc.ClassId = :classId AND sc.Status = 1";
                                        $query = $dbh->prepare($sql);
                                        $query->bindParam(':classId', $student->StudentClass, PDO::PARAM_INT);
                                        $query->execute();
                                        $subjects = $query->fetchAll(PDO::FETCH_OBJ);
                                        if($query->rowCount() > 0) {
                                            foreach($subjects as $subject) {
                                        ?>
                                        <tr>
                                            <td><?php echo htmlentities($subject->SubjectName); ?></td>
                                            <td><?php echo htmlentities($subject->SubjectCode); ?></td>
                                            <td><?php echo htmlentities($subject->TeacherName ?? 'Not Assigned'); ?></td>
                                        </tr>
                                        <?php }} else { ?>
                                        <tr>
                                            <td colspan="3" class="text-center">No subjects found</td>
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
        <!-- content-wrapper ends -->
    </div>
    <!-- main-panel ends -->
</div>
<!-- page-body-wrapper ends -->
</div>
<!-- container-scroller -->
<?php 
include('includes/footer.php');
}
?>
