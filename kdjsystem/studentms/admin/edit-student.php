<?php
if(session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(0);
include('includes/dbconnection.php');

if(strlen($_SESSION['sturecmsaid'])==0) {
    header('location:logout.php');
} else {
    if(isset($_POST['submit'])) {
        try {
            $sid = intval($_GET['editid']);
            $studentName = $_POST['studentName'];
            $studentEmail = $_POST['studentEmail'];
            $studentClass = $_POST['studentClass'];
            $gender = $_POST['gender'];
            $dob = $_POST['dob'];
            $studentId = $_POST['studentId'];
            $fatherName = $_POST['fatherName'];
            $motherName = $_POST['motherName'];
            $contactNumber = $_POST['contactNumber'];
            $alternateNumber = $_POST['alternateNumber'];
            $address = $_POST['address'];

            // Validate required fields
            if(empty($studentName) || empty($studentEmail) || empty($studentClass) || 
               empty($gender) || empty($dob) || empty($studentId)) {
                throw new Exception("Please fill all required fields");
            }

            // Validate email format
            if (!filter_var($studentEmail, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format");
            }

            // Check if email already exists for other students
            $sql = "SELECT ID FROM tblstudent WHERE StudentEmail=:email AND ID!=:sid";
            $query = $dbh->prepare($sql);
            $query->bindParam(':email', $studentEmail, PDO::PARAM_STR);
            $query->bindParam(':sid', $sid, PDO::PARAM_INT);
            $query->execute();
            if ($query->rowCount() > 0) {
                throw new Exception("Email already exists for another student");
            }

            // Check if student ID already exists for other students
            $sql = "SELECT ID FROM tblstudent WHERE StuID=:stuid AND ID!=:sid";
            $query = $dbh->prepare($sql);
            $query->bindParam(':stuid', $studentId, PDO::PARAM_STR);
            $query->bindParam(':sid', $sid, PDO::PARAM_INT);
            $query->execute();
            if ($query->rowCount() > 0) {
                throw new Exception("Student ID already exists for another student");
            }

            // Handle image upload if provided
            $image = null;
            if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                $maxSize = 5 * 1024 * 1024; // 5MB

                if(!in_array($_FILES['image']['type'], $allowedTypes)) {
                    throw new Exception('Only JPG, JPEG and PNG files are allowed for profile image.');
                }

                if($_FILES['image']['size'] > $maxSize) {
                    throw new Exception('Profile image must be less than 5MB.');
                }

                $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $image = $studentId . '_photo.' . $extension;
                $uploadPath = 'uploads/students/';

                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }

                if(!move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath . $image)) {
                    throw new Exception('Failed to upload profile image.');
                }
            }

            // Update student data
            $sql = "UPDATE tblstudent SET 
                    StudentName=:name,
                    StudentEmail=:email,
                    StudentClass=:class,
                    Gender=:gender,
                    DOB=:dob,
                    StuID=:stuid,
                    FatherName=:fname,
                    MotherName=:mname,
                    ContactNumber=:contact,
                    AlternateNumber=:altcontact,
                    Address=:address";

            // Add image to update if provided
            if($image) {
                $sql .= ", Image=:image";
            }

            $sql .= " WHERE ID=:sid";

            $query = $dbh->prepare($sql);
            $query->bindParam(':name', $studentName, PDO::PARAM_STR);
            $query->bindParam(':email', $studentEmail, PDO::PARAM_STR);
            $query->bindParam(':class', $studentClass, PDO::PARAM_STR);
            $query->bindParam(':gender', $gender, PDO::PARAM_STR);
            $query->bindParam(':dob', $dob, PDO::PARAM_STR);
            $query->bindParam(':stuid', $studentId, PDO::PARAM_STR);
            $query->bindParam(':fname', $fatherName, PDO::PARAM_STR);
            $query->bindParam(':mname', $motherName, PDO::PARAM_STR);
            $query->bindParam(':contact', $contactNumber, PDO::PARAM_STR);
            $query->bindParam(':altcontact', $alternateNumber, PDO::PARAM_STR);
            $query->bindParam(':address', $address, PDO::PARAM_STR);
            $query->bindParam(':sid', $sid, PDO::PARAM_INT);

            if($image) {
                $query->bindParam(':image', $image, PDO::PARAM_STR);
            }

            $query->execute();
            echo "<script>alert('Student details updated successfully');</script>";
            echo "<script>window.location.href = 'manage-students.php';</script>";

        } catch(Exception $e) {
            echo "<script>alert('" . $e->getMessage() . "');</script>";
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Student Management System | Edit Student</title>
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
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h4 class="card-title">Update Student Details</h4>
                                        <a href="manage-students.php" class="btn btn-action btn-view">
                                            <i class="icon-arrow-left-circle"></i> Back to List
                                        </a>
                                    </div>
                                    <?php
                                    $sid = $_GET['editid'];
                                    $sql = "SELECT tblstudent.*, tblclass.ClassName, tblclass.Level, tblclass.Session, tblclass.Term 
                                            FROM tblstudent 
                                            LEFT JOIN tblclass ON tblstudent.StudentClass = tblclass.ID 
                                            WHERE tblstudent.ID=:sid";
                                    $query = $dbh->prepare($sql);
                                    $query->bindParam(':sid', $sid, PDO::PARAM_STR);
                                    $query->execute();
                                    $results = $query->fetchAll(PDO::FETCH_OBJ);
                                    if($query->rowCount() > 0) {
                                        foreach($results as $row) {
                                    ?>
                                    <form class="forms-sample" method="post" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="studentName">Student Name</label>
                                                    <input type="text" name="studentName" class="form-control" value="<?php echo htmlentities($row->StudentName);?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="studentEmail">Student Email</label>
                                                    <input type="email" name="studentEmail" class="form-control" value="<?php echo htmlentities($row->StudentEmail);?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="studentClass">Class</label>
                                                    <select name="studentClass" class="form-control" required>
                                                        <option value="">Select Class</option>
                                                        <?php
                                                        $sql2 = "SELECT * FROM tblclass ORDER BY ClassName";
                                                        $query2 = $dbh->prepare($sql2);
                                                        $query2->execute();
                                                        $classes = $query2->fetchAll(PDO::FETCH_OBJ);
                                                        if($query2->rowCount() > 0) {
                                                            foreach($classes as $class) {
                                                                $selected = ($class->ID == $row->StudentClass) ? 'selected' : '';
                                                                echo "<option value='" . $class->ID . "' " . $selected . ">" . 
                                                                     htmlentities($class->ClassName . ' ' . $class->Level . 
                                                                     ' (' . $class->Session . ' - ' . $class->Term . ')') . "</option>";
                                                            }
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label for="gender">Gender</label>
                                                    <select name="gender" class="form-control" required>
                                                        <option value="">Select Gender</option>
                                                        <option value="Male" <?php if($row->Gender == 'Male') echo 'selected';?>>Male</option>
                                                        <option value="Female" <?php if($row->Gender == 'Female') echo 'selected';?>>Female</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label for="dob">Date of Birth</label>
                                                    <input type="date" name="dob" class="form-control" value="<?php echo htmlentities($row->DOB);?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="studentId">Student ID</label>
                                                    <input type="text" name="studentId" class="form-control" value="<?php echo htmlentities($row->StuID);?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="fatherName">Father's Name</label>
                                                    <input type="text" name="fatherName" class="form-control" value="<?php echo htmlentities($row->FatherName);?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="motherName">Mother's Name</label>
                                                    <input type="text" name="motherName" class="form-control" value="<?php echo htmlentities($row->MotherName);?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="contactNumber">Contact Number</label>
                                                    <input type="text" name="contactNumber" class="form-control" value="<?php echo htmlentities($row->ContactNumber);?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="alternateNumber">Alternate Number</label>
                                                    <input type="text" name="alternateNumber" class="form-control" value="<?php echo htmlentities($row->AlternateNumber);?>">
                                                </div>
                                                <div class="form-group">
                                                    <label for="address">Address</label>
                                                    <textarea name="address" class="form-control" rows="4" required><?php echo htmlentities($row->Address);?></textarea>
                                                </div>
                                                <div class="form-group">
                                                    <label for="image">Profile Image</label>
                                                    <?php if($row->Image): ?>
                                                        <div class="mb-2">
                                                            <img src="uploads/students/<?php echo htmlentities($row->Image);?>" 
                                                                 alt="Current Profile" 
                                                                 class="img-thumbnail"
                                                                 style="max-width: 100px;">
                                                        </div>
                                                    <?php endif; ?>
                                                    <input type="file" name="image" class="form-control" accept="image/*">
                                                    <small class="form-text text-muted">Leave empty to keep current image. Upload new image to change.</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-4">
                                            <button type="submit" name="submit" class="btn btn-action btn-edit mr-2">
                                                <i class="icon-check"></i> Update
                                            </button>
                                            <a href="manage-students.php" class="btn btn-action btn-view">
                                                <i class="icon-close"></i> Cancel
                                            </a>
                                        </div>
                                    </form>
                                    <?php }} ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="vendors/js/vendor.bundle.base.js"></script>
    <script src="vendors/datatables.net/jquery.dataTables.js"></script>
    <script src="vendors/datatables.net-bs4/dataTables.bootstrap4.js"></script>
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
</body>
</html>
<?php } ?>
