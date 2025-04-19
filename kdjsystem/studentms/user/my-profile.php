<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsstuid']) == 0) {
    header('location:login.php');
    exit();
} else {
    if(isset($_POST['submit'])) {
        $stuid = $_SESSION['sturecmsstuid']; 
        $studentname = $_POST['studentname'];
        $studentemail = $_POST['studentemail'];
        $gender = $_POST['gender'];
        $dob = $_POST['dob'];
        $fathername = $_POST['fathername'];
        $mothername = $_POST['mothername'];
        $contactnumber = $_POST['contactnumber'];
        $altcontactnumber = $_POST['altcontactnumber'];
        $address = $_POST['address'];
        
        // Handle profile picture upload
        if(isset($_FILES["profile_pic"]) && $_FILES["profile_pic"]["error"] == 0) {
            $allowed = array("jpg" => "image/jpg", "jpeg" => "image/jpeg", "png" => "image/png");
            $filename = $_FILES["profile_pic"]["name"];
            $filetype = $_FILES["profile_pic"]["type"];
            $filesize = $_FILES["profile_pic"]["size"];
            
            // Verify file extension
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            if(!array_key_exists($ext, $allowed)) {
                echo '<script>alert("Error: Please select a valid file format (JPG, JPEG, PNG).")</script>';
                exit;
            }
            
            // Verify file size - 5MB maximum
            $maxsize = 5 * 1024 * 1024;
            if($filesize > $maxsize) {
                echo '<script>alert("Error: File size is larger than the allowed limit (5MB).")</script>';
                exit;
            }
            
            // Verify MIME type
            if(in_array($filetype, $allowed)) {
                // Generate unique filename
                $new_filename = uniqid() . '.' . $ext;
                
                // Create directory if it doesn't exist
                if (!file_exists("studentimages")) {
                    mkdir("studentimages", 0777, true);
                }
                
                // Check if file exists
                if(file_exists("studentimages/" . $new_filename)) {
                    echo '<script>alert("Error: File already exists.")</script>';
                    exit;
                } else {
                    if(move_uploaded_file($_FILES["profile_pic"]["tmp_name"], "studentimages/" . $new_filename)) {
                        // Update database with new image filename
                        $sql = "UPDATE tblstudent SET StudentName=:studentname, StudentEmail=:studentemail, Gender=:gender, DOB=:dob, FatherName=:fathername, MotherName=:mothername, ContactNumber=:contactnumber, AltenateNumber=:altcontactnumber, Address=:address, Image=:image WHERE StuID=:stuid";
                        $query = $dbh->prepare($sql);
                        $query->bindParam(':image', $new_filename, PDO::PARAM_STR);
                    } else {
                        echo '<script>alert("Error: There was an error uploading your file.")</script>';
                        exit;
                    }
                }
            } else {
                echo '<script>alert("Error: There was a problem with the file upload. Please try again.")</script>';
                exit;
            }
        } else {
            // No new image uploaded, update without changing image
            $sql = "UPDATE tblstudent SET StudentName=:studentname, StudentEmail=:studentemail, Gender=:gender, DOB=:dob, FatherName=:fathername, MotherName=:mothername, ContactNumber=:contactnumber, AltenateNumber=:altcontactnumber, Address=:address WHERE StuID=:stuid";
            $query = $dbh->prepare($sql);
        }
        
        $query->bindParam(':studentname', $studentname, PDO::PARAM_STR);
        $query->bindParam(':studentemail', $studentemail, PDO::PARAM_STR);
        $query->bindParam(':gender', $gender, PDO::PARAM_STR);
        $query->bindParam(':dob', $dob, PDO::PARAM_STR);
        $query->bindParam(':fathername', $fathername, PDO::PARAM_STR);
        $query->bindParam(':mothername', $mothername, PDO::PARAM_STR);
        $query->bindParam(':contactnumber', $contactnumber, PDO::PARAM_STR);
        $query->bindParam(':altcontactnumber', $altcontactnumber, PDO::PARAM_STR);
        $query->bindParam(':address', $address, PDO::PARAM_STR);
        $query->bindParam(':stuid', $stuid, PDO::PARAM_STR);
        
        if($query->execute()) {
            echo '<script>alert("Profile has been updated successfully")</script>';
            echo "<script>window.location.href ='my-profile.php'</script>";
        } else {
            echo '<script>alert("Something Went Wrong. Please try again")</script>';
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Student Profile</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="css/style.css">
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
                                    <h4 class="card-title">My Profile</h4>
                                    <?php
                                    $stuid = $_SESSION['sturecmsstuid'];
                                    $sql = "SELECT s.*, c.ClassName 
                                           FROM tblstudent s 
                                           JOIN tblclass c ON s.StudentClass = c.ID 
                                           WHERE s.StuID=:stuid";
                                    $query = $dbh->prepare($sql);
                                    $query->bindParam(':stuid', $stuid, PDO::PARAM_STR);
                                    $query->execute();
                                    $results = $query->fetchAll(PDO::FETCH_OBJ);
                                    if($query->rowCount() > 0) {
                                        foreach($results as $row) {
                                    ?>
                                    <form class="forms-sample" method="post" enctype="multipart/form-data">
                                        <div class="text-center mb-4">
                                            <?php if($row->Image) { ?>
                                                <img src="../admin/studentimages/<?php echo htmlentities($row->Image);?>" alt="Student Image" class="rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                                            <?php } else { ?>
                                                <img src="../admin/studentimages/default.jpg" alt="Default Image" class="rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                                            <?php } ?>
                                            <div class="mt-3">
                                                <input type="file" class="form-control-file" name="profile_pic" accept="image/jpg,image/jpeg,image/png">
                                                <small class="form-text text-muted">Upload JPG, JPEG or PNG image. Max size 5MB.</small>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Student ID</label>
                                                    <input type="text" class="form-control" value="<?php echo htmlentities($row->StuID);?>" readonly>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Roll Number</label>
                                                    <input type="text" class="form-control" value="<?php echo htmlentities($row->RollNo);?>" readonly>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Student Name</label>
                                                    <input type="text" class="form-control" name="studentname" value="<?php echo htmlentities($row->StudentName);?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Student Email</label>
                                                    <input type="email" class="form-control" name="studentemail" value="<?php echo htmlentities($row->StudentEmail);?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Student Class</label>
                                                    <input type="text" class="form-control" value="<?php echo htmlentities($row->ClassName);?>" readonly>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Gender</label>
                                                    <select class="form-control" name="gender" required>
                                                        <option value="Male" <?php if($row->Gender == "Male") echo "selected";?>>Male</option>
                                                        <option value="Female" <?php if($row->Gender == "Female") echo "selected";?>>Female</option>
                                                        <option value="Other" <?php if($row->Gender == "Other") echo "selected";?>>Other</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Date of Birth</label>
                                                    <input type="date" class="form-control" name="dob" value="<?php echo htmlentities($row->DOB);?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Father's Name</label>
                                                    <input type="text" class="form-control" name="fathername" value="<?php echo htmlentities($row->FatherName);?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Mother's Name</label>
                                                    <input type="text" class="form-control" name="mothername" value="<?php echo htmlentities($row->MotherName);?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Contact Number</label>
                                                    <input type="text" class="form-control" name="contactnumber" value="<?php echo htmlentities($row->ContactNumber);?>" required maxlength="10" pattern="[0-9]+">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Alternate Contact Number</label>
                                                    <input type="text" class="form-control" name="altcontactnumber" value="<?php echo htmlentities($row->AltenateNumber);?>" maxlength="10" pattern="[0-9]+">
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label>Address</label>
                                                    <textarea class="form-control" name="address" rows="4" required><?php echo htmlentities($row->Address);?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 mt-4">
                                            <button type="submit" name="submit" class="btn btn-primary mr-2">Update Profile</button>
                                            <button type="reset" class="btn btn-light">Reset</button>
                                        </div>
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
