<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize variables
$error_message = '';
$username = '';

// Clear any existing session
if (isset($_SESSION['sturecmsstuid'])) {
    session_destroy();
    session_start();
}

if(isset($_POST['login'])) {
    try {
        $error_message .= "Login attempt started\n";
        
        // Validate input
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $password = isset($_POST['password']) ? trim($_POST['password']) : '';
        
        $error_message .= "Form data - Username: " . $username . "\n";
        
        if(empty($username) || empty($password)) {
            throw new Exception("Username and password are required");
        }
        
        include(__DIR__ . '/includes/dbconnection.php');
        
        // Test database connection
        try {
            $dbh->query("SELECT 1");
            $error_message .= "Database connection test successful\n";
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
        
        // First get user data
        $sql = "SELECT s.*, c.* 
               FROM tblstudent s
               LEFT JOIN tblclass c ON c.ID = s.ClassID
               WHERE (s.StudentId = ? OR s.UserName = ? OR s.StudentEmail = ?) 
               AND s.Password = ?
               AND s.Status = 1";
        $query = $dbh->prepare($sql);
        
        // Hash the password before binding
        $hashedPassword = md5($password);
        $error_message .= "Input username: " . $username . "\n";
        $error_message .= "Input password hash: " . $hashedPassword . "\n";
        
        // Execute with parameters
        if (!$query->execute([$username, $username, $username, $hashedPassword])) {
            throw new Exception("Query failed: " . print_r($query->errorInfo(), true));
        }
        
        // Debug the query results
        $error_message .= "Query executed successfully\n";
        $error_message .= "Number of rows found: " . $query->rowCount() . "\n";
        
        if($query->rowCount() == 0) {
            // Let's check if the user exists without password check
            $sql2 = "SELECT StudentId, UserName, Password FROM tblstudent 
                    WHERE (StudentId = ? OR UserName = ? OR StudentEmail = ?) 
                    AND Status = 1";
            $query2 = $dbh->prepare($sql2);
            $query2->execute([$username, $username, $username]);
            $user2 = $query2->fetch(PDO::FETCH_ASSOC);
            
            if($user2) {
                $error_message .= "User found but password mismatch.\n";
                $error_message .= "Stored password hash: " . $user2['Password'] . "\n";
            } else {
                $error_message .= "No user found with this username.\n";
            }
            throw new Exception("Invalid username or password");
        }
        
        // Get user data
        $user = $query->fetch(PDO::FETCH_ASSOC);
        if(!$user) {
            throw new Exception("Failed to fetch user data");
        }
        
        if(!$user['ClassID']) {
            throw new Exception("No class found for student. Please contact administrator.");
        }
        
        $error_message .= "User found: " . $user['UserName'] . " (Class: " . $user['ClassName'] . " Level: " . $user['Level'] . ")\n";
        
        // Set up session with user data
        if($user) {
            
            // Set session variables
            $_SESSION['sturecmsstuid'] = $user['StudentId'];
            $_SESSION['sturecmsname'] = $user['StudentName'];
            $_SESSION['sturecmsemail'] = $user['StudentEmail'];
            $_SESSION['sturecmsclass'] = $user['ClassName'];
            $_SESSION['sturecmslevel'] = $user['Level'];
            $_SESSION['sturecmssession'] = $user['Session'];
            $_SESSION['sturecmsterm'] = $user['Term'];
            $_SESSION['sturecmsclassid'] = $user['ClassID'];
            
            $error_message .= "Session variables set successfully\n";
            
            // Log session data
            error_log('Session data after login: ' . print_r($_SESSION, true));
            
            // Log successful login
            error_log("User logged in successfully: " . $user['StudentId']);
            error_log("Session data: " . print_r($_SESSION, true));
            
            // Clear any output buffers
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Redirect to dashboard
            header("Location: dashboard.php");
            exit();
        } else {
            throw new Exception("Invalid username or password");
        }
    } catch(Exception $e) {
        $error_message .= "Error: " . $e->getMessage() . "\n";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>KDJ International School | Student Login</title>
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
        .debug-info {
            max-width: 500px;
            margin: 0 auto 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if(!empty($error_message)): ?>
        <div class="debug-info">
            <div class="alert alert-info mb-4">
                <h5>Debug Information:</h5>
                <pre><?php echo htmlspecialchars($error_message); ?></pre>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="login-container" data-aos="fade-up">
            <div class="login-header">
                <div class="school-logo">
                    <span>KDJ</span> International
                </div>
                <div class="login-title">Student Portal</div>
            </div>
            <div class="login-form">
                <form method="post" id="loginForm" action="">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="username" name="username" 
                               placeholder="Student ID/Username/Email" required 
                               value="<?php echo htmlspecialchars($username); ?>">
                        <label for="username"><i class="fas fa-user me-2"></i>Student ID/Username/Email</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="password" 
                               name="password" placeholder="Password" required>
                        <label for="password"><i class="fas fa-lock me-2"></i>Password</label>
                    </div>
                    <button type="submit" class="btn btn-login" name="login">
                        <i class="fas fa-sign-in-alt me-2"></i>Sign In
                    </button>
                    <div class="auth-links">
                        <a href="../index.php" class="auth-link">
                            <i class="fas fa-home me-1"></i>Back to Home
                        </a>
                        <a href="#" class="auth-link">
                            <i class="fas fa-question-circle me-1"></i>Need Help?
                        </a>
                    </div>
                </form>
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
