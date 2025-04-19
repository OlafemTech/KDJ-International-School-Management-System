<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
} else {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>View Student Grades</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <!-- Layout styles -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/select2.min.css">
    <link rel="stylesheet" href="vendors/datatables/dataTables.bootstrap4.min.css">
    <style>
        .performance-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
        }
        .performance-Excellent { background-color: #28a745; color: white; }
        .performance-VeryGood { background-color: #17a2b8; color: white; }
        .performance-Good { background-color: #ffc107; color: black; }
        .performance-Average { background-color: #fd7e14; color: white; }
        .performance-NeedsImprovement { background-color: #dc3545; color: white; }
        .btn-group .btn { margin: 0 2px; }
    </style>
</head>
<body>
    <div class="container-scroller">
        <?php include_once('includes/header.php');?>
        <div class="container-fluid page-body-wrapper">
            <?php include_once('includes/sidebar.php');?>
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="row">
                        <div class="col-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">View Student Grades</h4>
                                    <form class="forms-sample" method="get">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="ClassID">Class</label>
                                                    <select class="form-control select2" name="class" id="ClassID" required>
                                                        <option value="">Select Class</option>
                                                        <?php
                                                        $sql = "SELECT * from tblclass ORDER BY ClassName, Level";
                                                        $query = $dbh->prepare($sql);
                                                        $query->execute();
                                                        $classes = $query->fetchAll(PDO::FETCH_OBJ);
                                                        foreach($classes as $class) {
                                                            $selected = ($_GET['class'] == $class->ID) ? 'selected' : '';
                                                            echo '<option value="'.$class->ID.'" '.$selected.'>'.$class->ClassName.' '.$class->Level.'</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="Term">Term</label>
                                                    <select class="form-control" name="term" id="Term" required>
                                                        <option value="">Select Term</option>
                                                        <?php
                                                        $terms = array('1st Term', '2nd Term', '3rd Term');
                                                        foreach($terms as $term) {
                                                            $selected = ($_GET['term'] == $term) ? 'selected' : '';
                                                            echo '<option value="'.$term.'" '.$selected.'>'.$term.'</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>&nbsp;</label>
                                                    <button type="submit" class="btn btn-primary btn-block">View Grades</button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>

                                    <?php if(isset($_GET['class']) && isset($_GET['term'])) { ?>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5>Class Results</h5>
                                        <a href="print-results.php?class=<?php echo intval($_GET['class']); ?>&term=<?php echo urlencode($_GET['term']); ?>" 
                                           class="btn btn-primary" target="_blank">
                                            <i class="icon-printer"></i> Print Results
                                        </a>
                                    </div>
                                    <div class="table-responsive mt-4">
                                        <table class="table table-bordered table-hover" id="gradesTable">
                                            <thead>
                                                <tr>
                                                    <th>Student Name</th>
                                                    <th>Subject</th>
                                                    <th>CA1 (20 max)</th>
                                                    <th>CA2 (20 max)</th>
                                                    <th>Total Test (40)</th>
                                                    <th>Exam (60 max)</th>
                                                    <th>Total Score (100)</th>
                                                    <th>Performance</th>
                                                    <th>Teacher's Comment</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $classId = intval($_GET['class']);
                                                $term = $_GET['term'];
                                                
                                                // Get the current session from the class
                                                $sql = "SELECT Session FROM tblclass WHERE ID = :classId LIMIT 1";
                                                $query = $dbh->prepare($sql);
                                                $query->bindParam(':classId', $classId, PDO::PARAM_INT);
                                                $query->execute();
                                                $currentSession = $query->fetchColumn();

                                                $sql = "SELECT 
                                                       g.ID, g.StudentID, 
                                                       g.CA1, g.CA2, g.Exam,
                                                       g.TeacherComment,
                                                       (g.CA1 + g.CA2) as TotalTest,
                                                       (g.CA1 + g.CA2 + g.Exam) as TotalScore,
                                                       CASE 
                                                           WHEN (g.CA1 + g.CA2 + g.Exam) >= 75 THEN 'Excellent'
                                                           WHEN (g.CA1 + g.CA2 + g.Exam) >= 65 THEN 'VeryGood'
                                                           WHEN (g.CA1 + g.CA2 + g.Exam) >= 55 THEN 'Good'
                                                           WHEN (g.CA1 + g.CA2 + g.Exam) >= 45 THEN 'Average'
                                                           ELSE 'NeedsImprovement'
                                                       END as Performance,
                                                       s.StudentName, sub.SubjectName, a.AdminName as TeacherName 
                                                       FROM tblgrades g
                                                       JOIN tblstudent s ON g.StudentID = s.ID
                                                       JOIN tblsubjects sub ON g.SubjectID = sub.ID
                                                       JOIN tbladmin a ON g.TeacherID = a.ID
                                                       WHERE g.ClassID = :classId 
                                                       AND g.Term = :term
                                                       AND g.Session = :session
                                                       ORDER BY s.StudentName, sub.SubjectName";
                                                
                                                $query = $dbh->prepare($sql);
                                                $query->bindParam(':classId', $classId, PDO::PARAM_INT);
                                                $query->bindParam(':term', $term, PDO::PARAM_STR);
                                                $query->bindParam(':session', $currentSession, PDO::PARAM_STR);
                                                $query->execute();
                                                
                                                while($row = $query->fetch(PDO::FETCH_ASSOC)) {
                                                    echo "<tr>";
                                                    echo "<td>".$row['StudentName']."</td>";
                                                    echo "<td>".$row['SubjectName']."</td>";
                                                    echo "<td>".number_format($row['CA1'], 2)."</td>";
                                                    echo "<td>".number_format($row['CA2'], 2)."</td>";
                                                    echo "<td>".number_format($row['TotalTest'], 2)."</td>";
                                                    echo "<td>".number_format($row['Exam'], 2)."</td>";
                                                    echo "<td>".number_format($row['TotalScore'], 2)."</td>";
                                                    echo "<td><span class='performance-badge performance-".$row['Performance']."'>".$row['Performance']."</span></td>";
                                                    echo "<td>".$row['TeacherComment']."</td>";
                                                    echo "<td>
                                                        <div class='btn-group' role='group'>
                                                            <a href='view-student-result.php?student=".$row['StudentID']."&class=".$classId."&term=".urlencode($term)."' 
                                                               class='btn btn-info btn-sm'>
                                                                <i class='icon-eye'></i> View
                                                            </a>
                                                            <a href='edit-grade.php?id=".$row['ID']."' 
                                                               class='btn btn-warning btn-sm'>
                                                                <i class='icon-pencil'></i> Edit
                                                            </a>
                                                        </div>
                                                    </td>";
                                                    echo "</tr>";
                                                }
                                                ?>
                                            </tbody>
                                        </table>
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
    <!-- plugins:js -->
    <script src="vendors/js/vendor.bundle.base.js"></script>
    <script src="js/select2.min.js"></script>
    <script src="vendors/datatables/jquery.dataTables.min.js"></script>
    <script src="vendors/datatables/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select2').select2();
            
            // Initialize DataTable
            var table = $('#gradesTable').DataTable({
                "pageLength": 25,
                "ordering": true,
                "order": [[0, "asc"], [1, "asc"]],
                "language": {
                    "search": "Search grades:",
                    "lengthMenu": "Show _MENU_ grades per page",
                    "info": "Showing _START_ to _END_ of _TOTAL_ grades"
                }
            });
        });
    </script>
</body>
</html>
<?php } ?>
