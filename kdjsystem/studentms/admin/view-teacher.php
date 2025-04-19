<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']==0)) {
  header('location:logout.php');
} else {
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Student Management System | View Teacher</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="vendors/datatables.net-bs4/dataTables.bootstrap4.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/custom-header.css">
    <link rel="stylesheet" href="css/custom-buttons.css">
    <style>
      .teacher-photo {
        width: 200px;
        height: 200px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #00c8bf;
        box-shadow: 0 0 15px rgba(0,0,0,0.1);
      }
      .table th {
        width: 200px;
        background-color: #f8f9fc;
      }
      .btn {
        margin-right: 10px;
      }
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
              <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title">Teacher Details</h4>
                    <?php
                    $vid = intval($_GET['viewid']);
                    $sql = "SELECT * FROM tblteacher WHERE ID=:vid";
                    $query = $dbh->prepare($sql);
                    $query->bindParam(':vid',$vid,PDO::PARAM_INT);
                    $query->execute();
                    $results = $query->fetchAll(PDO::FETCH_OBJ);
                    if($query->rowCount() > 0) {
                        foreach($results as $row) {
                    ?>
                    <div class="row">
                      <div class="col-md-4 text-center mb-4">
                        <img src="uploads/teachers/<?php echo !empty($row->Image) ? htmlentities($row->Image) : 'default.jpg'; ?>" 
                             alt="Teacher Photo" class="teacher-photo">
                      </div>
                      <div class="col-md-8">
                        <table class="table table-bordered">
                          <tr>
                            <th>Teacher ID</th>
                            <td><?php echo htmlentities($row->TeacherID);?></td>
                          </tr>
                          <tr>
                            <th>Full Name</th>
                            <td><?php echo htmlentities($row->FullName);?></td>
                          </tr>
                          <tr>
                            <th>Phone Number</th>
                            <td><?php echo htmlentities($row->PhoneNumber);?></td>
                          </tr>
                          <tr>
                            <th>Email</th>
                            <td><?php echo htmlentities($row->Email);?></td>
                          </tr>
                          <tr>
                            <th>Gender</th>
                            <td><?php echo htmlentities($row->Gender);?></td>
                          </tr>
                          <tr>
                            <th>Date of Birth</th>
                            <td><?php echo htmlentities($row->DateOfBirth);?></td>
                          </tr>
                          <tr>
                            <th>Address</th>
                            <td><?php echo htmlentities($row->Address);?></td>
                          </tr>
                          <tr>
                            <th>Marital Status</th>
                            <td><?php echo htmlentities($row->MaritalStatus);?></td>
                          </tr>
                          <tr>
                            <th>Qualification</th>
                            <td><?php echo htmlentities($row->Qualification);?></td>
                          </tr>
                          <tr>
                            <th>Joining Date</th>
                            <td><?php echo htmlentities($row->JoiningDate);?></td>
                          </tr>
                          <tr>
                            <th>Registration Date</th>
                            <td><?php echo htmlentities($row->CreationDate);?></td>
                          </tr>
                        </table>
                      </div>
                    </div>
                    <div class="mt-4">
                      <a href="manage-teachers.php" class="btn btn-primary">Back to List</a>
                      <a href="edit-teacher.php?editid=<?php echo htmlentities($row->ID);?>" 
                         class="btn btn-info">Edit Details</a>
                    </div>
                    <?php }} ?>
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
