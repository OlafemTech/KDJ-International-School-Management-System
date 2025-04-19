<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
    exit();
}

// Include backup functionality
require_once('includes/backup-database.php');

// Handle restore request
if (isset($_POST['restore']) && !empty($_POST['backup_file'])) {
    $backupFile = __DIR__ . '/backups/' . basename($_POST['backup_file']);
    if (file_exists($backupFile)) {
        try {
            // Read SQL file
            $sql = file_get_contents($backupFile);
            
            // Execute the SQL
            $dbh->exec($sql);
            $_SESSION['success'] = "Database restored successfully from backup!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error restoring backup: " . $e->getMessage();
        }
    }
}

// Get list of backup files
$backupDir = __DIR__ . '/backups/';
$backupFiles = [];
if (file_exists($backupDir)) {
    $backupFiles = array_diff(scandir($backupDir), array('.', '..'));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Student Management System | Database Backup</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container-scroller">
        <?php include_once('includes/header.php');?>
        <div class="container-fluid page-body-wrapper">
            <?php include_once('includes/sidebar.php');?>
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="page-header">
                        <h3 class="page-title">Database Backup Management</h3>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Database Backup</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="row">
                        <div class="col-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <?php if(isset($_SESSION['success'])): ?>
                                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                                            <?php 
                                            echo htmlentities($_SESSION['success']); 
                                            unset($_SESSION['success']);
                                            ?>
                                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                    <?php if(isset($_SESSION['error'])): ?>
                                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <?php 
                                            echo htmlentities($_SESSION['error']); 
                                            unset($_SESSION['error']);
                                            ?>
                                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Create Backup Button -->
                                    <form method="post" action="includes/backup-database.php" class="mb-4">
                                        <button type="submit" name="create_backup" class="btn btn-primary">
                                            Create New Backup
                                        </button>
                                    </form>

                                    <!-- Backup Files Table -->
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Backup File</th>
                                                    <th>Created Date</th>
                                                    <th>Size</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                if (!empty($backupFiles)):
                                                    foreach ($backupFiles as $file):
                                                        $filePath = $backupDir . $file;
                                                        if (is_file($filePath)):
                                                            $fileSize = filesize($filePath);
                                                            $fileDate = date("Y-m-d H:i:s", filemtime($filePath));
                                                ?>
                                                <tr>
                                                    <td><?php echo htmlentities($file); ?></td>
                                                    <td><?php echo $fileDate; ?></td>
                                                    <td><?php echo round($fileSize / 1024, 2); ?> KB</td>
                                                    <td>
                                                        <form method="post" style="display: inline;">
                                                            <input type="hidden" name="backup_file" value="<?php echo htmlentities($file); ?>">
                                                            <button type="submit" name="restore" class="btn btn-warning btn-sm" 
                                                                    onclick="return confirm('Are you sure you want to restore this backup? This will overwrite current data.');">
                                                                Restore
                                                            </button>
                                                        </form>
                                                        <a href="backups/<?php echo urlencode($file); ?>" 
                                                           class="btn btn-info btn-sm" 
                                                           download>
                                                            Download
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php 
                                                        endif;
                                                    endforeach;
                                                else:
                                                ?>
                                                <tr>
                                                    <td colspan="4" class="text-center">No backup files found</td>
                                                </tr>
                                                <?php endif; ?>
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
