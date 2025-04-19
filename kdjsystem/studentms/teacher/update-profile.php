<?php
include('includes/header.php');

if(!isset($_SESSION['teacherloggedin'])) {
    header('location: index.php');
    exit();
}

$error = '';
$success = '';

try {
    // Get current teacher details
    $sql = "SELECT * FROM tblteacher WHERE ID = :teacherid";
    $query = $dbh->prepare($sql);
    $query->bindParam(':teacherid', $_SESSION['teacherid'], PDO::PARAM_INT);
    $query->execute();
    $teacher = $query->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error fetching teacher details: " . $e->getMessage());
    $error = "Failed to fetch teacher details. Please try again.";
}

if(isset($_POST['update'])) {
    try {
        // Get form data
        $fullName = trim($_POST['fullName']);
        $email = trim($_POST['email']);
        $mobileNumber = trim($_POST['mobileNumber']);
        $qualification = trim($_POST['qualification']);
        $address = trim($_POST['address']);
        
        // Validation
        if(empty($fullName) || empty($email) || empty($mobileNumber) || 
           empty($qualification) || empty($address)) {
            throw new Exception("All fields are required");
        }
        
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }
        
        if(!preg_match("/^[0-9]{11}$/", $mobileNumber)) {
            throw new Exception("Mobile number must be 11 digits");
        }

        $uploadPath = '../admin/uploads/teachers/';
        $currentImage = $teacher['UserImage'];
        $currentCV = $teacher['CV'];
        $currentCert = $teacher['Certificate'];

        // Handle image upload
        if(isset($_FILES['Image']) && $_FILES['Image']['error'] == 0) {
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            
            if(!in_array($_FILES['Image']['type'], $allowedTypes)) {
                throw new Exception('Only JPG, JPEG and PNG files are allowed for profile image.');
            }
            
            if($_FILES['Image']['size'] > $maxSize) {
                throw new Exception('Profile image must be less than 5MB.');
            }
            
            $extension = pathinfo($_FILES['Image']['name'], PATHINFO_EXTENSION);
            $currentImage = $teacher['TeacherId'] . '_photo.' . $extension;
            
            if(!move_uploaded_file($_FILES['Image']['tmp_name'], $uploadPath . $currentImage)) {
                throw new Exception('Failed to upload profile image.');
            }
        }

        // Handle CV upload
        if(isset($_FILES['CV']) && $_FILES['CV']['error'] == 0) {
            if($_FILES['CV']['type'] !== 'application/pdf') {
                throw new Exception('CV must be in PDF format.');
            }
            
            if($_FILES['CV']['size'] > 10 * 1024 * 1024) { // 10MB limit
                throw new Exception('CV must be less than 10MB.');
            }
            
            $currentCV = $teacher['TeacherId'] . '_cv.pdf';
            
            if(!move_uploaded_file($_FILES['CV']['tmp_name'], $uploadPath . $currentCV)) {
                throw new Exception('Failed to upload CV.');
            }
        }

        // Handle Certificate upload
        if(isset($_FILES['Certificate']) && $_FILES['Certificate']['error'] == 0) {
            $allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
            
            if(!in_array($_FILES['Certificate']['type'], $allowedTypes)) {
                throw new Exception('Certificate must be in PDF, JPG, JPEG or PNG format.');
            }
            
            if($_FILES['Certificate']['size'] > 10 * 1024 * 1024) { // 10MB limit
                throw new Exception('Certificate must be less than 10MB.');
            }
            
            $extension = pathinfo($_FILES['Certificate']['name'], PATHINFO_EXTENSION);
            $currentCert = $teacher['TeacherId'] . '_cert.' . $extension;
            
            if(!move_uploaded_file($_FILES['Certificate']['tmp_name'], $uploadPath . $currentCert)) {
                throw new Exception('Failed to upload certificate.');
            }
        }

        // Update teacher data
        $sql = "UPDATE tblteacher SET 
                FullName = :fullName,
                Email = :email,
                MobileNumber = :mobileNumber,
                Qualification = :qualification,
                Address = :address,
                UserImage = :userImage,
                CV = :cv,
                Certificate = :certificate
                WHERE ID = :teacherid";

        $query = $dbh->prepare($sql);
        $query->execute([
            ':fullName' => $fullName,
            ':email' => $email,
            ':mobileNumber' => $mobileNumber,
            ':qualification' => $qualification,
            ':address' => $address,
            ':userImage' => $currentImage,
            ':cv' => $currentCV,
            ':certificate' => $currentCert,
            ':teacherid' => $_SESSION['teacherid']
        ]);

        $_SESSION['teachername'] = $fullName;
        $success = "Profile updated successfully!";
        
    } catch(Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Update Profile</title>
    <link rel="stylesheet" href="../admin/vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="../admin/vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="../admin/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="../admin/css/style.css">
</head>
<body>
    <div class="container-scroller">
        <div class="container-fluid page-body-wrapper">
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="row">
                        <div class="col-md-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Update Profile</h4>
                                    
                                    <?php if($error) { ?>
                                        <div class="alert alert-danger alert-dismissible fade show">
                                            <?php echo $error; ?>
                                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                                        </div>
                                    <?php } ?>
                                    
                                    <?php if($success) { ?>
                                        <div class="alert alert-success alert-dismissible fade show">
                                            <?php echo $success; ?>
                                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                                        </div>
                                    <?php } ?>

                                    <form class="forms-sample" method="post" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="fullName">Full Name<span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="fullName" name="fullName" 
                                                           value="<?php echo htmlentities($teacher['FullName']); ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="email">Email<span class="text-danger">*</span></label>
                                                    <input type="email" class="form-control" id="email" name="email" 
                                                           value="<?php echo htmlentities($teacher['Email']); ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="mobileNumber">Mobile Number<span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="mobileNumber" name="mobileNumber" 
                                                           value="<?php echo htmlentities($teacher['MobileNumber']); ?>" required>
                                                    <small class="form-text text-muted">11 digits required</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="qualification">Qualification<span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="qualification" name="qualification" 
                                                           value="<?php echo htmlentities($teacher['Qualification']); ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="address">Address<span class="text-danger">*</span></label>
                                            <textarea class="form-control" id="address" name="address" rows="4" required><?php echo htmlentities($teacher['Address']); ?></textarea>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="Image">Profile Photo</label>
                                                    <input type="file" class="form-control" id="Image" name="Image" accept="image/jpeg,image/jpg,image/png">
                                                    <small class="form-text text-muted">Upload JPG, JPEG or PNG (max 5MB)</small>
                                                    <?php if($teacher['UserImage'] && $teacher['UserImage'] != 'default.jpg'): ?>
                                                        <div class="mt-2">
                                                            <img src="../admin/uploads/teachers/<?php echo htmlentities($teacher['UserImage']); ?>" 
                                                                 class="img-thumbnail" style="max-height: 100px;" alt="Current Photo">
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="CV">CV (PDF)</label>
                                                    <input type="file" class="form-control" id="CV" name="CV" accept="application/pdf">
                                                    <small class="form-text text-muted">Upload PDF only (max 10MB)</small>
                                                    <?php if($teacher['CV']): ?>
                                                        <div class="mt-2">
                                                            <a href="../admin/uploads/teachers/<?php echo htmlentities($teacher['CV']); ?>" 
                                                               class="btn btn-sm btn-info" target="_blank">View Current CV</a>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="Certificate">Academic Certificate</label>
                                                    <input type="file" class="form-control" id="Certificate" name="Certificate" accept="application/pdf,image/jpeg,image/jpg,image/png">
                                                    <small class="form-text text-muted">Upload PDF or image (max 10MB)</small>
                                                    <?php if($teacher['Certificate']): ?>
                                                        <div class="mt-2">
                                                            <a href="../admin/uploads/teachers/<?php echo htmlentities($teacher['Certificate']); ?>" 
                                                               class="btn btn-sm btn-info" target="_blank">View Current Certificate</a>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <button type="submit" name="update" class="btn btn-primary mr-2">Update Profile</button>
                                        <a href="dashboard.php" class="btn btn-light">Cancel</a>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../admin/vendors/js/vendor.bundle.base.js"></script>
    <script src="../admin/js/off-canvas.js"></script>
    <script src="../admin/js/misc.js"></script>
    <script>
        // Client-side validation
        document.querySelector('form').addEventListener('submit', function(e) {
            var mobileNumber = document.getElementById('mobileNumber').value;
            var email = document.getElementById('email').value;
            
            // Mobile number validation
            if(!/^[0-9]{11}$/.test(mobileNumber)) {
                e.preventDefault();
                alert('Mobile number must be exactly 11 digits');
                return;
            }
            
            // Email validation
            if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address');
                return;
            }
        });

        // Image preview
        document.getElementById('Image').addEventListener('change', function() {
            var file = this.files[0];
            if(file) {
                if(!['image/jpeg', 'image/jpg', 'image/png'].includes(file.type)) {
                    alert('Please select a valid image file (JPG, JPEG, PNG)');
                    this.value = '';
                    return;
                }
                if(file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB');
                    this.value = '';
                    return;
                }
            }
        });

        // CV validation
        document.getElementById('CV').addEventListener('change', function() {
            var file = this.files[0];
            if(file) {
                if(file.type !== 'application/pdf') {
                    alert('CV must be in PDF format');
                    this.value = '';
                    return;
                }
                if(file.size > 10 * 1024 * 1024) {
                    alert('CV file size must be less than 10MB');
                    this.value = '';
                    return;
                }
            }
        });

        // Certificate validation
        document.getElementById('Certificate').addEventListener('change', function() {
            var file = this.files[0];
            if(file) {
                var allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
                if(!allowedTypes.includes(file.type)) {
                    alert('Certificate must be in PDF or image format');
                    this.value = '';
                    return;
                }
                if(file.size > 10 * 1024 * 1024) {
                    alert('Certificate file size must be less than 10MB');
                    this.value = '';
                    return;
                }
            }
        });
    </script>
</body>
</html>
