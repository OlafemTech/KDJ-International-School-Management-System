<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
    exit();
}

if(!isset($_GET['editid'])) {
    header('location:manage-students.php');
    exit();
}

$editid = intval($_GET['editid']);

if(isset($_POST['submit'])) {
    try {
        // Validate required fields
        $required = array(
            'StudentName', 'StudentEmail', 'StudentClass', 'Level', 'Gender', 
            'DOB', 'StuID', 'FatherName', 'MotherName', 'ContactNumber', 
            'AltenateNumber', 'Address'
        );
        
        foreach($required as $field) {
            if(empty($_POST[$field])) {
                throw new Exception("$field is required");
            }
        }
        
        // Validate class and level combination
        if($_POST['StudentClass'] === 'PG' && $_POST['Level'] !== 'PG') {
            throw new Exception("PG class must have PG level");
        }
        if($_POST['StudentClass'] !== 'PG' && $_POST['Level'] === 'PG') {
            throw new Exception("PG level is only valid for PG class");
        }
        
        // Check if class and level exist in tblclass
        $stmt = $dbh->prepare("SELECT COUNT(*) FROM tblclass WHERE ClassName = ? AND Level = ?");
        $stmt->execute([$_POST['StudentClass'], $_POST['Level']]);
        if($stmt->fetchColumn() == 0) {
            throw new Exception("Selected class and level combination does not exist");
        }
        
        // Check if student ID is unique (excluding current student)
        $stmt = $dbh->prepare("SELECT COUNT(*) FROM tblstudent WHERE StuID = ? AND ID != ?");
        $stmt->execute([$_POST['StuID'], $editid]);
        if($stmt->fetchColumn() > 0) {
            throw new Exception("Student ID already exists");
        }
        
        // Handle image upload if provided
        $image = null;
        if(!empty($_FILES['Image']['name'])) {
            $allowed = array('jpg', 'jpeg', 'png', 'gif');
            $filename = $_FILES['Image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if(!in_array($ext, $allowed)) {
                throw new Exception("Invalid image format. Allowed: " . implode(', ', $allowed));
            }
            
            $newname = uniqid() . "." . $ext;
            $target = "../images/" . $newname;
            
            if(!move_uploaded_file($_FILES['Image']['tmp_name'], $target)) {
                throw new Exception("Failed to upload image");
            }
            
            $image = $newname;
        }
        
        // Update student record
        $sql = "UPDATE tblstudent SET 
                StudentName=:name,
                StudentEmail=:email,
                StudentClass=:class,
                Level=:level,
                Gender=:gender,
                DOB=:dob,
                StuID=:stuid,
                FatherName=:fname,
                MotherName=:mname,
                ContactNumber=:contact,
                AltenateNumber=:altcontact,
                Address=:address";
                
        if($image) {
            $sql .= ",Image=:image";
        }
        
        $sql .= " WHERE ID=:editid";
        
        $query = $dbh->prepare($sql);
        $query->bindParam(':name', $_POST['StudentName'], PDO::PARAM_STR);
        $query->bindParam(':email', $_POST['StudentEmail'], PDO::PARAM_STR);
        $query->bindParam(':class', $_POST['StudentClass'], PDO::PARAM_STR);
        $query->bindParam(':level', $_POST['Level'], PDO::PARAM_STR);
        $query->bindParam(':gender', $_POST['Gender'], PDO::PARAM_STR);
        $query->bindParam(':dob', $_POST['DOB'], PDO::PARAM_STR);
        $query->bindParam(':stuid', $_POST['StuID'], PDO::PARAM_STR);
        $query->bindParam(':fname', $_POST['FatherName'], PDO::PARAM_STR);
        $query->bindParam(':mname', $_POST['MotherName'], PDO::PARAM_STR);
        $query->bindParam(':contact', $_POST['ContactNumber'], PDO::PARAM_STR);
        $query->bindParam(':altcontact', $_POST['AltenateNumber'], PDO::PARAM_STR);
        $query->bindParam(':address', $_POST['Address'], PDO::PARAM_STR);
        $query->bindParam(':editid', $editid, PDO::PARAM_INT);
        
        if($image) {
            $query->bindParam(':image', $image, PDO::PARAM_STR);
        }
        
        $query->execute();
        
        $_SESSION['success'] = "Student details updated successfully";
        header("Location: manage-students.php");
        exit();
        
    } catch(Exception $e) {
        $error = $e->getMessage();
    }
}

