<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('../includes/dbconnection.php');

if(isset($_POST['login'])) {
    try {
        $teacherid = trim($_POST['teacherid']); 
        $password = md5($_POST['password']);
        
        // Join with tblteacherlogin to verify credentials
        $sql = "SELECT t.TeacherID, t.FirstName, t.LastName, t.Email 
                FROM tblteacher t 
                INNER JOIN tblteacherlogin tl ON t.TeacherID = tl.TeacherID 
                WHERE t.TeacherID = :teacherid AND tl.Password = :password";
        $query = $dbh->prepare($sql);
        $query->bindParam(':teacherid', $teacherid, PDO::PARAM_STR);
        $query->bindParam(':password', $password, PDO::PARAM_STR);
        $query->execute();
        
        if($query->rowCount() > 0) {
            $result = $query->fetch(PDO::FETCH_OBJ);
            
            // Start session via header.php
            include('../includes/header.php');
            
            // Set session variables
            $_SESSION['teacherid'] = $result->TeacherID;
            $_SESSION['teachername'] = $result->FirstName . ' ' . $result->LastName;
            $_SESSION['teacheremail'] = $result->Email;

            // Update last login timestamp
            $updateSql = "UPDATE tblteacherlogin SET LastLogin = CURRENT_TIMESTAMP WHERE TeacherID = :teacherid";
            $updateQuery = $dbh->prepare($updateSql);
            $updateQuery->bindParam(':teacherid', $teacherid, PDO::PARAM_STR);
            $updateQuery->execute();

            if(!empty($_POST["remember"])) {
                // Set remember-me cookie with secure and httponly flags
                $token = bin2hex(random_bytes(32));
                $cookie_value = base64_encode($teacherid . ':' . $token);
                
                setcookie(
                    "teacher_remember",
                    $cookie_value,
                    [
                        'expires' => time() + (30 * 24 * 60 * 60),
                        'path' => '/',
                        'secure' => true,
                        'httponly' => true,
                        'samesite' => 'Strict'
                    ]
                );
                
                // Store remember token in database with expiry
                $tokenExpiry = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60));
                $updateToken = "UPDATE tblteacherlogin 
                               SET RememberToken = :token, 
                                   TokenExpiry = :expiry 
                               WHERE TeacherID = :teacherid";
                $tokenQuery = $dbh->prepare($updateToken);
                $tokenQuery->bindParam(':token', $token, PDO::PARAM_STR);
                $tokenQuery->bindParam(':expiry', $tokenExpiry, PDO::PARAM_STR);
                $tokenQuery->bindParam(':teacherid', $teacherid, PDO::PARAM_STR);
                $tokenQuery->execute();
            } else {
                if(isset($_COOKIE["teacher_remember"])) {
                    setcookie("teacher_remember", "", [
                        'expires' => time() - 3600,
                        'path' => '/',
                        'secure' => true,
                        'httponly' => true,
                        'samesite' => 'Strict'
                    ]);
                    
                    // Clear remember token
                    $clearToken = "UPDATE tblteacherlogin 
                                 SET RememberToken = NULL, 
                                     TokenExpiry = NULL 
                                 WHERE TeacherID = :teacherid";
                    $clearQuery = $dbh->prepare($clearToken);
                    $clearQuery->bindParam(':teacherid', $teacherid, PDO::PARAM_STR);
                    $clearQuery->execute();
                }
            }
            
            header('Location: dashboard.php');
            exit();
        } else {
            throw new Exception("Invalid Teacher ID or Password");
        }
    } catch (Exception $e) {
        $error_message = htmlspecialchars($e->getMessage());
    }
}

