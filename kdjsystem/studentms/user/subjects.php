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
$class = $_SESSION['sturecmsclass'];

// Get current session and term
$sql = "SELECT CurrentSession, CurrentTerm FROM tblsessionterm WHERE ID = 1";
$query = $dbh->prepare($sql);
$query->execute();
$academic_info = $query->fetch(PDO::FETCH_ASSOC);
$current_session = $academic_info['CurrentSession'];
$current_term = $academic_info['CurrentTerm'];

$pageTitle = "My Subjects";
include('includes/header.php');
?>

<div class="main-panel">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">My Subjects for <?php echo htmlspecialchars($current_session); ?> - <?php echo htmlspecialchars($current_term); ?></h4>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Subject Name</th>
                                        <th>Subject Code</th>
                                        <th>Teacher</th>
                                        <th>Current Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT s.SubjectName, s.SubjectCode, s.ID as SubjectId, 
                                           t.FirstName, t.LastName, 
                                           r.Marks
                                           FROM tblsubjectcombination sc 
                                           JOIN tblsubjects s ON sc.SubjectId = s.ID 
                                           LEFT JOIN tblteacher t ON sc.TeacherId = t.ID 
                                           LEFT JOIN tblresult r ON r.SubjectId = s.ID 
                                           AND r.StudentId = ? AND r.Term = ?
                                           WHERE sc.ClassId = ? AND sc.Status = 1
                                           ORDER BY s.SubjectName";
                                    
                                    $query = $dbh->prepare($sql);
                                    $query->execute([$sid, $current_term, $class]);
                                    $results = $query->fetchAll(PDO::FETCH_ASSOC);
                                    $cnt = 1;
                                    
                                    foreach($results as $result) {
                                        ?>
                                        <tr>
                                            <td><?php echo $cnt; ?></td>
                                            <td><?php echo htmlspecialchars($result['SubjectName']); ?></td>
                                            <td><?php echo htmlspecialchars($result['SubjectCode']); ?></td>
                                            <td>
                                                <?php 
                                                echo $result['FirstName'] ? 
                                                     htmlspecialchars($result['FirstName'] . ' ' . $result['LastName']) : 
                                                     'Not Assigned';
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                if (isset($result['Marks'])) {
                                                    echo htmlspecialchars($result['Marks']) . '%';
                                                } else {
                                                    echo 'Not Available';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                        <?php
                                        $cnt++;
                                    }
                                    
                                    if (empty($results)) {
                                        echo "<tr><td colspan='5' class='text-center'>No subjects found</td></tr>";
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
