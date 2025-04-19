<?php 
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/dbconnection.php');

if(strlen($_SESSION['sturecmsaid'])==0) {
    header('location:logout.php');
} else {
    try {
        if(!isset($_GET['viewid'])) {
            throw new Exception("No student ID provided");
        }
        
        $sid = intval($_GET['viewid']);
        if($sid <= 0) {
            throw new Exception("Invalid student ID");
        }
        
        // Get student details with class information
        $sql = "SELECT s.*, c.ClassName, c.Level, c.Session, c.Term 
                FROM tblstudent s
                LEFT JOIN tblclass c ON (
                    c.ClassName = s.StudentClass 
                    AND c.Level = s.Level 
                    AND c.Session = s.Session 
                    AND c.Term = s.Term
                )
                WHERE s.ID = :sid";
        
        $query = $dbh->prepare($sql);
        $query->bindParam(':sid', $sid, PDO::PARAM_INT);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_OBJ);
        
        if(!$result) {
            throw new Exception("Student not found");
        }

        // Define the image path
        $imagePath = "../uploads/student_images/";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Student Management System | View Student</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .student-profile {
            display: flex;
            gap: 30px;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .student-image {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            overflow: hidden;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 3px solid #fff;
            position: relative;
        }
        .student-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .student-image .default-avatar {
            font-size: 72px;
            color: #666;
            text-transform: uppercase;
        }
        .student-info {
            flex: 1;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 20px;
            align-items: center;
        }
        .info-label {
            color: #666;
            font-weight: 500;
            font-size: 0.95rem;
        }
        .info-value {
            color: #333;
            font-size: 1rem;
        }
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .btn-back {
            padding: 8px 16px;
            background: #f8f9fa;
            border: none;
            border-radius: 4px;
            color: #333;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }
        .btn-back:hover {
            background: #e9ecef;
            text-decoration: none;
            color: #333;
            transform: translateX(-2px);
        }
        .student-name {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .info-section {
            margin-bottom: 25px;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .section-title {
            font-size: 1.1rem;
            color: #444;
            margin-bottom: 15px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .section-title i {
            color: #4B49AC;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-left: 10px;
        }
        .status-active {
            background: #e3fcef;
            color: #00a651;
        }
        .image-upload {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0,0,0,0.5);
            padding: 8px;
            text-align: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .student-image:hover .image-upload {
            opacity: 1;
        }
        .image-upload label {
            color: #fff;
            cursor: pointer;
            margin: 0;
            font-size: 0.85rem;
        }
        .image-upload input[type="file"] {
            display: none;
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
                        <div class="col-12">
                            <div class="header-actions">
                                <h4 class="card-title mb-0">Student Details</h4>
                                <a href="manage-students.php" class="btn-back">
                                    <i class="icon-arrow-left-circle"></i> Back to List
                                </a>
                            </div>
                            
                            <div class="student-profile">
                                <div class="student-image">
                                    <?php if(!empty($result->Image) && file_exists($imagePath . $result->Image)): ?>
                                        <img src="<?php echo $imagePath . htmlentities($result->Image); ?>" 
                                             alt="<?php echo htmlentities($result->StudentName); ?>'s photo">
                                    <?php else: ?>
                                        <div class="default-avatar">
                                            <?php echo substr($result->StudentName, 0, 1); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if(isset($_SESSION['sturecmsaid'])): ?>
                                    <div class="image-upload">
                                        <form action="update-student-image.php" method="post" enctype="multipart/form-data">
                                            <input type="hidden" name="student_id" value="<?php echo $sid; ?>">
                                            <label>
                                                <i class="icon-camera"></i> Update Photo
                                                <input type="file" name="student_image" accept="image/*" onchange="this.form.submit()">
                                            </label>
                                        </form>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="student-info">
                                    <h3 class="student-name">
                                        <?php echo htmlentities($result->StudentName); ?>
                                        <span class="status-badge status-active">Active</span>
                                    </h3>
                                    
                                    <div class="info-section">
                                        <h4 class="section-title">
                                            <i class="icon-user"></i> Basic Information
                                        </h4>
                                        <div class="info-grid">
                                            <div class="info-label">Student ID</div>
                                            <div class="info-value"><?php echo htmlentities($result->StudentId); ?></div>

                                            <div class="info-label">Email</div>
                                            <div class="info-value"><?php echo htmlentities($result->StudentEmail); ?></div>

                                            <div class="info-label">Class</div>
                                            <div class="info-value">
                                                <?php 
                                                if($result->StudentClass) {
                                                    echo htmlentities($result->StudentClass . ' ' . $result->Level . ' (' . $result->Session . ')');
                                                } else {
                                                    echo '<span class="text-warning">No class assigned</span>';
                                                }
                                                ?>
                                            </div>

                                            <div class="info-label">Section</div>
                                            <div class="info-value"><?php echo htmlentities($result->Term); ?></div>

                                            <div class="info-label">Gender</div>
                                            <div class="info-value"><?php echo htmlentities($result->Gender); ?></div>

                                            <div class="info-label">Date of Birth</div>
                                            <div class="info-value"><?php echo htmlentities($result->DOB); ?></div>
                                        </div>
                                    </div>

                                    <div class="info-section">
                                        <h4 class="section-title">
                                            <i class="icon-people"></i> Parent Information
                                        </h4>
                                        <div class="info-grid">
                                            <div class="info-label">Father's Name</div>
                                            <div class="info-value"><?php echo htmlentities($result->FatherName); ?></div>

                                            <div class="info-label">Mother's Name</div>
                                            <div class="info-value"><?php echo htmlentities($result->MotherName); ?></div>
                                        </div>
                                    </div>

                                    <div class="info-section">
                                        <h4 class="section-title">
                                            <i class="icon-phone"></i> Contact Information
                                        </h4>
                                        <div class="info-grid">
                                            <div class="info-label">Contact Number</div>
                                            <div class="info-value"><?php echo htmlentities($result->ContactNumber); ?></div>

                                            <?php if(!empty($result->AlternateNumber)): ?>
                                            <div class="info-label">Alternate Number</div>
                                            <div class="info-value"><?php echo htmlentities($result->AlternateNumber); ?></div>
                                            <?php endif; ?>

                                            <div class="info-label">Address</div>
                                            <div class="info-value"><?php echo htmlentities($result->Address); ?></div>

                                            <div class="info-label">Admission Date</div>
                                            <div class="info-value"><?php echo htmlentities($result->DateofAdmission); ?></div>
                                        </div>
                                    </div>
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
<?php 
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
    }
} ?>
