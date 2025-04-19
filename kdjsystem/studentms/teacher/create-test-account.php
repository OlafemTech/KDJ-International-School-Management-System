<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('../includes/dbconnection.php');

try {
    $dbh->beginTransaction();

    // Test teacher data
    $teacherData = [
        'TeacherID' => 'KDJ/TCH/TEST',
        'FirstName' => 'Test',
        'LastName' => 'Teacher',
        'Email' => 'test.teacher@kdj.edu',
        'MobileNumber' => '1234567890',
        'Gender' => 'Male',
        'Qualification' => 'M.Ed',
        'TeachingExperience' => 5,
        'SubjectSpecialization' => 'Mathematics',
        'JoiningDate' => date('Y-m-d'),
        'Status' => 'Active'
    ];

    // 1. Check if teacher exists
    $checkSql = "SELECT TeacherID FROM tblteacher WHERE TeacherID = :teacherid";
    $checkQuery = $dbh->prepare($checkSql);
    $checkQuery->bindParam(':teacherid', $teacherData['TeacherID']);
    $checkQuery->execute();

    if ($checkQuery->rowCount() == 0) {
        // 2. Create teacher record
        $teacherSql = "INSERT INTO tblteacher (TeacherID, FirstName, LastName, Email, MobileNumber, 
                                             Gender, Qualification, TeachingExperience, 
                                             SubjectSpecialization, JoiningDate, Status) 
                       VALUES (:teacherid, :firstname, :lastname, :email, :mobile,
                              :gender, :qualification, :experience, 
                              :specialization, :joining, :status)";
        
        $teacherQuery = $dbh->prepare($teacherSql);
        $teacherQuery->execute($teacherData);
        
        // 3. Create login record with default password
        $password = md5('test123'); // Default password as specified in the memory
        $loginSql = "INSERT INTO tblteacherlogin (TeacherID, Password, PasswordChangedAt, PasswordExpiresAt) 
                     VALUES (:teacherid, :password, CURRENT_TIMESTAMP, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 90 DAY))";
        
        $loginQuery = $dbh->prepare($loginSql);
        $loginQuery->bindParam(':teacherid', $teacherData['TeacherID']);
        $loginQuery->bindParam(':password', $password);
        $loginQuery->execute();
        
        $dbh->commit();
        
        echo "<h3>Test Account Created Successfully</h3>";
        echo "<p>Teacher ID: {$teacherData['TeacherID']}</p>";
        echo "<p>Password: test123</p>";
        echo "<p>Email: {$teacherData['Email']}</p>";
        echo "<p><a href='login.php'>Go to Login Page</a></p>";
    } else {
        echo "<h3>Test Account Already Exists</h3>";
        echo "<p>Teacher ID: {$teacherData['TeacherID']}</p>";
        echo "<p>Password: test123</p>";
        echo "<p><a href='login.php'>Go to Login Page</a></p>";
    }

} catch (Exception $e) {
    if ($dbh->inTransaction()) {
        $dbh->rollBack();
    }
    echo "<h3>Error:</h3>";
    echo "<pre>";
    echo "Error: " . $e->getMessage();
    echo "</pre>";
}
?>
