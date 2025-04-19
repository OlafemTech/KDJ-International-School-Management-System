<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('../includes/dbconnection.php');

if(isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = md5($_POST['password']);
    
    try {
        $sql = "SELECT ID, AdminName FROM tbladmin WHERE UserName=:username AND Password=:password";
        $query = $dbh->prepare($sql);
        $query->bindParam(':username', $username, PDO::PARAM_STR);
        $query->bindParam(':password', $password, PDO::PARAM_STR);
        $query->execute();
        
        if($query->rowCount() > 0) {
            $result = $query->fetch(PDO::FETCH_OBJ);
            $_SESSION['sturecmsaid'] = $result->ID;
            $_SESSION['admin_name'] = $result->AdminName;
            
            if(!empty($_POST["remember"])) {
                // Only store username in cookie, never store password
                setcookie("user_login", $username, time() + (10 * 365 * 24 * 60 * 60));
            } else {
                if(isset($_COOKIE["user_login"])) {
                    setcookie("user_login", "", time() - 3600);
                }
            }
            
            $_SESSION['login'] = $username;
            header('Location: dashboard.php');
            exit();
        } else {
            echo "<script>alert('Invalid username or password');</script>";
        }
    } catch(PDOException $e) {
        error_log("Login Error: " . $e->getMessage());
        echo "<script>alert('An error occurred. Please try again.');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>KDJ International School | Admin Login</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
    <style>
        :root {
            --primary-color: #E1009F;
            --secondary-color: #333;
            --accent-color: #FFD700;
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
            background: linear-gradient(135deg, var(--primary-color), #ff1493);
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
            box-shadow: 0 0 0 0.2rem rgba(225, 0, 159, 0.25);
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
            background: #c4008b;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(225, 0, 159, 0.3);
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
            color: #c4008b;
            text-decoration: underline;
        }
        .login-footer {
            text-align: center;
            margin-top: 2rem;
            color: #666;
            font-size: 0.9rem;
        }
        @media (max-width: 576px) {
            .login-container {
                margin: 0;
            }
            .login-form {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container" data-aos="fade-up">
            <div class="login-header">
                <div class="school-logo">
                    <span>KDJ</span> International
                </div>
                <div class="login-title">Administrative Portal</div>
            </div>
            <div class="login-form">
                <form method="post" id="login" name="login">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="username" placeholder="Username" name="username" required value="<?php echo isset($_COOKIE["user_login"]) ? htmlspecialchars($_COOKIE["user_login"]) : ''; ?>">
                        <label for="username"><i class="fas fa-user me-2"></i>Username</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="password" placeholder="Password" name="password" required>
                        <label for="password"><i class="fas fa-lock me-2"></i>Password</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember" <?php echo isset($_COOKIE["user_login"]) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    <button type="submit" class="btn btn-login" name="login">
                        <i class="fas fa-sign-in-alt me-2"></i>Sign In
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
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 1000,
            once: true
        });
    </script>
</body>
</html>