<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']==0)) {
    header('location:logout.php');
} else {
    if(isset($_POST['submit'])) {
        try {
            $title = $_POST['title'];
            $subjectId = $_POST['subjectId'];
            $teacherId = $_POST['teacherId'];
            $classId = $_POST['classId'];
            $description = $_POST['description'];
            $dueDate = $_POST['dueDate'] . ' ' . $_POST['dueTime'];
            $maxGrade = $_POST['maxGrade'];
            
            // Handle file upload
            $attachmentURL = null;
            if(isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
                $allowed = array('pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png');
                $filename = $_FILES['attachment']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if(in_array($ext, $allowed)) {
                    $newFilename = time() . '_' . $filename;
                    $uploadPath = "../uploads/homework/";
                    
                    // Create directory if it doesn't exist
                    if(!file_exists($uploadPath)) {
                        mkdir($uploadPath, 0777, true);
                    }
                    
                    if(move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadPath . $newFilename)) {
                        $attachmentURL = $newFilename;
                    }
                }
            }
            
            $sql = "INSERT INTO tblhomework(Title, SubjectID, TeacherID, ClassID, Description, 
                                          DueDate, MaxGrade, AttachmentURL) 
                    VALUES(:title, :subjectId, :teacherId, :classId, :description, 
                           :dueDate, :maxGrade, :attachmentURL)";
            
            $query = $dbh->prepare($sql);
            $query->bindParam(':title', $title, PDO::PARAM_STR);
            $query->bindParam(':subjectId', $subjectId, PDO::PARAM_INT);
            $query->bindParam(':teacherId', $teacherId, PDO::PARAM_INT);
            $query->bindParam(':classId', $classId, PDO::PARAM_INT);
            $query->bindParam(':description', $description, PDO::PARAM_STR);
            $query->bindParam(':dueDate', $dueDate, PDO::PARAM_STR);
            $query->bindParam(':maxGrade', $maxGrade, PDO::PARAM_INT);
            $query->bindParam(':attachmentURL', $attachmentURL, PDO::PARAM_STR);
            
            $query->execute();
            $lastInsertId = $dbh->lastInsertId();
            
            if($lastInsertId > 0) {
                $_SESSION['success'] = "Homework has been added successfully";
                header('location: manage-homework.php');
                exit();
            }
        } catch(PDOException $e) {
            $_SESSION['error'] = "Error adding homework: " . $e->getMessage();
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Student Management System | Add Homework</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="vendors/select2/select2.min.css">
    <link rel="stylesheet" href="vendors/select2-bootstrap-theme/select2-bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container-scroller">
        <?php include_once('includes/header.php');?>
        <div class="container-fluid page-body-wrapper">
            <?php include_once('includes/sidebar.php');?>
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="page-header">
                        <h3 class="page-title">Add Homework</h3>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="manage-homework.php">Manage Homework</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Add Homework</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="row">
                        <div class="col-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Add New Homework</h4>
                                    <?php
                                    if(isset($_SESSION['error'])) {
                                        echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
                                        unset($_SESSION['error']);
                                    }
                                    ?>
                                    <form class="forms-sample" method="post" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Homework Title</label>
                                                    <input type="text" name="title" class="form-control" 
                                                           required placeholder="Enter homework title">
                                                </div>
                                                <div class="form-group">
                                                    <label>Subject</label>
                                                    <select name="subjectId" class="form-control select2" required>
                                                        <option value="">Select Subject</option>
                                                        <?php 
                                                        $sql = "SELECT * FROM tblsubjects ORDER BY SubjectName";
                                                        $query = $dbh->prepare($sql);
                                                        $query->execute();
                                                        $subjects = $query->fetchAll(PDO::FETCH_OBJ);
                                                        foreach($subjects as $subject) {
                                                            echo "<option value='{$subject->ID}'>{$subject->SubjectName}</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Teacher</label>
                                                    <select name="teacherId" class="form-control select2" required>
                                                        <option value="">Select Teacher</option>
                                                        <?php 
                                                        $sql = "SELECT * FROM tblteacher ORDER BY Name";
                                                        $query = $dbh->prepare($sql);
                                                        $query->execute();
                                                        $teachers = $query->fetchAll(PDO::FETCH_OBJ);
                                                        foreach($teachers as $teacher) {
                                                            echo "<option value='{$teacher->ID}'>{$teacher->Name}</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Class</label>
                                                    <select name="classId" class="form-control select2" required>
                                                        <option value="">Select Class</option>
                                                        <?php 
                                                        $sql = "SELECT * FROM tblclass ORDER BY ClassName, Section";
                                                        $query = $dbh->prepare($sql);
                                                        $query->execute();
                                                        $classes = $query->fetchAll(PDO::FETCH_OBJ);
                                                        foreach($classes as $class) {
                                                            echo "<option value='{$class->ID}'>{$class->ClassName} {$class->Section}</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Due Date</label>
                                                    <input type="date" name="dueDate" class="form-control" 
                                                           required min="<?php echo date('Y-m-d'); ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>Due Time</label>
                                                    <input type="time" name="dueTime" class="form-control" 
                                                           required value="23:59">
                                                </div>
                                                <div class="form-group">
                                                    <label>Maximum Grade</label>
                                                    <input type="number" name="maxGrade" class="form-control" 
                                                           required value="100" min="0" max="100">
                                                </div>
                                                <div class="form-group">
                                                    <label>Attachment</label>
                                                    <input type="file" name="attachment" class="form-control">
                                                    <small class="text-muted">
                                                        Allowed formats: PDF, DOC, DOCX, TXT, JPG, JPEG, PNG
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Description</label>
                                            <textarea name="description" class="form-control" rows="5" 
                                                      required placeholder="Enter homework description"></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary mr-2" name="submit">
                                            Add Homework
                                        </button>
                                        <a href="manage-homework.php" class="btn btn-light">Cancel</a>
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
    <script src="vendors/select2/select2.min.js"></script>
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
    <script src="js/select2.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2();
        });
    </script>
</body>
</html>
<?php } ?>