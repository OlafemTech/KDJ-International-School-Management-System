<?php
include('../includes/header.php');
include('../includes/dbconnection.php');

if (!isset($_SESSION['adminid'])) {
    header('location:logout.php');
    exit();
}

echo "<div style='padding: 20px;'>";
echo "<h2>Grading System Setup Status</h2>";

// Check tables
$tables = ['tblgradecategories', 'tblgradeitems', 'tblstudentgrades'];
foreach ($tables as $table) {
    $result = $dbh->query("SHOW TABLES LIKE '$table'");
    echo "<p>Table $table: " . ($result->rowCount() > 0 ? "✅ EXISTS" : "❌ NOT FOUND") . "</p>";
}

// Add initial grade categories if they don't exist
$categories = [
    ['Examinations', 40.00, 'Major examinations including midterms and finals'],
    ['Quizzes', 20.00, 'Regular class quizzes and assessments'],
    ['Assignments', 20.00, 'Take-home assignments and homework'],
    ['Class Participation', 10.00, 'Active participation in class discussions'],
    ['Projects', 10.00, 'Individual or group projects']
];

echo "<h3>Grade Categories:</h3>";
$sql = "SELECT * FROM tblgradecategories";
$result = $dbh->query($sql);
$existingCategories = $result->fetchAll(PDO::FETCH_ASSOC);

if (empty($existingCategories)) {
    echo "<p>Adding default grade categories...</p>";
    $sql = "INSERT INTO tblgradecategories (CategoryName, Weight, Description) VALUES (?, ?, ?)";
    $stmt = $dbh->prepare($sql);
    
    foreach ($categories as $category) {
        try {
            $stmt->execute($category);
            echo "<p>✅ Added category: {$category[0]} (Weight: {$category[1]}%)</p>";
        } catch (PDOException $e) {
            echo "<p>❌ Error adding {$category[0]}: " . htmlentities($e->getMessage()) . "</p>";
        }
    }
} else {
    echo "<p>Existing categories:</p>";
    echo "<table class='table table-bordered'>";
    echo "<thead><tr><th>Category</th><th>Weight</th><th>Description</th></tr></thead>";
    echo "<tbody>";
    foreach ($existingCategories as $category) {
        echo "<tr>";
        echo "<td>" . htmlentities($category['CategoryName']) . "</td>";
        echo "<td>" . htmlentities($category['Weight']) . "%</td>";
        echo "<td>" . htmlentities($category['Description']) . "</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
}

echo "<p><a href='manage-grade-categories.php' class='btn btn-primary'>Go to Grade Categories Management</a></p>";
echo "<p><a href='manage-grade-items.php' class='btn btn-primary'>Go to Grade Items Management</a></p>";
echo "<p><a href='manage-student-grades.php' class='btn btn-primary'>Go to Student Grades Management</a></p>";
echo "</div>";
?>