// Check for remember-me cookie
if(!isset($_SESSION['teacherid']) && isset($_COOKIE['teacher_remember'])) {
    try {
        list($cookie_teacherid, $cookie_token) = explode(':', base64_decode($_COOKIE['teacher_remember']));
        
        // Verify token and check expiry
        $sql = "SELECT t.TeacherID, t.FirstName, t.LastName, t.Email 
                FROM tblteacher t 
                INNER JOIN tblteacherlogin tl ON t.TeacherID = tl.TeacherID 
                WHERE t.TeacherID = :teacherid 
                AND tl.RememberToken = :token 
                AND tl.TokenExpiry > CURRENT_TIMESTAMP";
        $query = $dbh->prepare($sql);
        $query->bindParam(':teacherid', $cookie_teacherid, PDO::PARAM_STR);
        $query->bindParam(':token', $cookie_token, PDO::PARAM_STR);
        $query->execute();
        
        if($query->rowCount() > 0) {
            $result = $query->fetch(PDO::FETCH_OBJ);
            
            // Start session via header.php
            include('../includes/header.php');
            
            // Set session variables
            $_SESSION['teacherid'] = $result->TeacherID;
            $_SESSION['teachername'] = $result->FirstName . ' ' . $result->LastName;
            $_SESSION['teacheremail'] = $result->Email;
            
            header('Location: dashboard.php');
            exit();
        }
    } catch (Exception $e) {
        // Silent fail for cookie authentication
        error_log("Remember-me authentication failed: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>KDJ International School | Teacher Portal</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2196F3;
            --secondary-color: #333;
            --accent-color: #FFC107;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px;
        }
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            position: relative;
            max-width: 500px;
            width: 100%;
            margin: auto;
        }
        .login-header {
            text-align: center;
            padding: 40px 20px;
            background: linear-gradient(135deg, var(--primary-color), #1976D2);
            color: white;
        }
        .school-logo {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        .school-logo span {
            color: var(--accent-color);
        }
        .login-title {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        .login-form {
            padding: 40px;
        }
        .form-floating {
            margin-bottom: 1.5rem;
        }
        .form-floating .form-control {
            border: 2px solid #eee;
            border-radius: 10px;
            height: calc(3.5rem + 2px);
            padding: 1rem 0.75rem;
        }
        .form-floating .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(33, 150, 243, 0.25);
        }
        .form-floating label {
            padding: 1rem 0.75rem;
        }
        .btn-login {
            background: var(--primary-color);
            color: white;
            padding: 0.8rem;
            border-radius: 10px;
            width: 100%;
            font-weight: 500;
            margin-bottom: 1rem;
            transition: all 0.3s;
            border: none;
        }
        .btn-login:hover {
            background: #1976D2;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(33, 150, 243, 0.3);
        }
        .form-check {
            margin-bottom: 1rem;
        }
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .auth-links {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }
        .auth-link {
            color: var(--primary-color);
            text-decoration: none;
            transition: all 0.3s;
            font-weight: 500;
            font-size: 0.9rem;
        }
        .auth-link:hover {
            color: #1976D2;
            text-decoration: underline;
        }
        .login-footer {
            text-align: center;
            margin-top: 2rem;
            color: #666;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-header">
                <div class="school-logo">
                    <span>KDJ</span> International
                </div>
                <div class="login-title">Teacher Portal</div>
            </div>
            <div class="login-form">
                <?php if(isset($error_message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                <form method="post" id="login" name="login">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="teacherid" name="teacherid" placeholder="Teacher ID" 
                               value="<?php echo isset($_COOKIE['teacher_remember']) ? explode(':', base64_decode($_COOKIE['teacher_remember']))[0] : ''; ?>" required>
                        <label for="teacherid">Teacher ID</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                        <label for="password">Password</label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember" 
                               <?php echo isset($_COOKIE['teacher_remember']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="remember">
                            Remember me
                        </label>
                    </div>
                    <button type="submit" class="btn btn-login" name="login">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </button>
                    <div class="auth-links">
                        <a href="../index.php" class="auth-link">
                            <i class="fas fa-home me-1"></i>Back to Home
                        </a>
                        <a href="forgot-password.php" class="auth-link">
                            <i class="fas fa-key me-1"></i>Forgot Password?
                        </a>
                    </div>
                </form>
            </div>
            <div class="login-footer">
                <p>&copy; <?php echo date('Y'); ?> KDJ International School. All rights reserved.</p>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
