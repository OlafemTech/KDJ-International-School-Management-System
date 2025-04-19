<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('../includes/dbconnection.php');

try {
    $dbh->beginTransaction();

    // Get all teachers
    $sql = "SELECT ID, TeacherID, CONCAT(FirstName, ' ', LastName) as FullName FROM tblteacher";
    $query = $dbh->query($sql);
    $teachers = $query->fetchAll(PDO::FETCH_ASSOC);

    $defaultPassword = md5('teacher123');
    $added = 0;

    foreach($teachers as $teacher) {
        // Check if login already exists
        $checkSql = "SELECT COUNT(*) FROM tblteacherlogin WHERE TeacherID = :teacherid";
        $checkQuery = $dbh->prepare($checkSql);
        $checkQuery->bindParam(':teacherid', $teacher['TeacherID'], PDO::PARAM_STR);
        $checkQuery->execute();
        
        if ($checkQuery->fetchColumn() == 0) {
            // Add login credentials
            $insertSql = "INSERT INTO tblteacherlogin (TeacherID, Password) VALUES (:teacherid, :password)";
            $insertQuery = $dbh->prepare($insertSql);
            $insertQuery->bindParam(':teacherid', $teacher['TeacherID'], PDO::PARAM_STR);
            $insertQuery->bindParam(':password', $defaultPassword, PDO::PARAM_STR);
            $insertQuery->execute();
            $added++;
            
            echo "Added login credentials for teacher: " . $teacher['FullName'] . " (ID: " . $teacher['TeacherID'] . ")<br>";
        } else {
            echo "Teacher already has login credentials: " . $teacher['FullName'] . " (ID: " . $teacher['TeacherID'] . ")<br>";
        }
    }

    $dbh->commit();
    echo "<br>Successfully added login credentials for " . $added . " teachers.<br>";
    echo "Default password for all teachers is: teacher123<br>";
    echo "Please ask teachers to change their password upon first login.";

} catch(Exception $e) {
    if($dbh->inTransaction()) {
        $dbh->rollBack();
    }
    echo "Error: " . $e->getMessage() . "<br>";
    error_log("Error in setup-teacher-logins.php: " . $e->getMessage());
}
?>
