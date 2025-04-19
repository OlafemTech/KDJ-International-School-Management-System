<?php
session_start();
error_reporting(0);
include('../includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']) == 0) {
    echo json_encode(['error' => 'Session Expired']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['markingScript'])) {
    try {
        $gradeId = intval($_POST['gradeId']);
        $file = $_FILES['markingScript'];
        
        // Validate file
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Invalid file type. Only PDF, JPEG, and PNG files are allowed.');
        }
        
        if ($file['size'] > $maxSize) {
            throw new Exception('File is too large. Maximum size is 5MB.');
        }
        
        // Create upload directory if it doesn't exist
        $uploadDir = '../uploads/marking_scripts/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'marking_script_' . $gradeId . '_' . time() . '.' . $extension;
        $targetPath = $uploadDir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Update database
            $sql = "UPDATE tblgrades 
                   SET MarkingScript = :filename,
                       MarkingScriptUploadDate = CURRENT_TIMESTAMP 
                   WHERE ID = :gradeId";
            
            $query = $dbh->prepare($sql);
            $query->bindParam(':filename', $filename, PDO::PARAM_STR);
            $query->bindParam(':gradeId', $gradeId, PDO::PARAM_INT);
            
            if ($query->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Marking script uploaded successfully',
                    'filename' => $filename,
                    'uploadDate' => date('Y-m-d H:i:s')
                ]);
            } else {
                throw new Exception('Failed to update database.');
            }
        } else {
            throw new Exception('Failed to upload file.');
        }
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Invalid request']);
}
?>
