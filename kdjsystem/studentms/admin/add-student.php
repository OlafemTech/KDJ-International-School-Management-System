<?php
if(!isset($_SESSION)) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Include database connection if not already included
if (!isset($dbh)) {
    require_once(__DIR__ . '/includes/dbconnection.php');
}

// Check admin session
if (!isset($_SESSION['sturecmsaid']) || strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
    exit();
}

if(isset($_POST['submit'])) {
    try {
        // Ensure database connection exists
        if(!isset($dbh) || !($dbh instanceof PDO)) {
            throw new Exception("Database connection not available. Please ensure studentmsdb database is imported.");
        }
        
        $dbh->beginTransaction();
        
        // Get form data
        $studentName = trim($_POST['StudentName']);
        $studentEmail = trim($_POST['StudentEmail']);
        $studentClass = trim($_POST['StudentClass']);
        $level = trim($_POST['Level']);
        $gender = trim($_POST['Gender']);
        $dob = trim($_POST['DOB']);
        $studentId = trim($_POST['StudentId']);
        $fatherName = trim($_POST['FatherName']);
        $fatherOccupation = trim($_POST['FatherOccupation']);
        $motherName = trim($_POST['MotherName']);
        $motherOccupation = trim($_POST['MotherOccupation']);
        $contactNumber = trim($_POST['ContactNumber']);
        $alternateNumber = trim($_POST['AlternateNumber']);
        $address = trim($_POST['Address']);
        $session = trim($_POST['Session']);
        $term = trim($_POST['Term']);
        
        // Generate username and password
        $username = strtolower(str_replace(' ', '', $studentName)) . mt_rand(100, 999);
        $password = $studentId; // Store plain password temporarily for display
        $hashedPassword = md5($password); // Hash for storage
        
        // Server-side validation
        if(empty($studentName) || empty($studentEmail) || empty($studentClass) || 
           empty($level) || empty($gender) || empty($dob) || empty($studentId) ||
           empty($fatherName) || empty($fatherOccupation) || empty($motherName) || 
           empty($motherOccupation) || empty($contactNumber) || empty($address) || 
           empty($session) || empty($term)) {
            throw new Exception("Please fill all required fields");
        }

        // Validate email format
        if(!filter_var($studentEmail, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        // Validate contact number format (11 digits)
        if(!preg_match("/^[0-9]{11}$/", $contactNumber)) {
            throw new Exception("Contact number must be 11 digits");
        }

        // Validate alternate number if provided
        if(!empty($alternateNumber) && !preg_match("/^[0-9]{11}$/", $alternateNumber)) {
            throw new Exception("Alternate number must be 11 digits");
        }

        // Validate class name based on memory rules
        $validClasses = ['SS', 'JS', 'Basic', 'Nursery', 'PG'];
        if (!in_array($studentClass, $validClasses)) {
            throw new Exception("Invalid class selected. Valid options are: " . implode(', ', $validClasses));
        }

        // Validate level based on class per memory rules
        if ($studentClass === 'PG') {
            if ($level !== 'PG') {
                throw new Exception("For PG class, level must be PG");
            }
        } else {
            if (!in_array($level, ['1', '2', '3', '4', '5'])) {
                throw new Exception("For non-PG classes, level must be between 1 and 5");
            }
        }

        // Validate session format
        if (!preg_match('/^\d{4}\/\d{4}$/', $session)) {
            throw new Exception("Session must be in YYYY/YYYY format");
        }
        $years = explode('/', $session);
        if (intval($years[1]) !== intval($years[0]) + 1) {
            throw new Exception("Session years must be consecutive (e.g., 2024/2025)");
        }

        // Validate term
        $validTerms = ['1st Term', '2nd Term', '3rd Term'];
        if (!in_array($term, $validTerms)) {
            throw new Exception("Invalid term selected");
        }

        // Check if class exists
        $sql = "SELECT COUNT(*) FROM tblclass 
                WHERE ClassName = :class 
                AND Level = :level 
                AND Session = :session
                AND Term = :term";
        $query = $dbh->prepare($sql);
        $query->execute([
            ':class' => $studentClass,
            ':level' => $level,
            ':session' => $session,
            ':term' => $term
        ]);
        
        if ($query->fetchColumn() == 0) {
            throw new Exception("Selected class combination does not exist. Please add the class first with:<br>
                              - Class: $studentClass<br>
                              - Level: $level<br>
                              - Session: $session<br>
                              - Term: $term");
        }

        // Handle file uploads
        $image = 'default.jpg';
        $uploadPath = "../uploads/student_images/";
        if(!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

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
            $image = $studentId . '_photo.' . $extension;
            
            if(!move_uploaded_file($_FILES['Image']['tmp_name'], $uploadPath . $image)) {
                throw new Exception('Failed to upload profile image.');
            }
        }

        // Check if email already exists
        $check = $dbh->prepare("SELECT COUNT(*) FROM tblstudent WHERE StudentEmail = :email");
        $check->bindParam(':email', $studentEmail);
        $check->execute();
        if($check->fetchColumn() > 0) {
            throw new Exception("Email already registered");
        }

        // Check if username already exists
        $check = $dbh->prepare("SELECT COUNT(*) FROM tblstudent WHERE UserName = :username");
        $check->bindParam(':username', $username);
        $check->execute();
        if($check->fetchColumn() > 0) {
            // If username exists, try to generate a new one
            $username = strtolower(str_replace(' ', '', $studentName)) . mt_rand(1000, 9999);
        }

        // Insert student data
        $sql = "INSERT INTO tblstudent (StudentName, StudentEmail, StudentClass, Level, Gender, DOB, 
                StudentId, FatherName, FatherOccupation, MotherName, MotherOccupation, ContactNumber, 
                AlternateNumber, Address, Image, UserName, Password, Session, Term) 
                VALUES (:name, :email, :class, :level, :gender, :dob, :studentId, :fatherName, 
                :fatherOccupation, :motherName, :motherOccupation, :contact, :alternate, :address, 
                :image, :username, :password, :session, :term)";

        $query = $dbh->prepare($sql);
        $query->bindParam(':name', $studentName, PDO::PARAM_STR);
        $query->bindParam(':email', $studentEmail, PDO::PARAM_STR);
        $query->bindParam(':class', $studentClass, PDO::PARAM_STR);
        $query->bindParam(':level', $level, PDO::PARAM_STR);
        $query->bindParam(':gender', $gender, PDO::PARAM_STR);
        $query->bindParam(':dob', $dob, PDO::PARAM_STR);
        $query->bindParam(':studentId', $studentId, PDO::PARAM_STR);
        $query->bindParam(':fatherName', $fatherName, PDO::PARAM_STR);
        $query->bindParam(':fatherOccupation', $fatherOccupation, PDO::PARAM_STR);
        $query->bindParam(':motherName', $motherName, PDO::PARAM_STR);
        $query->bindParam(':motherOccupation', $motherOccupation, PDO::PARAM_STR);
        $query->bindParam(':contact', $contactNumber, PDO::PARAM_STR);
        $query->bindParam(':alternate', $alternateNumber, PDO::PARAM_STR);
        $query->bindParam(':address', $address, PDO::PARAM_STR);
        $query->bindParam(':image', $image, PDO::PARAM_STR);
        $query->bindParam(':username', $username, PDO::PARAM_STR);
        $query->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
        $query->bindParam(':session', $session, PDO::PARAM_STR);
        $query->bindParam(':term', $term, PDO::PARAM_STR);
        $query->execute();

        $dbh->commit();

        // Store success message with login details
        $_SESSION['success'] = '<div class="mb-0">
            <strong>Student added successfully!</strong><br><br>
            <div class="alert alert-info mb-0">
                <h5 class="alert-heading">Login Credentials</h5>
                <hr>
                <p class="mb-1"><strong>Student ID:</strong> '.$studentId.'</p>
                <p class="mb-1"><strong>Username:</strong> '.$username.'</p>
                <p class="mb-1"><strong>Password:</strong> '.$password.'</p>
                <hr>
                <p class="mb-0 text-danger"><strong>Important:</strong> Please save these credentials. They won\'t be shown again.</p>
            </div>
        </div>';
        
        header('location: manage-students.php');
        exit();

    } catch(Exception $e) {
        if (isset($dbh) && $dbh->inTransaction()) {
            $dbh->rollBack();
        }
        error_log("Error in add-student.php: " . $e->getMessage());
        $_SESSION['error'] = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Student Management System | Add Student</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/custom-header.css">
    <link rel="stylesheet" href="css/custom-buttons.css">
    <link rel="stylesheet" href="css/student-forms.css">
    <link rel="stylesheet" href="vendors/select2/select2.min.css">
    <link rel="stylesheet" href="vendors/select2-bootstrap-theme/select2-bootstrap.min.css">
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
                                    <div class="d-sm-flex align-items-center mb-4">
                                        <h4 class="card-title mb-sm-0">Add Student</h4>
                                        <div class="ml-auto">
                                            <a href="manage-students.php" class="btn btn-action btn-view">
                                                <i class="icon-arrow-left"></i> Back to Students
                                            </a>
                                        </div>
                                    </div>

                                    <?php if(isset($_SESSION['success'])) { ?>
                                        <div class="alert alert-success alert-dismissible fade show">
                                            <?php 
                                            echo $_SESSION['success'];
                                            unset($_SESSION['success']);
                                            ?>
                                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                                        </div>
                                    <?php } ?>
                                    
                                    <?php if(isset($_SESSION['error'])) { ?>
                                        <div class="alert alert-danger alert-dismissible fade show">
                                            <?php 
                                            echo $_SESSION['error'];
                                            unset($_SESSION['error']);
                                            ?>
                                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                                        </div>
                                    <?php } ?>

                                    <div id="formErrors"></div>

                                    <form class="student-form" method="post" id="studentForm" enctype="multipart/form-data">
                                        <div class="card mb-4">
                                            <div class="card-header bg-success text-white">
                                                <i class="icon-graduation"></i> Academic Information
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label for="StudentClass" class="required-field">Class</label>
                                                            <select class="form-control select2" id="StudentClass" name="StudentClass" required>
                                                                <option value="">Select Class</option>
                                                                <option value="SS">SS</option>
                                                                <option value="JS">JS</option>
                                                                <option value="Basic">Basic</option>
                                                                <option value="Nursery">Nursery</option>
                                                                <option value="PG">PG</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label for="Level" class="required-field">Level</label>
                                                            <select class="form-control select2" id="Level" name="Level" required>
                                                                <option value="">Select Level</option>
                                                            </select>
                                                            <small class="form-text text-muted">Level will be set automatically for PG class</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label for="Session" class="required-field">Session</label>
                                                            <input type="text" class="form-control" id="Session" name="Session" 
                                                                   placeholder="YYYY/YYYY" required pattern="\d{4}/\d{4}">
                                                            <div id="sessionFeedback"></div>
                                                            <small class="form-text text-muted">Format: YYYY/YYYY (e.g., 2024/2025)</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label for="Term" class="required-field">Term</label>
                                                            <select class="form-control select2" id="Term" name="Term" required>
                                                                <option value="">Select Term</option>
                                                                <option value="1st Term">1st Term</option>
                                                                <option value="2nd Term">2nd Term</option>
                                                                <option value="3rd Term">3rd Term</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="alert alert-info mt-3">
                                                    <i class="icon-info"></i> 
                                                    <strong>Note:</strong> The selected class must exist in the system. Please ensure you have added the class with the same combination of Class, Level, Session, and Term before registering a student.
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card mb-4">
                                            <div class="card-header bg-info text-white">
                                                <i class="icon-user"></i> Personal Information
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label for="StudentName" class="required-field">Student Name</label>
                                                            <input type="text" class="form-control" id="StudentName" name="StudentName" 
                                                                   placeholder="Enter full name" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label for="Gender" class="required-field">Gender</label>
                                                            <select class="form-control select2" id="Gender" name="Gender" required>
                                                                <option value="">Select Gender</option>
                                                                <option value="Male">Male</option>
                                                                <option value="Female">Female</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label for="DOB" class="required-field">Date of Birth</label>
                                                            <input type="date" class="form-control" id="DOB" name="DOB" required>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label for="StudentEmail" class="required-field">Email</label>
                                                            <input type="email" class="form-control" id="StudentEmail" name="StudentEmail" 
                                                                   placeholder="Enter email address" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label for="Image">Profile Photo</label>
                                                            <input type="file" class="form-control" id="Image" name="Image" 
                                                                   accept="image/jpeg,image/jpg,image/png">
                                                            <small class="form-text text-muted">
                                                                Allowed formats: JPG, JPEG, PNG. Max size: 5MB
                                                            </small>
                                                            <div id="imagePreview" class="mt-2" style="display: none;">
                                                                <img src="" alt="Preview" style="max-width: 200px; max-height: 200px;">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card mb-4">
                                            <div class="card-header bg-success text-white">
                                                <i class="icon-users"></i> Parent Information
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="FatherName" class="required-field">Father's Name</label>
                                                            <input type="text" class="form-control" id="FatherName" name="FatherName" 
                                                                   placeholder="Enter father's name" required>
                                                        </div>
                                                        <div class="form-group mt-3">
                                                            <label for="FatherOccupation" class="required-field">Father's Occupation</label>
                                                            <input type="text" class="form-control" id="FatherOccupation" name="FatherOccupation" 
                                                                   placeholder="Enter father's occupation" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="MotherName" class="required-field">Mother's Name</label>
                                                            <input type="text" class="form-control" id="MotherName" name="MotherName" 
                                                                   placeholder="Enter mother's name" required>
                                                        </div>
                                                        <div class="form-group mt-3">
                                                            <label for="MotherOccupation" class="required-field">Mother's Occupation</label>
                                                            <input type="text" class="form-control" id="MotherOccupation" name="MotherOccupation" 
                                                                   placeholder="Enter mother's occupation" required>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card mb-4">
                                            <div class="card-header bg-info text-white">
                                                <i class="icon-phone"></i> Contact Information
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="ContactNumber" class="required-field">Contact Number</label>
                                                            <input type="tel" class="form-control" id="ContactNumber" name="ContactNumber" 
                                                                   placeholder="Enter 11-digit number" required pattern="[0-9]{11}"
                                                                   title="Contact number must be 11 digits">
                                                            <small class="form-text text-muted">Format: 11 digits (e.g., 09123456789)</small>
                                                        </div>
                                                        <div class="form-group mt-3">
                                                            <label for="AlternateNumber">Alternate Number</label>
                                                            <input type="tel" class="form-control" id="AlternateNumber" name="AlternateNumber" 
                                                                   placeholder="Enter 11-digit number" pattern="[0-9]{11}"
                                                                   title="Alternate number must be 11 digits">
                                                            <small class="form-text text-muted">Optional, format: 11 digits</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="Address" class="required-field">Address</label>
                                                            <textarea class="form-control" id="Address" name="Address" rows="5" 
                                                                      placeholder="Enter complete address" required></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card mb-4">
                                            <div class="card-header bg-primary text-white">
                                                <i class="icon-lock"></i> Login Details
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="StudentId" class="required-field">Student ID</label>
                                                            <input type="text" class="form-control" id="StudentId" name="StudentId" 
                                                                   placeholder="Enter unique student ID" required>
                                                            <small class="form-text text-muted">This will be used as the initial password</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="StudentEmail" class="required-field">Email</label>
                                                            <input type="email" class="form-control" id="StudentEmail" name="StudentEmail" 
                                                                   placeholder="Enter email address" required>
                                                            <small class="form-text text-muted">Will be used for password recovery</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group mt-4">
                                            <button type="submit" name="submit" class="btn btn-action btn-edit mr-2">
                                                <i class="icon-plus"></i> Add Student
                                            </button>
                                            <button type="reset" class="btn btn-action btn-view">
                                                <i class="icon-refresh"></i> Reset
                                            </button>
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
    <script src="./vendors/select2/select2.min.js"></script>
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select2').select2({
                width: '100%',
                theme: 'bootstrap'
            });

            // Handle PG class selection
            $('#StudentClass').on('change', function() {
                var levelSelect = $('#Level');
                if ($(this).val() === 'PG') {
                    // Add PG option if it doesn't exist
                    if (!levelSelect.find('option[value="PG"]').length) {
                        levelSelect.append('<option value="PG">PG</option>');
                    }
                    levelSelect.val('PG').trigger('change').prop('disabled', true);
                } else {
                    // Remove PG option if it exists
                    levelSelect.find('option[value="PG"]').remove();
                    levelSelect.prop('disabled', false);
                }
            });

            // Session validation
            $('#Session').on('input', function() {
                var value = $(this).val();
                var pattern = /^\d{4}\/\d{4}$/;
                
                if (!pattern.test(value)) {
                    $(this).addClass('is-invalid');
                    return;
                }
                
                var years = value.split('/');
                var firstYear = parseInt(years[0]);
                var secondYear = parseInt(years[1]);
                
                if (secondYear !== firstYear + 1) {
                    $(this).addClass('is-invalid');
                    $(this).next('.invalid-feedback').text('Years must be consecutive (e.g., 2024/2025)');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });

            // Contact number validation
            function validatePhoneNumber(input) {
                var phonePattern = /^\d{11}$/;
                var isValid = phonePattern.test(input.value);
                if (!isValid && input.value) {
                    $(input).addClass('is-invalid')
                        .siblings('.invalid-feedback').remove();
                    $('<div class="invalid-feedback">Contact number must be 11 digits</div>')
                        .insertAfter(input);
                } else {
                    $(input).removeClass('is-invalid')
                        .siblings('.invalid-feedback').remove();
                }
                return isValid;
            }

            $('#ContactNumber, #AlternateNumber').on('input', function() {
                validatePhoneNumber(this);
            });

            // Image preview
            $('#Image').on('change', function() {
                var preview = $('#imagePreview');
                var file = this.files[0];
                
                if (file) {
                    // Validate file type
                    var allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                    if (!allowedTypes.includes(file.type)) {
                        $(this).val('');
                        preview.html('<div class="text-danger">Invalid file type. Only jpg, jpeg, or png allowed.</div>');
                        return;
                    }

                    // Validate file size (5MB max)
                    if (file.size > 5 * 1024 * 1024) {
                        $(this).val('');
                        preview.html('<div class="text-danger">File size must be less than 5MB.</div>');
                        return;
                    }

                    // Preview image
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        preview.html('<img src="' + e.target.result + '" class="img-thumbnail" style="max-height: 100px;">');
                    }
                    reader.readAsDataURL(file);
                } else {
                    preview.html('<span class="text-muted">No image selected</span>');
                }
            });

            // Form validation
            $('#studentForm').on('submit', function(e) {
                var isValid = true;
                var errors = [];
                
                // Clear previous errors
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();

                // Validate Student ID
                var studentId = $('#StudentId').val().trim();
                if (!studentId) {
                    isValid = false;
                    $('#StudentId').addClass('is-invalid');
                    $('#StudentId').after('<div class="invalid-feedback">Student ID is required</div>');
                }

                // Validate Email
                var email = $('#StudentEmail').val().trim();
                var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!email || !emailPattern.test(email)) {
                    isValid = false;
                    $('#StudentEmail').addClass('is-invalid');
                    $('#StudentEmail').after('<div class="invalid-feedback">Please enter a valid email address</div>');
                }

                // Validate Class and Level combination
                var className = $('#StudentClass').val();
                var level = $('#Level').val();
                if (className === 'PG' && level !== 'PG') {
                    isValid = false;
                    $('#Level').addClass('is-invalid');
                    $('#Level').after('<div class="invalid-feedback">For PG class, level must be PG</div>');
                } else if (className !== 'PG' && level === 'PG') {
                    isValid = false;
                    $('#Level').addClass('is-invalid');
                    $('#Level').after('<div class="invalid-feedback">PG level is only valid for PG class</div>');
                }

                // Validate Session
                var session = $('#Session').val().trim();
                var sessionPattern = /^\d{4}\/\d{4}$/;
                if (!session || !sessionPattern.test(session)) {
                    isValid = false;
                    $('#Session').addClass('is-invalid');
                    $('#Session').after('<div class="invalid-feedback">Please enter session in YYYY/YYYY format</div>');
                } else {
                    var years = session.split('/');
                    var firstYear = parseInt(years[0]);
                    var secondYear = parseInt(years[1]);
                    if (secondYear !== firstYear + 1) {
                        isValid = false;
                        $('#Session').addClass('is-invalid');
                        $('#Session').after('<div class="invalid-feedback">Years must be consecutive (e.g., 2024/2025)</div>');
                    }
                }

                // Validate Term
                var term = $('#Term').val();
                var validTerms = ['1st Term', '2nd Term', '3rd Term'];
                if (!term || !validTerms.includes(term)) {
                    isValid = false;
                    $('#Term').addClass('is-invalid');
                    $('#Term').after('<div class="invalid-feedback">Please select a valid term</div>');
                }

                // Validate contact numbers
                var contactNumber = $('#ContactNumber').val();
                var alternateNumber = $('#AlternateNumber').val();
                
                if (!validatePhoneNumber(document.getElementById('ContactNumber'))) {
                    isValid = false;
                }
                
                if (alternateNumber && !validatePhoneNumber(document.getElementById('AlternateNumber'))) {
                    isValid = false;
                }

                if (!isValid) {
                    e.preventDefault();
                    $('html, body').animate({
                        scrollTop: $('.is-invalid:first').offset().top - 100
                    }, 200);
                }
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            // Image preview
            $('#Image').change(function() {
                const file = this.files[0];
                const maxSize = 5 * 1024 * 1024; // 5MB
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                const preview = $('#imagePreview');
                
                preview.empty();
                
                if (file) {
                    if (!allowedTypes.includes(file.type)) {
                        preview.html('<span class="text-danger">Invalid file type. Only JPG, JPEG and PNG allowed.</span>');
                        this.value = '';
                        return;
                    }
                    
                    if (file.size > maxSize) {
                        preview.html('<span class="text-danger">File too large. Maximum size is 5MB.</span>');
                        this.value = '';
                        return;
                    }
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.html(`<img src="${e.target.result}" class="img-thumbnail" style="max-height: 100px">`);
                    }
                    reader.readAsDataURL(file);
                } else {
                    preview.html('<span class="text-muted">No image selected</span>');
                }
            });

            // Class and Level relationship
            $('#StudentClass').change(function() {
                const classVal = $(this).val();
                const levelSelect = $('#Level');
                
                levelSelect.empty();
                levelSelect.append('<option value="">Select Level</option>');
                
                if (classVal === 'PG') {
                    levelSelect.append('<option value="PG">PG</option>');
                    levelSelect.val('PG').prop('disabled', true);
                } else if (classVal) {
                    for (let i = 1; i <= 5; i++) {
                        levelSelect.append(`<option value="${i}">${i}</option>`);
                    }
                    levelSelect.prop('disabled', false);
                }
            });

            // Session validation
            $('#Session').on('input', function() {
                const sessionVal = $(this).val();
                const sessionRegex = /^\d{4}\/\d{4}$/;
                const feedback = $('#sessionFeedback');
                
                if (!sessionRegex.test(sessionVal)) {
                    feedback.html('<span class="text-danger">Session must be in YYYY/YYYY format</span>');
                    return;
                }
                
                const years = sessionVal.split('/');
                if (parseInt(years[1]) !== parseInt(years[0]) + 1) {
                    feedback.html('<span class="text-danger">Years must be consecutive (e.g., 2024/2025)</span>');
                } else {
                    feedback.html('<span class="text-success">Valid session format</span>');
                }
            });

            // Form validation
            $('form').submit(function(e) {
                const studentId = $('#StudentId').val().trim();
                const studentEmail = $('#StudentEmail').val().trim();
                const contactNumber = $('#ContactNumber').val().trim();
                const alternateNumber = $('#AlternateNumber').val().trim();
                const session = $('#Session').val().trim();
                let isValid = true;
                
                // Clear previous error messages
                $('.error-message').remove();
                $('.is-invalid').removeClass('is-invalid');
                
                // Email validation
                if (!isValidEmail(studentEmail)) {
                    showError($('#StudentEmail'), 'Please enter a valid email address');
                    isValid = false;
                }
                
                // Contact number validation
                if (!isValidPhoneNumber(contactNumber)) {
                    showError($('#ContactNumber'), 'Contact number must be 11 digits');
                    isValid = false;
                }
                
                // Alternate number validation (if provided)
                if (alternateNumber && !isValidPhoneNumber(alternateNumber)) {
                    showError($('#AlternateNumber'), 'Alternate number must be 11 digits');
                    isValid = false;
                }
                
                // Session validation
                if (!isValidSession(session)) {
                    showError($('#Session'), 'Invalid session format. Must be consecutive years (e.g., 2024/2025)');
                    isValid = false;
                }
                
                if (!isValid) {
                    e.preventDefault();
                }
            });
            
            // Helper functions
            function isValidEmail(email) {
                return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
            }
            
            function isValidPhoneNumber(number) {
                return /^[0-9]{11}$/.test(number);
            }
            
            function isValidSession(session) {
                const sessionRegex = /^\d{4}\/\d{4}$/;
                if (!sessionRegex.test(session)) return false;
                
                const years = session.split('/');
                return parseInt(years[1]) === parseInt(years[0]) + 1;
            }
            
            function showError(element, message) {
                element.addClass('is-invalid')
                      .after(`<div class="invalid-feedback error-message">${message}</div>`);
            }
        });
    </script>
    <script>
        $(document).ready(function() {
            // Parent information validation
            $('#FatherName, #MotherName').on('input', function() {
                const input = $(this);
                const name = input.val().trim();
                const nameRegex = /^[a-zA-Z\s.]+$/;
                
                if (!nameRegex.test(name)) {
                    showError(input, 'Name should only contain letters, spaces, and dots');
                } else {
                    removeError(input);
                }
            });

            $('#FatherOccupation, #MotherOccupation').on('input', function() {
                const input = $(this);
                const occupation = input.val().trim();
                const occupationRegex = /^[a-zA-Z\s\-/]+$/;
                
                if (!occupationRegex.test(occupation)) {
                    showError(input, 'Occupation should only contain letters, spaces, hyphens, and forward slashes');
                } else {
                    removeError(input);
                }
            });

            // Contact number validation
            $('#ContactNumber, #AlternateNumber').on('input', function() {
                const input = $(this);
                const number = input.val().trim();
                const numberRegex = /^[0-9]{11}$/;
                
                if (input.attr('id') === 'ContactNumber' && !number) {
                    showError(input, 'Contact number is required');
                } else if (number && !numberRegex.test(number)) {
                    showError(input, 'Number must be exactly 11 digits');
                } else {
                    removeError(input);
                }
            });

            // Form validation
            $('form').submit(function(e) {
                let isValid = true;
                
                // Clear previous error messages
                $('.error-message').remove();
                $('.is-invalid').removeClass('is-invalid');
                
                // Validate parent names
                const nameRegex = /^[a-zA-Z\s.]+$/;
                if (!nameRegex.test($('#FatherName').val().trim())) {
                    showError($('#FatherName'), 'Father\'s name should only contain letters, spaces, and dots');
                    isValid = false;
                }
                if (!nameRegex.test($('#MotherName').val().trim())) {
                    showError($('#MotherName'), 'Mother\'s name should only contain letters, spaces, and dots');
                    isValid = false;
                }
                
                // Validate occupations
                const occupationRegex = /^[a-zA-Z\s\-/]+$/;
                if (!occupationRegex.test($('#FatherOccupation').val().trim())) {
                    showError($('#FatherOccupation'), 'Father\'s occupation should only contain letters, spaces, hyphens, and forward slashes');
                    isValid = false;
                }
                if (!occupationRegex.test($('#MotherOccupation').val().trim())) {
                    showError($('#MotherOccupation'), 'Mother\'s occupation should only contain letters, spaces, hyphens, and forward slashes');
                    isValid = false;
                }
                
                // Validate contact numbers
                const numberRegex = /^[0-9]{11}$/;
                const contactNumber = $('#ContactNumber').val().trim();
                const alternateNumber = $('#AlternateNumber').val().trim();
                
                if (!numberRegex.test(contactNumber)) {
                    showError($('#ContactNumber'), 'Contact number must be exactly 11 digits');
                    isValid = false;
                }
                if (alternateNumber && !numberRegex.test(alternateNumber)) {
                    showError($('#AlternateNumber'), 'Alternate number must be exactly 11 digits');
                    isValid = false;
                }
                
                // Validate address
                const address = $('#Address').val().trim();
                if (!address) {
                    showError($('#Address'), 'Address is required');
                    isValid = false;
                } else if (address.length < 10) {
                    showError($('#Address'), 'Address must be at least 10 characters long');
                    isValid = false;
                }
                
                if (!isValid) {
                    e.preventDefault();
                }
            });
            
            // Helper functions
            function showError(element, message) {
                element.addClass('is-invalid')
                      .after(`<div class="invalid-feedback error-message">${message}</div>`);
            }
            
            function removeError(element) {
                element.removeClass('is-invalid')
                      .next('.error-message').remove();
            }
        });
    </script>
</body>
</html>
