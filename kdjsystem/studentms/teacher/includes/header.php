<?php
// Centralized session handling
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);
include('../../admin/includes/dbconnection.php');

// Security headers
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Validate teacher session
if(!isset($_SESSION['teacherloggedin'])) {
    header('location: ../index.php');
    exit();
}

// Get teacher information for the header
$teacherid = $_SESSION['teacherid'];
$sql = "SELECT * FROM tblteacher WHERE ID = :teacherid";
$query = $dbh->prepare($sql);
$query->bindParam(':teacherid', $teacherid, PDO::PARAM_INT);
$query->execute();
$teacher = $query->fetch(PDO::FETCH_ASSOC);

// If teacher not found, session is invalid
if(!$teacher) {
    session_destroy();
    header('location: ../index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>KDJ School - Teacher Portal</title>
    <link rel="stylesheet" href="../admin/vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="../admin/vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="../admin/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="../admin/css/style.css">
</head>
<body>
    <div class="container-scroller">
        <!-- Navbar -->
        <nav class="navbar default-layout-navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
            <div class="navbar-brand-wrapper d-flex align-items-center">
                <a class="navbar-brand brand-logo" href="dashboard.php">
                    <h4>KDJ School</h4>
                </a>
                <a class="navbar-brand brand-logo-mini" href="dashboard.php">
                    <h4>KDJ</h4>
                </a>
            </div>
            <div class="navbar-menu-wrapper d-flex align-items-center flex-grow-1">
                <h5 class="mb-0 font-weight-medium d-none d-lg-flex">Welcome to Teacher Portal!</h5>
                <ul class="navbar-nav navbar-nav-right ml-auto">
                    <li class="nav-item dropdown d-none d-xl-inline-flex user-dropdown">
                        <a class="nav-link dropdown-toggle" id="UserDropdown" href="#" data-toggle="dropdown" aria-expanded="false">
                            <img class="img-xs rounded-circle ml-2" 
                                 src="../admin/uploads/teachers/<?php echo htmlentities($teacher['UserImage']); ?>" 
                                 alt="Profile image">
                            <span class="font-weight-normal"><?php echo htmlentities($teacher['FullName']); ?></span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">
                            <div class="dropdown-header text-center">
                                <img class="img-md rounded-circle" 
                                     src="../admin/uploads/teachers/<?php echo htmlentities($teacher['UserImage']); ?>" 
                                     alt="Profile image">
                                <p class="mb-1 mt-3"><?php echo htmlentities($teacher['FullName']); ?></p>
                                <p class="font-weight-light text-muted mb-0"><?php echo htmlentities($teacher['TeacherId']); ?></p>
                            </div>
                            <a class="dropdown-item" href="update-profile.php">
                                <i class="dropdown-item-icon icon-user text-primary"></i> Update Profile
                            </a>
                            <a class="dropdown-item" href="change-password.php">
                                <i class="dropdown-item-icon icon-energy text-primary"></i> Change Password
                            </a>
                            <a class="dropdown-item" href="logout.php">
                                <i class="dropdown-item-icon icon-power text-primary"></i> Sign Out
                            </a>
                        </div>
                    </li>
                </ul>
                <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas">
                    <span class="icon-menu"></span>
                </button>
            </div>
        </nav>
