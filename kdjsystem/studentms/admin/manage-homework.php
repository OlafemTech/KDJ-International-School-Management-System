<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']==0)) {
    header('location:logout.php');
} else {
    // Delete homework
    if(isset($_GET['delid'])) {
        try {
            $id = intval($_GET['delid']);
            
            // Delete any attachments first
            $sql = "SELECT AttachmentURL FROM tblhomework WHERE ID=:id";
            $query = $dbh->prepare($sql);
            $query->bindParam(':id', $id, PDO::PARAM_INT);
            $query->execute();
            $result = $query->fetch(PDO::FETCH_OBJ);
            
            if($result && $result->AttachmentURL) {
                $attachmentPath = "../uploads/homework/" . $result->AttachmentURL;
                if(file_exists($attachmentPath)) {
                    unlink($attachmentPath);
                }
            }
            
            // Delete homework record (submissions will be deleted by CASCADE)
            $sql = "DELETE FROM tblhomework WHERE ID=:id";
            $query = $dbh->prepare($sql);
            $query->bindParam(':id', $id, PDO::PARAM_INT);
            $query->execute();
            
            $_SESSION['success'] = "Homework deleted successfully";
        } catch(PDOException $e) {
            $_SESSION['error'] = "Error deleting homework: " . $e->getMessage();
        }
        header('location: manage-homework.php');
        exit();
    }

    // Toggle homework status
    if(isset($_GET['id']) && isset($_GET['status'])) {
        try {
            $id = intval($_GET['id']);
            $status = $_GET['status'] == 'Active' ? 'Inactive' : 'Active';
            
            $sql = "UPDATE tblhomework SET Status=:status WHERE ID=:id";
            $query = $dbh->prepare($sql);
            $query->bindParam(':status', $status, PDO::PARAM_STR);
            $query->bindParam(':id', $id, PDO::PARAM_INT);
            $query->execute();
            
            $_SESSION['success'] = "Homework status updated successfully";
        } catch(PDOException $e) {
            $_SESSION['error'] = "Error updating status: " . $e->getMessage();
        }
        header('location: manage-homework.php');
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Student Management System | Manage Homework</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="./vendors/daterangepicker/daterangepicker.css">
    <link rel="stylesheet" href="./vendors/chartist/chartist.min.css">
    <link rel="stylesheet" href="./css/style.css">
    <link rel="stylesheet" href="./css/custom-buttons.css">
</head>
<body>
    <div class="container-scroller">
        <?php include_once('includes/header.php');?>
        <div class="container-fluid page-body-wrapper">
            <?php include_once('includes/sidebar.php');?>
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="page-header">
                        <h3 class="page-title">Manage Homework</h3>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Manage Homework</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="row">
                        <div class="col-md-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-sm-flex align-items-center mb-4">
                                        <h4 class="card-title mb-sm-0">Manage Homework</h4>
                                        <a href="add-homework.php" class="btn btn-primary ml-auto mb-3 mb-sm-0">
                                            <i class="icon-plus"></i> Add New Homework
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
                                    <div class="row mb-3">
                                        <div class="col-md-12">
                                            <form class="form-inline" method="GET">
                                                <select name="subject" class="form-control mb-2 mr-sm-2">
                                                    <option value="">All Subjects</option>
                                                    <?php
                                                    $sql = "SELECT DISTINCT s.* 
                                                           FROM tblsubjects s
                                                           JOIN tblsubjectteacherclass stc ON s.ID = stc.SubjectID
                                                           ORDER BY s.SubjectName";
                                                    $query = $dbh->prepare($sql);
                                                    $query->execute();
                                                    $subjects = $query->fetchAll(PDO::FETCH_OBJ);
                                                    foreach($subjects as $subject) {
                                                        $selected = (isset($_GET['subject']) && $_GET['subject'] == $subject->ID) ? 'selected' : '';
                                                        echo "<option value='" . htmlspecialchars($subject->ID) . "' {$selected}>" . 
                                                             htmlspecialchars($subject->SubjectName) . "</option>";
                                                    }
                                                    ?>
                                                </select>
                                                <select name="class" class="form-control mb-2 mr-sm-2">
                                                    <option value="">All Classes</option>
                                                    <?php
                                                    $sql = "SELECT DISTINCT c.* 
                                                           FROM tblclass c
                                                           JOIN tblsubjectteacherclass stc ON c.ID = stc.ClassID
                                                           ORDER BY c.ClassName, c.Section";
                                                    $query = $dbh->prepare($sql);
                                                    $query->execute();
                                                    $classes = $query->fetchAll(PDO::FETCH_OBJ);
                                                    foreach($classes as $class) {
                                                        $selected = (isset($_GET['class']) && $_GET['class'] == $class->ID) ? 'selected' : '';
                                                        echo "<option value='" . htmlspecialchars($class->ID) . "' {$selected}>" . 
                                                             htmlspecialchars($class->ClassName . ' ' . $class->Section) . "</option>";
                                                    }
                                                    ?>
                                                </select>
                                                <select name="status" class="form-control mb-2 mr-sm-2">
                                                    <option value="">All Status</option>
                                                    <option value="Active" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                                                    <option value="Inactive" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                                                </select>
                                                <button type="submit" class="btn btn-primary mb-2">Filter</button>
                                                <?php if(isset($_GET['subject']) || isset($_GET['class']) || isset($_GET['status'])) { ?>
                                                    <a href="manage-homework.php" class="btn btn-secondary mb-2 ml-2">Clear</a>
                                                <?php } ?>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Title</th>
                                                    <th>Subject</th>
                                                    <th>Class</th>
                                                    <th>Teacher</th>
                                                    <th>Due Date</th>
                                                    <th>Max Grade</th>
                                                    <th>Submissions</th>
                                                    <th>Status</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                // Build WHERE clause based on filters
                                                $where = [];
                                                $params = [];
                                                
                                                if(isset($_GET['subject']) && !empty($_GET['subject'])) {
                                                    $where[] = "h.SubjectID = :subjectId";
                                                    $params[':subjectId'] = intval($_GET['subject']);
                                                }
                                                
                                                if(isset($_GET['class']) && !empty($_GET['class'])) {
                                                    $where[] = "h.ClassID = :classId";
                                                    $params[':classId'] = intval($_GET['class']);
                                                }
                                                
                                                if(isset($_GET['status']) && !empty($_GET['status'])) {
                                                    $where[] = "h.Status = :status";
                                                    $params[':status'] = $_GET['status'];
                                                }
                                                
                                                $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
                                                
                                                $sql = "SELECT h.*, s.SubjectName, c.ClassName, c.Section, 
                                                              t.Name as TeacherName,
                                                              COUNT(DISTINCT hs.ID) as SubmissionCount,
                                                              COUNT(DISTINCT CASE WHEN hs.Status = 'Graded' THEN hs.ID END) as GradedCount
                                                       FROM tblhomework h
                                                       LEFT JOIN tblsubjects s ON h.SubjectID = s.ID
                                                       LEFT JOIN tblclass c ON h.ClassID = c.ID
                                                       LEFT JOIN tblteacher t ON h.TeacherID = t.ID
                                                       LEFT JOIN tblhomeworksubmissions hs ON h.ID = hs.HomeworkID
                                                       $whereClause
                                                       GROUP BY h.ID
                                                       ORDER BY h.DueDate DESC";
                                                
                                                $query = $dbh->prepare($sql);
                                                foreach($params as $param => $value) {
                                                    $query->bindValue($param, $value);
                                                }
                                                $query->execute();
                                                $results = $query->fetchAll(PDO::FETCH_OBJ);
                                                $cnt = 1;
                                                
                                                if($query->rowCount() > 0) {
                                                    foreach($results as $row) {
                                                        $isOverdue = strtotime($row->DueDate) < time();
                                                        $statusClass = $row->Status == 'Active' ? 
                                                                     ($isOverdue ? 'badge-warning' : 'badge-success') : 
                                                                     'badge-danger';
                                                ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($cnt);?></td>
                                                    <td>
                                                        <?php echo htmlspecialchars($row->Title);?>
                                                        <?php if($row->AttachmentURL) { ?>
                                                            <a href="../uploads/homework/<?php echo htmlspecialchars($row->AttachmentURL);?>" 
                                                               target="_blank" class="text-primary ml-2">
                                                                <i class="icon-paper-clip"></i>
                                                            </a>
                                                        <?php } ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($row->SubjectName);?></td>
                                                    <td><?php echo htmlspecialchars($row->ClassName . ' ' . $row->Section);?></td>
                                                    <td><?php echo htmlspecialchars($row->TeacherName);?></td>
                                                    <td>
                                                        <?php 
                                                        echo date('Y-m-d', strtotime($row->DueDate));
                                                        echo '<br><small class="text-muted">';
                                                        echo date('h:i A', strtotime($row->DueDate));
                                                        echo '</small>';
                                                        if($isOverdue && $row->Status == 'Active') {
                                                            echo '<br><span class="badge badge-warning">Overdue</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($row->MaxGrade);?></td>
                                                    <td>
                                                        <span class="badge badge-info">
                                                            <?php echo $row->SubmissionCount;?> Total
                                                        </span>
                                                        <?php if($row->SubmissionCount > 0) { ?>
                                                            <br>
                                                            <small class="text-muted">
                                                                <?php echo $row->GradedCount;?> Graded
                                                            </small>
                                                        <?php } ?>
                                                    </td>
                                                    <td>
                                                        <a href="?id=<?php echo $row->ID;?>&status=<?php echo $row->Status;?>" 
                                                           class="badge <?php echo $statusClass;?>">
                                                            <?php echo htmlspecialchars($row->Status);?>
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <div class="action-buttons">
                                                            <a href="view-submissions.php?hwid=<?php echo htmlspecialchars($row->ID);?>" 
                                                               class="btn btn-action btn-view" title="View Submissions">
                                                                <i class="icon-eye"></i>
                                                            </a>
                                                            <a href="edit-homework.php?id=<?php echo htmlspecialchars($row->ID);?>" 
                                                               class="btn btn-action btn-edit" title="Edit">
                                                                <i class="icon-pencil"></i>
                                                            </a>
                                                            <a href="?delid=<?php echo htmlspecialchars($row->ID);?>" 
                                                               onclick="return confirm('Do you really want to delete this homework? This will also delete all student submissions.');" 
                                                               class="btn btn-action btn-delete" title="Delete">
                                                                <i class="icon-trash"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php 
                                                        $cnt++;
                                                    }
                                                } else { ?>
                                                <tr>
                                                    <td colspan="10" class="text-center">No homework found</td>
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
                <?php include_once('includes/footer.php');?>
            </div>
        </div>
    </div>
    <script src="vendors/js/vendor.bundle.base.js"></script>
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
</body>
</html>
<?php } ?>
