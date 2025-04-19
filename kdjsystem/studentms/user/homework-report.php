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
                      <h4 class="card-title mb-0">My Homework Report</h4>
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
                    
                    // Get student class
                    $sql = "SELECT StudentClass FROM tblstudent WHERE ID = :studentId";
                    $query = $dbh->prepare($sql);
                    $query->bindParam(':studentId', $studentId, PDO::PARAM_INT);
                    $query->execute();
                    $classId = $query->fetch(PDO::FETCH_OBJ)->StudentClass;
                    
                    // Get homework statistics
                    $sql = "SELECT 
                           COUNT(DISTINCT h.ID) as TotalHomework,
                           COUNT(DISTINCT hs.ID) as Submitted,
                           COUNT(DISTINCT CASE WHEN hs.Status = 'Graded' THEN hs.ID END) as Graded,
                           COUNT(DISTINCT CASE WHEN hs.SubmissionDate > h.SubmissionDate THEN hs.ID END) as Late,
                           ROUND(AVG(CASE WHEN hs.Grade IS NOT NULL THEN hs.Grade END), 2) as AvgGrade,
                           COUNT(DISTINCT CASE WHEN h.SubmissionDate >= CURDATE() THEN h.ID END) as Pending
                           FROM tblhomework h
                           JOIN tblsubjectteacherclass stc ON h.SubjectID = stc.SubjectID
                           LEFT JOIN tblhomeworksubmissions hs ON h.ID = hs.HomeworkID AND hs.StudentID = :studentId
                           WHERE h.ClassID = :classId";
                    $query = $dbh->prepare($sql);
                    $query->bindParam(':studentId', $studentId, PDO::PARAM_INT);
                    $query->bindParam(':classId', $classId, PDO::PARAM_INT);
                    $query->execute();
                    $hwStats = $query->fetch(PDO::FETCH_OBJ);
                    ?>
                    
                    <div class="row mb-4">
                      <div class="col-md-2">
                        <div class="card bg-primary text-white">
                          <div class="card-body text-center">
                            <h3 class="mb-2"><?php echo $hwStats->TotalHomework;?></h3>
                            <p class="mb-0">Total</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-2">
                        <div class="card bg-success text-white">
                          <div class="card-body text-center">
                            <h3 class="mb-2"><?php echo $hwStats->Submitted;?></h3>
                            <p class="mb-0">Submitted</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-2">
                        <div class="card bg-info text-white">
                          <div class="card-body text-center">
                            <h3 class="mb-2"><?php echo $hwStats->Graded;?></h3>
                            <p class="mb-0">Graded</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-2">
                        <div class="card bg-warning text-white">
                          <div class="card-body text-center">
                            <h3 class="mb-2"><?php echo $hwStats->Late;?></h3>
                            <p class="mb-0">Late</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-2">
                        <div class="card bg-danger text-white">
                          <div class="card-body text-center">
                            <h3 class="mb-2"><?php echo $hwStats->Pending;?></h3>
                            <p class="mb-0">Pending</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-2">
                        <div class="card bg-secondary text-white">
                          <div class="card-body text-center">
                            <h3 class="mb-2"><?php echo $hwStats->AvgGrade ?: 'N/A';?></h3>
                            <p class="mb-0">Avg Grade</p>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="row mb-4">
                      <div class="col-md-6">
                        <div class="card">
                          <div class="card-body">
                            <h5 class="card-title">Recent Grades</h5>
                            <canvas id="gradesChart"></canvas>
                            <?php
                            // Get recent grades
                            $sql = "SELECT h.Title, hs.Grade, hs.SubmissionDate
                                   FROM tblhomeworksubmissions hs
                                   JOIN tblhomework h ON hs.HomeworkID = h.ID
                                   WHERE hs.StudentID = :studentId
                                   AND hs.Status = 'Graded'
                                   ORDER BY hs.SubmissionDate DESC
                                   LIMIT 5";
                            $query = $dbh->prepare($sql);
                            $query->bindParam(':studentId', $studentId, PDO::PARAM_INT);
                            $query->execute();
                            $grades = $query->fetchAll(PDO::FETCH_OBJ);
                            
                            $gradeLabels = [];
                            $gradeValues = [];
                            foreach(array_reverse($grades) as $grade) {
                                $gradeLabels[] = substr($grade->Title, 0, 15) . '...';
                                $gradeValues[] = $grade->Grade;
                            }
                            ?>
                            <script>
                            new Chart(document.getElementById('gradesChart'), {
                                type: 'line',
                                data: {
                                    labels: <?php echo json_encode($gradeLabels);?>,
                                    datasets: [{
                                        label: 'Grade',
                                        data: <?php echo json_encode($gradeValues);?>,
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
                            <h5 class="card-title">Subject Performance</h5>
                            <canvas id="subjectChart"></canvas>
                            <?php
                            // Get subject-wise performance
                            $sql = "SELECT s.SubjectName,
                                   COUNT(DISTINCT h.ID) as Total,
                                   COUNT(DISTINCT hs.ID) as Submitted
                                   FROM tblsubjects s
                                   JOIN tblsubjectteacherclass stc ON s.ID = stc.SubjectID
                                   LEFT JOIN tblhomework h ON s.ID = h.SubjectID
                                   LEFT JOIN tblhomeworksubmissions hs ON h.ID = hs.HomeworkID AND hs.StudentID = :studentId
                                   WHERE stc.ClassID = :classId
                                   GROUP BY s.ID";
                            $query = $dbh->prepare($sql);
                            $query->bindParam(':studentId', $studentId, PDO::PARAM_INT);
                            $query->bindParam(':classId', $classId, PDO::PARAM_INT);
                            $query->execute();
                            $subjects = $query->fetchAll(PDO::FETCH_OBJ);
                            
                            $subjectLabels = [];
                            $submissionRates = [];
                            foreach($subjects as $subject) {
                                $subjectLabels[] = $subject->SubjectName;
                                $rate = $subject->Total > 0 ? 
                                       round(($subject->Submitted / $subject->Total) * 100) : 0;
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
                                  </tr>
                                </thead>
                                <tbody>
                                  <?php
                                  $sql = "SELECT s.SubjectName, h.Title, h.SubmissionDate,
                                         hs.SubmissionDate as Submitted, hs.Status,
                                         hs.Grade, hs.TeacherComments
                                         FROM tblhomework h
                                         JOIN tblsubjects s ON h.SubjectID = s.ID
                                         LEFT JOIN tblhomeworksubmissions hs ON h.ID = hs.HomeworkID 
                                         AND hs.StudentID = :studentId
                                         WHERE h.ClassID = :classId
                                         ORDER BY h.SubmissionDate DESC
                                         LIMIT 10";
                                  $query = $dbh->prepare($sql);
                                  $query->bindParam(':studentId', $studentId, PDO::PARAM_INT);
                                  $query->bindParam(':classId', $classId, PDO::PARAM_INT);
                                  $query->execute();
                                  $submissions = $query->fetchAll(PDO::FETCH_OBJ);
                                  
                                  foreach($submissions as $sub) {
                                      $isLate = $sub->Submitted && 
                                               strtotime($sub->Submitted) > strtotime($sub->SubmissionDate);
                                  ?>
                                  <tr>
                                    <td><?php echo htmlentities($sub->SubjectName);?></td>
                                    <td><?php echo htmlentities($sub->Title);?></td>
                                    <td><?php echo date('Y-m-d', strtotime($sub->SubmissionDate));?></td>
                                    <td>
                                      <?php if($sub->Submitted) { ?>
                                        <?php if($isLate) { ?>
                                          <span class="text-warning">
                                            <?php echo date('Y-m-d', strtotime($sub->Submitted));?>
                                            (Late)
                                          </span>
                                        <?php } else { ?>
                                          <span class="text-success">
                                            <?php echo date('Y-m-d', strtotime($sub->Submitted));?>
                                          </span>
                                        <?php } ?>
                                      <?php } else { ?>
                                        <span class="text-danger">Not Submitted</span>
                                      <?php } ?>
                                    </td>
                                    <td>
                                      <?php if(!$sub->Submitted) { ?>
                                        <?php if(strtotime($sub->SubmissionDate) < time()) { ?>
                                          <span class="badge badge-danger">Overdue</span>
                                        <?php } else { ?>
                                          <span class="badge badge-warning">Pending</span>
                                        <?php } ?>
                                      <?php } else if($sub->Status == 'Graded') { ?>
                                        <span class="badge badge-success">Graded</span>
                                      <?php } else { ?>
                                        <span class="badge badge-info">Submitted</span>
                                      <?php } ?>
                                    </td>
                                    <td>
                                      <?php if($sub->Grade) { ?>
                                        <span class="badge badge-primary" 
                                              title="<?php echo htmlentities($sub->TeacherComments);?>">
                                          <?php echo $sub->Grade;?>
                                        </span>
                                      <?php } else { ?>
                                        -
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
