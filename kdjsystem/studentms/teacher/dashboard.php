<?php
// Remove direct session_start as per memory requirements
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/header.php'); // This will handle session management

// Session check is now handled in header.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>KDJ - Teacher Dashboard</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container-scroller">
        <div class="container-fluid page-body-wrapper">
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="row">
                        <div class="col-md-12 grid-margin">
                            <div class="row">
                                <div class="col-12 col-xl-8 mb-4 mb-xl-0">
                                    <h3 class="font-weight-bold">Welcome <?php echo htmlentities($_SESSION['teachername']); ?></h3>
                                    <h6 class="font-weight-normal mb-0">Teacher ID: <?php echo htmlentities($_SESSION['teachercode']); ?></h6>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php
                    // Get teacher details
                    try {
                        $sql = "SELECT * FROM tblteacher WHERE ID = :teacherid";
                        $query = $dbh->prepare($sql);
                        $query->bindParam(':teacherid', $_SESSION['teacherid'], PDO::PARAM_INT);
                        $query->execute();
                        $teacher = $query->fetch(PDO::FETCH_ASSOC);
                    } catch(PDOException $e) {
                        error_log("Error fetching teacher details: " . $e->getMessage());
                    }
                    ?>

                    <!-- Teacher Profile Card -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <img src="../admin/uploads/teachers/<?php echo htmlentities($teacher['UserImage']); ?>" 
                                         class="rounded-circle img-fluid mb-3" style="max-width: 150px;" 
                                         alt="Profile Photo">
                                    <h4><?php echo htmlentities($teacher['FullName']); ?></h4>
                                    <p class="text-muted"><?php echo htmlentities($teacher['Qualification']); ?></p>
                                </div>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item">
                                        <strong>Email:</strong> <?php echo htmlentities($teacher['Email']); ?>
                                    </li>
                                    <li class="list-group-item">
                                        <strong>Mobile:</strong> <?php echo htmlentities($teacher['MobileNumber']); ?>
                                    </li>
                                    <li class="list-group-item">
                                        <strong>Joining Date:</strong> <?php echo date('F j, Y', strtotime($teacher['JoiningDate'])); ?>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Documents</h4>
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Document Type</th>
                                                    <th>Status</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>Profile Photo</td>
                                                    <td>
                                                        <?php if($teacher['UserImage'] && $teacher['UserImage'] != 'default.jpg'): ?>
                                                            <span class="badge bg-success">Uploaded</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning">Not Uploaded</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if($teacher['UserImage'] && $teacher['UserImage'] != 'default.jpg'): ?>
                                                            <a href="../admin/uploads/teachers/<?php echo htmlentities($teacher['UserImage']); ?>" 
                                                               class="btn btn-sm btn-info" target="_blank">View</a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>CV</td>
                                                    <td>
                                                        <?php if($teacher['CV']): ?>
                                                            <span class="badge bg-success">Uploaded</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning">Not Uploaded</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if($teacher['CV']): ?>
                                                            <a href="../admin/uploads/teachers/<?php echo htmlentities($teacher['CV']); ?>" 
                                                               class="btn btn-sm btn-info" target="_blank">View</a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Academic Certificate</td>
                                                    <td>
                                                        <?php if($teacher['Certificate']): ?>
                                                            <span class="badge bg-success">Uploaded</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning">Not Uploaded</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if($teacher['Certificate']): ?>
                                                            <a href="../admin/uploads/teachers/<?php echo htmlentities($teacher['Certificate']); ?>" 
                                                               class="btn btn-sm btn-info" target="_blank">View</a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="mt-3">
                                        <a href="update-profile.php" class="btn btn-primary">Update Profile & Documents</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <?php
                        try {
                            // Get assigned subjects count
                            $stmt = $dbh->prepare("SELECT COUNT(DISTINCT SubjectID) as subjects FROM tblsubjectteacherclass WHERE TeacherID = :teachid");
                            $stmt->bindParam(':teachid', $_SESSION['teacherid'], PDO::PARAM_STR);
                            $stmt->execute();
                            $subjects = $stmt->fetch(PDO::FETCH_ASSOC)['subjects'];

                            // Get assigned classes count
                            $stmt = $dbh->prepare("SELECT COUNT(DISTINCT ClassID) as classes FROM tblsubjectteacherclass WHERE TeacherID = :teachid");
                            $stmt->bindParam(':teachid', $_SESSION['teacherid'], PDO::PARAM_STR);
                            $stmt->execute();
                            $classes = $stmt->fetch(PDO::FETCH_ASSOC)['classes'];

                            // Get total students count across all assigned classes
                            $stmt = $dbh->prepare("SELECT COUNT(DISTINCT s.ID) as students 
                                                FROM tblstudent s 
                                                JOIN tblsubjectteacherclass stc ON s.StudentClass = stc.ClassID 
                                                WHERE stc.TeacherID = :teachid");
                            $stmt->bindParam(':teachid', $_SESSION['teacherid'], PDO::PARAM_STR);
                            $stmt->execute();
                            $students = $stmt->fetch(PDO::FETCH_ASSOC)['students'];

                            // Get pending homework submissions count
                            $stmt = $dbh->prepare("SELECT COUNT(*) as pending FROM tblhomework h 
                                                JOIN tblsubjectteacherclass stc ON h.SubjectID = stc.SubjectID 
                                                WHERE stc.TeacherID = :teachid AND h.Status = 'Pending'");
                            $stmt->bindParam(':teachid', $_SESSION['teacherid'], PDO::PARAM_STR);
                            $stmt->execute();
                            $pending = $stmt->fetch(PDO::FETCH_ASSOC)['pending'];
                        } catch(PDOException $e) {
                            error_log("Error in dashboard.php: " . $e->getMessage());
                            echo "<div class='alert alert-danger'>An error occurred while fetching dashboard data. Please try again later.</div>";
                        }
                        ?>
                        <div class="col-md-3 mb-4">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-book text-primary" style="font-size: 2rem;"></i>
                                        <div class="ms-3">
                                            <h6 class="mb-1">Subjects</h6>
                                            <h4 class="mb-0"><?php echo isset($subjects) ? $subjects : '0'; ?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-4">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-grid-3x3-gap text-success" style="font-size: 2rem;"></i>
                                        <div class="ms-3">
                                            <h6 class="mb-1">Classes</h6>
                                            <h4 class="mb-0"><?php echo isset($classes) ? $classes : '0'; ?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-4">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-people text-info" style="font-size: 2rem;"></i>
                                        <div class="ms-3">
                                            <h6 class="mb-1">Students</h6>
                                            <h4 class="mb-0"><?php echo isset($students) ? $students : '0'; ?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-4">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-journal-check text-warning" style="font-size: 2rem;"></i>
                                        <div class="ms-3">
                                            <h6 class="mb-1">Pending</h6>
                                            <h4 class="mb-0"><?php echo isset($pending) ? $pending : '0'; ?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Today's Schedule</h4>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Time</th>
                                                    <th>Subject</th>
                                                    <th>Class</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                try {
                                                    $today = date('l');
                                                    $sql = "SELECT s.SubjectName, c.ClassName, sc.TimeSlot 
                                                            FROM tblsubjectteacherclass stc
                                                            JOIN tblsubjects s ON stc.SubjectID = s.ID
                                                            JOIN tblclass c ON stc.ClassID = c.ID
                                                            JOIN tblschedule sc ON stc.ID = sc.SubjectTeacherClassID
                                                            WHERE stc.TeacherID = :teachid AND sc.Day = :today
                                                            ORDER BY sc.TimeSlot";
                                                    $query = $dbh->prepare($sql);
                                                    $query->bindParam(':teachid', $_SESSION['teacherid'], PDO::PARAM_STR);
                                                    $query->bindParam(':today', $today, PDO::PARAM_STR);
                                                    $query->execute();
                                                    $results = $query->fetchAll(PDO::FETCH_OBJ);
                                                    
                                                    if($query->rowCount() > 0) {
                                                        foreach($results as $row) { ?>
                                                            <tr>
                                                                <td><?php echo htmlentities($row->TimeSlot); ?></td>
                                                                <td><?php echo htmlentities($row->SubjectName); ?></td>
                                                                <td><?php echo htmlentities($row->ClassName); ?></td>
                                                            </tr>
                                                        <?php }
                                                    } else { ?>
                                                        <tr>
                                                            <td colspan="3" class="text-center">No classes scheduled for today</td>
                                                        </tr>
                                                    <?php }
                                                } catch(PDOException $e) {
                                                    error_log("Error in dashboard.php schedule: " . $e->getMessage());
                                                    echo "<tr><td colspan='3' class='text-center text-danger'>Error loading schedule</td></tr>";
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Recent Homework Submissions</h4>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Student</th>
                                                    <th>Subject</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                try {
                                                    $sql = "SELECT s.FirstName, s.LastName, sub.SubjectName, h.Status, h.SubmissionDate 
                                                            FROM tblhomework h
                                                            JOIN tblstudent s ON h.StudentID = s.ID
                                                            JOIN tblsubjects sub ON h.SubjectID = sub.ID
                                                            JOIN tblsubjectteacherclass stc ON h.SubjectID = stc.SubjectID
                                                            WHERE stc.TeacherID = :teachid
                                                            ORDER BY h.SubmissionDate DESC LIMIT 5";
                                                    $query = $dbh->prepare($sql);
                                                    $query->bindParam(':teachid', $_SESSION['teacherid'], PDO::PARAM_STR);
                                                    $query->execute();
                                                    $results = $query->fetchAll(PDO::FETCH_OBJ);
                                                    
                                                    if($query->rowCount() > 0) {
                                                        foreach($results as $row) { ?>
                                                            <tr>
                                                                <td><?php echo htmlentities($row->FirstName . ' ' . $row->LastName); ?></td>
                                                                <td><?php echo htmlentities($row->SubjectName); ?></td>
                                                                <td>
                                                                    <span class="badge <?php echo $row->Status == 'Pending' ? 'bg-warning' : 'bg-success'; ?>">
                                                                        <?php echo htmlentities($row->Status); ?>
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        <?php }
                                                    } else { ?>
                                                        <tr>
                                                            <td colspan="3" class="text-center">No recent submissions</td>
                                                        </tr>
                                                    <?php }
                                                } catch(PDOException $e) {
                                                    error_log("Error in dashboard.php homework: " . $e->getMessage());
                                                    echo "<tr><td colspan='3' class='text-center text-danger'>Error loading submissions</td></tr>";
                                                }
                                                ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="vendors/js/vendor.bundle.base.js"></script>
</body>
</html>
