<?php
if(session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(0);
include('includes/dbconnection.php');

if(strlen($_SESSION['sturecmsaid'])==0) {
    header('location:logout.php');
} else {
    // Handle student deletion
    if(isset($_GET['delid'])) {
        $rid=intval($_GET['delid']);
        $sql="delete from tblstudent where ID=:rid";
        $query=$dbh->prepare($sql);
        $query->bindParam(':rid',$rid,PDO::PARAM_STR);
        $query->execute();
        $_SESSION['success'] = 'Data deleted';
        echo "<script>window.location.href = 'manage-students.php'</script>";
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Student Management System | Manage Students</title>
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
                                        <h4 class="card-title mb-sm-0">Manage Students</h4>
                                        <a href="add-student.php" class="btn btn-action btn-edit ml-auto mb-3 mb-sm-0">
                                            <i class="icon-plus"></i> Add New Student
                                        </a>
                                    </div>
                                    <?php 
                                    // Display success message if exists and then clear it
                                    if(isset($_SESSION['success'])) {
                                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
                                        echo $_SESSION['success'];
                                        echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                              </button>';
                                        echo '</div>';
                                        // Clear the success message
                                        unset($_SESSION['success']);
                                    }
                                    ?>
                                    <div class="table-responsive border rounded p-1">
                                        <table class="table" id="example">
                                            <thead>
                                                <tr>
                                                    <th class="font-weight-bold">S.No</th>
                                                    <th class="font-weight-bold">Student ID</th>
                                                    <th class="font-weight-bold">Student Name</th>
                                                    <th class="font-weight-bold">Class</th>
                                                    <th class="font-weight-bold">Level</th>
                                                    <th class="font-weight-bold">Session</th>
                                                    <th class="font-weight-bold">Term</th>
                                                    <th class="font-weight-bold">Registration Date</th>
                                                    <th class="font-weight-bold">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $sql="SELECT * from tblstudent";
                                                $query = $dbh -> prepare($sql);
                                                $query->execute();
                                                $results=$query->fetchAll(PDO::FETCH_OBJ);
                                                $cnt=1;
                                                if($query->rowCount() > 0) {
                                                    foreach($results as $row) {
                                                ?>
                                                <tr>
                                                    <td><?php echo htmlentities($cnt);?></td>
                                                    <td><?php echo htmlentities($row->StudentId);?></td>
                                                    <td><?php echo htmlentities($row->StudentName);?></td>
                                                    <td><?php echo htmlentities($row->StudentClass);?></td>
                                                    <td><?php echo htmlentities($row->Level);?></td>
                                                    <td><?php echo htmlentities($row->Session);?></td>
                                                    <td><?php echo htmlentities($row->Term);?></td>
                                                    <td><?php echo htmlentities($row->RegDate);?></td>
                                                    <td>
                                                        <div class="action-buttons">
                                                            <a href="edit-student.php?editid=<?php echo htmlentities($row->ID);?>" 
                                                               class="btn btn-action btn-edit" title="Edit">
                                                               <i class="icon-pencil"></i>
                                                            </a>
                                                            <a href="view-student.php?viewid=<?php echo htmlentities($row->ID);?>" 
                                                               class="btn btn-action btn-view" title="View">
                                                               <i class="icon-eye"></i>
                                                            </a>
                                                            <a href="manage-students.php?delid=<?php echo htmlentities($row->ID);?>" 
                                                               onclick="return confirm('Do you really want to delete this student?');" 
                                                               class="btn btn-action btn-delete" title="Delete">
                                                               <i class="icon-trash"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php 
                                                    $cnt=$cnt+1;
                                                }} ?>
                                            </tbody>
                                        </table>
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
    <script src="vendors/datatables.net/jquery.dataTables.js"></script>
    <script src="vendors/datatables.net-bs4/dataTables.bootstrap4.js"></script>
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
    <script>
    $(document).ready(function() {
        $('#example').DataTable({
            "order": [[ 0, "desc" ]],
            "pageLength": 10
        });
    });
    </script>
</body>
</html>
<?php } ?>