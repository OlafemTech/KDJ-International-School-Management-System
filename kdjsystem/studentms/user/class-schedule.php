<?php
// Remove session_start() since it's already in header.php
error_reporting(0);
require_once('../includes/dbconnection.php');

if (strlen($_SESSION['sturecmsstuid']) == 0) {
    header('location:login.php');
    exit();
}

try {
    $stuid = $_SESSION['sturecmsstuid'];
    $subjectFilter = isset($_GET['subject']) ? $_GET['subject'] : null;

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Class Schedule | KDJ International School</title>
    <link rel="stylesheet" href="vendors/typicons/typicons.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="css/vertical-layout-light/style.css">
    <link rel="stylesheet" href="vendors/datatables/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .schedule-day {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            overflow: hidden;
        }
        .day-header {
            background: linear-gradient(135deg, var(--primary-color), #ff1493);
            color: white;
            padding: 15px 20px;
            margin: 0;
            font-size: 1.2rem;
        }
        .day-content {
            padding: 20px;
        }
        .schedule-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 15px;
            transition: transform 0.2s;
        }
        .schedule-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .schedule-time {
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 8px 8px 0 0;
            font-weight: 500;
            color: #666;
            border-bottom: 1px solid #e0e0e0;
        }
        .schedule-details {
            padding: 15px;
        }
        .schedule-subject {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--primary-color);
        }
        .schedule-info {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        .homework-alert {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            border-radius: 6px;
            padding: 10px;
            margin-top: 10px;
        }
        .homework-alert i {
            color: #856404;
        }
        .no-schedule {
            text-align: center;
            padding: 30px;
            color: #666;
            font-style: italic;
        }
        .filter-bar {
            margin-bottom: 20px;
            padding: 15px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
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
                                            <h4 class="card-title mb-0">Class Schedule</h4>
                                            <div class="text-muted mt-2">
                                                <i class="fas fa-graduation-cap me-2"></i>
                                                <?php echo htmlentities($studentInfo->ClassName . ' - ' . $studentInfo->Section); ?>
                                            </div>
                                        </div>
                                        <?php if ($subjectFilter) { ?>
                                            <a href="class-schedule.php" class="btn btn-outline-primary">
                                                <i class="fas fa-calendar-alt me-2"></i>View Full Schedule
                                            </a>
                                        <?php } ?>
                                    </div>

                                    <?php if (!$subjectFilter) { ?>
                                        <div class="filter-bar">
                                            <div class="row align-items-center">
                                                <div class="col-md-6">
                                                    <h5 class="mb-0">
                                                        <i class="fas fa-filter me-2"></i>Quick Filters
                                                    </h5>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="d-flex justify-content-end">
                                                        <?php
                                                        $sql = "SELECT DISTINCT s.ID, s.SubjectName
                                                                FROM tblsubjects s
                                                                JOIN tblsubjectteacherclass stc ON s.ID = stc.SubjectId
                                                                WHERE stc.ClassId = :classId
                                                                ORDER BY s.SubjectName";
                                                        $query = $dbh->prepare($sql);
                                                        $query->bindParam(':classId', $studentInfo->StudentClass, PDO::PARAM_INT);
                                                        $query->execute();
                                                        $subjects = $query->fetchAll(PDO::FETCH_OBJ);
                                                        
                                                        foreach ($subjects as $subject) { ?>
                                                            <a href="class-schedule.php?subject=<?php echo $subject->ID; ?>" 
                                                               class="btn btn-outline-info btn-sm me-2">
                                                                <?php echo htmlentities($subject->SubjectName); ?>
                                                            </a>
                                                        <?php } ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>

                                    <?php
                                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                                    
                                    foreach ($days as $day) {
                                        // Get schedule for the day
                                        $sql = "SELECT cs.*, s.SubjectName, s.SubjectCode,
                                                CONCAT(t.FirstName, ' ', t.LastName) as TeacherName,
                                                t.TeacherID,
                                                h.Title as HomeworkTitle,
                                                h.DueDate as HomeworkDue,
                                                DATEDIFF(h.DueDate, CURDATE()) as DaysLeft
                                                FROM tblclassschedule cs
                                                JOIN tblsubjects s ON cs.SubjectId = s.ID
                                                JOIN tblteacher t ON cs.TeacherId = t.ID
                                                LEFT JOIN tblhomework h ON (
                                                    s.ID = h.SubjectId 
                                                    AND h.ClassId = cs.ClassId
                                                    AND h.DueDate >= CURDATE()
                                                )
                                                WHERE cs.ClassId = :classId
                                                AND cs.DayOfWeek = :day";
                                        
                                        if ($subjectFilter) {
                                            $sql .= " AND cs.SubjectId = :subjectId";
                                        }
                                        
                                        $sql .= " ORDER BY cs.StartTime";
                                        
                                        $query = $dbh->prepare($sql);
                                        $query->bindParam(':classId', $studentInfo->StudentClass, PDO::PARAM_INT);
                                        $query->bindParam(':day', $day, PDO::PARAM_STR);
                                        
                                        if ($subjectFilter) {
                                            $query->bindParam(':subjectId', $subjectFilter, PDO::PARAM_INT);
                                        }
                                        
                                        $query->execute();
                                        $schedules = $query->fetchAll(PDO::FETCH_OBJ);
                                        
                                        if ($query->rowCount() > 0) {
                                    ?>
                                            <div class="schedule-day">
                                                <h5 class="day-header">
                                                    <i class="fas fa-calendar-day me-2"></i><?php echo $day; ?>
                                                </h5>
                                                <div class="day-content">
                                                    <?php foreach ($schedules as $schedule) { ?>
                                                        <div class="schedule-card">
                                                            <div class="schedule-time">
                                                                <i class="fas fa-clock me-2"></i>
                                                                <?php 
                                                                echo date('h:i A', strtotime($schedule->StartTime)) . ' - ' . 
                                                                     date('h:i A', strtotime($schedule->EndTime));
                                                                ?>
                                                            </div>
                                                            <div class="schedule-details">
                                                                <div class="schedule-subject">
                                                                    <?php echo htmlentities($schedule->SubjectName); ?>
                                                                    <span class="badge bg-info ms-2">
                                                                        <?php echo htmlentities($schedule->SubjectCode); ?>
                                                                    </span>
                                                                </div>
                                                                <div class="schedule-info">
                                                                    <i class="fas fa-chalkboard-teacher me-2"></i>
                                                                    <?php echo htmlentities($schedule->TeacherName); ?>
                                                                    <small class="text-muted">
                                                                        (ID: <?php echo htmlentities($schedule->TeacherID); ?>)
                                                                    </small>
                                                                </div>
                                                                <div class="schedule-info">
                                                                    <i class="fas fa-door-open me-2"></i>
                                                                    Room <?php echo htmlentities($schedule->Room); ?>
                                                                </div>
                                                                <?php if ($schedule->HomeworkTitle && $schedule->DaysLeft >= 0) { ?>
                                                                    <div class="homework-alert">
                                                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                                                        <strong>Homework Due:</strong>
                                                                        <?php echo htmlentities($schedule->HomeworkTitle); ?>
                                                                        <br>
                                                                        <small class="text-danger">
                                                                            Due in <?php echo $schedule->DaysLeft; ?> days
                                                                            (<?php echo date('D, M d', strtotime($schedule->HomeworkDue)); ?>)
                                                                        </small>
                                                                    </div>
                                                                <?php } ?>
                                                            </div>
                                                        </div>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                    <?php
                                        }
                                    }
                                    
                                    // Check if no classes found at all
                                    $found = false;
                                    foreach ($days as $day) {
                                        $sql = "SELECT 1 FROM tblclassschedule 
                                                WHERE ClassId = :classId AND DayOfWeek = :day";
                                        if ($subjectFilter) {
                                            $sql .= " AND SubjectId = :subjectId";
                                        }
                                        $query = $dbh->prepare($sql);
                                        $query->bindParam(':classId', $studentInfo->StudentClass, PDO::PARAM_INT);
                                        $query->bindParam(':day', $day, PDO::PARAM_STR);
                                        if ($subjectFilter) {
                                            $query->bindParam(':subjectId', $subjectFilter, PDO::PARAM_INT);
                                        }
                                        $query->execute();
                                        if ($query->rowCount() > 0) {
                                            $found = true;
                                            break;
                                        }
                                    }
                                    
                                    if (!$found) {
                                    ?>
                                        <div class="no-schedule">
                                            <i class="fas fa-calendar-times fa-3x mb-3"></i>
                                            <h5>No Classes Scheduled</h5>
                                            <p class="text-muted">
                                                <?php
                                                if ($subjectFilter) {
                                                    echo "No classes found for this subject.";
                                                } else {
                                                    echo "No classes have been scheduled yet.";
                                                }
                                                ?>
                                            </p>
                                        </div>
                                    <?php } ?>
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
    error_log("Error in class-schedule.php: " . $e->getMessage());
    
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
