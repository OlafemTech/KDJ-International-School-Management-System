<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsteacherId']==0)) {
  header('location:logout.php');
} else {
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Homework Statistics</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <div class="d-flex justify-content-between align-items-center mb-4">
                      <h4 class="card-title mb-0">Homework Statistics</h4>
                      <div>
                        <a href="homework-submissions.php" class="btn btn-primary">
                          <i class="icon-doc"></i> View Submissions
                        </a>
                        <a href="manage-homework.php" class="btn btn-secondary ml-2">
                          <i class="icon-notebook"></i> Manage Homework
                        </a>
                      </div>
                    </div>

                    <?php
                    $teacherId = $_SESSION['sturecmsteacherId'];
                    
                    // Get teacher's subjects and classes
                    $sql = "SELECT DISTINCT s.ID as SubjectID, s.SubjectName,
                           c.ID as ClassID, c.ClassName, c.Section
                           FROM tblsubjectteacherclass stc
                           JOIN tblsubjects s ON stc.SubjectID = s.ID
                           JOIN tblclass c ON stc.ClassID = c.ID
                           WHERE stc.TeacherID = :teacherId
                           ORDER BY s.SubjectName, c.ClassName, c.Section";
                    $query = $dbh->prepare($sql);
                    $query->bindParam(':teacherId', $teacherId, PDO::PARAM_INT);
                    $query->execute();
                    $teacherClasses = $query->fetchAll(PDO::FETCH_OBJ);
                    
                    // Get overall statistics
                    $sql = "SELECT 
                           COUNT(DISTINCT h.ID) as TotalHomework,
                           COUNT(DISTINCT hs.ID) as TotalSubmissions,
                           COUNT(DISTINCT CASE WHEN hs.Status = 'Graded' THEN hs.ID END) as GradedSubmissions,
                           ROUND(AVG(CASE WHEN hs.Grade IS NOT NULL THEN hs.Grade END), 2) as AverageGrade,
                           COUNT(DISTINCT CASE WHEN hs.SubmissionDate > h.SubmissionDate THEN hs.ID END) as LateSubmissions
                           FROM tblhomework h
                           JOIN tblsubjectteacherclass stc ON h.SubjectID = stc.SubjectID
                           LEFT JOIN tblhomeworksubmissions hs ON h.ID = hs.HomeworkID
                           WHERE stc.TeacherID = :teacherId";
                    $query = $dbh->prepare($sql);
                    $query->bindParam(':teacherId', $teacherId, PDO::PARAM_INT);
                    $query->execute();
                    $stats = $query->fetch(PDO::FETCH_OBJ);
                    ?>

                    <div class="row mb-4">
                      <div class="col-md-2">
                        <div class="card bg-primary text-white">
                          <div class="card-body text-center">
                            <h3 class="mb-2"><?php echo $stats->TotalHomework;?></h3>
                            <p class="mb-0">Total Homework</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-2">
                        <div class="card bg-success text-white">
                          <div class="card-body text-center">
                            <h3 class="mb-2"><?php echo $stats->TotalSubmissions;?></h3>
                            <p class="mb-0">Submissions</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-2">
                        <div class="card bg-info text-white">
                          <div class="card-body text-center">
                            <h3 class="mb-2"><?php echo $stats->GradedSubmissions;?></h3>
                            <p class="mb-0">Graded</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-2">
                        <div class="card bg-warning text-white">
                          <div class="card-body text-center">
                            <h3 class="mb-2"><?php echo $stats->LateSubmissions;?></h3>
                            <p class="mb-0">Late</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-2">
                        <div class="card bg-danger text-white">
                          <div class="card-body text-center">
                            <h3 class="mb-2"><?php echo $stats->AverageGrade ?: 'N/A';?></h3>
                            <p class="mb-0">Avg Grade</p>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="row mb-4">
                      <div class="col-md-6">
                        <div class="card">
                          <div class="card-body">
                            <h5 class="card-title">Subject-wise Submission Rate</h5>
                            <canvas id="subjectChart"></canvas>
                            <?php
                            // Get subject-wise statistics
                            $sql = "SELECT s.SubjectName,
                                   COUNT(DISTINCT h.ID) as TotalHomework,
                                   COUNT(DISTINCT hs.ID) as Submissions
                                   FROM tblsubjects s
                                   JOIN tblsubjectteacherclass stc ON s.ID = stc.SubjectID
                                   LEFT JOIN tblhomework h ON s.ID = h.SubjectID
                                   LEFT JOIN tblhomeworksubmissions hs ON h.ID = hs.HomeworkID
                                   WHERE stc.TeacherID = :teacherId
                                   GROUP BY s.ID
                                   ORDER BY s.SubjectName";
                            $query = $dbh->prepare($sql);
                            $query->bindParam(':teacherId', $teacherId, PDO::PARAM_INT);
                            $query->execute();
                            $subjectStats = $query->fetchAll(PDO::FETCH_OBJ);
                            
                            $subjectLabels = [];
                            $submissionRates = [];
                            foreach($subjectStats as $stat) {
                                $subjectLabels[] = $stat->SubjectName;
                                $rate = $stat->TotalHomework > 0 ? 
                                       round(($stat->Submissions / ($stat->TotalHomework * count($teacherClasses))) * 100, 1) : 0;
                                $submissionRates[] = $rate;
                            }
                            ?>
                            <script>
                            new Chart(document.getElementById('subjectChart'), {
                                type: 'bar',
                                data: {
                                    labels: <?php echo json_encode($subjectLabels);?>,
                                    datasets: [{
                                        label: 'Submission Rate (%)',
                                        data: <?php echo json_encode($submissionRates);?>,
                                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                                        borderColor: 'rgba(54, 162, 235, 1)',
                                        borderWidth: 1
                                    }]
                                },
                                options: {
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            max: 100
                                        }
                                    }
                                }
                            });
                            </script>
                          </div>
                        </div>
                      </div>

                      <div class="col-md-6">
                        <div class="card">
                          <div class="card-body">
                            <h5 class="card-title">Grade Distribution</h5>
                            <canvas id="gradeChart"></canvas>
                            <?php
                            // Get grade distribution
                            $sql = "SELECT 
                                   CASE 
                                     WHEN Grade >= 90 THEN 'A (90-100)'
                                     WHEN Grade >= 80 THEN 'B (80-89)'
                                     WHEN Grade >= 70 THEN 'C (70-79)'
                                     WHEN Grade >= 60 THEN 'D (60-69)'
                                     ELSE 'F (0-59)'
                                   END as GradeRange,
                                   COUNT(*) as Count
                                   FROM tblhomeworksubmissions hs
                                   JOIN tblhomework h ON hs.HomeworkID = h.ID
                                   JOIN tblsubjectteacherclass stc ON h.SubjectID = stc.SubjectID
                                   WHERE stc.TeacherID = :teacherId
                                   AND hs.Status = 'Graded'
                                   GROUP BY GradeRange
                                   ORDER BY MIN(Grade) DESC";
                            $query = $dbh->prepare($sql);
                            $query->bindParam(':teacherId', $teacherId, PDO::PARAM_INT);
                            $query->execute();
                            $gradeStats = $query->fetchAll(PDO::FETCH_OBJ);
                            
                            $gradeLabels = [];
                            $gradeCounts = [];
                            foreach($gradeStats as $stat) {
                                $gradeLabels[] = $stat->GradeRange;
                                $gradeCounts[] = $stat->Count;
                            }
                            ?>
                            <script>
                            new Chart(document.getElementById('gradeChart'), {
                                type: 'pie',
                                data: {
                                    labels: <?php echo json_encode($gradeLabels);?>,
                                    datasets: [{
                                        data: <?php echo json_encode($gradeCounts);?>,
                                        backgroundColor: [
                                            'rgba(75, 192, 192, 0.5)',
                                            'rgba(54, 162, 235, 0.5)',
                                            'rgba(255, 206, 86, 0.5)',
                                            'rgba(255, 159, 64, 0.5)',
                                            'rgba(255, 99, 132, 0.5)'
                                        ],
                                        borderColor: [
                                            'rgba(75, 192, 192, 1)',
                                            'rgba(54, 162, 235, 1)',
                                            'rgba(255, 206, 86, 1)',
                                            'rgba(255, 159, 64, 1)',
                                            'rgba(255, 99, 132, 1)'
                                        ],
                                        borderWidth: 1
                                    }]
                                }
                            });
                            </script>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="row">
                      <div class="col-md-12">
                        <div class="card">
                          <div class="card-body">
                            <h5 class="card-title">Class-wise Performance</h5>
                            <div class="table-responsive">
                              <table class="table table-hover">
                                <thead>
                                  <tr>
                                    <th>Class</th>
                                    <th>Subject</th>
                                    <th>Total Homework</th>
                                    <th>Submissions</th>
                                    <th>Late</th>
                                    <th>Average Grade</th>
                                    <th>Action</th>
                                  </tr>
                                </thead>
                                <tbody>
                                  <?php
                                  foreach($teacherClasses as $class) {
                                      // Get class statistics
                                      $sql = "SELECT 
                                             COUNT(DISTINCT h.ID) as TotalHomework,
                                             COUNT(DISTINCT hs.ID) as Submissions,
                                             COUNT(DISTINCT CASE WHEN hs.SubmissionDate > h.SubmissionDate THEN hs.ID END) as Late,
                                             ROUND(AVG(CASE WHEN hs.Grade IS NOT NULL THEN hs.Grade END), 2) as AvgGrade
                                             FROM tblhomework h
                                             LEFT JOIN tblhomeworksubmissions hs ON h.ID = hs.HomeworkID
                                             WHERE h.SubjectID = :subjectId
                                             AND h.ClassID = :classId";
                                      $query = $dbh->prepare($sql);
                                      $query->bindParam(':subjectId', $class->SubjectID, PDO::PARAM_INT);
                                      $query->bindParam(':classId', $class->ClassID, PDO::PARAM_INT);
                                      $query->execute();
                                      $classStats = $query->fetch(PDO::FETCH_OBJ);
                                  ?>
                                  <tr>
                                    <td><?php echo htmlentities($class->ClassName . ' ' . $class->Section);?></td>
                                    <td><?php echo htmlentities($class->SubjectName);?></td>
                                    <td><?php echo $classStats->TotalHomework;?></td>
                                    <td>
                                      <span class="badge badge-info">
                                        <?php echo $classStats->Submissions;?> / 
                                        <?php echo $classStats->TotalHomework * count($teacherClasses);?>
                                      </span>
                                    </td>
                                    <td>
                                      <?php if($classStats->Late > 0) { ?>
                                      <span class="badge badge-warning"><?php echo $classStats->Late;?></span>
                                      <?php } else { ?>
                                      <span class="badge badge-success">0</span>
                                      <?php } ?>
                                    </td>
                                    <td><?php echo $classStats->AvgGrade ?: 'N/A';?></td>
                                    <td>
                                      <a href="homework-submissions.php?subject=<?php echo $class->SubjectID;?>&class=<?php echo $class->ClassID;?>" 
                                         class="btn btn-outline-primary btn-sm">
                                        View Submissions
                                      </a>
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
<?php } ?>
