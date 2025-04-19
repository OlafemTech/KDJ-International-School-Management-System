<?php
// Get teacher information for sidebar
$teachid = $_SESSION['teacherid'];
$sql = "SELECT t.TeacherID, CONCAT(t.FirstName, ' ', t.LastName) as FullName, t.PassportPhoto, t.Status,
               tl.LastLogin, CASE WHEN tl.LastLogin IS NOT NULL THEN 'Active' ELSE 'Inactive' END as LoginStatus
        FROM tblteacher t 
        LEFT JOIN tblteacherlogin tl ON t.TeacherID = tl.TeacherID
        WHERE t.TeacherID = :teachid AND t.Status = 'Active'";
$query = $dbh->prepare($sql);
$query->bindParam(':teachid', $teachid, PDO::PARAM_STR);
$query->execute();
if($query->rowCount() > 0) {
  $teacher = $query->fetch(PDO::FETCH_OBJ);
} else {
  // Handle the case when no teacher is found or inactive
  $_SESSION['error'] = "Invalid or inactive teacher account. Please contact administrator.";
  header('location: logout.php');
  exit;
}
?>
<nav class="sidebar sidebar-offcanvas" id="sidebar">
  <ul class="nav">
    <li class="nav-item nav-profile">
      <a href="my-profile.php" class="nav-link">
        <div class="profile-image">
          <?php 
          if($teacher->PassportPhoto && file_exists("../admin/teacherphoto/" . $teacher->PassportPhoto)) {
          ?>
            <img class="img-xs rounded-circle" src="../admin/teacherphoto/<?php echo htmlentities($teacher->PassportPhoto);?>" alt="<?php echo htmlentities($teacher->FullName);?>'s profile photo">
          <?php } else { ?>
            <div class="img-xs rounded-circle bg-primary text-white text-center" style="line-height: 40px; font-size: 20px;">
              <?php echo strtoupper(substr($teacher->FullName, 0, 1));?>
            </div>
          <?php } ?>
          <div class="dot-indicator <?php echo $teacher->LoginStatus == 'Active' ? 'bg-success' : 'bg-warning';?>"></div>
        </div>
        <div class="text-wrapper">
          <p class="profile-name"><?php echo htmlentities($teacher->FullName);?></p>
          <p class="designation">
            <i class="icon-badge"></i> <?php echo htmlentities($teacher->TeacherID);?>
          </p>
        </div>
        <small class="last-login text-muted">
          <?php echo $teacher->LastLogin ? 'Last login: ' . date('M d, Y H:i', strtotime($teacher->LastLogin)) : 'First login';?>
        </small>
      </a>
    </li>
    <li class="nav-item nav-category">
      <i class="icon-grid menu-icon"></i> Main Menu
    </li>
    <li class="nav-item">
      <a class="nav-link" href="dashboard.php">
        <i class="icon-home menu-icon"></i>
        <span class="menu-title">Dashboard</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" data-bs-toggle="collapse" href="#subject-menu" aria-expanded="false" aria-controls="subject-menu">
        <i class="icon-graduation menu-icon"></i>
        <span class="menu-title">My Subjects</span>
        <i class="icon-arrow-down menu-arrow"></i>
      </a>
      <div class="collapse" id="subject-menu">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item">
            <a class="nav-link" href="assigned-subjects.php">
              <i class="icon-book-open"></i> Assigned Subjects
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="class-schedule.php">
              <i class="icon-calendar"></i> Class Schedule
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="student-list.php">
              <i class="icon-people"></i> Student Lists
            </a>
          </li>
        </ul>
      </div>
    </li>
    <li class="nav-item">
      <a class="nav-link" data-bs-toggle="collapse" href="#homework-menu" aria-expanded="false" aria-controls="homework-menu">
        <i class="icon-doc menu-icon"></i>
        <span class="menu-title">Assignments</span>
        <i class="icon-arrow-down menu-arrow"></i>
      </a>
      <div class="collapse" id="homework-menu">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item">
            <a class="nav-link" href="add-homework.php">
              <i class="icon-plus"></i> New Assignment
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="manage-homework.php">
              <i class="icon-list"></i> Manage Assignments
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="grade-assignments.php">
              <i class="icon-check"></i> Grade Submissions
            </a>
          </li>
        </ul>
      </div>
    </li>
    <li class="nav-item">
      <a class="nav-link" data-bs-toggle="collapse" href="#notice-menu" aria-expanded="false" aria-controls="notice-menu">
        <i class="icon-bell menu-icon"></i>
        <span class="menu-title">Notices</span>
        <i class="icon-arrow-down menu-arrow"></i>
      </a>
      <div class="collapse" id="notice-menu">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item">
            <a class="nav-link" href="add-notice.php">
              <i class="icon-plus"></i> Add Notice
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="manage-notices.php">
              <i class="icon-list"></i> Manage Notices
            </a>
          </li>
        </ul>
      </div>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="my-profile.php">
        <i class="icon-user menu-icon"></i>
        <span class="menu-title">My Profile</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="change-password.php">
        <i class="icon-lock menu-icon"></i>
        <span class="menu-title">Change Password</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link text-danger" href="logout.php">
        <i class="icon-logout menu-icon"></i>
        <span class="menu-title">Logout</span>
      </a>
    </li>
  </ul>
