<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']==0)) {
  header('location:logout.php');
} else {
    try {
        // Step 1: Create sample classes if none exist
        $sql = "SELECT COUNT(*) as count FROM tblclass";
        $query = $dbh->prepare($sql);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] == 0) {
            // Sample class data
            $classes = array(
                array('SS-1 (2024/2025)', '1st Term'),
                array('SS-2 (2024/2025)', '1st Term'),
                array('JS-1 (2024/2025)', '1st Term'),
                array('JS-2 (2024/2025)', '1st Term'),
                array('Basic-1 (2024/2025)', '1st Term'),
                array('PG-PG (2024/2025)', '1st Term')
            );
            
            $sql = "INSERT INTO tblclass (ClassName, Section) VALUES (:className, :section)";
            $query = $dbh->prepare($sql);
            
            foreach ($classes as $class) {
                $query->bindParam(':className', $class[0], PDO::PARAM_STR);
                $query->bindParam(':section', $class[1], PDO::PARAM_STR);
                $query->execute();
            }
            
            $_SESSION['success'] = "Sample classes created successfully! ";
        }
        
        // Step 2: Generate test students
        header("Location: generate-test-students.php");
        exit();
        
    } catch (Exception $e) {
        error_log("Error in setup-test-data.php: " . $e->getMessage());
        $_SESSION['error'] = "Something went wrong while setting up test data. Please try again.";
        header("Location: manage-students.php");
        exit();
    }
}
?>
