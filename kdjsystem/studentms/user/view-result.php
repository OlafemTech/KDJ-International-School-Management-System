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

// Get available sessions and terms for the student
$sql = "SELECT DISTINCT Session, Term, ClassId 
        FROM tblresult 
        WHERE StudentId = ? 
        ORDER BY Session DESC, FIELD(Term, '3rd Term', '2nd Term', '1st Term')";
$query = $dbh->prepare($sql);
$query->execute([$sid]);
$available_results = $query->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "View Results";
include('includes/header.php');
?>

<div class="main-panel">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-12 grid-margin">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">View Results</h4>
                        <div class="accordion" id="resultAccordion">
                            <?php
                            $accordion_id = 1;
                            $current_session = '';
                            
                            foreach($available_results as $result) {
                                if ($current_session != $result['Session']) {
                                    if ($current_session != '') {
                                        echo "</div></div></div>"; // Close previous session div
                                    }
                                    $current_session = $result['Session'];
                                    ?>
                                    <div class="card">
                                        <div class="card-header" id="heading<?php echo $accordion_id; ?>">
                                            <h2 class="mb-0">
                                                <button class="btn btn-link" type="button" data-toggle="collapse" 
                                                        data-target="#collapse<?php echo $accordion_id; ?>" 
                                                        aria-expanded="true" aria-controls="collapse<?php echo $accordion_id; ?>">
                                                    Session: <?php echo htmlspecialchars($result['Session']); ?>
                                                </button>
                                            </h2>
                                        </div>
                                        <div id="collapse<?php echo $accordion_id; ?>" class="collapse" 
                                             aria-labelledby="heading<?php echo $accordion_id; ?>" 
                                             data-parent="#resultAccordion">
                                            <div class="card-body">
                                    <?php
                                }
                                ?>
                                <div class="mb-4">
                                    <h5><?php echo htmlspecialchars($result['Term']); ?></h5>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Subject</th>
                                                    <th>Marks</th>
                                                    <th>Grade</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $sql = "SELECT r.*, s.SubjectName 
                                                       FROM tblresult r 
                                                       JOIN tblsubjects s ON r.SubjectId = s.ID 
                                                       WHERE r.StudentId = ? 
                                                       AND r.Session = ? 
                                                       AND r.Term = ? 
                                                       AND r.ClassId = ?
                                                       ORDER BY s.SubjectName";
                                                $query = $dbh->prepare($sql);
                                                $query->execute([$sid, $result['Session'], $result['Term'], $result['ClassId']]);
                                                $subject_results = $query->fetchAll(PDO::FETCH_ASSOC);
                                                
                                                $total_marks = 0;
                                                $subject_count = 0;
                                                
                                                foreach($subject_results as $subject) {
                                                    $status = $subject['Marks'] >= 50 ? 'Pass' : 'Fail';
                                                    $statusClass = $subject['Marks'] >= 50 ? 'text-success' : 'text-danger';
                                                    
                                                    // Calculate grade
                                                    $grade = '';
                                                    if ($subject['Marks'] >= 70) $grade = 'A';
                                                    elseif ($subject['Marks'] >= 60) $grade = 'B';
                                                    elseif ($subject['Marks'] >= 50) $grade = 'C';
                                                    elseif ($subject['Marks'] >= 40) $grade = 'D';
                                                    else $grade = 'F';
                                                    
                                                    $total_marks += $subject['Marks'];
                                                    $subject_count++;
                                                    ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($subject['SubjectName']); ?></td>
                                                        <td><?php echo htmlspecialchars($subject['Marks']); ?>%</td>
                                                        <td><?php echo $grade; ?></td>
                                                        <td class="<?php echo $statusClass; ?>"><?php echo $status; ?></td>
                                                    </tr>
                                                    <?php
                                                }
                                                
                                                if ($subject_count > 0) {
                                                    $average = $total_marks / $subject_count;
                                                    ?>
                                                    <tr class="table-info">
                                                        <td><strong>Average</strong></td>
                                                        <td colspan="3"><strong><?php echo number_format($average, 2); ?>%</strong></td>
                                                    </tr>
                                                    <?php
                                                }
                                                
                                                if (empty($subject_results)) {
                                                    echo "<tr><td colspan='4' class='text-center'>No results available</td></tr>";
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <?php
                                $accordion_id++;
                            }
                            
                            if (!empty($available_results)) {
                                echo "</div></div></div>"; // Close last session div
                            }
                            
                            if (empty($available_results)) {
                                echo "<p class='text-center'>No results available</p>";
                            }
                            ?>
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
