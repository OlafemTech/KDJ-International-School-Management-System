<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
if (strlen($_SESSION['sturecmsaid']==0)) {
  header('location:logout.php');
  } else{
   if(isset($_POST['submit']))
  {
    try {
      $cname=$_POST['cname'];
      $level=$_POST['level'];
      $session=$_POST['session'];
      $term=$_POST['term'];
      $eid=$_GET['editid'];

      // Validate session format
      if (!preg_match("/^\d{4}\/\d{4}$/", $session)) {
        throw new Exception("Invalid session format. Please use YYYY/YYYY format.");
      }
      
      // Validate session years
      $years = explode("/", $session);
      if ($years[1] - $years[0] !== 1) {
        throw new Exception("Invalid session years. Second year must be one year after first year.");
      }

      // For PG class, use PG as both class name and level
      if ($cname === 'PG') {
        $level = 'PG';
      }

      $className = $cname . "-" . $level . " (" . $session . ")";
      $sql="update tblclass set ClassName=:cname,Section=:term where ID=:eid";
      $query=$dbh->prepare($sql);
      $query->bindParam(':cname',$className,PDO::PARAM_STR);
      $query->bindParam(':term',$term,PDO::PARAM_STR);
      $query->bindParam(':eid',$eid,PDO::PARAM_STR);
      $query->execute();
      
      echo '<script>alert("Class has been updated")</script>';
    } catch (Exception $e) {
      // Log detailed error but show generic message to user
      error_log("Error in edit-class-detail.php: " . $e->getMessage());
      echo '<script>alert("Something Went Wrong. Please check your input and try again.")</script>';
    }
  }

  ?>
<!DOCTYPE html>
<html lang="en">
  <head>
   
    <title>Student  Management System|| Update Class</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <!-- endinject -->
    <!-- Plugin css for this page -->
    <link rel="stylesheet" href="vendors/select2/select2.min.css">
    <link rel="stylesheet" href="vendors/select2-bootstrap-theme/select2-bootstrap.min.css">
    <!-- End plugin css for this page -->
    <!-- inject:css -->
    <!-- endinject -->
    <!-- Layout styles -->
    <link rel="stylesheet" href="css/style.css" />
    
  </head>
  <body>
    <div class="container-scroller">
      <!-- partial:partials/_navbar.html -->
      <?php include('includes/header.php');?>
      <!-- partial -->
      <div class="container-fluid page-body-wrapper">
        <!-- partial:partials/_sidebar.html -->
      <?php include_once('includes/sidebar.php');?>
        <!-- partial -->
        <div class="main-panel">
          <div class="content-wrapper">
            <div class="page-header">
              <h3 class="page-title"> Update Class </h3>
              <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                  <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                  <li class="breadcrumb-item active" aria-current="page"> Update Class</li>
                </ol>
              </nav>
            </div>
            <div class="row">
          
              <div class="col-12 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title" style="text-align: center;">Update Class</h4>
                   
                    <form class="forms-sample" method="post">
                      <?php
$eid=$_GET['editid'];
$sql="SELECT * from tblclass where ID=:eid";
$query = $dbh->prepare($sql);
$query->bindParam(':eid', $eid, PDO::PARAM_INT);
$query->execute();
$results=$query->fetchAll(PDO::FETCH_OBJ);
$cnt=1;
if($query->rowCount() > 0)
{
foreach($results as $row)
{
    // Parse existing values from ClassName field
    $classNameParts = explode(" (", $row->ClassName);
    $classAndLevel = explode("-", $classNameParts[0]);
    $className = isset($classAndLevel[0]) ? $classAndLevel[0] : "";
    $level = isset($classAndLevel[1]) ? $classAndLevel[1] : "";
    $session = isset($classNameParts[1]) ? rtrim($classNameParts[1], ")") : "";
?>  
                      <div class="form-group">
                        <label for="className">Class Name</label>
                        <select name="cname" class="form-control" required='true' id="className">
                          <option value="">Choose Class</option>
                          <option value="SS" <?php if($className=="SS") echo 'selected'; ?>>SS</option>
                          <option value="JS" <?php if($className=="JS") echo 'selected'; ?>>JS</option>
                          <option value="Basic" <?php if($className=="Basic") echo 'selected'; ?>>Basic</option>
                          <option value="Nursery" <?php if($className=="Nursery") echo 'selected'; ?>>Nursery</option>
                          <option value="PG" <?php if($className=="PG") echo 'selected'; ?>>PG</option>
                        </select>
                      </div>
                      <div class="form-group">
                        <label for="level">Level</label>
                        <select name="level" class="form-control" required='true' id="levelSelect" <?php if($className=="PG") echo 'disabled'; ?>>
                          <option value="">Choose Level</option>
                          <?php if($className=="PG"): ?>
                          <option value="PG" selected>PG</option>
                          <?php else: ?>
                          <option value="1" <?php if($level=="1") echo 'selected'; ?>>1</option>
                          <option value="2" <?php if($level=="2") echo 'selected'; ?>>2</option>
                          <option value="3" <?php if($level=="3") echo 'selected'; ?>>3</option>
                          <option value="4" <?php if($level=="4") echo 'selected'; ?>>4</option>
                          <option value="5" <?php if($level=="5") echo 'selected'; ?>>5</option>
                          <?php endif; ?>
                        </select>
                        <?php if($className=="PG"): ?>
                        <input type="hidden" name="level" value="PG">
                        <?php endif; ?>
                      </div>
                      <div class="form-group">
                        <label for="session">Session (e.g., 2024/2025)</label>
                        <input type="text" name="session" value="<?php echo htmlentities($session);?>" class="form-control" required='true' pattern="\d{4}/\d{4}" title="Please enter session in format YYYY/YYYY">
                      </div>
                      <div class="form-group">
                        <label for="term">Term</label>
                        <select name="term" class="form-control" required='true'>
                          <option value="">Choose Term</option>
                          <option value="1st Term" <?php if($row->Section=="1st Term") echo 'selected'; ?>>1st Term</option>
                          <option value="2nd Term" <?php if($row->Section=="2nd Term") echo 'selected'; ?>>2nd Term</option>
                          <option value="3rd Term" <?php if($row->Section=="3rd Term") echo 'selected'; ?>>3rd Term</option>
                        </select>
                      </div><?php $cnt=$cnt+1;}} ?>
                      <button type="submit" class="btn btn-primary mr-2" name="submit">Update</button>
                     
                    </form>
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
    <!-- endinject -->
    <!-- Plugin js for this page -->
    <script src="vendors/select2/select2.min.js"></script>
    <script src="vendors/typeahead.js/typeahead.bundle.min.js"></script>
    <!-- End plugin js for this page -->
    <!-- inject:js -->
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
    <!-- endinject -->
    <!-- Custom js for this page -->
    <script src="js/typeahead.js"></script>
    <script src="js/select2.js"></script>
    <!-- End custom js for this page -->
    <script>
      document.getElementById('className').addEventListener('change', function() {
        const levelSelect = document.getElementById('levelSelect');
        const hiddenLevel = document.querySelector('input[type="hidden"][name="level"]');
        
        if (this.value === 'PG') {
          levelSelect.innerHTML = '<option value="PG" selected>PG</option>';
          levelSelect.disabled = true;
          if (!hiddenLevel) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'level';
            input.value = 'PG';
            levelSelect.parentNode.appendChild(input);
          }
        } else {
          levelSelect.innerHTML = `
            <option value="">Choose Level</option>
            <option value="1">1</option>
            <option value="2">2</option>
            <option value="3">3</option>
            <option value="4">4</option>
            <option value="5">5</option>
          `;
          levelSelect.disabled = false;
          if (hiddenLevel) {
            hiddenLevel.remove();
          }
        }
      });
    </script>
  </body>
</html><?php }  ?>