<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('../admin/includes/dbconnection.php');

if(isset($_SESSION['teacherloggedin'])) {
    header('location: dashboard.php');
    exit();
}

$error = '';
$success = '';

if(isset($_POST['login'])) {
    try {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        
        // Validate input
        if(empty($username) || empty($password)) {
            throw new Exception("Please fill in all fields");
        }
        
        // Check teacher credentials
        $sql = "SELECT ID, TeacherId, FullName, Password, UserImage 
                FROM tblteacher 
                WHERE UserName = :username";
        $query = $dbh->prepare($sql);
        $query->bindParam(':username', $username, PDO::PARAM_STR);
        $query->execute();
        
        if($query->rowCount() > 0) {
            $result = $query->fetch(PDO::FETCH_ASSOC);
            
            if(md5($password) === $result['Password']) {
                $_SESSION['teacherloggedin'] = true;
                $_SESSION['teacherid'] = $result['ID'];
                $_SESSION['teachername'] = $result['FullName'];
                $_SESSION['teachercode'] = $result['TeacherId'];
                
                header('location: dashboard.php');
                exit();
            } else {
                throw new Exception("Invalid password");
            }
        } else {
            throw new Exception("Invalid username");
        }
        
    } catch(Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Teacher Login</title>
    <link rel="stylesheet" href="../admin/vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="../admin/vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="../admin/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="../admin/css/style.css">
</head>
<body>
    <div class="container-scroller">
        <div class="container-fluid page-body-wrapper full-page-wrapper">
            <div class="content-wrapper d-flex align-items-center auth">
                <div class="row flex-grow">
                    <div class="col-lg-4 mx-auto">
                        <div class="auth-form-light text-left p-5">
                            <div class="brand-logo text-center">
                                <h3>KDJ School</h3>
                                <h5>Teacher Portal</h5>
                            </div>
                            
                            <?php if($error) { ?>
                                <div class="alert alert-danger alert-dismissible fade show">
                                    <?php echo $error; ?>
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                </div>
                            <?php } ?>
                            
                            <?php if($success) { ?>
                                <div class="alert alert-success alert-dismissible fade show">
                                    <?php echo $success; ?>
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                </div>
                            <?php } ?>
                            
                            <form class="pt-3" method="post">
                                <div class="form-group">
                                    <input type="text" class="form-control form-control-lg" 
                                           id="username" name="username" placeholder="Username"
                                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                           required>
                                </div>
                                <div class="form-group">
                                    <input type="password" class="form-control form-control-lg" 
                                           id="password" name="password" placeholder="Password"
                                           required>
                                </div>
                                <div class="mt-3">
                                    <button type="submit" name="login" 
                                            class="btn btn-block btn-primary btn-lg font-weight-medium auth-form-btn">
                                        SIGN IN
                                    </button>
                                </div>
                                <div class="text-center mt-4 font-weight-light">
                                    <a href="forgot-password.php" class="text-primary">Forgot Password?</a>
                                </div>
                                <div class="text-center mt-4 font-weight-light">
                                    <a href="../" class="text-primary">Back to Home</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../admin/vendors/js/vendor.bundle.base.js"></script>
    <script src="../admin/js/off-canvas.js"></script>
    <script src="../admin/js/misc.js"></script>
</body>
</html>
