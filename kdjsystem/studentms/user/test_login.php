<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Connect to database
    $dbh = new PDO("mysql:host=localhost;dbname=studentmsdb", "root", "");
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Database connection successful!<br>";
    
    // Check if table exists
    $result = $dbh->query("SHOW TABLES LIKE 'tblstudent'");
    if($result->rowCount() > 0) {
        echo "Table tblstudent exists!<br>";
        
        // Get sample user
        $stmt = $dbh->query("SELECT StudentId, UserName, StudentEmail FROM tblstudent LIMIT 1");
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($user) {
            echo "Sample user credentials (for testing):<br>";
            echo "Student ID: " . $user['StudentId'] . "<br>";
            echo "Username: " . $user['UserName'] . "<br>";
            echo "Email: " . $user['StudentEmail'] . "<br>";
        } else {
            echo "No users found in the table.<br>";
        }
    } else {
        echo "Table tblstudent does not exist!<br>";
    }
    
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
