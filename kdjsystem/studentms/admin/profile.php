<?php
if(session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/dbconnection.php');

// First, check if Avatar column exists, if not create it
try {
    $sql = "SHOW COLUMNS FROM tbladmin LIKE 'Avatar'";
    $query = $dbh->prepare($sql);
    $query->execute();
    if ($query->rowCount() == 0) {
        $sql = "ALTER TABLE tbladmin ADD COLUMN Avatar VARCHAR(255) DEFAULT NULL";
        $query = $dbh->prepare($sql);
        $query->execute();
    }
} catch(PDOException $e) {
    error_log("Error checking/creating Avatar column: " . $e->getMessage());
}

if(strlen($_SESSION['sturecmsaid'])==0) {
    header('location:logout.php');
} else {
    if(isset($_POST['submit'])) {
        $adminid=$_SESSION['sturecmsaid'];
        $AName=$_POST['adminname'];
        $mobno=$_POST['mobilenumber'];
        $email=$_POST['email'];

        // Handle avatar upload
        $success = true;
        $upload_msg = "";
        
        if(isset($_FILES["avatar"]) && $_FILES["avatar"]["error"] == 0) {
            $target_dir = __DIR__ . "/images/avatars/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $avatar = $_FILES["avatar"]["name"];
            $imageFileType = strtolower(pathinfo($avatar, PATHINFO_EXTENSION));
            $newFileName = "admin_" . $adminid . "_" . time() . "." . $imageFileType;
            $target_file = $target_dir . $newFileName;

            // Check file size and type
            if ($_FILES["avatar"]["size"] > 500000) {
                $success = false;
                $upload_msg = "File is too large. Maximum size is 500KB.";
            }
            if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
                $success = false;
                $upload_msg = "Only JPG, JPEG & PNG files are allowed.";
            }

            if($success) {
                if(move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {
                    try {
                        $sql="update tbladmin set AdminName=:adminname, MobileNumber=:mobilenumber, Email=:email, Avatar=:avatar where ID=:aid";
                        $query = $dbh->prepare($sql);
                        $query->bindParam(':adminname',$AName,PDO::PARAM_STR);
                        $query->bindParam(':email',$email,PDO::PARAM_STR);
                        $query->bindParam(':mobilenumber',$mobno,PDO::PARAM_STR);
                        $query->bindParam(':avatar',$newFileName,PDO::PARAM_STR);
                        $query->bindParam(':aid',$adminid,PDO::PARAM_STR);
                        
                        if($query->execute()) {
                            echo '<script>alert("Profile has been updated with new avatar."); window.location.href="profile.php";</script>';
                        } else {
                            echo '<script>alert("Database update failed. Please try again.");</script>';
                        }
                    } catch(PDOException $e) {
                        error_log("Error updating profile: " . $e->getMessage());
                        echo '<script>alert("An error occurred while updating profile.");</script>';
                    }
                } else {
                    echo '<script>alert("Error uploading avatar: Unable to move file to destination.");</script>';
                }
            } else {
                echo '<script>alert("' . $upload_msg . '");</script>';
            }
        } else {
            // Update without avatar
            try {
                $sql="update tbladmin set AdminName=:adminname, MobileNumber=:mobilenumber, Email=:email where ID=:aid";
                $query = $dbh->prepare($sql);
                $query->bindParam(':adminname',$AName,PDO::PARAM_STR);
                $query->bindParam(':email',$email,PDO::PARAM_STR);
                $query->bindParam(':mobilenumber',$mobno,PDO::PARAM_STR);
                $query->bindParam(':aid',$adminid,PDO::PARAM_STR);
                
                if($query->execute()) {
                    echo '<script>alert("Profile has been updated"); window.location.href="profile.php";</script>';
                } else {
                    echo '<script>alert("Database update failed. Please try again.");</script>';
                }
            } catch(PDOException $e) {
                error_log("Error updating profile: " . $e->getMessage());
                echo '<script>alert("An error occurred while updating profile.");</script>';
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Student Management System | Profile</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/custom-header.css">
    <style>
        .avatar-upload {
            margin: 20px 0;
            text-align: center;
        }
        .current-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin-bottom: 15px;
        }
        .avatar-upload input[type="file"] {
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container-scroller">
        <?php include('includes/header.php');?>
        <div class="container-fluid page-body-wrapper">
            <?php include('includes/sidebar.php');?>
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="row">
                        <div class="col-md-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Admin Profile</h4>
                                    <form class="forms-sample" method="post" enctype="multipart/form-data">
                                        <?php
                                        $aid=$_SESSION['sturecmsaid'];
                                        $sql="SELECT * from tbladmin where ID=:aid";
                                        $query = $dbh -> prepare($sql);
                                        $query->bindParam(':aid',$aid,PDO::PARAM_STR);
                                        $query->execute();
                                        $results=$query->fetchAll(PDO::FETCH_OBJ);
                                        if($query->rowCount() > 0) {
                                            foreach($results as $row) {
                                        ?>
                                        <div class="avatar-upload">
                                            <img src="<?php echo !empty($row->Avatar) ? 'images/avatars/'.$row->Avatar : 'images/avatars/default-avatar.png'; ?>" 
                                                 alt="Admin Avatar" class="current-avatar">
                                            <div>
                                                <input type="file" name="avatar" class="form-control" accept="image/*">
                                                <small class="text-muted">Maximum file size: 500KB. Allowed formats: JPG, JPEG, PNG</small>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="adminname">Admin Name</label>
                                            <input type="text" name="adminname" value="<?php echo htmlentities($row->AdminName);?>" class="form-control" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="username">User Name</label>
                                            <input type="text" name="username" value="<?php echo htmlentities($row->UserName);?>" class="form-control" readonly>
                                        </div>
                                        <div class="form-group">
                                            <label for="email">Email</label>
                                            <input type="email" name="email" value="<?php echo htmlentities($row->Email);?>" class="form-control" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="mobilenumber">Mobile Number</label>
                                            <input type="text" name="mobilenumber" value="<?php echo htmlentities($row->MobileNumber);?>" class="form-control" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="adminregdate">Admin Registration Date</label>
                                            <input type="text" value="<?php echo htmlentities($row->AdminRegdate);?>" class="form-control" readonly>
                                        </div>
                                        <?php }} ?>
                                        <button type="submit" class="btn btn-primary mr-2" name="submit">Update</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="vendors/js/vendor.bundle.base.js"></script>
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
</body>
</html>
<?php } ?>