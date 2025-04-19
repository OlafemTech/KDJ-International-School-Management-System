<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('../includes/dbconnection.php');

try {
    // Check if database exists
    $sql = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = :dbname";
    $query = $dbh->prepare($sql);
    $query->bindParam(':dbname', DB_NAME, PDO::PARAM_STR);
    $query->execute();
    
    if ($query->rowCount() == 0) {
        die("Database does not exist!");
    }
    
    // Check student table
    $sql = "SELECT * FROM tblstudent";
    $query = $dbh->prepare($sql);
    $query->execute();
    
    echo "<h3>Student Records:</h3>";
    echo "<pre>";
    print_r($query->fetchAll(PDO::FETCH_OBJ));
    echo "</pre>";
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
