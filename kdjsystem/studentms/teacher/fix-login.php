<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('../includes/dbconnection.php');

try {
    // Start transaction
    $dbh->beginTransaction();
    
    // 1. Get all teachers without login records
    $sql = "SELECT t.TeacherID, t.FirstName, t.LastName, t.Email 
            FROM tblteacher t 
            LEFT JOIN tblteacherlogin tl ON t.TeacherID = tl.TeacherID 
            WHERE tl.TeacherID IS NULL AND t.Status = 'Active'";
    $query = $dbh->prepare($sql);
    $query->execute();
    $teachers = $query->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Creating Login Records for Teachers</h3>";
    
    if (count($teachers) > 0) {
        // 2. Create login records for teachers
        $insertSql = "INSERT INTO tblteacherlogin (TeacherID, Password, PasswordChangedAt, PasswordExpiresAt) 
                      VALUES (:teacherid, :password, CURRENT_TIMESTAMP, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 90 DAY))";
        $insertQuery = $dbh->prepare($insertSql);
        
        foreach ($teachers as $teacher) {
            // Default password: First 3 letters of first name + last 4 digits of TeacherID
            $defaultPass = strtolower(substr($teacher['FirstName'], 0, 3) . substr($teacher['TeacherID'], -4));
            $hashedPass = md5($defaultPass);
            
            $insertQuery->bindParam(':teacherid', $teacher['TeacherID']);
            $insertQuery->bindParam(':password', $hashedPass);
            $insertQuery->execute();
            
            echo "<p>Created login for {$teacher['FirstName']} {$teacher['LastName']} (ID: {$teacher['TeacherID']})<br>";
            echo "Default password: <strong>{$defaultPass}</strong></p>";
        }
        
        $dbh->commit();
        echo "<p style='color: green;'>Successfully created login records for " . count($teachers) . " teachers.</p>";
    } else {
        echo "<p>No teachers found without login records.</p>";
    }
    
    // 3. Display test credentials for verification
    echo "<h3>Test Login Credentials</h3>";
    $sql = "SELECT t.TeacherID, t.FirstName, t.LastName, t.Email, t.Status,
                   CASE WHEN tl.TeacherID IS NOT NULL THEN 'Yes' ELSE 'No' END as HasLoginAccess
            FROM tblteacher t
            LEFT JOIN tblteacherlogin tl ON t.TeacherID = tl.TeacherID
            WHERE t.Status = 'Active'
            LIMIT 5";
    $query = $dbh->prepare($sql);
    $query->execute();
    $testTeachers = $query->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($testTeachers) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Teacher ID</th><th>Name</th><th>Email</th><th>Status</th><th>Has Login</th></tr>";
        foreach ($testTeachers as $teacher) {
            echo "<tr>";
            echo "<td>{$teacher['TeacherID']}</td>";
            echo "<td>{$teacher['FirstName']} {$teacher['LastName']}</td>";
            echo "<td>{$teacher['Email']}</td>";
            echo "<td>{$teacher['Status']}</td>";
            echo "<td>{$teacher['HasLoginAccess']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($dbh->inTransaction()) {
        $dbh->rollBack();
    }
    echo "<h3>Error:</h3>";
    echo "<pre>";
    echo "Error: " . $e->getMessage();
    echo "</pre>";
}
?>
