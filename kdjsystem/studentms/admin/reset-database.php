<?php
// Include database connection
include('../includes/dbconnection.php');

try {
    // Drop and recreate the database
    $pdo = new PDO("mysql:host=".DB_HOST, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Drop existing database if it exists
    $pdo->exec("DROP DATABASE IF EXISTS `".DB_NAME."`");
    echo "Dropped existing database...<br>";
    
    // Create fresh database
    $pdo->exec("CREATE DATABASE `".DB_NAME."` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Created fresh database...<br>";
    
    // Reconnect to the new database
    $dbh = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create all tables
    checkRequiredTables($dbh);
    echo "Created all required tables...<br>";
    
    // Insert sample admin account if none exists
    $sql = "SELECT COUNT(*) FROM tbladmin";
    $count = $dbh->query($sql)->fetchColumn();
    
    if($count == 0) {
        $sql = "INSERT INTO tbladmin (AdminName, UserName, MobileNumber, Email, Password) 
                VALUES ('Administrator', 'admin', '1234567890', 'admin@example.com', 
                        :password)";
        $query = $dbh->prepare($sql);
        $query->execute(['password' => md5('admin123')]);
        echo "Created default admin account (Username: admin, Password: admin123)<br>";
    }
    
    // Insert sample grade categories
    $categories = [
        ['Assignments', 30.00, 'Regular homework and take-home assignments'],
        ['Quizzes', 20.00, 'Short in-class assessments'],
        ['Midterm Exam', 20.00, 'Mid-semester comprehensive exam'],
        ['Final Exam', 30.00, 'End of semester comprehensive exam']
    ];
    
    $sql = "INSERT INTO tblgradecategories (CategoryName, Weight, Description) VALUES (?, ?, ?)";
    $stmt = $dbh->prepare($sql);
    
    foreach($categories as $category) {
        $stmt->execute($category);
    }
    echo "Created sample grade categories...<br>";
    
    // Insert sample class
    $sql = "INSERT INTO tblclass (ClassName, Section) VALUES ('Grade 10', 'A')";
    $dbh->exec($sql);
    $classId = $dbh->lastInsertId();
    echo "Created sample class...<br>";
    
    // Insert sample teacher
    $sql = "INSERT INTO tblteacher (TeacherID, FirstName, LastName, Email, MobileNumber, Gender, 
            DateOfBirth, Qualification, TeachingExperience, Status) 
            VALUES ('TCH001', 'John', 'Smith', 'john.smith@example.com', '1234567890', 
            'Male', '1980-01-01', 'M.Ed.', 10, 'Active')";
    $dbh->exec($sql);
    echo "Created sample teacher...<br>";
    
    // Insert sample subjects
    $subjects = [
        ['Mathematics', 'MATH101', 'Basic mathematics including algebra and geometry'],
        ['Science', 'SCI101', 'General science including physics and chemistry'],
        ['English', 'ENG101', 'English language and literature']
    ];
    
    $sql = "INSERT INTO tblsubjects (SubjectName, SubjectCode, Description) VALUES (?, ?, ?)";
    $stmt = $dbh->prepare($sql);
    
    foreach($subjects as $subject) {
        $stmt->execute($subject);
        $subjectId = $dbh->lastInsertId();
        
        // Assign subject to teacher and class
        $sql2 = "INSERT INTO tblsubjectteacherclass (SubjectID, TeacherID, ClassID, 
                AcademicYear, Semester) VALUES (?, 'TCH001', ?, '2024-2025', '1')";
        $stmt2 = $dbh->prepare($sql2);
        $stmt2->execute([$subjectId, $classId]);
    }
    echo "Created sample subjects and assigned to teacher...<br>";
    
    // Insert sample student
    $sql = "INSERT INTO tblstudent (StudentID, StudentName, StudentEmail, StudentClass, 
            Gender, DOB, StuID, FatherName, MotherName, ContactNumber, Address, UserName, Password) 
            VALUES ('STU001', 'Jane Doe', 'jane.doe@example.com', :classId, 'Female', 
            '2005-01-01', 'S2024001', 'John Doe', 'Mary Doe', '9876543210', 
            '123 Student St', 'student', :password)";
    $stmt = $dbh->prepare($sql);
    $stmt->execute([
        'classId' => $classId,
        'password' => md5('student123')
    ]);
    echo "Created sample student account (Username: student, Password: student123)<br>";
    
    // Insert sample grade items for each subject
    $sql = "SELECT SubjectID FROM tblsubjects";
    $subjects = $dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    
    foreach($subjects as $subjectId) {
        // Get category IDs
        $sql = "SELECT CategoryID FROM tblgradecategories";
        $categories = $dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);
        
        foreach($categories as $categoryId) {
            if($categoryId == 1) { // Assignments
                // Create multiple assignments
                for($i = 1; $i <= 3; $i++) {
                    $sql = "INSERT INTO tblgradeitems (CategoryID, SubjectID, ItemName, MaxScore, 
                            Weight, DueDate, Description) 
                            VALUES (:categoryId, :subjectId, :itemName, 100, 33.33, 
                            DATE_ADD(CURRENT_DATE, INTERVAL :days DAY), :description)";
                    $stmt = $dbh->prepare($sql);
                    $stmt->execute([
                        'categoryId' => $categoryId,
                        'subjectId' => $subjectId,
                        'itemName' => "Assignment $i",
                        'days' => $i * 7,
                        'description' => "Regular assignment $i"
                    ]);
                }
            } else {
                // Create single item for other categories
                $sql = "INSERT INTO tblgradeitems (CategoryID, SubjectID, ItemName, MaxScore, 
                        Weight, DueDate, Description) 
                        VALUES (:categoryId, :subjectId, :itemName, 100, 100, 
                        DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY), :description)";
                $stmt = $dbh->prepare($sql);
                $stmt->execute([
                    'categoryId' => $categoryId,
                    'subjectId' => $subjectId,
                    'itemName' => "Main Assessment",
                    'description' => "Main assessment for this category"
                ]);
            }
        }
    }
    echo "Created sample grade items...<br>";
    
    echo "<div class='alert alert-success'>Database reset complete! You can now <a href='login.php'>login</a> with:<br>
          Admin - Username: admin, Password: admin123<br>
          Student - Username: student, Password: student123</div>";
    
} catch(PDOException $e) {
    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
}
?>