try {
    // Get student details
    $sql = "SELECT * FROM tblstudent WHERE ID = :editid";
    $query = $dbh->prepare($sql);
    $query->bindParam(':editid', $editid, PDO::PARAM_INT);
    $query->execute();
    
    if($query->rowCount() == 0) {
        $_SESSION['error'] = "Student not found";
        header('location:manage-students.php');
        exit();
    }
    
    $student = $query->fetch(PDO::FETCH_ASSOC);
    
    // Initialize variables to prevent undefined array key warnings
    $studentFields = ['StudentName', 'StudentEmail', 'StudentClass', 'Level', 'Gender', 
                     'DOB', 'StuID', 'FatherName', 'MotherName', 'ContactNumber', 
                     'AltenateNumber', 'Address', 'Image'];
                     
    foreach ($studentFields as $field) {
        if (!isset($student[$field])) {
            $student[$field] = '';
        }
    }
    
} catch(PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header('location:manage-students.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Student</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="./css/style.css">
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
                                    <div class="d-sm-flex align-items-center mb-4">
                                        <h4 class="card-title mb-sm-0">Edit Student</h4>
                                        <a href="manage-students.php" class="btn btn-secondary ml-auto">Back</a>
                                    </div>
                                    
                                    <?php if(isset($error)) { ?>
                                        <div class="alert alert-danger alert-dismissible fade show">
                                            <?php echo $error; ?>
                                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                                        </div>
                                    <?php } ?>
                                    
                                    <form class="forms-sample" method="post" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="StudentName">Student Name</label>
                                                    <input type="text" name="StudentName" class="form-control" required
                                                           value="<?php echo htmlspecialchars($student['StudentName'], ENT_QUOTES, 'UTF-8'); ?>">
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="StudentEmail">Email</label>
                                                    <input type="email" name="StudentEmail" class="form-control" required
                                                           value="<?php echo htmlspecialchars($student['StudentEmail'], ENT_QUOTES, 'UTF-8'); ?>">
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="StudentClass">Class</label>
                                                    <select name="StudentClass" class="form-control" required>
                                                        <option value="">Select Class</option>
                                                        <?php 
                                                        $classes = ['SS', 'JS', 'Basic', 'Nursery', 'PG'];
                                                        foreach($classes as $class) {
                                                            $selected = ($student['StudentClass'] === $class) ? 'selected' : '';
                                                            echo "<option value='" . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . "' $selected>" . 
                                                                 htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . "</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="Level">Level</label>
                                                    <select name="Level" class="form-control" required>
                                                        <option value="">Select Level</option>
                                                        <?php 
                                                        $levels = ['1', '2', '3', '4', '5', 'PG'];
                                                        foreach($levels as $level) {
                                                            $selected = ($student['Level'] === $level) ? 'selected' : '';
                                                            echo "<option value='" . htmlspecialchars($level, ENT_QUOTES, 'UTF-8') . "' $selected>" . 
                                                                 htmlspecialchars($level, ENT_QUOTES, 'UTF-8') . "</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="Gender">Gender</label>
                                                    <select name="Gender" class="form-control" required>
                                                        <option value="">Select Gender</option>
                                                        <?php 
                                                        $genders = ['Male', 'Female'];
                                                        foreach($genders as $gender) {
                                                            $selected = ($student['Gender'] === $gender) ? 'selected' : '';
                                                            echo "<option value='" . htmlspecialchars($gender, ENT_QUOTES, 'UTF-8') . "' $selected>" . 
                                                                 htmlspecialchars($gender, ENT_QUOTES, 'UTF-8') . "</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="DOB">Date of Birth</label>
                                                    <input type="date" name="DOB" class="form-control" required
                                                           value="<?php echo htmlspecialchars($student['DOB'], ENT_QUOTES, 'UTF-8'); ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="StuID">Student ID</label>
                                                    <input type="text" name="StuID" class="form-control" required
                                                           value="<?php echo htmlspecialchars($student['StuID'], ENT_QUOTES, 'UTF-8'); ?>">
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="FatherName">Father's Name</label>
                                                    <input type="text" name="FatherName" class="form-control" required
                                                           value="<?php echo htmlspecialchars($student['FatherName'], ENT_QUOTES, 'UTF-8'); ?>">
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="MotherName">Mother's Name</label>
                                                    <input type="text" name="MotherName" class="form-control" required
                                                           value="<?php echo htmlspecialchars($student['MotherName'], ENT_QUOTES, 'UTF-8'); ?>">
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="ContactNumber">Contact Number</label>
                                                    <input type="text" name="ContactNumber" class="form-control" required
                                                           value="<?php echo htmlspecialchars($student['ContactNumber'], ENT_QUOTES, 'UTF-8'); ?>">
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="AltenateNumber">Alternate Number</label>
                                                    <input type="text" name="AltenateNumber" class="form-control" required
                                                           value="<?php echo htmlspecialchars($student['AltenateNumber'], ENT_QUOTES, 'UTF-8'); ?>">
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="Image">Student Photo</label>
                                                    <input type="file" name="Image" class="form-control">
                                                    <?php if($student['Image'] && $student['Image'] != 'default.jpg') { ?>
                                                        <small class="form-text text-muted">Current: <?php echo htmlspecialchars($student['Image'], ENT_QUOTES, 'UTF-8'); ?></small>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                            
                                            <div class="col-12">
                                                <div class="form-group">
                                                    <label for="Address">Address</label>
                                                    <textarea name="Address" class="form-control" rows="4" required><?php echo htmlspecialchars($student['Address'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <button type="submit" name="submit" class="btn btn-primary mr-2">Update</button>
                                        <a href="manage-students.php" class="btn btn-light">Cancel</a>
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
    document.addEventListener('DOMContentLoaded', function() {
        const classSelect = document.querySelector('select[name="StudentClass"]');
        const levelSelect = document.querySelector('select[name="Level"]');
        
        function updateLevelOptions() {
            const selectedClass = classSelect.value;
            const levelOptions = levelSelect.options;
            
            if(selectedClass === 'PG') {
                levelSelect.value = 'PG';
                for(let option of levelOptions) {
                    if(option.value === 'PG') {
                        option.disabled = false;
                    } else {
                        option.disabled = true;
                    }
                }
            } else if(selectedClass) {
                if(levelSelect.value === 'PG') {
                    levelSelect.value = '';
                }
                for(let option of levelOptions) {
                    if(option.value === 'PG') {
                        option.disabled = true;
                    } else {
                        option.disabled = false;
                    }
                }
            } else {
                for(let option of levelOptions) {
                    option.disabled = false;
                }
            }
        }
        
        updateLevelOptions();
        classSelect.addEventListener('change', updateLevelOptions);
    });
    </script>
</body>
</html>