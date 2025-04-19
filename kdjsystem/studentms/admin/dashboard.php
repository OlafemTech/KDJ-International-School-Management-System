<?php
if(session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if admin is logged in
if (!isset($_SESSION['sturecmsaid']) || strlen($_SESSION['sturecmsaid']) == 0) {
    header('location: login.php');
    exit();
}

include('includes/dbconnection.php');

try {
    // Get total classes
    $sql1 = "SELECT COUNT(*) as total FROM tblclass";
    $query1 = $dbh->prepare($sql1);
    $query1->execute();
    $result1 = $query1->fetch(PDO::FETCH_ASSOC);
    $totalclass = $result1['total'] ?? 0;

    // Get total students and gender statistics
    $sql2 = "SELECT 
             COUNT(*) as total,
             SUM(CASE WHEN Gender = 'Male' THEN 1 ELSE 0 END) as male_count,
             SUM(CASE WHEN Gender = 'Female' THEN 1 ELSE 0 END) as female_count
             FROM tblstudent";
    $query2 = $dbh->prepare($sql2);
    $query2->execute();
    $result2 = $query2->fetch(PDO::FETCH_ASSOC);
    $totalstudents = $result2['total'] ?? 0;
    $malestudents = $result2['male_count'] ?? 0;
    $femalestudents = $result2['female_count'] ?? 0;

    // Get total notices
    $sql3 = "SELECT COUNT(*) as total FROM tblnotice";
    $query3 = $dbh->prepare($sql3);
    $query3->execute();
    $result3 = $query3->fetch(PDO::FETCH_ASSOC);
    $totalnotice = $result3['total'] ?? 0;

    // Get teacher statistics
    $sql4 = "SELECT 
             COUNT(*) as total,
             SUM(CASE WHEN Gender = 'Male' THEN 1 ELSE 0 END) as male_count,
             SUM(CASE WHEN Gender = 'Female' THEN 1 ELSE 0 END) as female_count,
             COUNT(DISTINCT Qualification) as qual_count
             FROM tblteacher";
    $query4 = $dbh->prepare($sql4);
    $query4->execute();
    $result4 = $query4->fetch(PDO::FETCH_ASSOC);
    $totalteachers = $result4['total'] ?? 0;
    $maleteachers = $result4['male_count'] ?? 0;
    $femaleteachers = $result4['female_count'] ?? 0;
    $qualifications = $result4['qual_count'] ?? 0;

} catch (PDOException $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    $totalclass = $totalstudents = $totalnotice = $totalteachers = 0;
    $malestudents = $femalestudents = $maleteachers = $femaleteachers = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Student Management System | Dashboard</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <!-- Plugin css for this page -->
    <link rel="stylesheet" href="vendors/daterangepicker/daterangepicker.css">
    <link rel="stylesheet" href="vendors/chartist/chartist.min.css">
    <!-- Layout styles -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/custom-header.css">
    <link rel="stylesheet" href="css/dashboard-stats.css">
    <style>
      @keyframes rotate {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
      }
      .rotating {
        animation: rotate 1s linear infinite;
      }
      .toast {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        border-radius: 4px;
        color: white;
        opacity: 0;
        transition: opacity 0.3s ease;
        z-index: 9999;
      }
      .toast.visible {
        opacity: 1;
      }
      .toast-success {
        background-color: #28a745;
      }
      .toast-error {
        background-color: #dc3545;
      }
    </style>
  </head>
  <body>
    <div class="container-scroller">
      <!-- partial:partials/_navbar.html -->
      <header>
        <?php include_once('includes/header.php');?>
      </header>
      <!-- partial -->
      <div class="container-fluid page-body-wrapper">
        <!-- partial:partials/_sidebar.html -->
        <?php include_once('includes/sidebar.php');?>
        <!-- partial -->
        <div class="main-panel">
          <div class="content-wrapper">
            <!-- Summary Cards -->
            <div class="row">
              <div class="col-md-12 grid-margin">
                <div class="card">
                  <div class="card-body">
                    <div class="row">
                      <div class="col-md-12">
                        <div class="d-sm-flex align-items-baseline report-summary-header">
                          <h5 class="font-weight-semibold">Report Summary</h5>
                          <span class="ml-auto">Last Updated: <span class="last-updated"><?php echo date('M d, Y H:i:s'); ?></span></span>
                          <button class="btn btn-icons border-0 p-2"><i class="icon-refresh"></i></button>
                        </div>
                      </div>
                    </div>
                    <div class="row report-inner-cards-wrapper">
                      <!-- Classes Card -->
                      <div class="col-md-6 col-xl-3 report-inner-card">
                        <div class="inner-card-text">
                          <span class="report-title">TOTAL CLASSES</span>
                          <h4 class="total-classes"><?php echo $totalclass; ?></h4>
                          <a href="manage-class.php" class="small text-muted">View Details</a>
                        </div>
                        <div class="inner-card-icon bg-success">
                          <i class="icon-graduation"></i>
                        </div>
                      </div>
                      <!-- Students Card -->
                      <div class="col-md-6 col-xl-3 report-inner-card">
                        <div class="inner-card-text">
                          <span class="report-title">TOTAL STUDENTS</span>
                          <h4 class="total-students"><?php echo $totalstudents; ?></h4>
                          <a href="manage-students.php" class="small text-muted">View Details</a>
                        </div>
                        <div class="inner-card-icon bg-primary">
                          <i class="icon-people"></i>
                        </div>
                      </div>
                      <!-- Teachers Card -->
                      <div class="col-md-6 col-xl-3 report-inner-card">
                        <div class="inner-card-text">
                          <span class="report-title">TOTAL TEACHERS</span>
                          <h4 class="total-teachers"><?php echo $totalteachers; ?></h4>
                          <a href="manage-teachers.php" class="small text-muted">View Details</a>
                        </div>
                        <div class="inner-card-icon bg-info">
                          <i class="icon-user"></i>
                        </div>
                      </div>
                      <!-- Notices Card -->
                      <div class="col-md-6 col-xl-3 report-inner-card">
                        <div class="inner-card-text">
                          <span class="report-title">TOTAL NOTICES</span>
                          <h4 class="total-notices"><?php echo $totalnotice; ?></h4>
                          <a href="manage-notice.php" class="small text-muted">View Details</a>
                        </div>
                        <div class="inner-card-icon bg-warning">
                          <i class="icon-bell"></i>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Gender Statistics -->
            <div class="row">
              <!-- Student Gender Statistics -->
              <div class="col-md-6 grid-margin">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title">Student Gender Distribution</h4>
                    <div class="row mt-3">
                      <div class="col-6">
                        <div class="d-flex align-items-center">
                          <div class="icon-square bg-primary text-white mr-3">
                            <i class="icon-user"></i>
                          </div>
                          <div>
                            <h3 class="mb-0 male-students"><?php echo $malestudents; ?></h3>
                            <p class="text-muted mb-0">Male Students</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-6">
                        <div class="d-flex align-items-center">
                          <div class="icon-square bg-danger text-white mr-3">
                            <i class="icon-user-female"></i>
                          </div>
                          <div>
                            <h3 class="mb-0 female-students"><?php echo $femalestudents; ?></h3>
                            <p class="text-muted mb-0">Female Students</p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Teacher Gender Statistics -->
              <div class="col-md-6 grid-margin">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title">Teacher Gender Distribution</h4>
                    <div class="row mt-3">
                      <div class="col-6">
                        <div class="d-flex align-items-center">
                          <div class="icon-square bg-info text-white mr-3">
                            <i class="icon-user"></i>
                          </div>
                          <div>
                            <h3 class="mb-0 male-teachers"><?php echo $maleteachers; ?></h3>
                            <p class="text-muted mb-0">Male Teachers</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-6">
                        <div class="d-flex align-items-center">
                          <div class="icon-square bg-warning text-white mr-3">
                            <i class="icon-user-female"></i>
                          </div>
                          <div>
                            <h3 class="mb-0 female-teachers"><?php echo $femaleteachers; ?></h3>
                            <p class="text-muted mb-0">Female Teachers</p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Additional Teacher Statistics -->
            <div class="row">
              <div class="col-md-12 grid-margin">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title">Teacher Qualifications Overview</h4>
                    <p class="card-description">Distribution of teachers across different qualification levels</p>
                    <div class="row mt-3">
                      <div class="col-12">
                        <div class="table-responsive">
                          <table class="table table-hover qualifications-table">
                            <thead>
                              <tr>
                                <th>Qualification</th>
                                <th>Count</th>
                                <th>Percentage</th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php
                              $sql = "SELECT Qualification, COUNT(*) as count 
                                     FROM tblteacher 
                                     GROUP BY Qualification 
                                     ORDER BY FIELD(Qualification, 'SSCE/Tech', 'NSCE/ND', 'HND/Bsc', 'Msc')";
                              $query = $dbh->prepare($sql);
                              $query->execute();
                              while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                                $percentage = ($row['count'] / $totalteachers) * 100;
                                echo "<tr>";
                                echo "<td>" . htmlentities($row['Qualification']) . "</td>";
                                echo "<td>" . $row['count'] . "</td>";
                                echo "<td>" . number_format($percentage, 1) . "%</td>";
                                echo "</tr>";
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
            </div>

          </div>
          <!-- content-wrapper ends -->
          <!-- partial:partials/_footer.html -->
          <?php include_once('includes/footer.php');?>
          <!-- partial -->
        </div>
        <!-- main-panel ends -->
      </div>
      <!-- page-body-wrapper ends -->
    </div>
    <!-- container-scroller -->
    <!-- plugins:js -->
    <script src="vendors/js/vendor.bundle.base.js"></script>
    <!-- Plugin js for this page -->
    <script src="vendors/chart.js/Chart.min.js"></script>
    <script src="vendors/moment/moment.min.js"></script>
    <script src="vendors/daterangepicker/daterangepicker.js"></script>
    <script src="vendors/chartist/chartist.min.js"></script>
    <!-- Custom js for this page -->
    <script src="js/dashboard-update.js"></script>
  </body>
</html>