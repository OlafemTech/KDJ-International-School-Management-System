<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']==0)) {
  header('location:logout.php');
} else {
    if(isset($_POST['submit'])) {
        $tid = intval($_GET['editid']);
        $fullName = $_POST['fullName'];
        $phoneNumber = $_POST['phoneNumber'];
        $email = $_POST['email'];
        $gender = $_POST['gender'];
        $address = $_POST['address'];
        $stateOfOrigin = $_POST['stateOfOrigin'];
        $lgOfOrigin = $_POST['lgOfOrigin'];
        $maritalStatus = $_POST['maritalStatus'];
        $qualification = $_POST['qualification'];
        
        try {
            $sql = "UPDATE tblteacher SET FullName=:fullName, PhoneNumber=:phoneNumber, Email=:email, 
                    Gender=:gender, Address=:address, StateOfOrigin=:stateOfOrigin, 
                    LGOfOrigin=:lgOfOrigin, MaritalStatus=:maritalStatus, Qualification=:qualification 
                    WHERE ID=:tid";
            $query = $dbh->prepare($sql);
            $query->bindParam(':fullName',$fullName,PDO::PARAM_STR);
            $query->bindParam(':phoneNumber',$phoneNumber,PDO::PARAM_STR);
            $query->bindParam(':email',$email,PDO::PARAM_STR);
            $query->bindParam(':gender',$gender,PDO::PARAM_STR);
            $query->bindParam(':address',$address,PDO::PARAM_STR);
            $query->bindParam(':stateOfOrigin',$stateOfOrigin,PDO::PARAM_STR);
            $query->bindParam(':lgOfOrigin',$lgOfOrigin,PDO::PARAM_STR);
            $query->bindParam(':maritalStatus',$maritalStatus,PDO::PARAM_STR);
            $query->bindParam(':qualification',$qualification,PDO::PARAM_STR);
            $query->bindParam(':tid',$tid,PDO::PARAM_INT);
            $query->execute();

            // Handle passport photo update if provided
            if(!empty($_FILES["passport"]["name"])) {
                $passport = $_FILES["passport"]["name"];
                $extension = substr($passport,strlen($passport)-4,strlen($passport));
                $allowed_extensions = array(".jpg",".jpeg",".png");
                
                if(!in_array($extension, $allowed_extensions)) {
                    $_SESSION['error'] = "Invalid format. Only jpg / jpeg / png format allowed";
                    header('location: edit-teacher.php?editid='.$tid);
                    return;
                }
                
                // Get old photo to delete
                $sql = "SELECT PassportPhoto FROM tblteacher WHERE ID=:tid";
                $query = $dbh->prepare($sql);
                $query->bindParam(':tid', $tid, PDO::PARAM_INT);
                $query->execute();
                $result = $query->fetch(PDO::FETCH_ASSOC);
                
                if($result && $result['PassportPhoto']) {
                    unlink("teacherphoto/".$result['PassportPhoto']);
                }
                
                $passport = md5($passport).time().$extension;
                move_uploaded_file($_FILES["passport"]["tmp_name"],"teacherphoto/".$passport);
                
                $sql = "UPDATE tblteacher SET PassportPhoto=:passport WHERE ID=:tid";
                $query = $dbh->prepare($sql);
                $query->bindParam(':passport',$passport,PDO::PARAM_STR);
                $query->bindParam(':tid',$tid,PDO::PARAM_INT);
                $query->execute();
            }

            $_SESSION['success'] = "Teacher details updated successfully";
            header('location: edit-teacher.php?editid='.$tid);
            return;

        } catch(PDOException $e) {
            $_SESSION['error'] = "Something went wrong. Please try again";
            error_log($e->getMessage());
            header('location: edit-teacher.php?editid='.$tid);
            return;
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Student Management System | Edit Teacher</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="vendors/datatables.net-bs4/dataTables.bootstrap4.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/custom-header.css">
    <link rel="stylesheet" href="css/custom-buttons.css">
  </head>
  <body>
    <div class="container-scroller">
      <?php include('includes/header.php');?>
      <div class="container-fluid page-body-wrapper">
        <?php include('includes/sidebar.php');?>
        <div class="main-panel">
          <div class="content-wrapper">
            <div class="row">
              <div class="col-12 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title">Edit Teacher</h4>
                    <?php if(isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php 
                            echo $_SESSION['success']; 
                            unset($_SESSION['success']);
                            ?>
                        </div>
                    <?php endif; ?>
                    <?php if(isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php 
                            echo $_SESSION['error']; 
                            unset($_SESSION['error']);
                            ?>
                        </div>
                    <?php endif; ?>
                    <?php
                    $tid = intval($_GET['editid']);
                    $sql = "SELECT * FROM tblteacher WHERE ID=:tid";
                    $query = $dbh->prepare($sql);
                    $query->bindParam(':tid',$tid,PDO::PARAM_INT);
                    $query->execute();
                    $results = $query->fetchAll(PDO::FETCH_OBJ);
                    if($query->rowCount() > 0) {
                        foreach($results as $row) {
                    ?>
                    <form class="forms-sample" method="post" enctype="multipart/form-data">
                      <div class="form-group">
                        <label>Current Photo</label><br>
                        <img src="teacherphoto/<?php echo htmlentities($row->PassportPhoto);?>" 
                             alt="Current Photo" style="width: 100px; height: 100px; border-radius: 50%;">
                      </div>
                      <div class="form-group">
                        <label for="fullName">Full Name</label>
                        <input type="text" name="fullName" class="form-control" 
                               value="<?php echo htmlentities($row->FullName);?>" required>
                      </div>
                      <div class="form-group">
                        <label for="phoneNumber">Phone Number</label>
                        <input type="tel" name="phoneNumber" class="form-control" 
                               value="<?php echo htmlentities($row->PhoneNumber);?>" required>
                      </div>
                      <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" name="email" class="form-control" 
                               value="<?php echo htmlentities($row->Email);?>" required>
                      </div>
                      <div class="form-group">
                        <label for="gender">Gender</label>
                        <select name="gender" class="form-control" required>
                          <option value="Male" <?php if($row->Gender == 'Male') echo 'selected';?>>Male</option>
                          <option value="Female" <?php if($row->Gender == 'Female') echo 'selected';?>>Female</option>
                        </select>
                      </div>
                      <div class="form-group">
                        <label for="address">Residential Address</label>
                        <textarea name="address" class="form-control" rows="4" required><?php echo htmlentities($row->Address);?></textarea>
                      </div>
                      <div class="form-group">
                        <label for="stateOfOrigin">State of Origin</label>
                        <input type="text" name="stateOfOrigin" class="form-control" 
                               value="<?php echo htmlentities($row->StateOfOrigin);?>" required>
                      </div>
                      <div class="form-group">
                        <label for="lgOfOrigin">LG of Origin</label>
                        <input type="text" name="lgOfOrigin" class="form-control" 
                               value="<?php echo htmlentities($row->LGOfOrigin);?>" required>
                      </div>
                      <div class="form-group">
                        <label for="maritalStatus">Marital Status</label>
                        <select name="maritalStatus" class="form-control" required>
                          <option value="Single" <?php if($row->MaritalStatus == 'Single') echo 'selected';?>>Single</option>
                          <option value="Married" <?php if($row->MaritalStatus == 'Married') echo 'selected';?>>Married</option>
                          <option value="Divorced" <?php if($row->MaritalStatus == 'Divorced') echo 'selected';?>>Divorced</option>
                          <option value="Widowed" <?php if($row->MaritalStatus == 'Widowed') echo 'selected';?>>Widowed</option>
                        </select>
                      </div>
                      <div class="form-group">
                        <label for="qualification">Qualification</label>
                        <select name="qualification" class="form-control" required>
                          <option value="SSCE/Tech" <?php if($row->Qualification == 'SSCE/Tech') echo 'selected';?>>SSCE/Tech</option>
                          <option value="NSCE/ND" <?php if($row->Qualification == 'NSCE/ND') echo 'selected';?>>NSCE/ND</option>
                          <option value="HND/BSc" <?php if($row->Qualification == 'HND/BSc') echo 'selected';?>>HND/BSc</option>
                        </select>
                      </div>
                      <div class="form-group">
                        <label for="passport">Update Passport Photo</label>
                        <input type="file" name="passport" class="form-control">
                        <small class="form-text text-muted">Leave empty to keep current photo. Upload JPG, JPEG or PNG format only</small>
                      </div>
                      <button type="submit" name="submit" class="btn btn-primary mr-2">Update Teacher</button>
                      <a href="manage-teachers.php" class="btn btn-light">Cancel</a>
                    </form>
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
