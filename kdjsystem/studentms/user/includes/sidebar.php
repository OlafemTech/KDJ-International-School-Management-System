<?php
if(session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('dbconnection.php');

$currentPage = basename($_SERVER['PHP_SELF']);

// Get student details
$stuid = $_SESSION['sturecmsstuid'];
$sql = "SELECT s.*, c.ClassName, c.Level, c.Session, c.Term 
        FROM tblstudent s 
        LEFT JOIN tblclass c ON s.StudentClass = c.ID 
        WHERE s.UserName = :stuid";
$query = $dbh->prepare($sql);
$query->bindParam(':stuid', $stuid, PDO::PARAM_STR);
$query->execute();
$student = $query->fetch(PDO::FETCH_OBJ);

// Get student's subjects
$classId = $student->StudentClass;
$sql = "SELECT s.SubjectName 
        FROM tblsubjectcombination sc 
        JOIN tblsubjects s ON sc.SubjectId = s.ID 
        WHERE sc.ClassId = :classId AND sc.Status = 1";
$query = $dbh->prepare($sql);
$query->bindParam(':classId', $classId, PDO::PARAM_INT);
$query->execute();
$subjects = $query->fetchAll(PDO::FETCH_OBJ);

// Get unread notices count
$sql = "SELECT COUNT(*) as count FROM tblnotice WHERE Status = 1";
$query = $dbh->prepare($sql);
$query->execute();
$noticeCount = $query->fetch(PDO::FETCH_OBJ)->count;
?>
<nav class="sidebar sidebar-offcanvas" id="sidebar">
    <div class="text-center sidebar-brand-wrapper d-flex align-items-center">
        <a class="sidebar-brand brand-logo" href="dashboard.php">
            <h3 style="color: white;">KDJ SMS</h3>
        </a>
    </div>
    <ul class="nav">
        <li class="nav-item nav-profile">
            <a href="#" class="nav-link">
                <div class="profile-image">
                    <img class="img-xs rounded-circle" 
                         src="<?php echo !empty($student->StudentImage) ? '../admin/images/studentimages/'.$student->StudentImage : '../admin/images/avatars/default-avatar.png'; ?>" 
                         alt="profile image">
                    <div class="dot-indicator bg-success"></div>
                </div>
                <div class="text-wrapper">
                    <p class="profile-name"><?php echo htmlentities($student->StudentName); ?></p>
                    <p class="designation">
                        <?php echo htmlentities($student->ClassName); ?> Level <?php echo htmlentities($student->Level); ?>
                        <br>
                        <?php echo htmlentities($student->Session) . " - " . htmlentities($student->Term); ?>
                    </p>
                </div>
            </a>
        </li>
        <li class="nav-item nav-category">
            <span class="nav-link">Main Menu</span>
        </li>
        <li class="nav-item <?php echo $currentPage == 'dashboard.php' ? 'active' : ''; ?>">
            <a class="nav-link" href="dashboard.php">
                <span class="menu-title">Dashboard</span>
                <i class="mdi mdi-view-dashboard menu-icon"></i>
            </a>
        </li>
        <li class="nav-item <?php echo $currentPage == 'profile.php' ? 'active' : ''; ?>">
            <a class="nav-link" href="profile.php">
                <span class="menu-title">My Profile</span>
                <i class="mdi mdi-account menu-icon"></i>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="collapse" href="#academic" aria-expanded="false" aria-controls="academic">
                <span class="menu-title">Academic</span>
                <i class="mdi mdi-book-open-page-variant menu-icon"></i>
                <i class="menu-arrow"></i>
            </a>
            <div class="collapse" id="academic">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item">
                        <a class="nav-link" href="subjects.php">
                            <i class="mdi mdi-book menu-icon"></i>
                            <span>My Subjects</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="homework.php">
                            <i class="mdi mdi-clipboard-text menu-icon"></i>
                            <span>Homework</span>
                        </a>
                    </li>
                </ul>
            </div>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="collapse" href="#results" aria-expanded="false" aria-controls="results">
                <span class="menu-title">Results</span>
                <i class="mdi mdi-chart-bar menu-icon"></i>
                <i class="menu-arrow"></i>
            </a>
            <div class="collapse" id="results">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item">
                        <a class="nav-link" href="view-result.php?term=<?php echo urlencode($student->Term); ?>">
                            <i class="mdi mdi-file-document menu-icon"></i>
                            <span>Current Term</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="result-history.php">
                            <i class="mdi mdi-history menu-icon"></i>
                            <span>Result History</span>
                        </a>
                    </li>
                </ul>
            </div>
        </li>
        <li class="nav-item <?php echo $currentPage == 'notices.php' ? 'active' : ''; ?>">
            <a class="nav-link" href="notices.php">
                <span class="menu-title">Notice Board</span>
                <i class="mdi mdi-bulletin-board menu-icon"></i>
                <?php
                // Get unread notice count
                $sql = "SELECT COUNT(*) as count FROM tblnotice WHERE Status = 1 AND PostingDate > COALESCE((SELECT LastNoticeView FROM tblstudent WHERE UserName = :stuid), '2000-01-01')";
                $query = $dbh->prepare($sql);
                $query->bindParam(':stuid', $stuid, PDO::PARAM_STR);
                $query->execute();
                $unreadCount = $query->fetch(PDO::FETCH_OBJ)->count;
                if($unreadCount > 0):
                ?>
                <span class="badge badge-pill badge-danger"><?php echo $unreadCount; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item <?php echo $currentPage == 'change-password.php' ? 'active' : ''; ?>">
            <a class="nav-link" href="change-password.php">
                <span class="menu-title">Change Password</span>
                <i class="mdi mdi-key-variant menu-icon"></i>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="logout.php">
                <span class="menu-title">Logout</span>
                <i class="mdi mdi-logout menu-icon"></i>
            </a>
        </li>
    </ul>
</nav>
<style>
    .sidebar {
        background-color: #1e1e2d;
        padding: 20px;
    }
    .sidebar .nav .nav-item .nav-link {
        padding: 10px 0;
        color: #a2a3b7;
        border-radius: 4px;
        -webkit-transition: all 0.4s ease;
        transition: all 0.4s ease;
    }
    .sidebar .nav .nav-item .nav-link:hover {
        color: #fff;
        background: rgba(255,255,255,0.1);
    }
    .sidebar .nav .nav-item.active .nav-link {
        color: #fff;
        background: rgba(255,255,255,0.1);
    }
    .sidebar .nav .nav-item .menu-icon {
        margin-right: 0;
        margin-left: auto;
        width: 30px;
        text-align: center;
        color: #a2a3b7;
    }
    .sidebar .nav .nav-item.active .menu-icon {
        color: #fff;
    }
    .sidebar .nav .nav-item .menu-arrow {
        margin-left: auto;
        -webkit-transition: all 0.4s ease;
        transition: all 0.4s ease;
    }
    .sidebar .nav .nav-item .menu-arrow.show {
        -webkit-transform: rotate(90deg);
        transform: rotate(90deg);
    }
    .sidebar .nav .nav-category {
        color: #fff;
        font-size: 0.875rem;
        font-weight: 500;
        padding: 10px 0px;
        text-transform: uppercase;
    }
    .sidebar .nav .nav-item .sub-menu {
        padding-left: 20px;
    }
    .sidebar .nav .nav-item .sub-menu .nav-link {
        padding: 8px 0;
        font-size: 0.875rem;
    }
    .sidebar .nav-profile {
        padding: 15px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        margin-bottom: 15px;
    }
    .sidebar .nav-profile .profile-image img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
    }
    .sidebar .nav-profile .text-wrapper {
        margin-left: 15px;
    }
    .sidebar .nav-profile .profile-name {
        font-size: 14px;
        font-weight: 500;
        margin-bottom: 5px;
        color: #fff;
    }
    .sidebar .nav-profile .designation {
        font-size: 12px;
        color: #a2a3b7;
    }
    .sidebar-brand-wrapper {
        padding: 0 20px;
        min-height: 60px;
        margin-bottom: 20px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    .badge-danger {
        background-color: #dc3545;
        color: #fff;
        padding: 0.25em 0.6em;
        font-size: 75%;
        font-weight: 700;
        border-radius: 10rem;
        margin-left: 5px;
    }
    .sidebar .nav .nav-item .collapse .nav.sub-menu .nav-item .nav-link.active {
        color: whitesmoke;
        background: #333;
    }
</style>