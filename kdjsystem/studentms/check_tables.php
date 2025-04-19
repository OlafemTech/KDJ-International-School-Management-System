<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('admin/includes/dbconnection.php');

try {
    // Check and create tables as needed
    $dbh->exec("CREATE TABLE IF NOT EXISTS `tbladmin` (
        `ID` int(11) NOT NULL AUTO_INCREMENT,
        `AdminName` varchar(120) DEFAULT NULL,
        `UserName` varchar(120) DEFAULT NULL,
        `MobileNumber` bigint(10) DEFAULT NULL,
        `Email` varchar(200) DEFAULT NULL,
        `Password` varchar(200) DEFAULT NULL,
        `AdminRegdate` timestamp NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`ID`)
    )");
    
    $dbh->exec("CREATE TABLE IF NOT EXISTS `tblclass` (
        `ID` int(11) NOT NULL AUTO_INCREMENT,
        `ClassName` varchar(50) NOT NULL,
        `Level` varchar(10) NOT NULL,
        `Session` varchar(9) NOT NULL,
        `Term` varchar(20) NOT NULL,
        `CreationDate` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`ID`),
        UNIQUE KEY `class_unique` (`ClassName`,`Level`,`Session`,`Term`)
    )");
    
    $dbh->exec("CREATE TABLE IF NOT EXISTS `tblstudent` (
        `ID` int(11) NOT NULL AUTO_INCREMENT,
        `StudentName` varchar(200) NOT NULL,
        `StudentEmail` varchar(200) NOT NULL,
        `StudentClass` varchar(100) NOT NULL,
        `Gender` varchar(50) NOT NULL,
        `DOB` date NOT NULL,
        `StuID` varchar(100) NOT NULL,
        `FatherName` varchar(200) NOT NULL,
        `MotherName` varchar(200) NOT NULL,
        `ContactNumber` varchar(20) NOT NULL,
        `AltenateNumber` varchar(20) NOT NULL,
        `Address` mediumtext NOT NULL,
        `UserName` varchar(200) NOT NULL,
        `Password` varchar(200) NOT NULL,
        `Image` varchar(200) DEFAULT 'default.jpg',
        `DateofAdmission` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`ID`),
        UNIQUE KEY `StuID` (`StuID`),
        UNIQUE KEY `UserName` (`UserName`)
    )");

    // Add admin if not exists
    $adminCheck = $dbh->query("SELECT COUNT(*) FROM tbladmin")->fetchColumn();
    if ($adminCheck == 0) {
        $sql = "INSERT INTO tbladmin (AdminName, UserName, Email, Password) VALUES 
                ('Administrator', 'admin', 'admin@kdjschool.com', :password)";
        $stmt = $dbh->prepare($sql);
        $password = md5('admin123');
        $stmt->execute([':password' => $password]);
        echo "✓ Created admin account (Username: admin, Password: admin123)<br>";
    } else {
        echo "✓ Admin account exists<br>";
    }

    // Add sample classes if none exist
    $classCheck = $dbh->query("SELECT COUNT(*) FROM tblclass")->fetchColumn();
    if ($classCheck == 0) {
        $classes = [
            ['SS', '1', '2024/2025', '1st Term'],
            ['JS', '2', '2024/2025', '1st Term'],
            ['Basic', '3', '2024/2025', '1st Term'],
            ['Nursery', '1', '2024/2025', '1st Term'],
            ['PG', 'PG', '2024/2025', '1st Term']
        ];

        $sql = "INSERT INTO tblclass (ClassName, Level, Session, Term) VALUES (:class, :level, :session, :term)";
        $stmt = $dbh->prepare($sql);
        
        foreach ($classes as $class) {
            $stmt->execute([
                ':class' => $class[0],
                ':level' => $class[1],
                ':session' => $class[2],
                ':term' => $class[3]
            ]);
        }
        echo "✓ Added sample class records<br>";
    } else {
        echo "✓ Class records exist<br>";
    }

    // Add sample students if none exist
    $studentCheck = $dbh->query("SELECT COUNT(*) FROM tblstudent")->fetchColumn();
    if ($studentCheck == 0) {
        $students = [
            [
                'name' => 'John Smith',
                'email' => 'john.smith@student.kdj.com',
                'class' => '10',
                'gender' => 'Male',
                'dob' => '2008-05-15',
                'stuid' => 'KDJ2024001',
                'father' => 'Michael Smith',
                'mother' => 'Sarah Smith',
                'contact' => '1234567890',
                'alternate' => '0987654321',
                'address' => '123 Main St, City',
                'username' => 'john.smith',
                'password' => md5('student123')
            ],
            [
                'name' => 'Emma Johnson',
                'email' => 'emma.johnson@student.kdj.com',
                'class' => '9',
                'gender' => 'Female',
                'dob' => '2009-03-20',
                'stuid' => 'KDJ2024002',
                'father' => 'David Johnson',
                'mother' => 'Lisa Johnson',
                'contact' => '2345678901',
                'alternate' => '1098765432',
                'address' => '456 Oak Ave, Town',
                'username' => 'emma.johnson',
                'password' => md5('student123')
            ]
        ];
        
        $sql = "INSERT INTO tblstudent (StudentName, StudentEmail, StudentClass, Gender, DOB, 
                StuID, FatherName, MotherName, ContactNumber, AltenateNumber, Address, UserName, Password) 
                VALUES (:name, :email, :class, :gender, :dob, :stuid, :father, :mother, :contact, 
                :alternate, :address, :username, :password)";
        
        $stmt = $dbh->prepare($sql);
        $success = 0;
        
        foreach ($students as $student) {
            try {
                $stmt->execute([
                    ':name' => $student['name'],
                    ':email' => $student['email'],
                    ':class' => $student['class'],
                    ':gender' => $student['gender'],
                    ':dob' => $student['dob'],
                    ':stuid' => $student['stuid'],
                    ':father' => $student['father'],
                    ':mother' => $student['mother'],
                    ':contact' => $student['contact'],
                    ':alternate' => $student['alternate'],
                    ':address' => $student['address'],
                    ':username' => $student['username'],
                    ':password' => $student['password']
                ]);
                $success++;
            } catch (PDOException $e) {
                echo "Error adding student {$student['name']}: " . $e->getMessage() . "<br>";
            }
        }
        echo "✓ Added $success sample student records<br>";
    } else {
        echo "✓ Student records exist<br>";
    }

    echo "<br>✓ Database setup complete! <a href='admin/'>Click here to login</a>";

} catch(PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
