<?php
include('includes/header.php');

// Security check - only allow admin and teachers to view this page
if (!isset($_SESSION['teacherid']) && !isset($_SESSION['adminid'])) {
    header('location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>KDJ - Test Login System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Teacher Login System Test</h2>
        
        <?php
        try {
            // Test teacher table structure
            echo '<div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">Teacher Table Structure Test</h3>
                    </div>
                    <div class="card-body">';
            
            $sql = "DESCRIBE tblteacher";
            $query = $dbh->query($sql);
            $columns = $query->fetchAll(PDO::FETCH_ASSOC);
            
            echo '<h4>Required Fields:</h4>';
            $required_fields = [
                'TeacherID' => 'varchar(20) PRIMARY KEY',
                'FirstName' => 'varchar(50) NOT NULL',
                'LastName' => 'varchar(50) NOT NULL',
                'Email' => 'varchar(100) UNIQUE NOT NULL',
                'Status' => "enum('Active','Inactive')"
            ];
            
            $missing_fields = [];
            $existing_fields = array_column($columns, 'Type', 'Field');
            
            foreach ($required_fields as $field => $type) {
                if (!isset($existing_fields[$field])) {
                    $missing_fields[] = "$field ($type)";
                }
            }
            
            if (empty($missing_fields)) {
                echo '<div class="alert alert-success">All required teacher fields are present!</div>';
            } else {
                echo '<div class="alert alert-danger">Missing required fields: ' . implode(', ', $missing_fields) . '</div>';
            }
            echo '</div></div>';

            // Test teacher login table structure
            echo '<div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">Teacher Login Table Structure Test</h3>
                    </div>
                    <div class="card-body">';
            
            $sql = "DESCRIBE tblteacherlogin";
            $query = $dbh->query($sql);
            $columns = $query->fetchAll(PDO::FETCH_ASSOC);
            
            echo '<h4>Required Fields:</h4>';
            $required_fields = [
                'TeacherID' => 'varchar(20) PRIMARY KEY',
                'Password' => 'varchar(255) NOT NULL',
                'RememberToken' => 'varchar(64)',
                'TokenExpiry' => 'datetime',
                'LastLogin' => 'datetime',
                'FailedAttempts' => 'int'
            ];
            
            $missing_fields = [];
            $existing_fields = array_column($columns, 'Type', 'Field');
            
            foreach ($required_fields as $field => $type) {
                if (!isset($existing_fields[$field])) {
                    $missing_fields[] = "$field ($type)";
                }
            }
            
            if (empty($missing_fields)) {
                echo '<div class="alert alert-success">All required login fields are present!</div>';
            } else {
                echo '<div class="alert alert-danger">Missing required fields: ' . implode(', ', $missing_fields) . '</div>';
            }
            echo '</div></div>';

            // Test sample teacher data
            echo '<div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">Sample Teacher Data Test</h3>
                    </div>
                    <div class="card-body">';
            
            // Insert test teacher if none exists
            $test_teacher = [
                'TeacherID' => 'KDJ/TCH/TEST',
                'FirstName' => 'Test',
                'LastName' => 'Teacher',
                'Email' => 'test.teacher@kdj.edu',
                'Status' => 'Active'
            ];
            
            try {
                $sql = "INSERT IGNORE INTO tblteacher (TeacherID, FirstName, LastName, Email, Status) 
                        VALUES (:tid, :fname, :lname, :email, :status)";
                $query = $dbh->prepare($sql);
                $query->execute($test_teacher);
                
                if ($query->rowCount() > 0) {
                    echo '<div class="alert alert-info">Created test teacher account.</div>';
                }
            } catch (PDOException $e) {
                echo '<div class="alert alert-warning">Could not create test teacher: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            
            // Insert test login if none exists
            try {
                $sql = "INSERT IGNORE INTO tblteacherlogin (TeacherID, Password) 
                        VALUES (:tid, :pwd)";
                $query = $dbh->prepare($sql);
                $query->execute([
                    'tid' => $test_teacher['TeacherID'],
                    'pwd' => md5('test123')
                ]);
                
                if ($query->rowCount() > 0) {
                    echo '<div class="alert alert-info">Created test login credentials.</div>';
                }
            } catch (PDOException $e) {
                echo '<div class="alert alert-warning">Could not create test login: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            
            // Test login functionality
            echo '<h4>Test Login Credentials:</h4>';
            echo '<pre class="bg-light p-3">
Teacher ID: ' . htmlspecialchars($test_teacher['TeacherID']) . '
Password: test123</pre>';
            
            // Test remember token functionality
            echo '<h4>Remember Token Test:</h4>';
            try {
                $token = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
                
                $sql = "UPDATE tblteacherlogin 
                        SET RememberToken = :token,
                            TokenExpiry = :expiry
                        WHERE TeacherID = :tid";
                $query = $dbh->prepare($sql);
                $query->execute([
                    'token' => $token,
                    'expiry' => $expiry,
                    'tid' => $test_teacher['TeacherID']
                ]);
                
                if ($query->rowCount() > 0) {
                    echo '<div class="alert alert-success">Remember token test passed!</div>';
                } else {
                    echo '<div class="alert alert-warning">Could not update remember token.</div>';
                }
            } catch (PDOException $e) {
                echo '<div class="alert alert-danger">Remember token test failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            
            // Test failed attempts functionality
            echo '<h4>Failed Attempts Test:</h4>';
            try {
                $sql = "UPDATE tblteacherlogin 
                        SET FailedAttempts = 0,
                            LockoutUntil = NULL
                        WHERE TeacherID = :tid";
                $query = $dbh->prepare($sql);
                $query->execute(['tid' => $test_teacher['TeacherID']]);
                
                if ($query->rowCount() >= 0) {
                    echo '<div class="alert alert-success">Failed attempts reset successful!</div>';
                } else {
                    echo '<div class="alert alert-warning">Could not reset failed attempts.</div>';
                }
            } catch (PDOException $e) {
                echo '<div class="alert alert-danger">Failed attempts test failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            
            // Display current teacher status
            echo '<h4>Current Teacher Status:</h4>';
            try {
                $sql = "SELECT 
                            t.TeacherID,
                            t.FirstName,
                            t.LastName,
                            t.Email,
                            t.Status,
                            tl.LastLogin,
                            tl.LastLogout,
                            tl.FailedAttempts,
                            CASE 
                                WHEN tl.RememberToken IS NOT NULL THEN 'Yes'
                                ELSE 'No'
                            END as HasRememberToken,
                            CASE 
                                WHEN tl.TokenExpiry > CURRENT_TIMESTAMP THEN 'Valid'
                                WHEN tl.TokenExpiry IS NOT NULL THEN 'Expired'
                                ELSE 'N/A'
                            END as TokenStatus
                        FROM tblteacher t 
                        LEFT JOIN tblteacherlogin tl ON t.TeacherID = tl.TeacherID 
                        WHERE t.TeacherID = :tid";
                $query = $dbh->prepare($sql);
                $query->execute(['tid' => $test_teacher['TeacherID']]);
                $status = $query->fetch(PDO::FETCH_ASSOC);
                
                if ($status) {
                    echo '<table class="table table-bordered">
                            <tbody>';
                    foreach ($status as $key => $value) {
                        echo '<tr>
                                <th>' . htmlspecialchars($key) . '</th>
                                <td>' . htmlspecialchars($value ?? 'NULL') . '</td>
                            </tr>';
                    }
                    echo '</tbody></table>';
                } else {
                    echo '<div class="alert alert-warning">Could not retrieve teacher status.</div>';
                }
            } catch (PDOException $e) {
                echo '<div class="alert alert-danger">Status check failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            
        } catch(Exception $e) {
            echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            error_log("Error in test-login.php: " . $e->getMessage());
        }
        ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
