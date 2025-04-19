<?php
// Session is handled in header.php
error_reporting(0);
require_once('../includes/dbconnection.php');

// Check if student is logged in
if (strlen($_SESSION['sturecmsstuid']) == 0) {
    header('location:login.php');
    exit();
}

try {
    // Get student's class information first
    $stuid = $_SESSION['sturecmsstuid'];
    $sql = "SELECT s.StudentClass, c.ClassName, c.Section 
            FROM tblstudent s
            JOIN tblclass c ON s.StudentClass = c.ID
            WHERE s.StuID = :stuid";
    $query = $dbh->prepare($sql);
    $query->bindParam(':stuid', $stuid, PDO::PARAM_STR);
    $query->execute();
    $studentData = $query->fetch(PDO::FETCH_OBJ);
    
    if (!$studentData) {
        throw new Exception("Unable to fetch student class information");
    }
    
    $studentClass = $studentData->StudentClass;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>My Subjects | KDJ International School</title>
    <link rel="stylesheet" href="vendors/typicons/typicons.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="css/vertical-layout-light/style.css">
    <link rel="stylesheet" href="vendors/datatables/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container-scroller">
        <?php include_once('includes/header.php'); ?>
        <div class="container-fluid page-body-wrapper">
            <?php include_once('includes/sidebar.php'); ?>
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h4 class="card-title mb-0">My Subjects</h4>
                                        <div class="text-muted">
                                            Class: <?php echo htmlentities($studentData->ClassName . ' - ' . $studentData->Section); ?>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table id="subjectTable" class="table table-bordered table-hover">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Subject Name</th>
                                                    <th>Subject Code</th>
                                                    <th>Teacher</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $sql = "SELECT DISTINCT 
                                                        s.ID, s.SubjectName, s.SubjectCode, 
                                                        t.FirstName, t.LastName, t.TeacherID,
                                                        stc.ID as AssignmentId
                                                        FROM tblsubjects s
                                                        JOIN tblsubjectteacherclass stc ON s.ID = stc.SubjectId
                                                        JOIN tblteacher t ON stc.TeacherId = t.ID
                                                        WHERE stc.ClassId = :classid
                                                        ORDER BY s.SubjectName";
                                                $query = $dbh->prepare($sql);
                                                $query->bindParam(':classid', $studentClass, PDO::PARAM_INT);
                                                $query->execute();
                                                $results = $query->fetchAll(PDO::FETCH_OBJ);
                                                $cnt = 1;
                                                if ($query->rowCount() > 0) {
                                                    foreach ($results as $row) { ?>
                                                        <tr>
                                                            <td><?php echo htmlentities($cnt); ?></td>
                                                            <td>
                                                                <div class="font-weight-bold"><?php echo htmlentities($row->SubjectName); ?></div>
                                                            </td>
                                                            <td><?php echo htmlentities($row->SubjectCode); ?></td>
                                                            <td>
                                                                <div><?php echo htmlentities($row->FirstName . " " . $row->LastName); ?></div>
                                                                <small class="text-muted">ID: <?php echo htmlentities($row->TeacherID); ?></small>
                                                            </td>
                                                            <td>
                                                                <a href="view-homework.php?subject=<?php echo htmlentities($row->ID); ?>" 
                                                                   class="btn btn-sm btn-info" title="View Homework">
                                                                    <i class="fas fa-book"></i>
                                                                </a>
                                                                <a href="subject-notices.php?subject=<?php echo htmlentities($row->ID); ?>" 
                                                                   class="btn btn-sm btn-warning" title="Subject Notices">
                                                                    <i class="fas fa-bell"></i>
                                                                </a>
                                                                <a href="class-schedule.php?subject=<?php echo htmlentities($row->ID); ?>" 
                                                                   class="btn btn-sm btn-success" title="Class Schedule">
                                                                    <i class="fas fa-calendar"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                <?php $cnt = $cnt + 1;
                                                    }
                                                } else { ?>
                                                    <tr>
                                                        <td colspan="5" class="text-center">
                                                            <div class="alert alert-info">
                                                                <i class="fas fa-info-circle me-2"></i>
                                                                No subjects have been assigned to your class yet.
                                                            </div>
                                                        </td>
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
            </div>
        </div>
    </div>
    <!-- Scripts -->
    <script src="vendors/js/vendor.bundle.base.js"></script>
    <script src="vendors/datatables/jquery.dataTables.min.js"></script>
    <script src="vendors/datatables/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#subjectTable').DataTable({
                "order": [[1, "asc"]], // Sort by Subject Name by default
                "pageLength": 10,
                "language": {
                    "emptyTable": "No subjects assigned to your class yet",
                    "zeroRecords": "No matching subjects found"
                }
            });
        });
    </script>
</body>
</html>
<?php 
} catch (Exception $e) {
    // Log the error for debugging
    error_log("Error in my-subjects.php: " . $e->getMessage());
    
    // Display a user-friendly error message
    echo '<div style="text-align: center; margin-top: 20px;">';
    echo '<h3>Unable to load your subjects at this time</h3>';
    echo '<p>We apologize for the inconvenience. Please try again later.</p>';
    echo '<a href="dashboard.php" class="btn btn-primary">Return to Dashboard</a>';
    echo '</div>';
}
?>
