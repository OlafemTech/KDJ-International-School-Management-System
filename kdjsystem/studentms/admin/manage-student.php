<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('../includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']==0)) {
    header('location:logout.php');
    exit();
}

// Code for deletion
if(isset($_GET['delid'])) {
    try {
        $id = $_GET['delid'];
        
        // Delete student record
        $sql = "DELETE FROM tblstudent WHERE StudentID=:id";
        $query = $dbh->prepare($sql);
        $query->bindParam(':id', $id, PDO::PARAM_STR);
        $query->execute();
        
        $_SESSION['success'] = "Student deleted successfully";
        header('location: manage-student.php');
        exit();
    } catch(PDOException $e) {
        $_SESSION['error'] = "Error deleting student: " . $e->getMessage();
        header('location: manage-student.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Student Management System | Manage Students</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="./vendors/daterangepicker/daterangepicker.css">
    <link rel="stylesheet" href="./vendors/chartist/chartist.min.css">
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>
    <div class="container-scroller">
        <?php include_once('includes/header.php');?>
        <div class="container-fluid page-body-wrapper">
            <?php include_once('includes/sidebar.php');?>
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="page-header">
                        <h3 class="page-title">Manage Students</h3>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Manage Students</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="row">
                        <div class="col-md-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-sm-flex align-items-center mb-4">
                                        <h4 class="card-title mb-sm-0">Manage Students</h4>
                                        <a href="add-student.php" class="btn btn-primary ml-auto mb-3 mb-sm-0">
                                            <i class="icon-plus"></i> Add New Student
                                        </a>
                                    </div>
                                    <?php 
                                    if(isset($_SESSION['success'])) {
                                        echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
                                        unset($_SESSION['success']);
                                    }
                                    if(isset($_SESSION['error'])) {
                                        echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
                                        unset($_SESSION['error']);
                                    }
                                    ?>
                                    <div class="table-responsive border rounded p-1">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th class="font-weight-bold">S.No</th>
                                                    <th class="font-weight-bold">Student ID</th>
                                                    <th class="font-weight-bold">Student Name</th>
                                                    <th class="font-weight-bold">Class</th>
                                                    <th class="font-weight-bold">Email</th>
                                                    <th class="font-weight-bold">Admission Date</th>
                                                    <th class="font-weight-bold">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php
                                            // Pagination
                                            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                                            $records_per_page = 10;
                                            $offset = ($page - 1) * $records_per_page;

                                            // Get total records
                                            $total_query = "SELECT COUNT(*) FROM tblstudent";
                                            $total_result = $dbh->query($total_query);
                                            $total_records = $total_result->fetchColumn();
                                            $total_pages = ceil($total_records / $records_per_page);

                                            // Get students with class info
                                            $sql = "SELECT s.*, c.ClassName, c.Section 
                                                    FROM tblstudent s 
                                                    LEFT JOIN tblclass c ON s.StudentClass = c.ID 
                                                    ORDER BY s.DateofAdmission DESC 
                                                    LIMIT :offset, :limit";
                                            
                                            $query = $dbh->prepare($sql);
                                            $query->bindParam(':offset', $offset, PDO::PARAM_INT);
                                            $query->bindParam(':limit', $records_per_page, PDO::PARAM_INT);
                                            $query->execute();
                                            $results = $query->fetchAll(PDO::FETCH_OBJ);

                                            if($query->rowCount() > 0) {
                                                $cnt = $offset + 1;
                                                foreach($results as $row) {
                                            ?>
                                                <tr>
                                                    <td><?php echo htmlentities($cnt);?></td>
                                                    <td><?php echo htmlentities($row->StudentID);?></td>
                                                    <td><?php echo htmlentities($row->StudentName);?></td>
                                                    <td>
                                                        <?php 
                                                        echo $row->ClassName ? 
                                                            htmlentities($row->ClassName . ' - ' . $row->Section) : 
                                                            'Not Assigned';
                                                        ?>
                                                    </td>
                                                    <td><?php echo htmlentities($row->StudentEmail);?></td>
                                                    <td><?php echo htmlentities(date('d-m-Y', strtotime($row->DateofAdmission)));?></td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <a href="edit-student.php?editid=<?php echo htmlentities($row->StudentID);?>" 
                                                               class="btn btn-primary btn-sm" title="Edit">
                                                                <i class="icon-pencil"></i>
                                                            </a>
                                                            <a href="view-student.php?viewid=<?php echo htmlentities($row->StudentID);?>" 
                                                               class="btn btn-info btn-sm" title="View">
                                                                <i class="icon-eye"></i>
                                                            </a>
                                                            <a href="manage-student.php?delid=<?php echo htmlentities($row->StudentID);?>" 
                                                               onclick="return confirm('Do you really want to delete this student?');" 
                                                               class="btn btn-danger btn-sm" title="Delete">
                                                                <i class="icon-trash"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php 
                                                    $cnt++;
                                                }
                                            } else {
                                            ?>
                                                <tr>
                                                    <td colspan="7" class="text-center">No students found</td>
                                                </tr>
                                            <?php 
                                            }
                                            ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php if($total_pages > 1): ?>
                                    <div class="pagination-wrapper mt-4">
                                        <ul class="pagination justify-content-center">
                                            <?php if($page > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=1">First</a>
                                                </li>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?php echo $page-1; ?>">Previous</a>
                                                </li>
                                            <?php endif; ?>

                                            <?php for($i = max(1, $page-2); $i <= min($page+2, $total_pages); $i++): ?>
                                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                                </li>
                                            <?php endfor; ?>

                                            <?php if($page < $total_pages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?php echo $page+1; ?>">Next</a>
                                                </li>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?php echo $total_pages; ?>">Last</a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                    <?php endif; ?>
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
    <script src="./vendors/chart.js/Chart.min.js"></script>
    <script src="./vendors/moment/moment.min.js"></script>
    <script src="./vendors/daterangepicker/daterangepicker.js"></script>
    <script src="./vendors/chartist/chartist.min.js"></script>
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
</body>
</html>
