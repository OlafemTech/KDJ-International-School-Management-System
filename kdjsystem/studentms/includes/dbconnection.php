<?php
// First try to connect without database to create it if not exists
try {
    $pdo = new PDO("mysql:host=localhost", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS studentmsdb");
    
    // Now connect to the specific database
    $dsn = "mysql:host=localhost;dbname=studentmsdb";
    $options = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    );
    
    $dbh = new PDO($dsn, "root", "", $options);
    
    // Create tblstudent if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS `tblstudent` (
        `ID` int(11) NOT NULL AUTO_INCREMENT,
        `StudentName` varchar(200) NOT NULL,
        `StudentEmail` varchar(200) NOT NULL,
        `ClassID` int(11) NOT NULL,
        `Gender` varchar(10) NOT NULL,
        `DOB` date NOT NULL,
        `StudentId` varchar(100) NOT NULL,
        `FatherName` varchar(200) NOT NULL,
        `FatherOccupation` varchar(200) NOT NULL,
        `MotherName` varchar(200) NOT NULL,
        `MotherOccupation` varchar(200) NOT NULL,
        `ContactNumber` varchar(11) NOT NULL,
        `AlternateNumber` varchar(11) DEFAULT NULL,
        `Address` text NOT NULL,
        `Image` varchar(200) DEFAULT 'default.jpg',
        `UserName` varchar(120) NOT NULL,
        `Password` varchar(200) NOT NULL,
        `DateofAdmission` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`ID`),
        UNIQUE KEY `StudentEmail` (`StudentEmail`),
        UNIQUE KEY `StudentId` (`StudentId`),
        UNIQUE KEY `UserName` (`UserName`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $dbh->exec($sql);
    
    // Check if we have any test user, if not create one
    $stmt = $dbh->query("SELECT COUNT(*) FROM tblstudent");
    if($stmt->fetchColumn() == 0) {
        $sql = "INSERT INTO tblstudent (StudentName, StudentEmail, ClassID, Gender, DOB, StudentId, 
                FatherName, FatherOccupation, MotherName, MotherOccupation, ContactNumber, Address, UserName, Password) 
                VALUES 
                ('Test Student', 'test@student.com', 1, 'Male', '2005-01-01', 'KDJ230365', 
                'Father Name', 'Business', 'Mother Name', 'Teacher', '12345678901', 'Test Address', 'ummu558', 
                '" . md5('123456') . "')";
        $dbh->exec($sql);
    }
    
} catch(PDOException $e) {
    // Log the error with full details
    error_log("Database Error in " . __FILE__ . " at line " . __LINE__ . ": " . $e->getMessage());
    
    // Show user-friendly message
    die("<div style='padding: 20px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; margin: 10px; border-radius: 5px;'>
        <h3>Database Connection Error</h3>
        <p>Please check if:</p>
        <ul>
            <li>XAMPP is running</li>
            <li>MySQL service is started in XAMPP Control Panel</li>
            <li>Port 3306 is available and not blocked</li>
        </ul>
        <p><small>Error details: " . htmlspecialchars($e->getMessage()) . "</small></p>
        </div>");
}
?>