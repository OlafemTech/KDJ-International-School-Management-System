<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug function to log to both error log and console
function debug_to_console($data, $context = 'Debug') {
    $output = $data;
    if (is_array($data) || is_object($data)) {
        $output = json_encode($data, JSON_PRETTY_PRINT);
    }
    error_log("[$context] $output");
    echo "<script>console.log('[$context]', " . json_encode($output) . ");</script>";
}

// Set page title
$pageTitle = 'Dashboard';

// Debug session data
debug_to_console($_SESSION, 'Session Data');
debug_to_console(session_id(), 'Session ID');

// Initialize variables
$student = null;
$row = null;  // Initialize $row for header.php
$total_subjects = 0;
$average_marks = 0;
$unread_notices = 0;

// Debug session information
debug_to_console($_SESSION, 'Session Data');
debug_to_console(session_id(), 'Session ID');

// Check for student ID in session
if (!isset($_SESSION['sturecmsstuid']) || empty($_SESSION['sturecmsstuid'])) {
    debug_to_console('No student ID in session');
    header('Location: login.php');
    exit();
}

// Set up custom error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("[$errno] $errstr in $errfile:$errline");
    debug_to_console("Error: $errstr", 'ERROR');
    return true;
});

$stuid = $_SESSION['sturecmsstuid'];
debug_to_console($stuid, 'Student ID');

// Include database connection
require_once('includes/dbconnection.php');
if (!isset($dbh) || !($dbh instanceof PDO)) {
    debug_to_console('Database connection failed: Connection not established', 'ERROR');
    die('Database connection failed. Please try again later.');
}
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
debug_to_console('Database connection successful');

