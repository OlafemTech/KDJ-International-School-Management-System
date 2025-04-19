<?php
session_start();
error_reporting(0);
include('../includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']) == 0) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Get session and term from POST
$session = isset($_POST['session']) ? trim($_POST['session']) : '';
$term = isset($_POST['term']) ? trim($_POST['term']) : '';

// Validate inputs
if (empty($session) || empty($term)) {
    echo json_encode(['error' => 'Session and Term are required']);
    exit();
}

try {
    // Get classes for the selected session and term
    $sql = "SELECT ID, ClassName, Level FROM tblclass 
            WHERE Session = :session AND Term = :term 
            ORDER BY ClassName, Level";
    
    $query = $dbh->prepare($sql);
    $query->bindParam(':session', $session, PDO::PARAM_STR);
    $query->bindParam(':term', $term, PDO::PARAM_STR);
    $query->execute();
    
    $classes = $query->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($classes);
    
} catch(PDOException $e) {
    error_log("Class Load Error: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to load classes']);
}
?>
