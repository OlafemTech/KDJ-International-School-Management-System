<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

// Check if admin is logged in
if (strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
    exit();
}

$msg = '';
$error = '';

if(isset($_GET['editid'])) {
    try {
        $editid = intval($_GET['editid']);
        $sql = "SELECT * FROM tblclass WHERE ID = :id";
        $query = $dbh->prepare($sql);
        $query->bindParam(':id', $editid, PDO::PARAM_INT);
        $query->execute();
        
        if($query->rowCount() == 0) {
            throw new Exception("Class not found");
        }
        
        $classData = $query->fetch(PDO::FETCH_ASSOC);
        
    } catch(Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: manage-classes.php");
        exit();
    }
} else {
    header("Location: manage-classes.php");
    exit();
}

if(isset($_POST['submit'])) {
    try {
        // Server-side validation
        $className = trim($_POST['className']);
        $level = trim($_POST['level']);
        $session = trim($_POST['session']);
        $term = trim($_POST['term']);
        
        // 1. All fields required check
        if(empty($className) || empty($level) || empty($session) || empty($term)) {
            throw new Exception("All fields are required");
        }
        
        // 2. Class Name validation
        $validClassNames = ['SS', 'JS', 'Basic', 'Nursery', 'PG'];
        if(!in_array($className, $validClassNames)) {
            throw new Exception("Invalid class name");
        }
        
        // 3. Level validation
        if($className === 'PG') {
            if($level !== 'PG') {
                throw new Exception("PG class must have PG level");
            }
        } else {
            if(!is_numeric($level) || $level < 1 || $level > 5) {
                throw new Exception("Level must be between 1 and 5 for non-PG classes");
            }
        }
        
        // 4. Session format validation
        if(!preg_match('/^\d{4}\/\d{4}$/', $session)) {
            throw new Exception("Session must be in YYYY/YYYY format");
        }
        
        $years = explode('/', $session);
        if(intval($years[1]) !== intval($years[0]) + 1) {
            throw new Exception("Session years must be consecutive");
        }
        
        // 5. Term validation
        $validTerms = ['1st Term', '2nd Term', '3rd Term'];
        if(!in_array($term, $validTerms)) {
            throw new Exception("Invalid term selected");
        }
        
        // 6. Check for duplicate combination (excluding current record)
        $check = $dbh->prepare("SELECT COUNT(*) FROM tblclass WHERE 
            ClassName = :className AND Level = :level AND 
            Session = :session AND Term = :term AND ID != :id");
            
        $check->execute([
            ':className' => $className,
            ':level' => $level,
            ':session' => $session,
            ':term' => $term,
            ':id' => $editid
        ]);
        
        if($check->fetchColumn() > 0) {
            throw new Exception("This class combination already exists");
        }
        
        // Check if students are affected
        if($className != $classData['ClassName'] || $level != $classData['Level']) {
            $studentCheck = $dbh->prepare("SELECT COUNT(*) FROM tblstudent WHERE 
                StudentClass = :oldClassName AND Level = :oldLevel");
            $studentCheck->execute([
                ':oldClassName' => $classData['ClassName'],
                ':oldLevel' => $classData['Level']
            ]);
            
            if($studentCheck->fetchColumn() > 0) {
                throw new Exception("Cannot modify class name or level: Students are assigned to this class");
            }
        }
        
        // All validation passed, update the class
        $sql = "UPDATE tblclass SET 
                ClassName = :className,
                Level = :level,
                Session = :session,
                Term = :term
                WHERE ID = :id";
                
        $query = $dbh->prepare($sql);
        $query->execute([
            ':className' => $className,
            ':level' => $level,
            ':session' => $session,
            ':term' => $term,
            ':id' => $editid
        ]);
        
        $_SESSION['success'] = "Class updated successfully";
        header("Location: manage-classes.php");
        exit();
        
    } catch(Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Student Management System | Edit Class</title>
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
                                        <h4 class="card-title mb-sm-0">Edit Class</h4>
                                        <a href="manage-classes.php" class="btn btn-secondary ml-auto">Back to Classes</a>
                                    </div>
                                    
                                    <?php if($error) { ?>
                                        <div class="alert alert-danger alert-dismissible fade show">
                                            <?php echo $error; ?>
                                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                                        </div>
                                    <?php } ?>
                                    
                                    <form class="forms-sample" method="post" id="classForm">
                                        <div class="form-group">
                                            <label for="className">Class Name</label>
                                            <select class="form-control" id="className" name="className" required>
                                                <option value="">Select Class</option>
                                                <option value="SS" <?php echo $classData['ClassName'] === 'SS' ? 'selected' : ''; ?>>SS</option>
                                                <option value="JS" <?php echo $classData['ClassName'] === 'JS' ? 'selected' : ''; ?>>JS</option>
                                                <option value="Basic" <?php echo $classData['ClassName'] === 'Basic' ? 'selected' : ''; ?>>Basic</option>
                                                <option value="Nursery" <?php echo $classData['ClassName'] === 'Nursery' ? 'selected' : ''; ?>>Nursery</option>
                                                <option value="PG" <?php echo $classData['ClassName'] === 'PG' ? 'selected' : ''; ?>>PG</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="level">Level</label>
                                            <select class="form-control" id="level" name="level" required>
                                                <option value="">Select Level</option>
                                                <?php if($classData['ClassName'] === 'PG') { ?>
                                                    <option value="PG" selected>PG</option>
                                                <?php } else { ?>
                                                    <option value="1" <?php echo $classData['Level'] === '1' ? 'selected' : ''; ?>>1</option>
                                                    <option value="2" <?php echo $classData['Level'] === '2' ? 'selected' : ''; ?>>2</option>
                                                    <option value="3" <?php echo $classData['Level'] === '3' ? 'selected' : ''; ?>>3</option>
                                                    <option value="4" <?php echo $classData['Level'] === '4' ? 'selected' : ''; ?>>4</option>
                                                    <option value="5" <?php echo $classData['Level'] === '5' ? 'selected' : ''; ?>>5</option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="session">Session (YYYY/YYYY)</label>
                                            <input type="text" class="form-control" id="session" name="session" 
                                                   placeholder="e.g., 2024/2025" required 
                                                   pattern="\d{4}/\d{4}"
                                                   value="<?php echo htmlspecialchars($classData['Session']); ?>">
                                            <small class="form-text text-muted">Format: YYYY/YYYY (e.g., 2024/2025)</small>
                                        </div>
                                        <div class="form-group">
                                            <label for="term" class="required-field">Term</label>
                                            <select class="form-control" id="term" name="term" required>
                                                <option value="">Select Term</option>
                                                <option value="First Term" <?php echo $classData['Term'] === 'First Term' ? 'selected' : ''; ?>>First Term</option>
                                                <option value="Second Term" <?php echo $classData['Term'] === 'Second Term' ? 'selected' : ''; ?>>Second Term</option>
                                                <option value="Third Term" <?php echo $classData['Term'] === 'Third Term' ? 'selected' : ''; ?>>Third Term</option>
                                            </select>
                                        </div>
                                        <button type="submit" name="submit" class="btn btn-primary mr-2">Update</button>
                                        <a href="manage-classes.php" class="btn btn-light">Cancel</a>
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
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const classNameSelect = document.getElementById('className');
        const levelSelect = document.getElementById('level');
        const sessionInput = document.getElementById('session');
        const form = document.getElementById('classForm');
        
        // Dynamic Level Selection based on Class Name
        function updateLevelOptions() {
            const selectedClass = classNameSelect.value;
            
            // Clear existing options
            while (levelSelect.options.length > 1) {
                levelSelect.remove(1);
            }
            
            // Add appropriate options based on class name
            if (selectedClass === 'PG') {
                const pgOption = new Option('PG', 'PG');
                levelSelect.add(pgOption);
                levelSelect.value = 'PG';
                levelSelect.disabled = true;
            } else if (selectedClass) {
                levelSelect.disabled = false;
                for (let i = 1; i <= 5; i++) {
                    const option = new Option(i.toString(), i.toString());
                    levelSelect.add(option);
                }
            }
        }
        
        // Session input validation and formatting
        function formatSession(input) {
            let value = input.replace(/\D/g, '');
            
            if (value.length >= 4) {
                const year1 = value.substr(0, 4);
                const year2 = (parseInt(year1) + 1).toString();
                value = year1 + '/' + year2;
            }
            
            return value.substr(0, 9);
        }
        
        // Event Listeners
        classNameSelect.addEventListener('change', updateLevelOptions);
        
        sessionInput.addEventListener('input', function(e) {
            const formattedValue = formatSession(e.target.value);
            e.target.value = formattedValue;
        });
        
        form.addEventListener('submit', function(e) {
            const sessionValue = sessionInput.value;
            
            if (sessionValue && !/^\d{4}\/\d{4}$/.test(sessionValue)) {
                e.preventDefault();
                alert('Please enter session in YYYY/YYYY format');
                return;
            }
            
            if (sessionValue) {
                const [year1, year2] = sessionValue.split('/').map(Number);
                if (year2 !== year1 + 1) {
                    e.preventDefault();
                    alert('Session years must be consecutive');
                    return;
                }
            }
            
            if (classNameSelect.value === 'PG' && levelSelect.value !== 'PG') {
                e.preventDefault();
                alert('PG class must have PG level');
                return;
            }
        });
        
        // Initialize level options based on initial class name
        updateLevelOptions();
    });
    </script>
</body>
</html>
