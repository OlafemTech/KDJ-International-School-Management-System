<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']==0)) {
    header('location:logout.php');
} else {
    if(isset($_POST['submit'])) {
        try {
            $id = intval($_GET['id']);
            $title = $_POST['title'];
            $subjectId = $_POST['subjectId'];
            $teacherId = $_POST['teacherId'];
            $classId = $_POST['classId'];
            $description = $_POST['description'];
            $dueDate = $_POST['dueDate'] . ' ' . $_POST['dueTime'];
            $maxGrade = $_POST['maxGrade'];
            $status = $_POST['status'];
            
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
                    
                    // Delete old attachment if exists
                    $sql = "SELECT AttachmentURL FROM tblhomework WHERE ID=:id";
                    $query = $dbh->prepare($sql);
                    $query->bindParam(':id', $id, PDO::PARAM_INT);
                    $query->execute();
                    $result = $query->fetch(PDO::FETCH_OBJ);
                    
                    if($result && $result->AttachmentURL) {
                        $oldFile = $uploadPath . $result->AttachmentURL;
                        if(file_exists($oldFile)) {
                            unlink($oldFile);
                        }
                    }
                    
                    if(move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadPath . $newFilename)) {
                        $attachmentURL = $newFilename;
                    }
                }
            }
            
            // Build SQL based on whether there's a new attachment
            if($attachmentURL !== null) {
                $sql = "UPDATE tblhomework SET 
                        Title=:title, SubjectID=:subjectId, TeacherID=:teacherId, 
                        ClassID=:classId, Description=:description, DueDate=:dueDate,
                        MaxGrade=:maxGrade, Status=:status, AttachmentURL=:attachmentURL 
                        WHERE ID=:id";
            } else {
                $sql = "UPDATE tblhomework SET 
                        Title=:title, SubjectID=:subjectId, TeacherID=:teacherId, 
                        ClassID=:classId, Description=:description, DueDate=:dueDate,
                        MaxGrade=:maxGrade, Status=:status 
                        WHERE ID=:id";
            }
            
            $query = $dbh->prepare($sql);
            $query->bindParam(':title', $title, PDO::PARAM_STR);
            $query->bindParam(':subjectId', $subjectId, PDO::PARAM_INT);
            $query->bindParam(':teacherId', $teacherId, PDO::PARAM_INT);
            $query->bindParam(':classId', $classId, PDO::PARAM_INT);
            $query->bindParam(':description', $description, PDO::PARAM_STR);
            $query->bindParam(':dueDate', $dueDate, PDO::PARAM_STR);
            $query->bindParam(':maxGrade', $maxGrade, PDO::PARAM_INT);
            $query->bindParam(':status', $status, PDO::PARAM_STR);
            $query->bindParam(':id', $id, PDO::PARAM_INT);
            
            if($attachmentURL !== null) {
                $query->bindParam(':attachmentURL', $attachmentURL, PDO::PARAM_STR);
            }
            
            $query->execute();
            
            $_SESSION['success'] = "Homework has been updated successfully";
            header('location: manage-homework.php');
            exit();
        } catch(PDOException $e) {
            $_SESSION['error'] = "Error updating homework: " . $e->getMessage();
        }
    }
    
    // Get homework details
    $id = intval($_GET['id']);
    $sql = "SELECT h.*, s.SubjectName, c.ClassName, c.Section, t.Name as TeacherName,
                   DATE(h.DueDate) as DueDateOnly, TIME(h.DueDate) as DueTimeOnly
            FROM tblhomework h
            LEFT JOIN tblsubjects s ON s.ID = h.SubjectID
            LEFT JOIN tblclass c ON c.ID = h.ClassID
            LEFT JOIN tblteacher t ON t.ID = h.TeacherID
            WHERE h.ID=:id";
    $query = $dbh->prepare($sql);
    $query->bindParam(':id', $id, PDO::PARAM_INT);
    $query->execute();
    $homework = $query->fetch(PDO::FETCH_OBJ);
    
    if(!$homework) {
        $_SESSION['error'] = "Homework not found";
        header('location: manage-homework.php');
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Student Management System | Edit Homework</title>
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
                        <h3 class="page-title">Edit Homework</h3>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="manage-homework.php">Manage Homework</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Edit Homework</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="row">
                        <div class="col-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Edit Homework Details</h4>
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
                                                           required value="<?php echo htmlentities($homework->Title);?>">
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
                                                            $selected = ($subject->ID == $homework->SubjectID) ? 'selected' : '';
                                                            echo "<option value='{$subject->ID}' {$selected}>{$subject->SubjectName}</option>";
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
                                                            $selected = ($teacher->ID == $homework->TeacherID) ? 'selected' : '';
                                                            echo "<option value='{$teacher->ID}' {$selected}>{$teacher->Name}</option>";
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
                                                            $selected = ($class->ID == $homework->ClassID) ? 'selected' : '';
                                                            echo "<option value='{$class->ID}' {$selected}>{$class->ClassName} {$class->Section}</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Due Date</label>
                                                    <input type="date" name="dueDate" class="form-control" 
                                                           required value="<?php echo $homework->DueDateOnly;?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>Due Time</label>
                                                    <input type="time" name="dueTime" class="form-control" 
                                                           required value="<?php echo $homework->DueTimeOnly;?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>Maximum Grade</label>
                                                    <input type="number" name="maxGrade" class="form-control" 
                                                           required value="<?php echo $homework->MaxGrade;?>" min="0" max="100">
                                                </div>
                                                <div class="form-group">
                                                    <label>Status</label>
                                                    <select name="status" class="form-control" required>
                                                        <option value="Active" <?php echo $homework->Status == 'Active' ? 'selected' : '';?>>Active</option>
                                                        <option value="Inactive" <?php echo $homework->Status == 'Inactive' ? 'selected' : '';?>>Inactive</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Attachment</label>
                                                    <?php if($homework->AttachmentURL) { ?>
                                                        <div class="mb-2">
                                                            <a href="../uploads/homework/<?php echo $homework->AttachmentURL;?>" 
                                                               target="_blank" class="text-primary">
                                                                <i class="icon-paper-clip"></i> Current Attachment
                                                            </a>
                                                        </div>
                                                    <?php } ?>
                                                    <input type="file" name="attachment" class="form-control">
                                                    <small class="text-muted">
                                                        Leave empty to keep current attachment. Allowed formats: PDF, DOC, DOCX, TXT, JPG, JPEG, PNG
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Description</label>
                                            <textarea name="description" class="form-control" rows="5" 
                                                      required><?php echo htmlentities($homework->Description);?></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary mr-2" name="submit">
                                            Update Homework
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