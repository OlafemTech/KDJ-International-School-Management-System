<?php
session_start();
error_reporting(0);
include('../includes/dbconnection.php');

if (strlen($_SESSION['sturecmsstuid']) == 0) {
    header('location:logout.php');
} else {
    // Get student's class information first
    $stuid = $_SESSION['sturecmsstuid'];
    $sql = "SELECT s.StudentClass, c.ClassName 
            FROM tblstudent s
            JOIN tblclass c ON s.StudentClass = c.ID
            WHERE s.StudentId = :stuid";
    $query = $dbh->prepare($sql);
    $query->bindParam(':stuid', $stuid, PDO::PARAM_STR);
    $query->execute();
    $studentData = $query->fetch(PDO::FETCH_OBJ);
    $studentClass = $studentData->StudentClass;
?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <title>Subject Teachers</title>
        <!-- Include your CSS files here -->
        <link rel="stylesheet" href="vendors/typicons/typicons.css">
        <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
        <link rel="stylesheet" href="css/vertical-layout-light/style.css">
        <link rel="stylesheet" href="vendors/datatables/dataTables.bootstrap4.min.css">
    </head>
    <body>
        <div class="container-scroller">
            <?php include('includes/header.php'); ?>
            <div class="container-fluid page-body-wrapper">
                <?php include('includes/sidebar.php'); ?>
                <div class="main-panel">
                    <div class="content-wrapper">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-body">
                                        <h4 class="card-title">Subject Teachers - <?php echo htmlentities($studentData->ClassName); ?></h4>
                                        <div class="table-responsive">
                                            <table id="teacherTable" class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Teacher Name</th>
                                                        <th>Subject</th>
                                                        <th>Email</th>
                                                        <th>Contact</th>
                                                        <th>Class</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    // Get all teachers assigned to student's class subjects
                                                    $sql = "SELECT DISTINCT t.FirstName, t.LastName, t.Email, t.MobileNumber, 
                                                            s.SubjectName, c.ClassName
                                                            FROM tblteacher t
                                                            JOIN tblsubjectteacherclass stc ON t.ID = stc.TeacherId
                                                            JOIN tblsubjects s ON stc.SubjectId = s.ID
                                                            JOIN tblclass c ON stc.ClassId = c.ID
                                                            WHERE stc.ClassId = :classid
                                                            ORDER BY s.SubjectName";
                                                    $query = $dbh->prepare($sql);
                                                    $query->bindParam(':classid', $studentClass, PDO::PARAM_INT);
                                                    $query->execute();
                                                    $results = $query->fetchAll(PDO::FETCH_OBJ);
                                                    $cnt = 1;
                                                    if ($query->rowCount() > 0) {
                                                        foreach ($results as $row) { ?>
                                                            <tr>
                                                                <td><?php echo htmlentities($cnt); ?></td>
                                                                <td><?php echo htmlentities($row->FirstName . " " . $row->LastName); ?></td>
                                                                <td><?php echo htmlentities($row->SubjectName); ?></td>
                                                                <td><?php echo htmlentities($row->Email); ?></td>
                                                                <td><?php echo htmlentities($row->MobileNumber); ?></td>
                                                                <td><?php echo htmlentities($row->ClassName); ?></td>
                                                            </tr>
                                                    <?php $cnt = $cnt + 1;
                                                        }
                                                    } else { ?>
                                                        <tr>
                                                            <td colspan="6" class="text-center">No teachers assigned to your class subjects yet.</td>
                                                        </tr>
                                                    <?php } ?>
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
        <!-- Scripts -->
        <script src="vendors/js/vendor.bundle.base.js"></script>
        <script src="vendors/datatables/jquery.dataTables.min.js"></script>
        <script src="vendors/datatables/dataTables.bootstrap4.min.js"></script>
        <script>
            $(document).ready(function() {
                $('#teacherTable').DataTable({
                    "order": [[2, "asc"]] // Sort by Subject by default
                });
            });
        </script>
    </body>
    </html>
<?php } ?>
