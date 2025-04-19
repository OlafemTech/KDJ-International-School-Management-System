<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

$messages = [];
$success = true;

try {
    $dbh->beginTransaction();

    // Check admin account
    $adminCheck = $dbh->query("SELECT COUNT(*) FROM tbladmin")->fetchColumn();
    if ($adminCheck == 0) {
        // Create default admin
        $sql = "INSERT INTO tbladmin (AdminName, UserName, Email, Password) VALUES 
                ('Administrator', 'admin', 'admin@kdjschool.com', :password)";
        $stmt = $dbh->prepare($sql);
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt->execute([':password' => $password]);
        $messages[] = "✓ Created admin account (Username: admin, Password: admin123)";
    } else {
        $messages[] = "✓ Admin account exists";
    }

    // Sample class data
    $classes = [
        ['SS', '1', '2024/2025', 'First'],
        ['JS', '2', '2024/2025', 'First'],
        ['Basic', '3', '2024/2025', 'First'],
        ['Nursery', '1', '2024/2025', 'First'],
        ['PG', 'PG', '2024/2025', 'First']
    ];

    // Insert classes
    $stmt = $dbh->prepare("INSERT INTO tblclass (ClassName, Level, Session, Term) VALUES (?, ?, ?, ?)");
    foreach ($classes as $class) {
        $stmt->execute($class);
    }
    $messages[] = "✓ Added sample class records";

    // Sample teacher data
    $teachers = [
        [
            'John Smith', 'john.smith@example.com', '1234567890', 'Male', 'Single',
            '1985-01-01', '123 Main St', 'HND/Bsc', 'TCH001', '2024-01-01',
            'johnsmith', password_hash('password123', PASSWORD_DEFAULT), 'default.jpg'
        ],
        [
            'Jane Doe', 'jane.doe@example.com', '0987654321', 'Female', 'Married',
            '1990-01-01', '456 Oak St', 'Msc', 'TCH002', '2024-01-01',
            'janedoe', password_hash('password123', PASSWORD_DEFAULT), 'default.jpg'
        ]
    ];

    // Insert teachers
    $stmt = $dbh->prepare("INSERT INTO tblteacher (FullName, Email, MobileNumber, Gender, MaritalStatus, 
                          DateOfBirth, Address, Qualification, TeacherId, JoiningDate, UserName, Password, Image) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($teachers as $teacher) {
        $stmt->execute($teacher);
    }
    $messages[] = "✓ Added sample teacher records";

    // Sample subjects with proper class and teacher relationships
    $subjects = [
        // Format: [SubjectName, SubjectCode, ClassID, TeacherID]
        ['Mathematics', 'MATH101', 1, 1], // SS1 Math by John Smith
        ['English Language', 'ENG101', 1, 2], // SS1 English by Jane Doe
        ['Mathematics', 'MATH102', 2, 1], // JS2 Math by John Smith
        ['English Language', 'ENG102', 2, 2], // JS2 English by Jane Doe
        ['Basic Science', 'SCI101', 3, 1], // Basic3 Science by John Smith
        ['Basic English', 'ENG103', 3, 2]  // Basic3 English by Jane Doe
    ];

    // Insert subjects
    $stmt = $dbh->prepare("INSERT INTO tblsubjects (SubjectName, SubjectCode, ClassID, TeacherID) 
                          VALUES (?, ?, ?, ?)");
    foreach ($subjects as $subject) {
        $stmt->execute($subject);
    }
    $messages[] = "✓ Added sample subject records";

    $dbh->commit();
    $messages[] = "✓ Setup completed successfully!";
} catch (Exception $e) {
    $dbh->rollBack();
    $success = false;
    $messages[] = "✗ Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KDJ System Setup</title>
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .setup-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .message {
            padding: 10px;
            margin: 5px 0;
            border-radius: 4px;
        }
        .success { color: #155724; }
        .error { color: #721c24; }
    </style>
</head>
<body>
    <div class="container-scroller">
        <div class="setup-container">
            <h2 class="text-center mb-4">KDJ System Setup</h2>
            
            <?php foreach ($messages as $message): ?>
                <div class="message <?php echo $success ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endforeach; ?>

            <div class="text-center mt-4">
                <?php if ($success): ?>
                    <p class="text-success mb-3">Setup completed successfully!</p>
                    <a href="index.php" class="btn btn-primary">Go to Login</a>
                <?php else: ?>
                    <p class="text-danger mb-3">Setup encountered some errors.</p>
                    <a href="setup.php" class="btn btn-secondary">Try Again</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