</nav>

<style>
/* Enhanced sidebar styles */
.sidebar {
    font-family: 'Simple-Line-Icons', 'FontAwesome', sans-serif;
}

.sidebar .nav-item {
    margin-bottom: 5px;
}

.sidebar .nav-link {
    padding: 12px 15px;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.sidebar .nav-link:hover {
    background-color: rgba(0,0,0,0.05);
}

.sidebar .menu-icon {
    margin-right: 10px;
    font-size: 18px;
    width: 20px;
    text-align: center;
}

.sidebar .menu-arrow {
    float: right;
    transition: transform 0.3s ease;
}

.sidebar .nav-link[aria-expanded="true"] .menu-arrow {
    transform: rotate(180deg);
}

.sidebar .sub-menu {
    padding-left: 35px;
}

.sidebar .sub-menu .nav-link {
    padding: 8px 15px;
    font-size: 0.9em;
}

.sidebar .sub-menu .icon-book-open,
.sidebar .sub-menu .icon-calendar,
.sidebar .sub-menu .icon-people,
.sidebar .sub-menu .icon-plus,
.sidebar .sub-menu .icon-list,
.sidebar .sub-menu .icon-check {
    margin-right: 8px;
    font-size: 14px;
}

.sidebar .nav-profile {
    padding: 15px;
    margin-bottom: 15px;
    border-bottom: 1px solid rgba(0,0,0,0.1);
}

.sidebar .nav-profile .profile-image {
    position: relative;
    margin-right: 15px;
}

.sidebar .nav-profile .img-xs {
    width: 40px;
    height: 40px;
    object-fit: cover;
}

.sidebar .nav-profile .dot-indicator {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.sidebar .nav-profile .text-wrapper {
    overflow: hidden;
}

.sidebar .nav-profile .profile-name {
    margin: 0;
    font-weight: 500;
    font-size: 15px;
    line-height: 1.2;
}

.sidebar .nav-profile .designation {
    margin: 0;
    font-size: 12px;
    color: #6c757d;
}

.sidebar .nav-profile .last-login {
    display: block;
    font-size: 11px;
    margin-top: 5px;
}

.sidebar .nav-category {
    font-size: 13px;
    font-weight: 500;
    text-transform: uppercase;
    color: #6c757d;
    margin: 15px 0 10px;
    padding: 0 15px;
}

.text-danger {
    color: #dc3545 !important;
}

/* Animation for menu collapse */
.collapse {
    transition: all 0.2s ease;
}

/* Responsive adjustments */
@media (max-width: 991px) {
    .sidebar {
        position: fixed;
        z-index: 999;
    }
    
    .sidebar-offcanvas {
        position: fixed;
        max-height: 100vh;
        top: 0;
        bottom: 0;
        overflow-y: auto;
        right: -260px;
        transition: all 0.25s ease-out;
    }
    
    .sidebar-offcanvas.active {
        right: 0;
    }
}
</style>
