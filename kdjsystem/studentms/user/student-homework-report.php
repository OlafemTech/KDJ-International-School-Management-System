<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsuid']==0)) {
  header('location:logout.php');
} else {
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>My Homework Report</title>
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
                      <h4 class="card-title mb-0">My Homework Performance</h4>
                      <div>
                        <a href="view-homework.php" class="btn btn-primary">
                          <i class="icon-doc"></i> View Homework
                        </a>
                        <a href="submit-homework.php" class="btn btn-success ml-2">
                          <i class="icon-cloud-upload"></i> Submit Homework
                        </a>
                      </div>
                    </div>

                    <?php
                    $studentId = $_SESSION['sturecmsuid'];
                    
                    // Get student's class and subjects
                    $sql = "SELECT s.StudentClass, c.ClassName, c.Section
                           FROM tblstudent s
                           JOIN tblclass c ON s.StudentClass = c.ID
                           WHERE s.ID = :studentId";
                    $query = $dbh->prepare($sql);
                    $query->bindParam(':studentId', $studentId, PDO::PARAM_INT);
                    $query->execute();
                    $studentInfo = $query->fetch(PDO::FETCH_OBJ);
                    
                    // Get selected subject filter
                    $selectedSubject = isset($_GET['subject']) ? $_GET['subject'] : '';
                    ?>

                    <form method="GET" class="mb-4">
                      <div class="row">
                        <div class="col-md-4">
                          <div class="form-group">
                            <label>Filter by Subject</label>
                            <select name="subject" class="form-control" onchange="this.form.submit()">
                              <option value="">All Subjects</option>
                              <?php
                              $sql = "SELECT DISTINCT s.ID, s.SubjectName
                                     FROM tblsubjects s
                                     JOIN tblhomework h ON s.ID = h.SubjectID
                                     WHERE h.ClassID = :classId
                                     ORDER BY s.SubjectName";
                              $query = $dbh->prepare($sql);
                              $query->bindParam(':classId', $studentInfo->StudentClass, PDO::PARAM_INT);
                              $query->execute();
                              $subjects = $query->fetchAll(PDO::FETCH_OBJ);
                              
                              foreach($subjects as $subject) {
                                  $selected = $selectedSubject == $subject->ID ? 'selected' : '';
                                  echo "<option value='{$subject->ID}' {$selected}>{$subject->SubjectName}</option>";
                              }
                              ?>
                            </select>
                          </div>
                        </div>
                      </div>
                    </form>

                    <div class="row mb-4">
                      <?php
                      // Get overall statistics
                      $sql = "SELECT 
                             COUNT(DISTINCT h.ID) as TotalHomework,
                             COUNT(DISTINCT hs.ID) as Submitted,
                             COUNT(DISTINCT CASE WHEN hs.Status = 'Graded' THEN hs.ID END) as Graded,
                             COUNT(DISTINCT CASE WHEN hs.SubmissionDate > h.SubmissionDate THEN hs.ID END) as Late,
                             ROUND(AVG(CASE WHEN hs.Status = 'Graded' THEN hs.Grade END), 2) as AvgGrade
                             FROM tblhomework h
                             LEFT JOIN tblhomeworksubmissions hs ON h.ID = hs.HomeworkID AND hs.StudentID = :studentId
                             WHERE h.ClassID = :classId";
                      
                      if($selectedSubject) {
                          $sql .= " AND h.SubjectID = :subjectId";
                      }
                      
                      $query = $dbh->prepare($sql);
                      $query->bindParam(':studentId', $studentId, PDO::PARAM_INT);
                      $query->bindParam(':classId', $studentInfo->StudentClass, PDO::PARAM_INT);
                      if($selectedSubject) {
                          $query->bindParam(':subjectId', $selectedSubject, PDO::PARAM_INT);
                      }
                      $query->execute();
                      $stats = $query->fetch(PDO::FETCH_OBJ);
                      
                      $submissionRate = $stats->TotalHomework > 0 ? 
                                      round(($stats->Submitted / $stats->TotalHomework) * 100) : 0;
                      $onTimeRate = $stats->Submitted > 0 ? 
                                  round((($stats->Submitted - $stats->Late) / $stats->Submitted) * 100) : 0;
                      ?>
                      
                      <div class="col-md-3">
                        <div class="card bg-primary text-white">
                          <div class="card-body">
                            <h6 class="card-title">Total Homework</h6>
                            <h2><?php echo $stats->TotalHomework;?></h2>
                            <p class="mb-0">
                              <?php echo $stats->Submitted;?> Submitted
                              (<?php echo $submissionRate;?>%)
                            </p>
                          </div>
                        </div>
                      </div>
                      
                      <div class="col-md-3">
                        <div class="card bg-success text-white">
                          <div class="card-body">
                            <h6 class="card-title">Average Grade</h6>
                            <h2><?php echo $stats->AvgGrade ?: 'N/A';?></h2>
                            <p class="mb-0">
                              <?php echo $stats->Graded;?> Graded Submissions
                            </p>
                          </div>
                        </div>
                      </div>
                      
                      <div class="col-md-3">
                        <div class="card bg-info text-white">
                          <div class="card-body">
                            <h6 class="card-title">On-Time Rate</h6>
                            <h2><?php echo $onTimeRate;?>%</h2>
                            <p class="mb-0">
                              <?php echo $stats->Late;?> Late Submissions
                            </p>
                          </div>
                        </div>
                      </div>
                      
                      <div class="col-md-3">
                        <div class="card bg-warning text-white">
                          <div class="card-body">
                            <h6 class="card-title">Class Rank</h6>
                            <?php
                            // Get student's rank in class
                            $sql = "WITH StudentGrades AS (
                                   SELECT s.ID, s.StudentName,
                                   ROUND(AVG(CASE WHEN hs.Status = 'Graded' THEN hs.Grade END), 2) as AvgGrade,
                                   RANK() OVER (ORDER BY AVG(CASE WHEN hs.Status = 'Graded' THEN hs.Grade END) DESC) as Rank
                                   FROM tblstudent s
                                   JOIN tblhomework h ON s.StudentClass = h.ClassID
                                   LEFT JOIN tblhomeworksubmissions hs ON h.ID = hs.HomeworkID AND hs.StudentID = s.ID
                                   WHERE s.StudentClass = :classId";
                            
                            if($selectedSubject) {
                                $sql .= " AND h.SubjectID = :subjectId";
                            }
                            
                            $sql .= " GROUP BY s.ID, s.StudentName)
                                     SELECT Rank, COUNT(*) OVER() as Total
                                     FROM StudentGrades
                                     WHERE ID = :studentId";
                            
                            $query = $dbh->prepare($sql);
                            $query->bindParam(':classId', $studentInfo->StudentClass, PDO::PARAM_INT);
                            $query->bindParam(':studentId', $studentId, PDO::PARAM_INT);
                            if($selectedSubject) {
                                $query->bindParam(':subjectId', $selectedSubject, PDO::PARAM_INT);
                            }
                            $query->execute();
                            $rank = $query->fetch(PDO::FETCH_OBJ);
                            ?>
                            <h2><?php echo $rank ? "{$rank->Rank}/{$rank->Total}" : 'N/A';?></h2>
                            <p class="mb-0">Based on Average Grade</p>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="row mb-4">
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
                                   WHERE hs.StudentID = :studentId
                                   AND hs.Status = 'Graded'";
                            
                            if($selectedSubject) {
                                $sql .= " AND h.SubjectID = :subjectId";
                            }
                            
                            $sql .= " GROUP BY GradeRange
                                     ORDER BY MIN(Grade) DESC";
                            
                            $query = $dbh->prepare($sql);
                            $query->bindParam(':studentId', $studentId, PDO::PARAM_INT);
                            if($selectedSubject) {
                                $query->bindParam(':subjectId', $selectedSubject, PDO::PARAM_INT);
                            }
                            $query->execute();
                            $grades = $query->fetchAll(PDO::FETCH_OBJ);
                            
                            $gradeLabels = [];
                            $gradeCounts = [];
                            foreach($grades as $grade) {
                                $gradeLabels[] = $grade->GradeRange;
                                $gradeCounts[] = $grade->Count;
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

                      <div class="col-md-6">
                        <div class="card">
                          <div class="card-body">
                            <h5 class="card-title">Grade Trends</h5>
                            <canvas id="trendChart"></canvas>
                            <?php
                            // Get grade trends
                            $sql = "SELECT DATE(hs.SubmissionDate) as Date,
                                   h.Title,
                                   hs.Grade
                                   FROM tblhomeworksubmissions hs
                                   JOIN tblhomework h ON hs.HomeworkID = h.ID
                                   WHERE hs.StudentID = :studentId
                                   AND hs.Status = 'Graded'";
                            
                            if($selectedSubject) {
                                $sql .= " AND h.SubjectID = :subjectId";
                            }
                            
                            $sql .= " ORDER BY hs.SubmissionDate DESC
                                     LIMIT 10";
                            
                            $query = $dbh->prepare($sql);
                            $query->bindParam(':studentId', $studentId, PDO::PARAM_INT);
                            if($selectedSubject) {
                                $query->bindParam(':subjectId', $selectedSubject, PDO::PARAM_INT);
                            }
                            $query->execute();
                            $trends = $query->fetchAll(PDO::FETCH_OBJ);
                            
                            $trendDates = [];
                            $trendGrades = [];
                            $trendTitles = [];
                            foreach($trends as $trend) {
                                $trendDates[] = date('Y-m-d', strtotime($trend->Date));
                                $trendGrades[] = $trend->Grade;
                                $trendTitles[] = $trend->Title;
                            }
                            ?>
                            <script>
                            new Chart(document.getElementById('trendChart'), {
                                type: 'line',
                                data: {
                                    labels: <?php echo json_encode($trendDates);?>,
                                    datasets: [{
                                        label: 'Grade',
                                        data: <?php echo json_encode($trendGrades);?>,
                                        borderColor: 'rgb(75, 192, 192)',
                                        tension: 0.1
                                    }]
                                },
                                options: {
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            max: 100
                                        }
                                    },
                                    plugins: {
                                        tooltip: {
                                            callbacks: {
                                                title: function(context) {
                                                    return <?php echo json_encode($trendTitles);?>[context[0].dataIndex];
                                                }
                                            }
                                        }
                                    }
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
                            <h5 class="card-title">Recent Submissions</h5>
                            <div class="table-responsive">
                              <table class="table table-hover">
                                <thead>
                                  <tr>
                                    <th>Subject</th>
                                    <th>Title</th>
                                    <th>Due Date</th>
                                    <th>Submitted</th>
                                    <th>Status</th>
                                    <th>Grade</th>
                                    <th>Action</th>
                                  </tr>
                                </thead>
                                <tbody>
                                  <?php
                                  // Get recent submissions
                                  $sql = "SELECT h.*, s.SubjectName, c.ClassName, c.Level,
                                         hs.SubmissionText, hs.AttachmentURL, hs.Status,
                                         hs.SubmissionDate, hs.Grade, hs.Feedback
                                  FROM tblhomework h
                                  JOIN tblsubjects s ON h.SubjectID = s.ID
                                  JOIN tblclass c ON h.ClassID = c.ID
                                  LEFT JOIN tblhomeworksubmissions hs ON h.ID = hs.HomeworkID 
                                       AND hs.StudentId = :studentId
                                  WHERE h.ClassID = (SELECT StudentClass FROM tblstudent WHERE ID = :studentId)
                                  ORDER BY h.DueDate DESC";
                                  $query = $dbh->prepare($sql);
                                  $query->bindParam(':studentId', $_SESSION['sturecmsuid'], PDO::PARAM_INT);
                                  $query->execute();
                                  $submissions = $query->fetchAll(PDO::FETCH_OBJ);
                                  
                                  foreach($submissions as $submission) {
                                      $dueDate = date('Y-m-d', strtotime($submission->DueDate));
                                      $submittedDate = $submission->SubmissionDate ? 
                                                     date('Y-m-d', strtotime($submission->SubmissionDate)) : '';
                                      $isLate = $submittedDate && $submittedDate > $dueDate;
                                  ?>
                                  <tr>
                                    <td><?php echo htmlentities($submission->SubjectName);?></td>
                                    <td><?php echo htmlentities($submission->Title);?></td>
                                    <td><?php echo $dueDate;?></td>
                                    <td>
                                      <?php if($submittedDate) { ?>
                                        <?php echo $submittedDate;?>
                                        <?php if($isLate) { ?>
                                          <span class="badge badge-warning">Late</span>
                                        <?php } ?>
                                      <?php } else { ?>
                                        Not Submitted
                                      <?php } ?>
                                    </td>
                                    <td>
                                      <?php
                                      if(!$submittedDate) {
                                          if(strtotime($dueDate) < time()) {
                                              echo '<span class="badge badge-danger">Overdue</span>';
                                          } else {
                                              echo '<span class="badge badge-warning">Pending</span>';
                                          }
                                      } else {
                                          if($submission->Status == 'Graded') {
                                              echo '<span class="badge badge-success">Graded</span>';
                                          } else {
                                              echo '<span class="badge badge-info">Submitted</span>';
                                          }
                                      }
                                      ?>
                                    </td>
                                    <td>
                                      <?php
                                      if($submission->Status == 'Graded') {
                                          echo $submission->Grade;
                                          if($submission->Grade >= 90) {
                                              echo ' <i class="icon-star text-warning"></i>';
                                          }
                                      } else {
                                          echo 'N/A';
                                      }
                                      ?>
                                    </td>
                                    <td>
                                      <?php if(!$submittedDate && strtotime($dueDate) >= time()) { ?>
                                        <a href="submit-homework.php?id=<?php echo $submission->ID;?>" 
                                           class="btn btn-outline-primary btn-sm">
                                          Submit
                                        </a>
                                      <?php } else { ?>
                                        <a href="view-submission.php?id=<?php echo $submission->ID;?>" 
                                           class="btn btn-outline-info btn-sm">
                                          View
                                        </a>
                                      <?php } ?>
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
