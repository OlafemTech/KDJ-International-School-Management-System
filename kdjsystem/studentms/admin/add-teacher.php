<?php
if(session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(0);
include('includes/dbconnection.php');

if(strlen($_SESSION['sturecmsaid'])==0) {
    header('location:logout.php');
} else {
    $error = '';
    $success = '';

    if(isset($_POST['submit'])) {
        try {
            // Get form data
            $fullName = trim($_POST['fullName']);
            $email = trim($_POST['email']);
            $mobileNumber = trim($_POST['mobileNumber']);
            $gender = trim($_POST['gender']);
            $maritalStatus = trim($_POST['maritalStatus']);
            $dob = trim($_POST['dob']);
            $address = trim($_POST['address']);
            $qualification = trim($_POST['qualification']);
            $joiningDate = trim($_POST['joiningDate']);
            
            // Generate TeacherId (YYYY + random 4 digits)
            $teacherId = 'TCH' . date('Y') . mt_rand(1000, 9999);
            
            // Generate username and password
            $username = strtolower(str_replace(' ', '', $fullName)) . mt_rand(100, 999);
            $plainPassword = $teacherId; // Store plain password temporarily for display
            $password = md5($plainPassword); // Hash for storage

            // Check if username already exists
            $check = $dbh->prepare("SELECT COUNT(*) FROM tblteacher WHERE UserName = :username");
            $check->bindParam(':username', $username);
            $check->execute();
            if($check->fetchColumn() > 0) {
                // If username exists, try to generate a new one
                $username = strtolower(str_replace(' ', '', $fullName)) . mt_rand(1000, 9999);
            }

            // Server-side validation
            if(empty($fullName) || empty($email) || empty($mobileNumber) || 
               empty($gender) || empty($maritalStatus) || empty($dob) || empty($address) || 
               empty($qualification) || empty($joiningDate)) {
                throw new Exception("Please fill all required fields");
            }

            // Validate email format
            if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format");
            }

            // Validate mobile number format (11 digits)
            if(!preg_match("/^[0-9]{11}$/", $mobileNumber)) {
                throw new Exception("Mobile number must be 11 digits");
            }

            // Validate gender
            if(!in_array($gender, ['Male', 'Female'])) {
                throw new Exception("Invalid gender selected");
            }

            // Validate marital status
            if(!in_array($maritalStatus, ['Single', 'Married', 'Divorced', 'Widowed'])) {
                throw new Exception("Invalid marital status selected");
            }

            // Validate qualification
            if(!in_array($qualification, ['SSCE/Tech', 'NSCE/ND', 'HND/Bsc', 'Msc'])) {
                throw new Exception("Invalid qualification selected");
            }

            // Check if email already exists
            $check = $dbh->prepare("SELECT COUNT(*) FROM tblteacher WHERE Email = :email");
            $check->bindParam(':email', $email);
            $check->execute();
            if($check->fetchColumn() > 0) {
                throw new Exception("Email already registered");
            }

            // Check if mobile number already exists
            $check = $dbh->prepare("SELECT COUNT(*) FROM tblteacher WHERE MobileNumber = :mobile");
            $check->bindParam(':mobile', $mobileNumber);
            $check->execute();
            if($check->fetchColumn() > 0) {
                throw new Exception("Mobile number already registered");
            }

            // Handle file uploads
            $image = 'default.jpg';
            $cv = null;
            $certificate = null;
            $uploadPath = 'uploads/teachers/';

            // Create upload directories if they don't exist
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

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
                $image = $teacherId . '_photo.' . $extension;
                
                if(!move_uploaded_file($_FILES['Image']['tmp_name'], $uploadPath . $image)) {
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
                
                $cv = $teacherId . '_cv.pdf';
                
                if(!move_uploaded_file($_FILES['CV']['tmp_name'], $uploadPath . $cv)) {
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
                $certificate = $teacherId . '_cert.' . $extension;
                
                if(!move_uploaded_file($_FILES['Certificate']['tmp_name'], $uploadPath . $certificate)) {
                    throw new Exception('Failed to upload certificate.');
                }
            }

            // Insert teacher data
            $sql = "INSERT INTO tblteacher (FullName, Email, MobileNumber, Gender, MaritalStatus, DateOfBirth, 
                    Address, Qualification, TeacherId, JoiningDate, UserName, Password,
                    Image, CV, Certificate) 
                    VALUES (:fullName, :email, :mobile, :gender, :maritalStatus, :dob, :address, :qualification,
                    :teacherId, :joiningDate, :username, :password, :image, :cv, :certificate)";

            $query = $dbh->prepare($sql);
            $query->bindParam(':fullName', $fullName, PDO::PARAM_STR);
            $query->bindParam(':email', $email, PDO::PARAM_STR);
            $query->bindParam(':mobile', $mobileNumber, PDO::PARAM_STR);
            $query->bindParam(':gender', $gender, PDO::PARAM_STR);
            $query->bindParam(':maritalStatus', $maritalStatus, PDO::PARAM_STR);
            $query->bindParam(':dob', $dob, PDO::PARAM_STR);
            $query->bindParam(':address', $address, PDO::PARAM_STR);
            $query->bindParam(':qualification', $qualification, PDO::PARAM_STR);
            $query->bindParam(':teacherId', $teacherId, PDO::PARAM_STR);
            $query->bindParam(':joiningDate', $joiningDate, PDO::PARAM_STR);
            $query->bindParam(':username', $username, PDO::PARAM_STR);
            $query->bindParam(':password', $password, PDO::PARAM_STR);
            $query->bindParam(':image', $image, PDO::PARAM_STR);
            $query->bindParam(':cv', $cv, PDO::PARAM_STR);
            $query->bindParam(':certificate', $certificate, PDO::PARAM_STR);
            $query->execute();

            // Store success message with login credentials
            $_SESSION['success'] = "Teacher added successfully!<br><br>
                                  <div class='alert alert-info'>
                                    <h5 class='alert-heading'>Teacher Portal Login Credentials</h5>
                                    <hr>
                                    <p class='mb-1'><strong>Teacher ID:</strong> $teacherId</p>
                                    <p class='mb-1'><strong>Username:</strong> $username</p>
                                    <p class='mb-1'><strong>Password:</strong> $plainPassword</p>
                                    <hr>
                                    <p class='mb-0 text-danger'><strong>Important:</strong> Please save these credentials. They won't be shown again.</p>
                                    <p class='mb-0'><small>Teachers can log in at: <a href='../teacher/' target='_blank'>Teacher Portal</a></small></p>
                                  </div>";

            // Redirect to prevent form resubmission
            header("Location: add-teacher.php");
            exit();

        } catch(Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Student Management System | Add Teacher</title>
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
                        <div class="col-md-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-sm-flex align-items-center mb-4">
                                        <h4 class="card-title mb-sm-0">Add Teacher</h4>
                                        <a href="manage-teachers.php" class="btn btn-secondary ml-auto">Back to Teachers</a>
                                    </div>
                                    
                                    <?php if($error) { ?>
                                        <div class="alert alert-danger alert-dismissible fade show">
                                            <?php echo $error; ?>
                                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                                        </div>
                                    <?php } ?>

                                    <?php if(isset($_SESSION['success'])) { ?>
                                        <div class="alert alert-success alert-dismissible fade show">
                                            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                                        </div>
                                    <?php } ?>
                                    
                                    <form class="forms-sample" method="post" id="teacherForm" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="fullName">Full Name<span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="fullName" name="fullName" 
                                                           value="<?php echo isset($_POST['fullName']) ? htmlspecialchars($_POST['fullName']) : ''; ?>" 
                                                           required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="email">Email<span class="text-danger">*</span></label>
                                                    <input type="email" class="form-control" id="email" name="email" 
                                                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                                           required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="mobileNumber">Mobile Number<span class="text-danger">*</span></label>
                                                    <input type="tel" class="form-control" id="mobileNumber" name="mobileNumber" 
                                                           value="<?php echo isset($_POST['mobileNumber']) ? htmlspecialchars($_POST['mobileNumber']) : ''; ?>" 
                                                           pattern="[0-9]{11}" title="Please enter 11 digits"
                                                           required>
                                                    <small class="form-text text-muted">Enter 11 digits</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="gender">Gender<span class="text-danger">*</span></label>
                                                    <select class="form-control" id="gender" name="gender" required>
                                                        <option value="">Select Gender</option>
                                                        <option value="Male" <?php echo isset($_POST['gender']) && $_POST['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                                                        <option value="Female" <?php echo isset($_POST['gender']) && $_POST['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="maritalStatus">Marital Status<span class="text-danger">*</span></label>
                                                    <select class="form-control" id="maritalStatus" name="maritalStatus" required>
                                                        <option value="">Select Marital Status</option>
                                                        <option value="Single" <?php echo isset($_POST['maritalStatus']) && $_POST['maritalStatus'] === 'Single' ? 'selected' : ''; ?>>Single</option>
                                                        <option value="Married" <?php echo isset($_POST['maritalStatus']) && $_POST['maritalStatus'] === 'Married' ? 'selected' : ''; ?>>Married</option>
                                                        <option value="Divorced" <?php echo isset($_POST['maritalStatus']) && $_POST['maritalStatus'] === 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
                                                        <option value="Widowed" <?php echo isset($_POST['maritalStatus']) && $_POST['maritalStatus'] === 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="dob">Date of Birth<span class="text-danger">*</span></label>
                                                    <input type="date" class="form-control" id="dob" name="dob" 
                                                           value="<?php echo isset($_POST['dob']) ? htmlspecialchars($_POST['dob']) : ''; ?>" 
                                                           required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="qualification">Qualification<span class="text-danger">*</span></label>
                                                    <select class="form-control" id="qualification" name="qualification" required>
                                                        <option value="">Select Qualification</option>
                                                        <option value="SSCE/Tech" <?php echo isset($_POST['qualification']) && $_POST['qualification'] === 'SSCE/Tech' ? 'selected' : ''; ?>>SSCE/Tech</option>
                                                        <option value="NSCE/ND" <?php echo isset($_POST['qualification']) && $_POST['qualification'] === 'NSCE/ND' ? 'selected' : ''; ?>>NSCE/ND</option>
                                                        <option value="HND/Bsc" <?php echo isset($_POST['qualification']) && $_POST['qualification'] === 'HND/Bsc' ? 'selected' : ''; ?>>HND/Bsc</option>
                                                        <option value="Msc" <?php echo isset($_POST['qualification']) && $_POST['qualification'] === 'Msc' ? 'selected' : ''; ?>>Msc</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="joiningDate">Joining Date<span class="text-danger">*</span></label>
                                                    <input type="date" class="form-control" id="joiningDate" name="joiningDate" 
                                                           value="<?php echo isset($_POST['joiningDate']) ? htmlspecialchars($_POST['joiningDate']) : ''; ?>" 
                                                           required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label>Login Details</label>
                                                    <div class="alert alert-info">
                                                        <small>Username and password will be generated automatically:</small>
                                                        <ul class="mb-0">
                                                            <li><small>Username will be based on your full name + random numbers</small></li>
                                                            <li><small>Initial password will be your Teacher ID</small></li>
                                                            <li><small>You can change your password after first login</small></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="address">Address<span class="text-danger">*</span></label>
                                            <textarea class="form-control" id="address" name="address" rows="4" required><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="Image">Profile Photo</label>
                                                    <input type="file" class="form-control" id="Image" name="Image" accept="image/jpeg,image/jpg,image/png">
                                                    <small class="form-text text-muted">Upload JPG, JPEG or PNG (max 5MB)</small>
                                                    <div id="imagePreview" class="mt-2"></div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="CV">CV (PDF)</label>
                                                    <input type="file" class="form-control" id="CV" name="CV" accept="application/pdf">
                                                    <small class="form-text text-muted">Upload PDF only (max 10MB)</small>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="Certificate">Academic Certificate</label>
                                                    <input type="file" class="form-control" id="Certificate" name="Certificate" accept="application/pdf,image/jpeg,image/jpg,image/png">
                                                    <small class="form-text text-muted">Upload PDF, JPG, JPEG or PNG (max 10MB)</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-4">
                                            <button type="submit" name="submit" class="btn btn-primary mr-2">Add Teacher</button>
                                            <button type="reset" class="btn btn-light">Clear Form</button>
                                        </div>
                                    </form>
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
    <script>
        $(document).ready(function() {
            // Image preview
            $('#Image').on('change', function() {
                var preview = $('#imagePreview');
                var file = this.files[0];
                
                if(file) {
                    if(!file.type.match('image/jpeg') && !file.type.match('image/jpg') && !file.type.match('image/png')) {
                        alert('Please select a valid image file (JPG, JPEG, PNG)');
                        this.value = '';
                        preview.html('');
                        return;
                    }
                    
                    if(file.size > 5 * 1024 * 1024) {
                        alert('Image size must be less than 5MB');
                        this.value = '';
                        preview.html('');
                        return;
                    }
                    
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        preview.html('<img src="' + e.target.result + '" class="img-thumbnail" style="max-height: 150px;">');
                    }
                    reader.readAsDataURL(file);
                } else {
                    preview.html('');
                }
            });
            
            // CV validation
            $('#CV').on('change', function() {
                var file = this.files[0];
                
                if(file) {
                    if(!file.type.match('application/pdf')) {
                        alert('CV must be in PDF format');
                        this.value = '';
                        return;
                    }
                    
                    if(file.size > 10 * 1024 * 1024) { // 10MB limit
                        alert('CV must be less than 10MB');
                        this.value = '';
                        return;
                    }
                }
            });
            
            // Certificate validation
            $('#Certificate').on('change', function() {
                var file = this.files[0];
                
                if(file) {
                    if(!file.type.match('application/pdf') && !file.type.match('image/jpeg') && 
                       !file.type.match('image/jpg') && !file.type.match('image/png')) {
                        alert('Certificate must be in PDF, JPG, JPEG or PNG format');
                        this.value = '';
                        return;
                    }
                    
                    if(file.size > 10 * 1024 * 1024) { // 10MB limit
                        alert('Certificate must be less than 10MB');
                        this.value = '';
                        return;
                    }
                }
            });
            
            // Form validation
            $('#teacherForm').on('submit', function(e) {
                var mobileNumber = $('#mobileNumber').val();
                if(!/^[0-9]{11}$/.test(mobileNumber)) {
                    alert('Mobile number must be exactly 11 digits');
                    e.preventDefault();
                    return false;
                }
                return true;
            });
        });
    </script>
</body>
</html>
