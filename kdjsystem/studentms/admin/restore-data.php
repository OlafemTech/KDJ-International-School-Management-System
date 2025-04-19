<?php
include('includes/dbconnection.php');

try {
    // Create fresh student table
    $sql = "CREATE TABLE IF NOT EXISTS tblstudent (
        ID int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        StudentName varchar(200) NOT NULL,
        StudentEmail varchar(200) NOT NULL,
        StudentClass varchar(100) NOT NULL,
        Gender varchar(50) NOT NULL,
        DOB date NOT NULL,
        StuID varchar(100) NOT NULL,
        FatherName varchar(200) NOT NULL,
        MotherName varchar(200) NOT NULL,
        ContactNumber varchar(20) NOT NULL,
        AltenateNumber varchar(20) NOT NULL,
        Address mediumtext NOT NULL,
        UserName varchar(200) NOT NULL,
        Password varchar(200) NOT NULL,
        Image varchar(200) DEFAULT 'default.jpg',
        DateofAdmission timestamp DEFAULT CURRENT_TIMESTAMP
    )";
    
    $dbh->exec($sql);
    
    // Insert student records one by one to avoid any issues
    $students = [
        ['jphj', 'jphjp@gmail.com', '4', 'Male', '2022-01-13', 'bbmb', 'ui-99', 'mmbmb', '5465454645', '4646546565', 'J-90B, Hariram Nagra New Delhi', 'khjkjh'],
        ['Kishore Sharma', 'kishore@gmail.com', '3', 'Male', '2019-01-05', '10A12345', 'Janak Sharma', 'Indra Devi', '7879879879', '7987979879', 'fjedh kjk', 'kishore'],
        ['Anshul', 'anshul@gmail.com', '2', 'Female', '1986-01-05', 'ui-990', 'Kailesj', 'jakmmm', '4646546546', '6546598798', 'jkjkljkjoujuoil', 'anshul'],
        ['John Doe', 'john@gmail.com', '1', 'Female', '2002-02-10', '10805121', 'Anuj Kumar', 'Garima Singh', '1234598741', '1234567899', 'New Delhi', 'john12'],
        ['Anuj Kumar Singh', 'akkr@gmail.com', '8', 'Male', '2001-05-30', '1080523', 'Rijendra Singh', 'Kamlesh Devi', '1472569630', '1236987450', 'New Delhi', 'anujk3'],
        ['Rahul Kumar', 'Rahul12@gmail.com', '1', 'Male', '2009-01-01', '12331255', 'Ajay Singh', 'Apporva Singh', '1231231230', '1234567890', 'Test Address', 'rahul1'],
        ['Thomas Abiodun', 'thomas@kdjschool.com', '1', 'Male', '2005-12-12', 'KDJ/25/0567', 'Stephen', 'Elizabeth', '8036303030', '9065821054', 'No 14 Ayegbami Area', 'KDJ/25']
    ];
    
    $sql = "INSERT INTO tblstudent (StudentName, StudentEmail, StudentClass, Gender, DOB, StuID, 
            FatherName, MotherName, ContactNumber, AltenateNumber, Address, UserName, Password) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, MD5('password123'))";
    
    $stmt = $dbh->prepare($sql);
    $restored = 0;
    
    foreach ($students as $student) {
        try {
            $stmt->execute($student);
            $restored++;
        } catch (PDOException $e) {
            echo "Error with student {$student[0]}: " . $e->getMessage() . "<br>";
            continue;
        }
    }
    
    echo "<div style='font-family: Arial, sans-serif; padding: 20px;'>";
    echo "<h2>Restoration Complete</h2>";
    echo "<p>Successfully restored $restored student records.</p>";
    echo "<p><a href='manage-students.php' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>View Students</a></p>";
    echo "</div>";
    
} catch(PDOException $e) {
    echo "<div style='font-family: Arial, sans-serif; padding: 20px; color: red;'>";
    echo "<h2>Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
