<?php
session_start();
error_reporting(0);
include('../includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']) == 0) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Get parameters from POST
$classId = isset($_POST['classId']) ? intval($_POST['classId']) : 0;
$session = isset($_POST['session']) ? trim($_POST['session']) : '';
$term = isset($_POST['term']) ? trim($_POST['term']) : '';

// Validate inputs
if (empty($classId) || empty($session) || empty($term)) {
    echo json_encode(['error' => 'Class ID, Session, and Term are required']);
    exit();
}

try {
    // Get students from the selected class, session, and term
    $sql = "SELECT s.ID, s.StudentName 
            FROM tblstudent s 
            INNER JOIN tblclass c ON c.ID = :classId 
                AND c.Session = :session 
                AND c.Term = :term 
            WHERE s.StudentClass = c.ClassName 
            AND s.Level = c.Level 
            AND s.Session = :session 
            AND s.Term = :term 
            ORDER BY s.StudentName ASC";
    
    $query = $dbh->prepare($sql);
    $query->bindParam(':classId', $classId, PDO::PARAM_INT);
    $query->bindParam(':session', $session, PDO::PARAM_STR);
    $query->bindParam(':term', $term, PDO::PARAM_STR);
    $query->execute();
    
    $students = $query->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($students);
    
} catch(PDOException $e) {
    error_log("Student Load Error: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to load students']);
}
?>
