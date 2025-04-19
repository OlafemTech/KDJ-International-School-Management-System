<?php
session_start();
error_reporting(0);
require_once('../includes/dbconnection.php');

if (strlen($_SESSION['sturecmsstuid']) == 0) {
    header('location:login.php');
    exit();
}

try {
    if(!isset($_GET['subject'])) {
        throw new Exception("No subject specified");
    }

    $stuid = $_SESSION['sturecmsstuid'];
    $subjectId = $_GET['subject'];

    // Get student and class info
    $sql = "SELECT s.*, c.ClassName, c.Section 
            FROM tblstudent s
            JOIN tblclass c ON s.StudentClass = c.ID
            WHERE s.StuID = :stuid";
    $query = $dbh->prepare($sql);
    $query->bindParam(':stuid', $stuid, PDO::PARAM_STR);
    $query->execute();
    $studentInfo = $query->fetch(PDO::FETCH_OBJ);

    if (!$studentInfo) {
        throw new Exception("Unable to fetch student information");
    }

    // Get subject and teacher info
    $sql = "SELECT s.*, CONCAT(t.FirstName, ' ', t.LastName) as TeacherName, t.TeacherID
            FROM tblsubjects s
            JOIN tblsubjectteacherclass stc ON s.ID = stc.SubjectId
            JOIN tblteacher t ON stc.TeacherId = t.ID
            WHERE s.ID = :subjectId AND stc.ClassId = :classId";
    $query = $dbh->prepare($sql);
    $query->bindParam(':subjectId', $subjectId, PDO::PARAM_INT);
    $query->bindParam(':classId', $studentInfo->StudentClass, PDO::PARAM_INT);
    $query->execute();
    $subject = $query->fetch(PDO::FETCH_OBJ);

    if (!$subject) {
        throw new Exception("Subject not found or you don't have access to it");
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Notices - <?php echo htmlentities($subject->SubjectName); ?> | KDJ International School</title>
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
                                        <div>
                                            <h4 class="card-title mb-0"><?php echo htmlentities($subject->SubjectName); ?> Notices</h4>
                                            <div class="text-muted mt-2">
                                                <i class="fas fa-chalkboard-teacher me-2"></i>Teacher: 
                                                <?php echo htmlentities($subject->TeacherName); ?> 
                                                (ID: <?php echo htmlentities($subject->TeacherID); ?>)
                                                <br>
                                                <i class="fas fa-graduation-cap me-2"></i>Class: 
                                                <?php echo htmlentities($studentInfo->ClassName . ' - ' . $studentInfo->Section); ?>
                                            </div>
                                        </div>
                                        <a href="my-subjects.php" class="btn btn-outline-primary">
                                            <i class="fas fa-arrow-left me-2"></i>Back to Subjects
                                        </a>
                                    </div>

                                    <?php
                                    // Get subject notices
                                    $sql = "SELECT n.*, DATEDIFF(CURDATE(), n.CreationDate) as DaysAgo
                                            FROM tblsubjectnotices n
                                            WHERE n.SubjectId = :subjectId 
                                            AND n.ClassId = :classId
                                            ORDER BY n.CreationDate DESC";
                                    $query = $dbh->prepare($sql);
                                    $query->bindParam(':subjectId', $subject->ID, PDO::PARAM_INT);
                                    $query->bindParam(':classId', $studentInfo->StudentClass, PDO::PARAM_INT);
                                    $query->execute();
                                    $notices = $query->fetchAll(PDO::FETCH_OBJ);

                                    if ($query->rowCount() > 0) {
                                        foreach ($notices as $notice) {
                                            $isNew = $notice->DaysAgo <= 2;
                                    ?>
                                            <div class="card mb-4">
                                                <div class="card-header d-flex justify-content-between align-items-center">
                                                    <h5 class="mb-0">
                                                        <?php echo htmlentities($notice->Title); ?>
                                                        <?php if ($isNew) { ?>
                                                            <span class="badge bg-success ms-2">New!</span>
                                                        <?php } ?>
                                                    </h5>
                                                    <small class="text-muted">
                                                        <?php 
                                                        if ($notice->DaysAgo == 0) {
                                                            echo 'Today';
                                                        } else if ($notice->DaysAgo == 1) {
                                                            echo 'Yesterday';
                                                        } else {
                                                            echo $notice->DaysAgo . ' days ago';
                                                        }
                                                        ?>
                                                    </small>
                                                </div>
                                                <div class="card-body">
                                                    <p class="card-text">
                                                        <?php echo nl2br(htmlentities($notice->Message)); ?>
                                                    </p>
                                                    <div class="text-muted">
                                                        <small>
                                                            <i class="fas fa-clock me-1"></i>
                                                            Posted on <?php echo date('D, M d, Y', strtotime($notice->CreationDate)); ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                    <?php
                                        }
                                    } else {
                                    ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            No notices found for this subject.
                                        </div>
                                    <?php
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="vendors/js/vendor.bundle.base.js"></script>
    <script src="vendors/datatables/jquery.dataTables.min.js"></script>
    <script src="vendors/datatables/dataTables.bootstrap4.min.js"></script>
</body>
</html>
<?php
} catch (Exception $e) {
    // Log error for debugging
    error_log("Error in subject-notices.php: " . $e->getMessage());
    
    // Show user-friendly error page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Error | KDJ International School</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card shadow">
                        <div class="card-body text-center">
                            <h3 class="text-danger mb-4">
                                <i class="fas fa-exclamation-circle"></i>
                                Error
                            </h3>
                            <p class="mb-4">
                                <?php echo htmlentities($e->getMessage()); ?>
                            </p>
                            <a href="my-subjects.php" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-2"></i>
                                Return to My Subjects
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
<?php
}
?>
