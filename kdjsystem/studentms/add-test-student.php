<?php
include('includes/dbconnection.php');

try {
    // Check if test student already exists
    $check = $dbh->prepare("SELECT COUNT(*) FROM tblstudent WHERE StudentId = 'SS/2024/001'");
    $check->execute();
    if($check->fetchColumn() > 0) {
        echo "Test student already exists.";
        exit();
    }

    $sql = "INSERT INTO tblstudent (
        StudentName, StudentEmail, StudentClass, Level, Gender, DOB,
        StudentId, FatherName, FatherOccupation, MotherName, MotherOccupation,
        ContactNumber, Address, UserName, Password, Session, Term
    ) VALUES (
        'John Doe',
        'john.doe@example.com',
        'SS',
        '1',
        'Male',
        '2010-01-01',
        'SS/2024/001',
        'James Doe',
        'Engineer',
        'Jane Doe',
        'Doctor',
        '08012345678',
        '123 School Road',
        'johndoe',
        :password,
        '2024/2025',
        '1st Term'
    )";

    $stmt = $dbh->prepare($sql);
    $password = md5('student123');
    $stmt->bindParam(':password', $password);
    $stmt->execute();
    
    echo "Test student created successfully!\n";
    echo "Login credentials:\n";
    echo "Username: johndoe\n";
    echo "Password: student123\n";
    echo "Student ID: SS/2024/001\n";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
