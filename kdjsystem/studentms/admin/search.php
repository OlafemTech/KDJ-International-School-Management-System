<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']==0)) {
    header('location:logout.php');
} else {
    // Initialize variables
    $searchResults = [];
    $sdata = '';
    $pageno = isset($_GET['pageno']) ? (int)$_GET['pageno'] : 1;
    $no_of_records_per_page = 10;
    $offset = ($pageno-1) * $no_of_records_per_page;
    $total_pages = 1;
    $hasSearched = false;

    if(isset($_POST['search'])) {
        $hasSearched = true;
        try {
            // Sanitize search input
            $sdata = trim($_POST['searchdata']);
            
            // Count total matching records
            $countSql = "SELECT COUNT(*) as total FROM tblstudent 
                        WHERE StudentId LIKE :search 
                        OR StudentName LIKE :search 
                        OR StudentEmail LIKE :search";
            $countQuery = $dbh->prepare($countSql);
            $searchParam = "%{$sdata}%";
            $countQuery->bindParam(':search', $searchParam, PDO::PARAM_STR);
            $countQuery->execute();
            $total_rows = $countQuery->fetch(PDO::FETCH_ASSOC)['total'];
            $total_pages = ceil($total_rows / $no_of_records_per_page);

            // Get paginated results
            $sql = "SELECT 
                    ID as sid,
                    StudentId,
                    StudentName,
                    StudentEmail,
                    StudentClass,
                    Level,
                    DateofAdmission
                    FROM tblstudent 
                    WHERE StudentId LIKE :search 
                    OR StudentName LIKE :search 
                    OR StudentEmail LIKE :search 
                    ORDER BY StudentName ASC 
                    LIMIT :offset, :limit";
            
            $query = $dbh->prepare($sql);
            $query->bindParam(':search', $searchParam, PDO::PARAM_STR);
            $query->bindParam(':offset', $offset, PDO::PARAM_INT);
            $query->bindParam(':limit', $no_of_records_per_page, PDO::PARAM_INT);
            $query->execute();
            $searchResults = $query->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            echo "<script>alert('Database error: " . addslashes($e->getMessage()) . "');</script>";
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Student Management System | Search Students</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .search-form {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .search-input {
            border: 2px solid #e0e3ff;
            border-radius: 6px;
            padding: 10px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .search-input:focus {
            border-color: #6c7ae0;
            box-shadow: 0 0 0 2px rgba(108,122,224,0.2);
        }
        .search-button {
            background: #6c7ae0;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .search-button:hover {
            background: #5563d8;
            transform: translateY(-1px);
        }
        .table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }
        .table thead th {
            background: #f8f9ff;
            color: #2a2a2a;
            font-weight: 600;
            padding: 12px;
        }
        .table tbody td {
            padding: 12px;
            vertical-align: middle;
        }
        .pagination {
            display: flex;
            gap: 5px;
            margin-top: 20px;
        }
        .pagination a {
            padding: 8px 12px;
            border: 1px solid #e0e3ff;
            border-radius: 4px;
            color: #6c7ae0;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .pagination a:hover {
            background: #f8f9ff;
            border-color: #6c7ae0;
        }
        .pagination .active a {
            background: #6c7ae0;
            color: white;
            border-color: #6c7ae0;
        }
        .pagination .disabled a {
            color: #999;
            pointer-events: none;
        }
        .btn-action {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.9em;
            margin: 0 2px;
        }
        .no-results {
            text-align: center;
            padding: 40px;
            color: #666;
            font-size: 1.1em;
        }
    </style>
</head>
<body>
    <div class="container-scroller">
        <?php include_once('includes/header.php');?>
        <div class="container-fluid page-body-wrapper">
            <?php include_once('includes/sidebar.php');?>
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="page-header">
                        <h3 class="page-title">Search Student</h3>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Search Student</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="row">
                        <div class="col-md-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <form method="post" class="search-form">
                                        <div class="form-group">
                                            <label for="searchdata"><strong>Search Student</strong></label>
                                            <input id="searchdata" type="text" name="searchdata" class="form-control search-input" 
                                                   value="<?php echo htmlspecialchars($sdata); ?>"
                                                   placeholder="Search by Student ID, Name, or Email" required>
                                        </div>
                                        <button type="submit" class="btn search-button" name="search">
                                            <i class="icon-magnifier"></i> Search
                                        </button>
                                    </form>

                                    <?php if($hasSearched): ?>
                                        <div class="mt-4">
                                            <h4>Results for "<?php echo htmlspecialchars($sdata); ?>"</h4>
                                            <?php if($total_rows > 0): ?>
                                                <p class="text-muted">Found <?php echo $total_rows; ?> matching records</p>
                                            <?php endif; ?>
                                        </div>

                                        <div class="table-responsive border rounded p-3 mt-3">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>S.No</th>
                                                        <th>Student ID</th>
                                                        <th>Class</th>
                                                        <th>Name</th>
                                                        <th>Email</th>
                                                        <th>Admission Date</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php 
                                                    if(!empty($searchResults)):
                                                        $cnt = $offset + 1;
                                                        foreach($searchResults as $row):
                                                    ?>
                                                    <tr>
                                                        <td><?php echo $cnt++; ?></td>
                                                        <td><?php echo htmlspecialchars($row->StudentId); ?></td>
                                                        <td><?php echo htmlspecialchars($row->StudentClass . ' ' . $row->Level); ?></td>
                                                        <td><?php echo htmlspecialchars($row->StudentName); ?></td>
                                                        <td><?php echo htmlspecialchars($row->StudentEmail); ?></td>
                                                        <td><?php echo htmlspecialchars($row->DateofAdmission); ?></td>
                                                        <td>
                                                            <a href="edit-student-detail.php?editid=<?php echo $row->sid; ?>" 
                                                               class="btn btn-info btn-action">
                                                                <i class="icon-pencil"></i>
                                                            </a>
                                                            <a href="manage-students.php?delid=<?php echo $row->sid; ?>" 
                                                               onclick="return confirm('Are you sure you want to delete this student?');" 
                                                               class="btn btn-danger btn-action">
                                                                <i class="icon-trash"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <?php 
                                                        endforeach;
                                                    else:
                                                    ?>
                                                    <tr>
                                                        <td colspan="7" class="no-results">
                                                            <i class="icon-info text-muted"></i>
                                                            <p>No records found matching your search criteria</p>
                                                        </td>
                                                    </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>

                                        <?php if($total_pages > 1): ?>
                                        <div class="pagination-wrapper">
                                            <ul class="pagination">
                                                <li class="<?php echo $pageno <= 1 ? 'disabled' : ''; ?>">
                                                    <a href="<?php echo $pageno <= 1 ? '#' : '?pageno=1&search=' . urlencode($sdata); ?>">First</a>
                                                </li>
                                                <li class="<?php echo $pageno <= 1 ? 'disabled' : ''; ?>">
                                                    <a href="<?php echo $pageno <= 1 ? '#' : '?pageno='.($pageno-1).'&search=' . urlencode($sdata); ?>">Prev</a>
                                                </li>
                                                <?php for($i = max(1, $pageno-2); $i <= min($total_pages, $pageno+2); $i++): ?>
                                                <li class="<?php echo $pageno == $i ? 'active' : ''; ?>">
                                                    <a href="?pageno=<?php echo $i; ?>&search=<?php echo urlencode($sdata); ?>"><?php echo $i; ?></a>
                                                </li>
                                                <?php endfor; ?>
                                                <li class="<?php echo $pageno >= $total_pages ? 'disabled' : ''; ?>">
                                                    <a href="<?php echo $pageno >= $total_pages ? '#' : '?pageno='.($pageno+1).'&search=' . urlencode($sdata); ?>">Next</a>
                                                </li>
                                                <li class="<?php echo $pageno >= $total_pages ? 'disabled' : ''; ?>">
                                                    <a href="<?php echo $pageno >= $total_pages ? '#' : '?pageno='.$total_pages.'&search=' . urlencode($sdata); ?>">Last</a>
                                                </li>
                                            </ul>
                                        </div>
                                        <?php endif; ?>
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
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
</body>
</html>
<?php } ?>