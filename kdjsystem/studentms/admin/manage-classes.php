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

// Code for deletion
if(isset($_GET['delid'])) {
    try {
        $id = intval($_GET['delid']);
        
        // First check if class exists
        $check = $dbh->prepare("SELECT ClassName, Level, Session, Term FROM tblclass WHERE ID = :id");
        $check->bindParam(':id', $id, PDO::PARAM_INT);
        $check->execute();
        
        if($check->rowCount() == 0) {
            throw new Exception("Class not found");
        }
        
        $classInfo = $check->fetch(PDO::FETCH_ASSOC);
        
        // Check if any students are assigned to this class
        $studentCheck = $dbh->prepare("SELECT COUNT(*) FROM tblstudent WHERE 
            StudentClass = :className AND Level = :level AND Session = :session AND Term = :term");
        $studentCheck->execute([
            ':className' => $classInfo['ClassName'],
            ':level' => $classInfo['Level'],
            ':session' => $classInfo['Session'],
            ':term' => $classInfo['Term']
        ]);
        
        if($studentCheck->fetchColumn() > 0) {
            throw new Exception("Cannot delete class: Students are assigned to this class");
        }
        
        // Safe to delete
        $sql = "DELETE FROM tblclass WHERE ID=:id";
        $query = $dbh->prepare($sql);
        $query->bindParam(':id', $id, PDO::PARAM_INT);
        $query->execute();
        
        $_SESSION['success'] = "Class deleted successfully";
        header("Location: manage-classes.php");
        exit();
        
    } catch(Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: manage-classes.php");
        exit();
    }
}

// Get filter values
$filterClass = isset($_GET['class']) ? $_GET['class'] : '';
$filterSession = isset($_GET['session']) ? $_GET['session'] : '';
$filterTerm = isset($_GET['term']) ? $_GET['term'] : '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Student Management System | Manage Classes</title>
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
                    
                    <div class="row">
                        <div class="col-md-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-sm-flex align-items-center mb-4">
                                        <h4 class="card-title mb-sm-0">Manage Classes</h4>
                                        <div class="ml-auto">
                                            <a href="add-class.php" class="btn btn-primary">Add New Class</a>
                                        </div>
                                    </div>
                                    
                                    <!-- Filters -->
                                    <form class="form-inline mb-4" method="GET">
                                        <select name="class" class="form-control mr-2">
                                            <option value="">All Classes</option>
                                            <option value="SS" <?php echo $filterClass === 'SS' ? 'selected' : ''; ?>>SS</option>
                                            <option value="JS" <?php echo $filterClass === 'JS' ? 'selected' : ''; ?>>JS</option>
                                            <option value="Basic" <?php echo $filterClass === 'Basic' ? 'selected' : ''; ?>>Basic</option>
                                            <option value="Nursery" <?php echo $filterClass === 'Nursery' ? 'selected' : ''; ?>>Nursery</option>
                                            <option value="PG" <?php echo $filterClass === 'PG' ? 'selected' : ''; ?>>PG</option>
                                        </select>
                                        
                                        <input type="text" name="session" class="form-control mr-2" 
                                               placeholder="Session (YYYY/YYYY)" 
                                               value="<?php echo htmlentities($filterSession); ?>"
                                               pattern="\d{4}/\d{4}">
                                               
                                        <select name="term" class="form-control mr-2">
                                            <option value="">All Terms</option>
                                             <option value="1st Term" <?php echo $filterTerm === '1st Term' ? 'selected' : ''; ?>>1st Term</option>
                                             <option value="2nd Term" <?php echo $filterTerm === '2nd Term' ? 'selected' : ''; ?>>2nd Term</option>
                                             <option value="3rd Term" <?php echo $filterTerm === '3rd Term' ? 'selected' : ''; ?>>3rd Term</option>
                                        </select>
                                        
                                        <button type="submit" class="btn btn-primary">Filter</button>
                                        <?php if($filterClass || $filterSession || $filterTerm) { ?>
                                            <a href="manage-classes.php" class="btn btn-secondary ml-2">Clear</a>
                                        <?php } ?>
                                    </form>
                                    
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Class Name</th>
                                                    <th>Level</th>
                                                    <th>Session</th>
                                                    <th>Term</th>
                                                    <th>Subjects</th>
                                                    <th>Students</th>
                                                    <th>Created On</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                try {
                                                    $sql = "SELECT c.*, 
                                                            (SELECT COUNT(*) FROM tblsubjects s WHERE s.ClassID = c.ID) as SubjectCount,
                                                            (SELECT COUNT(*) FROM tblstudent st WHERE st.StudentClass = c.ClassName 
                                                             AND st.Level = c.Level AND st.Session = c.Session AND st.Term = c.Term) as StudentCount
                                                            FROM tblclass c
                                                            WHERE 1=1";
                                                    
                                                    $params = array();
                                                    
                                                    if($filterClass) {
                                                        $sql .= " AND c.ClassName = :className";
                                                        $params[':className'] = $filterClass;
                                                    }
                                                    
                                                    if($filterSession) {
                                                        $sql .= " AND c.Session = :session";
                                                        $params[':session'] = $filterSession;
                                                    }
                                                    
                                                    if($filterTerm) {
                                                        $sql .= " AND c.Term = :term";
                                                        $params[':term'] = $filterTerm;
                                                    }
                                                    
                                                    $sql .= " ORDER BY c.ClassName ASC, c.Level ASC, c.Session DESC, FIELD(c.Term, '1st Term', '2nd Term', '3rd Term')";
                                                    
                                                    $query = $dbh->prepare($sql);
                                                    foreach ($params as $key => &$val) {
                                                        $query->bindParam($key, $val);
                                                    }
                                                    $query->execute();
                                                    $results = $query->fetchAll(PDO::FETCH_OBJ);
                                                    $cnt = 1;
                                                    
                                                    if($query->rowCount() > 0) {
                                                        foreach($results as $row) { ?>
                                                            <tr>
                                                                <td><?php echo htmlentities($cnt);?></td>
                                                                <td><?php echo htmlentities($row->ClassName);?></td>
                                                                <td><?php echo htmlentities($row->Level);?></td>
                                                                <td><?php echo htmlentities($row->Session);?></td>
                                                                <td><?php echo htmlentities($row->Term);?></td>
                                                                <td><?php echo htmlentities($row->SubjectCount);?></td>
                                                                <td><?php echo htmlentities($row->StudentCount);?></td>
                                                                <td><?php echo date('Y-m-d', strtotime($row->CreationDate));?></td>
                                                                <td>
                                                                    <a href="edit-class.php?editid=<?php echo htmlentities($row->ID);?>" 
                                                                       class="btn btn-sm btn-primary mr-2" title="Edit">
                                                                       <i class="icon-pencil"></i>
                                                                    </a>
                                                                    <a href="manage-classes.php?delid=<?php echo htmlentities($row->ID);?>" 
                                                                       onclick="return confirm('Do you really want to delete this class? This will also remove all associated subjects and student records.');" 
                                                                       class="btn btn-sm btn-danger" title="Delete">
                                                                       <i class="icon-trash"></i>
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                            <?php 
                                                            $cnt++;
                                                        }
                                                    } else { ?>
                                                        <tr>
                                                            <td colspan="8" class="text-center">No classes found</td>
                                                        </tr>
                                                    <?php }
                                                    
                                                } catch(PDOException $e) {
                                                    echo "<tr><td colspan='8' class='text-center text-danger'>Database error: " . $e->getMessage() . "</td></tr>";
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
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
        // Session input validation
        const sessionInput = document.querySelector('input[name="session"]');
        
        sessionInput.addEventListener('input', function(e) {
            let value = e.target.value;
            
            // Remove any non-digit characters except /
            value = value.replace(/[^\d/]/g, '');
            
            // Ensure proper YYYY/YYYY format
            if(value.length >= 4 && !value.includes('/')) {
                value = value.substr(0, 4) + '/' + value.substr(4);
            }
            
            // Limit to 9 characters (YYYY/YYYY)
            value = value.substr(0, 9);
            
            e.target.value = value;
        });
        
        // Form validation
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            const sessionValue = sessionInput.value;
            
            if(sessionValue && !/^\d{4}\/\d{4}$/.test(sessionValue)) {
                e.preventDefault();
                alert('Please enter session in YYYY/YYYY format');
                return;
            }
            
            if(sessionValue) {
                const [year1, year2] = sessionValue.split('/').map(Number);
                if(year2 !== year1 + 1) {
                    e.preventDefault();
                    alert('Session years must be consecutive');
                    return;
                }
            }
        });
    });
    </script>
</body>
</html>
