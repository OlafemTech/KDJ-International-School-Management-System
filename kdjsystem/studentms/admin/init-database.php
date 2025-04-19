<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('../includes/dbconnection.php');

// Only allow admin to run this script
if (!isset($_SESSION['sturecmsaid'])) {
    header('location:logout.php');
    exit();
}

try {
    // Begin transaction
    $dbh->beginTransaction();
    
    // Drop existing tables in reverse order of dependencies
    $tables = [
        'tblstudentgrades',
        'tblsubjects',
        'tblstudent',
        'tblclass'
    ];
    
    foreach ($tables as $table) {
        $dbh->exec("DROP TABLE IF EXISTS $table");
    }
    
    // Create tables in order of dependencies
    
    // 1. Class Table
    $dbh->exec("
        CREATE TABLE IF NOT EXISTS tblclass (
            ID int(11) NOT NULL AUTO_INCREMENT,
            ClassName varchar(100) NOT NULL,
            Section varchar(100) NOT NULL,
            PRIMARY KEY (ID)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // 2. Student Table
    $dbh->exec("
        CREATE TABLE IF NOT EXISTS tblstudent (
            ID int(11) AUTO_INCREMENT PRIMARY KEY,
            StudentId varchar(20) NOT NULL UNIQUE,
            StudentName varchar(200) DEFAULT NULL,
            StudentEmail varchar(200) DEFAULT NULL,
            StudentClass int(11) DEFAULT NULL,
            Gender varchar(50) DEFAULT NULL,
            DOB date DEFAULT NULL,
            FatherName varchar(200) DEFAULT NULL,
            MotherName varchar(200) DEFAULT NULL,
            ContactNumber bigint(10) DEFAULT NULL,
            AltenateNumber bigint(10) DEFAULT NULL,
            Address text DEFAULT NULL,
            UserName varchar(200) DEFAULT NULL,
            Password varchar(200) DEFAULT NULL,
            Image varchar(200) DEFAULT NULL,
            DateofAdmission timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (StudentClass) REFERENCES tblclass(ID) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // 3. Subjects Table
    $dbh->exec("
        CREATE TABLE IF NOT EXISTS tblsubjects (
            SubjectID int(11) NOT NULL AUTO_INCREMENT,
            SubjectName varchar(200) NOT NULL,
            SubjectCode varchar(100) NOT NULL UNIQUE,
            Description text,
            CreatedAt timestamp DEFAULT CURRENT_TIMESTAMP,
            UpdatedAt timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (SubjectID)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // 4. Student Grades Table
    $dbh->exec("
        CREATE TABLE IF NOT EXISTS tblstudentgrades (
            GradeID int(11) NOT NULL AUTO_INCREMENT,
            StudentID varchar(20) NOT NULL,
            SubjectID int(11) NOT NULL,
            Term int(11) NOT NULL,
            Session varchar(9) NOT NULL,
            Quiz decimal(5,2) DEFAULT NULL,
            Assignment decimal(5,2) DEFAULT NULL,
            ClassParticipation decimal(5,2) DEFAULT NULL,
            MidtermExam decimal(5,2) DEFAULT NULL,
            FinalExam decimal(5,2) DEFAULT NULL,
            CreatedAt timestamp DEFAULT CURRENT_TIMESTAMP,
            UpdatedAt timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (GradeID),
            UNIQUE KEY unique_grade (StudentID, SubjectID, Term, Session),
            KEY idx_student (StudentID),
            KEY idx_subject (SubjectID),
            CONSTRAINT fk_grade_student FOREIGN KEY (StudentID) REFERENCES tblstudent (StudentId) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT fk_grade_subject FOREIGN KEY (SubjectID) REFERENCES tblsubjects (SubjectID) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Insert sample data
    
    // 1. Sample Classes
    $classes = [
        ['JSS 1', 'A'],
        ['JSS 1', 'B'],
        ['JSS 2', 'A'],
        ['JSS 2', 'B'],
        ['JSS 3', 'A'],
        ['JSS 3', 'B']
    ];
    
    $stmt = $dbh->prepare("INSERT INTO tblclass (ClassName, Section) VALUES (?, ?)");
    foreach ($classes as $class) {
        $stmt->execute($class);
    }
    
    // 2. Sample Subjects
    $subjects = [
        ['Mathematics', 'MATH101', 'Basic mathematics and algebra'],
        ['English Language', 'ENG101', 'English grammar and composition'],
        ['Basic Science', 'SCI101', 'Introduction to science concepts'],
        ['Social Studies', 'SOC101', 'Study of society and social relationships'],
        ['Basic Technology', 'TECH101', 'Introduction to technology concepts']
    ];
    
    $stmt = $dbh->prepare("INSERT INTO tblsubjects (SubjectName, SubjectCode, Description) VALUES (?, ?, ?)");
    foreach ($subjects as $subject) {
        $stmt->execute($subject);
    }
    
    // Commit transaction
    $dbh->commit();
    
    echo "Database initialized successfully with sample data!";
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($dbh->inTransaction()) {
        $dbh->rollBack();
    }
    echo "Error: " . $e->getMessage();
}
?>
