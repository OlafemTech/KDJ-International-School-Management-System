<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsgid']) == 0) {
    header('location:logout.php');
    exit();
}

$sid = $_SESSION['sturecmsgid'];

// Get student details
$sql = "SELECT * FROM tblstudent WHERE ID = ?";
$query = $dbh->prepare($sql);
$query->execute([$sid]);
$student = $query->fetch(PDO::FETCH_ASSOC);

$pageTitle = "My Profile";
include('includes/header.php');
?>

<div class="main-panel">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-12 grid-margin">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Student Profile</h4>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <tbody>
                                            <tr>
                                                <th width="200">Student Name</th>
                                                <td><?php echo htmlspecialchars($student['StudentName']); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Student ID</th>
                                                <td><?php echo htmlspecialchars($student['StudentId']); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Student Email</th>
                                                <td><?php echo htmlspecialchars($student['StudentEmail']); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Student Class</th>
                                                <td><?php echo htmlspecialchars($student['StudentClass']); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Gender</th>
                                                <td><?php echo htmlspecialchars($student['Gender']); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Date of Birth</th>
                                                <td><?php echo htmlspecialchars($student['DOB']); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Level</th>
                                                <td><?php echo htmlspecialchars($student['Level']); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Registration Date</th>
                                                <td><?php echo htmlspecialchars($student['DateofAdmission']); ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- content-wrapper ends -->
    
    <!-- partial:partials/_footer.html -->
    <footer class="footer">
        <div class="d-sm-flex justify-content-center justify-content-sm-between">
            <span class="text-muted text-center text-sm-left d-block d-sm-inline-block">Copyright © <?php echo date('Y'); ?></span>
        </div>
    </footer>
    <!-- partial -->
</div>
<!-- main-panel ends -->

<?php include('includes/footer.php'); ?>
