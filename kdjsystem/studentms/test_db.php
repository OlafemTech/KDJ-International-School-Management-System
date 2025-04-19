<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

try {
    // Test 1: Check if we can query the database
    echo "<h3>Test 1: Database Connection</h3>";
    $test = $dbh->query("SELECT 1");
    echo "✅ Database connection successful<br><br>";

    // Test 2: Check if tblstudent exists
    echo "<h3>Test 2: Table Check</h3>";
    $tables = $dbh->query("SHOW TABLES LIKE 'tblstudent'")->fetchAll();
    if (count($tables) > 0) {
        echo "✅ Table 'tblstudent' exists<br>";
        
        // Test 3: Show table structure
        echo "<h3>Test 3: Table Structure</h3>";
        $columns = $dbh->query("SHOW COLUMNS FROM tblstudent")->fetchAll();
        echo "<pre>";
        print_r($columns);
        echo "</pre><br>";

        // Test 4: Check if there are any records
        echo "<h3>Test 4: Record Count</h3>";
        $count = $dbh->query("SELECT COUNT(*) as count FROM tblstudent")->fetch();
        echo "Number of records: " . $count['count'] . "<br><br>";

        if ($count['count'] > 0) {
            // Test 5: Show sample record (without sensitive data)
            echo "<h3>Test 5: Sample Record</h3>";
            $sample = $dbh->query("SELECT ID, StudentName, StudentEmail, StudentId, UserName FROM tblstudent LIMIT 1")->fetch();
            echo "<pre>";
            print_r($sample);
            echo "</pre>";
        }
    } else {
        echo "❌ Table 'tblstudent' does not exist<br>";
    }

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
