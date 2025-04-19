<?php
if(session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

// Verify database connection
try {
    if (!$dbh) {
        throw new PDOException("Database connection failed");
    }
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

if(strlen($_SESSION['sturecmsaid'])==0) {
    header('location:logout.php');
} else {
    if(isset($_POST['submit'])) {
        try {
            // Validate and sanitize inputs
            if (!isset($_POST['className'])) {
                throw new Exception("Class name is required");
            }

            $className = strval($_POST['className']);
            
            // Special handling for PG class
            if ($className === 'PG') {
                $level = 'PG';
            } else {
                if (!isset($_POST['level'])) {
                    throw new Exception("Level is required for non-PG classes");
                }
                $level = strval($_POST['level']);
            }

            if (!isset($_POST['session']) || !isset($_POST['term'])) {
                throw new Exception("Session and Term are required");
            }

            $session = strval($_POST['session']);
            $term = strval($_POST['term']);

            // Validate inputs
            if (empty($className) || empty($level) || empty($session) || empty($term)) {
                throw new Exception("All fields are required");
            }

            // Validate class combination
            $sql = "SELECT ID from tblclass WHERE ClassName=:className AND Level=:level AND Session=:session AND Term=:term";
            $query = $dbh->prepare($sql);
            $query->bindParam(':className', $className, PDO::PARAM_STR);
            $query->bindParam(':level', $level, PDO::PARAM_STR);
            $query->bindParam(':session', $session, PDO::PARAM_STR);
            $query->bindParam(':term', $term, PDO::PARAM_STR);
            $query->execute();

            if (!$query) {
                throw new Exception("Database query failed");
            }

            if($query->rowCount() > 0) {
                echo '<script>alert("This class combination already exists.")</script>';
            } else {
                $sql="INSERT INTO tblclass(ClassName, Level, Session, Term) VALUES(:className, :level, :session, :term)";
                $query=$dbh->prepare($sql);
                $query->bindParam(':className', $className, PDO::PARAM_STR);
                $query->bindParam(':level', $level, PDO::PARAM_STR);
                $query->bindParam(':session', $session, PDO::PARAM_STR);
                $query->bindParam(':term', $term, PDO::PARAM_STR);
                if (!$query->execute()) {
                    throw new Exception("Failed to insert class");
                }
                $LastInsertId=$dbh->lastInsertId();
                if($LastInsertId>0) {
                    echo '<script>alert("Class has been added.")</script>';
                    echo "<script>window.location.href ='manage-class.php'</script>";
                } else {
                    echo '<script>alert("Something went wrong. Please try again")</script>';
                }
            }
        } catch (Exception $e) {
            echo '<script>alert("Error: ' . addslashes($e->getMessage()) . '")</script>';
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Student Management System | Add Class</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/custom-header.css">
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
                                    <h4 class="card-title">Add Class</h4>
                                    <form class="forms-sample" method="post" id="addClass">
                                        <div class="form-group">
                                            <label for="className">Class Name</label>
                                            <select name="className" class="form-control" required id="className" onchange="updateLevelOptions()">
                                                <option value="">Select Class</option>
                                                <option value="PG">PG</option>
                                                <option value="Nursery">Nursery</option>
                                                <option value="Basic">Basic</option>
                                                <option value="JS">JS</option>
                                                <option value="SS">SS</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="level">Level</label>
                                            <select name="level" class="form-control" required id="level">
                                                <option value="">Select Level</option>
                                                <option value="1">1</option>
                                                <option value="2">2</option>
                                                <option value="3">3</option>
                                                <option value="4">4</option>
                                                <option value="5">5</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="session">Session</label>
                                            <input type="text" name="session" class="form-control" required 
                                                   placeholder="YYYY/YYYY" pattern="\d{4}/\d{4}"
                                                   title="Please enter session in format YYYY/YYYY">
                                        </div>
                                        <div class="form-group">
                                            <label for="term">Term</label>
                                            <select name="term" class="form-control" required>
                                                <option value="">Select Term</option>
                                                <option value="1st Term">1st Term</option>
                                                <option value="2nd Term">2nd Term</option>
                                                <option value="3rd Term">3rd Term</option>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-primary mr-2" name="submit">Add</button>
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
            // Initial update of level options
            updateLevelOptions();
            
            // Add form submission handler
            document.getElementById('addClass').addEventListener('submit', function(e) {
                var className = document.getElementById('className').value;
                var level = document.getElementById('level');
                
                // For PG class, ensure level is set to PG
                if (className === 'PG') {
                    level.value = 'PG';
                }
            });
        });

        function updateLevelOptions() {
            var className = document.getElementById('className').value;
            var levelSelect = document.getElementById('level');
            
            if (className === 'PG') {
                // For PG class, set level to PG only
                levelSelect.innerHTML = '<option value="PG" selected>PG</option>';
                levelSelect.value = 'PG';
                levelSelect.disabled = true;
            } else {
                // For other classes, show levels 1-5
                levelSelect.disabled = false;
                levelSelect.innerHTML = `
                    <option value="">Select Level</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                `;
            }
        }
    </script>
</body>
</html>
<?php } ?>