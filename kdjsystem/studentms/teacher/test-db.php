<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('../includes/dbconnection.php');

try {
    // Test 1: Check tblteacher structure
    echo "<h3>Test 1: tblteacher Structure</h3>";
    $sql = "DESCRIBE tblteacher";
    $query = $dbh->prepare($sql);
    $query->execute();
    echo "<pre>";
    print_r($query->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";

    // Test 2: Check tblteacherlogin structure
    echo "<h3>Test 2: tblteacherlogin Structure</h3>";
    $sql = "DESCRIBE tblteacherlogin";
    $query = $dbh->prepare($sql);
    $query->execute();
    echo "<pre>";
    print_r($query->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";

    // Test 3: Check existing teacher records
    echo "<h3>Test 3: Sample Teacher Records (without sensitive data)</h3>";
    $sql = "SELECT TeacherID, FirstName, LastName, Email, Status 
            FROM tblteacher 
            LIMIT 5";
    $query = $dbh->prepare($sql);
    $query->execute();
    echo "<pre>";
    print_r($query->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";

    // Test 4: Check login records (without showing passwords)
    echo "<h3>Test 4: Sample Login Records (without passwords)</h3>";
    $sql = "SELECT TeacherID, LastLogin, RememberToken, TokenExpiry 
            FROM tblteacherlogin 
            LIMIT 5";
    $query = $dbh->prepare($sql);
    $query->execute();
    echo "<pre>";
    print_r($query->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";

    // Test 5: Check for mismatched records
    echo "<h3>Test 5: Teachers without Login Records</h3>";
    $sql = "SELECT t.TeacherID, t.FirstName, t.LastName 
            FROM tblteacher t 
            LEFT JOIN tblteacherlogin tl ON t.TeacherID = tl.TeacherID 
            WHERE tl.TeacherID IS NULL";
    $query = $dbh->prepare($sql);
    $query->execute();
    echo "<pre>";
    print_r($query->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";

} catch (PDOException $e) {
    echo "<h3>Database Error:</h3>";
    echo "<pre>";
    echo "Error: " . $e->getMessage();
    echo "</pre>";
}
?>
