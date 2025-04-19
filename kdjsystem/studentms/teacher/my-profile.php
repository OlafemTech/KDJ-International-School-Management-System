<?php
include('includes/header.php');
include_once('includes/sidebar.php');

if(isset($_POST['submit'])) {
    try {
        $tid = $_SESSION['teacheruid'];
        $fullName = $_POST['fullName'];
        $phoneNumber = $_POST['phoneNumber'];
        $email = $_POST['email'];
        $address = $_POST['address'];
        $stateOfOrigin = $_POST['stateOfOrigin'];
        $lgOfOrigin = $_POST['lgOfOrigin'];
        $maritalStatus = $_POST['maritalStatus'];
        $qualification = $_POST['qualification'];

        // Start transaction
        $dbh->beginTransaction();

        // Update teacher information
        $sql = "UPDATE tblteacher SET 
                FullName=:fullName,
                PhoneNumber=:phoneNumber,
                Email=:email,
                Address=:address,
                StateOfOrigin=:stateOfOrigin,
                LGOfOrigin=:lgOfOrigin,
                MaritalStatus=:maritalStatus,
                Qualification=:qualification
                WHERE ID=:tid";
        
        $query = $dbh->prepare($sql);
        $query->bindParam(':fullName', $fullName, PDO::PARAM_STR);
        $query->bindParam(':phoneNumber', $phoneNumber, PDO::PARAM_STR);
        $query->bindParam(':email', $email, PDO::PARAM_STR);
        $query->bindParam(':address', $address, PDO::PARAM_STR);
        $query->bindParam(':stateOfOrigin', $stateOfOrigin, PDO::PARAM_STR);
        $query->bindParam(':lgOfOrigin', $lgOfOrigin, PDO::PARAM_STR);
        $query->bindParam(':maritalStatus', $maritalStatus, PDO::PARAM_STR);
        $query->bindParam(':qualification', $qualification, PDO::PARAM_STR);
        $query->bindParam(':tid', $tid, PDO::PARAM_STR);
        $query->execute();

        // Handle passport photo upload if provided
        if(isset($_FILES["passport"]["name"]) && !empty($_FILES["passport"]["name"])) {
            $passport = $_FILES["passport"]["name"];
            $extension = strtolower(pathinfo($passport, PATHINFO_EXTENSION));
            $allowed_extensions = array("jpg", "jpeg", "png");
            
            if(!in_array($extension, $allowed_extensions)) {
                throw new Exception("Invalid format. Only jpg / jpeg / png format allowed");
            }
            
            $newPassport = md5($passport) . time() . '.' . $extension;
            if(move_uploaded_file($_FILES["passport"]["tmp_name"], "../admin/teacherphoto/".$newPassport)) {
                // Update passport photo in database
                $sql = "UPDATE tblteacher SET PassportPhoto=:passport WHERE ID=:tid";
                $query = $dbh->prepare($sql);
                $query->bindParam(':passport', $newPassport, PDO::PARAM_STR);
                $query->bindParam(':tid', $tid, PDO::PARAM_STR);
                $query->execute();
            }
        }

        $dbh->commit();
        echo "<script>alert('Profile updated successfully');</script>";
        echo "<script>window.location.href='my-profile.php'</script>";
    } catch(Exception $e) {
        $dbh->rollBack();
        echo "<script>alert('" . htmlspecialchars($e->getMessage()) . "');</script>";
    }
}

// Fetch current teacher information
$tid = $_SESSION['teacheruid'];
$sql = "SELECT * FROM tblteacher WHERE ID=:tid";
$query = $dbh->prepare($sql);
$query->bindParam(':tid', $tid, PDO::PARAM_STR);
$query->execute();
$teacher = $query->fetch(PDO::FETCH_OBJ);
?>

<div class="main-panel">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">My Profile</h4>
                        <form class="forms-sample" method="post" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Teacher ID</label>
                                        <input type="text" class="form-control" value="<?php echo htmlentities($teacher->TeacherID); ?>" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Registration Date</label>
                                        <input type="text" class="form-control" value="<?php echo htmlentities($teacher->CreationDate); ?>" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="fullName">Full Name</label>
                                <input type="text" name="fullName" class="form-control" required 
                                       value="<?php echo htmlentities($teacher->FullName); ?>">
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phoneNumber">Phone Number</label>
                                        <input type="tel" name="phoneNumber" class="form-control" required pattern="[0-9]{11}"
                                               value="<?php echo htmlentities($teacher->PhoneNumber); ?>"
                                               placeholder="e.g., 08012345678">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email</label>
                                        <input type="email" name="email" class="form-control" required
                                               value="<?php echo htmlentities($teacher->Email); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="address">Residential Address</label>
                                <textarea name="address" class="form-control" rows="4" required><?php echo htmlentities($teacher->Address); ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="stateOfOrigin">State of Origin</label>
                                        <input type="text" name="stateOfOrigin" class="form-control" required
                                               value="<?php echo htmlentities($teacher->StateOfOrigin); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="lgOfOrigin">LG of Origin</label>
                                        <input type="text" name="lgOfOrigin" class="form-control" required
                                               value="<?php echo htmlentities($teacher->LGOfOrigin); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="maritalStatus">Marital Status</label>
                                        <select name="maritalStatus" class="form-control" required>
                                            <option value="Single" <?php if($teacher->MaritalStatus == 'Single') echo 'selected'; ?>>Single</option>
                                            <option value="Married" <?php if($teacher->MaritalStatus == 'Married') echo 'selected'; ?>>Married</option>
                                            <option value="Divorced" <?php if($teacher->MaritalStatus == 'Divorced') echo 'selected'; ?>>Divorced</option>
                                            <option value="Widowed" <?php if($teacher->MaritalStatus == 'Widowed') echo 'selected'; ?>>Widowed</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="qualification">Qualification</label>
                                        <select name="qualification" class="form-control" required>
                                            <option value="SSCE/Tech" <?php if($teacher->Qualification == 'SSCE/Tech') echo 'selected'; ?>>SSCE/Tech</option>
                                            <option value="NSCE/ND" <?php if($teacher->Qualification == 'NSCE/ND') echo 'selected'; ?>>NSCE/ND</option>
                                            <option value="HND/BSc" <?php if($teacher->Qualification == 'HND/BSc') echo 'selected'; ?>>HND/BSc</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Current Passport Photo</label>
                                <div class="row">
                                    <div class="col-md-3">
                                        <img src="../admin/teacherphoto/<?php echo htmlentities($teacher->PassportPhoto); ?>" 
                                             class="img-fluid" alt="Current Passport Photo">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="passport">Update Passport Photo</label>
                                <input type="file" name="passport" class="form-control" accept="image/jpeg,image/jpg,image/png">
                                <small class="form-text text-muted">Leave empty to keep current photo. Upload new JPG, JPEG or PNG format only.</small>
                            </div>

                            <button type="submit" name="submit" class="btn btn-primary mr-2">Update Profile</button>
                            <button type="reset" class="btn btn-light">Reset</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once('includes/footer.php'); ?>