// Get student details with error handling
try {
    // Query to get student details
    $sql = "SELECT s.*, c.ID as ClassID, c.ClassName, c.Level, c.Session, c.Term 
            FROM tblstudent s 
            INNER JOIN tblclass c ON s.StudentClass = c.ClassName 
                AND s.Level = c.Level 
                AND s.Session = c.Session 
                AND s.Term = c.Term 
            WHERE s.StudentId = :stuid AND s.Status = 1";
    
    debug_to_console('Fetching student with ID: ' . $_SESSION['sturecmsstuid']);
    $query = $dbh->prepare($sql);
    $query->bindParam(':stuid', $_SESSION['sturecmsstuid'], PDO::PARAM_STR);
    
    if (!$query->execute()) {
        $error = $query->errorInfo();
        debug_to_console($error, 'Query Error');
        throw new Exception('Failed to execute student query: ' . $error[2]);
    }
    
    $student = $query->fetch(PDO::FETCH_OBJ);
    if (!$student) {
        debug_to_console("No active student found with ID: $stuid", 'ERROR');
        throw new Exception('Student not found or inactive');
    }
    
    // Set student data for header.php
    $row = $student;
    
    // Verify required fields
    $required_fields = ['ID', 'StudentId', 'StudentName', 'StudentClass', 'Level', 'Session', 'Term'];
    foreach ($required_fields as $field) {
        if (!isset($student->$field)) {
            error_log("Missing required field: " . $field);
            throw new Exception('Incomplete student data: ' . $field . ' is missing');
        }
    }
    
    // Get class ID first
    $sql = "SELECT ID FROM tblclass 
            WHERE ClassName = :class 
            AND Level = :level 
            AND Session = :session 
            AND Term = :term";
    $query = $dbh->prepare($sql);
    $query->bindParam(':class', $student->StudentClass, PDO::PARAM_STR);
    $query->bindParam(':level', $student->Level, PDO::PARAM_STR);
    $query->bindParam(':session', $student->Session, PDO::PARAM_STR);
    $query->bindParam(':term', $student->Term, PDO::PARAM_STR);
    $query->execute();
    $classData = $query->fetch(PDO::FETCH_OBJ);
    
    if (!$classData) {
        throw new Exception('Class not found for student');
    }
    
    // Get total subjects
    $sql = "SELECT COUNT(*) as total FROM tblsubjects WHERE ClassID = :classId";
    $query = $dbh->prepare($sql);
    $query->bindParam(':classId', $student->ClassID, PDO::PARAM_INT);
    debug_to_console('Getting subjects for ClassID: ' . $student->ClassID);
    $query->execute();
    $result = $query->fetch(PDO::FETCH_OBJ);
    $total_subjects = $result ? $result->total : 0;
    debug_to_console('Total subjects found: ' . $total_subjects);
    
    // Get average marks
    $sql = "SELECT AVG((COALESCE(CA1, 0) + COALESCE(CA2, 0) + COALESCE(Exam, 0)) / 3) as average 
            FROM tblresult 
            WHERE StudentId = :studentId 
            AND Session = :session 
            AND Term = :term";
    $query = $dbh->prepare($sql);
    $query->bindParam(':studentId', $student->StudentId, PDO::PARAM_STR);
    $query->bindParam(':session', $student->Session, PDO::PARAM_STR);
    $query->bindParam(':term', $student->Term, PDO::PARAM_STR);
    debug_to_console('Getting average marks for student: ' . $student->StudentId);
    $query->execute();
    $result = $query->fetch(PDO::FETCH_OBJ);
    $average_marks = $result ? round($result->average, 2) : 0;
    debug_to_console('Average marks: ' . $average_marks);
    
    // Get unread notices
    $sql = "SELECT COUNT(*) as count FROM tblnotice WHERE Status = 1";
    $query = $dbh->prepare($sql);
    $query->execute();
    $result = $query->fetch(PDO::FETCH_OBJ);
    $unread_notices = $result ? $result->count : 0;
    
    // Set page title and ensure it's available to header.php
    $pageTitle = "Dashboard";
    // Include header with necessary variables set
    if (!isset($row) || !isset($pageTitle)) {
        error_log("Critical variables not set before including header");
        throw new Exception('Dashboard setup incomplete');
    }
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
                                        $query->bindParam(':studentId', $student->StudentId, PDO::PARAM_STR);
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
                                        try {
                                            $sql = "SELECT s.SubjectName, s.SubjectCode, t.FullName as TeacherName 
                                                    FROM tblsubjects s 
                                                    LEFT JOIN tblteacher t ON s.TeacherID = t.ID 
                                                    WHERE s.ClassID = :classId 
                                                    ORDER BY s.SubjectName ASC";
                                            $query = $dbh->prepare($sql);
                                            $query->bindParam(':classId', $student->ClassID, PDO::PARAM_INT);
                                            debug_to_console('Fetching subjects for ClassID: ' . $student->ClassID);
                                            $query->execute();
                                            $subjects = $query->fetchAll(PDO::FETCH_OBJ);
                                            debug_to_console('Found ' . count($subjects) . ' subjects');
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
                                            <td colspan="3" class="text-center">No subjects assigned to your class yet</td>
                                        </tr>
                                        <?php }
                                        } catch (Exception $e) {
                                            debug_to_console('Error fetching subjects: ' . $e->getMessage(), 'ERROR');
                                        ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-danger">Error loading subjects. Please try again later.</td>
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
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    debug_to_console($e->getMessage(), 'Database Error');
    echo '<!DOCTYPE html><html><head><title>Error</title>';
    echo '<link rel="stylesheet" href="../assets/css/style.css"></head><body>';
    echo '<div class="container"><div class="row justify-content-center"><div class="col-md-6">';
    echo '<div class="alert alert-danger mt-5">Database error occurred. Please try again later.</div>';
    echo '</div></div></div></body></html>';
    exit();
} catch (Exception $e) {
    error_log("Application error: " . $e->getMessage());
    debug_to_console($e->getMessage(), 'Application Error');
    echo '<!DOCTYPE html><html><head><title>Error</title>';
    echo '<link rel="stylesheet" href="../assets/css/style.css"></head><body>';
    echo '<div class="container"><div class="row justify-content-center"><div class="col-md-6">';
    echo '<div class="alert alert-danger mt-5">An error occurred: ' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '</div></div></div></body></html>';
    exit();
} finally {
    restore_error_handler();
}


?>
