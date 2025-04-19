<?php
if(session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(0);
include('includes/dbconnection.php');

if(strlen($_SESSION['sturecmsaid'])==0) {
    header('location:logout.php');
} else {
    // Handle class deletion
    if(isset($_GET['delid'])) {
        $rid=intval($_GET['delid']);
        $sql="delete from tblclass where ID=:rid";
        $query=$dbh->prepare($sql);
        $query->bindParam(':rid',$rid,PDO::PARAM_STR);
        $query->execute();
        echo "<script>alert('Data deleted');</script>"; 
        echo "<script>window.location.href = 'manage-class.php'</script>";
    }
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
                    <div class="row">
                        <div class="col-md-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-sm-flex align-items-center mb-4">
                                        <h4 class="card-title mb-sm-0">Manage Classes</h4>
                                        <a href="add-class.php" class="btn btn-action btn-edit ml-auto mb-3 mb-sm-0">
                                            <i class="icon-plus"></i> Add New Class
                                        </a>
                                    </div>
                                    <div class="table-responsive border rounded p-1">
                                        <table class="table" id="example">
                                            <thead>
                                                <tr>
                                                    <th class="font-weight-bold">S.No</th>
                                                    <th class="font-weight-bold">Class Name</th>
                                                    <th class="font-weight-bold">Level</th>
                                                    <th class="font-weight-bold">Session</th>
                                                    <th class="font-weight-bold">Term</th>
                                                    <th class="font-weight-bold">Creation Date</th>
                                                    <th class="font-weight-bold">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $sql="SELECT * from tblclass";
                                                $query = $dbh -> prepare($sql);
                                                $query->execute();
                                                $results=$query->fetchAll(PDO::FETCH_OBJ);
                                                $cnt=1;
                                                if($query->rowCount() > 0) {
                                                    foreach($results as $row) {
                                                ?>
                                                <tr>
                                                    <td><?php echo htmlentities($cnt);?></td>
                                                    <td><?php echo htmlentities($row->ClassName);?></td>
                                                    <td><?php echo htmlentities($row->Level);?></td>
                                                    <td><?php echo htmlentities($row->Session);?></td>
                                                    <td><?php echo htmlentities($row->Term);?></td>
                                                    <td><?php echo htmlentities($row->CreationDate);?></td>
                                                    <td>
                                                        <div class="action-buttons">
                                                            <a href="edit-class.php?editid=<?php echo htmlentities($row->ID);?>" 
                                                               class="btn btn-action btn-edit" title="Edit">
                                                               <i class="icon-pencil"></i>
                                                            </a>
                                                            <a href="view-class.php?viewid=<?php echo htmlentities($row->ID);?>" 
                                                               class="btn btn-action btn-view" title="View">
                                                               <i class="icon-eye"></i>
                                                            </a>
                                                            <a href="manage-class.php?delid=<?php echo htmlentities($row->ID);?>" 
                                                               onclick="return confirm('Do you really want to delete this class?');" 
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