<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
    exit();
}

if(!isset($_GET['viewid'])) {
    header('location:manage-students.php');
    exit();
}

try {
    $viewid = intval($_GET['viewid']);
    
    // Get student details with class information
    $sql = "SELECT s.*, c.Session, c.Term 
            FROM tblstudent s 
            LEFT JOIN tblclass c ON s.StudentClass = c.ClassName 
                AND s.Level = c.Level 
            WHERE s.ID = :viewid";
            
    $query = $dbh->prepare($sql);
    $query->bindParam(':viewid', $viewid, PDO::PARAM_INT);
    $query->execute();
    
    if($query->rowCount() == 0) {
        $_SESSION['error'] = "Student not found";
        header('location:manage-students.php');
        exit();
    }
    
    $student = $query->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header('location:manage-students.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Student Details</title>
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
                    <div class="row">
                        <div class="col-md-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-sm-flex align-items-center mb-4">
                                        <h4 class="card-title mb-sm-0">Student Details</h4>
                                        <a href="manage-students.php" class="btn btn-secondary ml-auto">Back</a>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4 text-center mb-4">
                                            <div class="profile-image">
                                                <?php 
                                                $imagePath = "studentimages/";
                                                $imageFile = $student['Image'] ? htmlentities($student['Image']) : 'default.jpg';
                                                $fullImagePath = $imagePath . $imageFile;
                                                ?>
                                                <img src="<?php echo $fullImagePath; ?>" 
                                                     alt="Student Profile Photo" class="img-fluid rounded">
                                            </div>
                                        </div>
                                        <div class="col-md-8">
                                            <div class="table-responsive">
                                                <table class="table table-bordered">
                                                    <tr>
                                                        <th width="200">Student ID</th>
                                                        <td><?php echo htmlentities($student['StudentId']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Registration Date</th>
                                                        <td><?php echo date('F j, Y', strtotime($student['DateofAdmission'])); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Student Name</th>
                                                        <td><?php echo htmlentities($student['StudentName']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Email</th>
                                                        <td><?php echo htmlentities($student['StudentEmail']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Class</th>
                                                        <td><?php echo htmlentities($student['StudentClass']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Level</th>
                                                        <td><?php echo htmlentities($student['Level']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Gender</th>
                                                        <td><?php echo htmlentities($student['Gender']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Date of Birth</th>
                                                        <td><?php echo date('F j, Y', strtotime($student['DOB'])); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Father's Name</th>
                                                        <td><?php echo htmlentities($student['FatherName']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Mother's Name</th>
                                                        <td><?php echo htmlentities($student['MotherName']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Contact Number</th>
                                                        <td><?php echo htmlentities($student['ContactNumber']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Alternate Number</th>
                                                        <td><?php echo htmlentities($student['AlternateNumber']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Address</th>
                                                        <td><?php echo nl2br(htmlentities($student['Address'])); ?></td>
                                                    </tr>
                                                    <?php if($student['Session'] && $student['Term']) { ?>
                                                    <tr>
                                                        <th>Current Session</th>
                                                        <td><?php echo htmlentities($student['Session']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Current Term</th>
                                                        <td><?php echo htmlentities($student['Term']); ?></td>
                                                    </tr>
                                                    <?php } ?>
                                                </table>
                                            </div>
                                            <div class="mt-4">
                                                <a href="edit-student-detail.php?editid=<?php echo $viewid; ?>" 
                                                   class="btn btn-primary">Edit Details</a>
                                            </div>
                                        </div>
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
