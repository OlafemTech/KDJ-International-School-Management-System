<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/dbconnection.php');

try {
    // Start transaction
    $dbh->beginTransaction();

    // Get all existing teachers
    $sql = "SELECT TeacherID FROM tblteacher WHERE TeacherID NOT IN (SELECT TeacherID FROM tblteacherlogin)";
    $query = $dbh->prepare($sql);
    $query->execute();
    $teachers = $query->fetchAll(PDO::FETCH_OBJ);

    $defaultPassword = md5('teacher123'); // Default password for all teachers
    $count = 0;

    foreach($teachers as $teacher) {
        // Insert into teacher login table
        $sql = "INSERT INTO tblteacherlogin (TeacherID, Password) VALUES (:teacherid, :password)";
        $query = $dbh->prepare($sql);
        $query->bindParam(':teacherid', $teacher->TeacherID, PDO::PARAM_STR);
        $query->bindParam(':password', $defaultPassword, PDO::PARAM_STR);
        $query->execute();
        $count++;
    }

    $dbh->commit();
    echo "Successfully added login credentials for " . $count . " teachers.<br>";
    echo "Default password for all teachers is: teacher123<br>";
    echo "Please ask teachers to change their password upon first login.";

} catch(Exception $e) {
    if($dbh->inTransaction()) {
        $dbh->rollBack();
    }
    echo "Error: " . $e->getMessage();
    error_log("Error in update-existing-teachers-login.php: " . $e->getMessage());
}
?>
