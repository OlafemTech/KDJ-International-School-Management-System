<?php
include('includes/header.php');

if(isset($_POST['submit'])) {
    try {
        $teacherid = $_SESSION['teacherid'];
        $currentPassword = md5($_POST['currentpassword']);
        $newPassword = md5($_POST['newpassword']);
        $confirmPassword = md5($_POST['confirmpassword']);

        // Verify current password
        $sql = "SELECT ID FROM tblteacher WHERE ID = :teacherid AND Password = :currentpassword";
        $query = $dbh->prepare($sql);
        $query->bindParam(':teacherid', $teacherid, PDO::PARAM_INT);
        $query->bindParam(':currentpassword', $currentPassword, PDO::PARAM_STR);
        $query->execute();

        if($query->rowCount() > 0) {
            if($newPassword !== $confirmPassword) {
                throw new Exception("New Password and Confirm Password do not match");
            }

            // Update password
            $updateSql = "UPDATE tblteacher SET Password = :newpassword WHERE ID = :teacherid";
            $updateQuery = $dbh->prepare($updateSql);
            $updateQuery->bindParam(':newpassword', $newPassword, PDO::PARAM_STR);
            $updateQuery->bindParam(':teacherid', $teacherid, PDO::PARAM_INT);
            $updateQuery->execute();

            $success = "Password changed successfully!";
        } else {
            throw new Exception("Current Password is incorrect");
        }
    } catch(Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Change Password</title>
    <link rel="stylesheet" href="../admin/vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="../admin/vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="../admin/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="../admin/css/style.css">
</head>
<body>
    <div class="container-scroller">
        <div class="container-fluid page-body-wrapper">
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="row">
                        <div class="col-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Change Password</h4>
                                    
                                    <?php if(isset($error)) { ?>
                                        <div class="alert alert-danger alert-dismissible fade show">
                                            <?php echo $error; ?>
                                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                                        </div>
                                    <?php } ?>
                                    
                                    <?php if(isset($success)) { ?>
                                        <div class="alert alert-success alert-dismissible fade show">
                                            <?php echo $success; ?>
                                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                                        </div>
                                    <?php } ?>

                                    <form class="forms-sample" method="post" onsubmit="return validateForm();">
                                        <div class="form-group">
                                            <label for="currentpassword">Current Password<span class="text-danger">*</span></label>
                                            <input type="password" name="currentpassword" class="form-control" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="newpassword">New Password<span class="text-danger">*</span></label>
                                            <input type="password" name="newpassword" class="form-control" id="newpassword" required>
                                            <small class="form-text text-muted">Password must be at least 6 characters long</small>
                                        </div>
                                        <div class="form-group">
                                            <label for="confirmpassword">Confirm Password<span class="text-danger">*</span></label>
                                            <input type="password" name="confirmpassword" class="form-control" id="confirmpassword" required>
                                        </div>
                                        <button type="submit" name="submit" class="btn btn-primary mr-2">Change Password</button>
                                        <a href="dashboard.php" class="btn btn-light">Cancel</a>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../admin/vendors/js/vendor.bundle.base.js"></script>
    <script src="../admin/js/off-canvas.js"></script>
    <script src="../admin/js/misc.js"></script>
    <script>
        function validateForm() {
            var newPassword = document.getElementById("newpassword").value;
            var confirmPassword = document.getElementById("confirmpassword").value;
            
            if(newPassword.length < 6) {
                alert("Password must be at least 6 characters long");
                return false;
            }
            
            if(newPassword !== confirmPassword) {
                alert("New Password and Confirm Password do not match");
                return false;
            }
            
            return true;
        }
    </script>
</body>
</html>
