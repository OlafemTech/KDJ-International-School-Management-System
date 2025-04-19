 <?php 
if(session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(0);

if (!isset($dbh)) {
    include('includes/dbconnection.php');
}

if (strlen($_SESSION['sturecmsaid'])==0) {
    header('location:logout.php');
} else {
    $aid=$_SESSION['sturecmsaid'];
    $sql="SELECT * from tbladmin where ID=:aid";
    $query = $dbh->prepare($sql);
    $query->bindParam(':aid',$aid,PDO::PARAM_STR);
    $query->execute();
    $row = $query->fetch(PDO::FETCH_OBJ);
?>
<!-- Add MDI Icons -->
<link rel="stylesheet" href="https://cdn.materialdesignicons.com/6.5.95/css/materialdesignicons.min.css">
<div class="header-container">
    <div class="left-section">
        <span class="logo">
            <i class="mdi mdi-school"></i>
            KDJ SMS
        </span>
    </div>
    <div class="middle-section">
        <i class="mdi mdi-view-dashboard"></i>
        Administrator Dashboard
    </div>
    <div class="right-section">
        <div class="admin-dropdown">
            <div class="admin-info">
                <img src="<?php echo !empty($row->Avatar) ? 'images/avatars/'.$row->Avatar : 'images/avatars/default-avatar.png'; ?>" 
                     alt="Administrator" class="admin-avatar">
                <span><?php echo htmlentities($row->AdminName); ?></span>
                <i class="mdi mdi-chevron-down"></i>
            </div>
            <div class="dropdown-content">
                <div class="admin-details">
                    <img src="<?php echo !empty($row->Avatar) ? 'images/avatars/'.$row->Avatar : 'images/avatars/default-avatar.png'; ?>" 
                         alt="Administrator" class="admin-avatar-large">
                    <div class="admin-text">
                        <p class="admin-name"><?php echo htmlentities($row->AdminName); ?></p>
                        <p class="admin-email"><?php echo htmlentities($row->Email); ?></p>
                    </div>
                </div>
                <a href="profile.php">
                    <i class="mdi mdi-account"></i>
                    My Profile
                </a>
                <a href="change-password.php">
                    <i class="mdi mdi-cog"></i>
                    Settings
                </a>
                <a href="logout.php">
                    <i class="mdi mdi-logout"></i>
                    Sign Out
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.header-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 2rem;
    background: #000;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    color: #fff;
}

.left-section .logo {
    font-size: 1.5rem;
    font-weight: 600;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.left-section .logo i {
    font-size: 1.75rem;
    color: whitesmoke;
}

.middle-section {
    font-size: 1.1rem;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.middle-section i {
    font-size: 1.25rem;
}

.right-section {
    display: flex;
    align-items: center;
}

.admin-dropdown {
    position: relative;
    display: inline-block;
}

.admin-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem;
    cursor: pointer;
    border-radius: 4px;
    transition: background-color 0.3s;
}

.admin-info:hover {
    background-color: rgba(255,255,255,0.1);
}

.admin-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
}

.admin-avatar-large {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    object-fit: cover;
}

.dropdown-content {
    display: none;
    position: absolute;
    right: 0;
    min-width: 240px;
    background-color: #fff;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-radius: 4px;
    z-index: 1000;
}

.admin-dropdown:hover .dropdown-content {
    display: block;
}

.admin-details {
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    border-bottom: 1px solid #eee;
}

.admin-text {
    flex: 1;
}

.admin-name {
    font-weight: 600;
    margin: 0;
    color: #333;
}

.admin-email {
    font-size: 0.875rem;
    color: #666;
    margin: 0;
}

.dropdown-content a {
    color: #333;
    padding: 0.75rem 1rem;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: background-color 0.3s;
}

.dropdown-content a:hover {
    background-color: #f5f5f5;
}

.dropdown-content a i {
    font-size: 1.25rem;
    color: #666;
}

@media (max-width: 768px) {
    .header-container {
        padding: 1rem;
    }
    
    .middle-section {
        display: none;
    }
}
</style>
<?php } ?>