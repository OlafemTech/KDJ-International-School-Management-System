<?php
session_start();
require_once('dbconnection.php');

// Verify user is logged in
if (!isset($_SESSION['sturecmsuid']) && !isset($_SESSION['sturecmsteacherId']) && !isset($_SESSION['sturecmsaid'])) {
    http_response_code(403);
    die('Access denied');
}

// Get file ID and verify it exists
if (!isset($_GET['file'])) {
    http_response_code(400);
    die('File ID not provided');
}

$fileId = intval($_GET['file']);

try {
    // Get file information
    $sql = "SELECT ha.*, h.ClassID, h.SubjectID, hs.StudentID
            FROM tblhomeworkattachments ha
            LEFT JOIN tblhomework h ON ha.HomeworkID = h.ID
            LEFT JOIN tblhomeworksubmissions hs ON ha.SubmissionID = hs.ID
            WHERE ha.ID = :fileId";
    $query = $dbh->prepare($sql);
    $query->bindParam(':fileId', $fileId, PDO::PARAM_INT);
    $query->execute();
    $file = $query->fetch(PDO::FETCH_OBJ);
    
    if (!$file) {
        http_response_code(404);
        die('File not found');
    }
    
    // Check access permissions
    $hasAccess = false;
    
    if (isset($_SESSION['sturecmsaid'])) {
        // Admin has access to all files
        $hasAccess = true;
    }
    else if (isset($_SESSION['sturecmsteacherId'])) {
        // Teacher needs to be assigned to the class/subject
        $teacherId = $_SESSION['sturecmsteacherId'];
        
        $sql = "SELECT 1 FROM tblsubjectteacherclass 
                WHERE TeacherID = :teacherId 
                AND ClassID = :classId 
                AND SubjectID = :subjectId";
        $query = $dbh->prepare($sql);
        $query->bindParam(':teacherId', $teacherId, PDO::PARAM_INT);
        $query->bindParam(':classId', $file->ClassID, PDO::PARAM_INT);
        $query->bindParam(':subjectId', $file->SubjectID, PDO::PARAM_INT);
        $query->execute();
        
        $hasAccess = $query->rowCount() > 0;
    }
    else if (isset($_SESSION['sturecmsuid'])) {
        // Student needs to be in the class and either:
        // 1. The file is from a homework assigned to their class
        // 2. The file is from their own submission
        $studentId = $_SESSION['sturecmsuid'];
        
        $sql = "SELECT s.StudentClass 
                FROM tblstudent s
                WHERE s.ID = :studentId";
        $query = $dbh->prepare($sql);
        $query->bindParam(':studentId', $studentId, PDO::PARAM_INT);
        $query->execute();
        $student = $query->fetch(PDO::FETCH_OBJ);
        
        if ($student && $student->StudentClass == $file->ClassID) {
            if ($file->HomeworkID) {
                // Homework file - student has access if in the class
                $hasAccess = true;
            } else if ($file->StudentID == $studentId) {
                // Submission file - student only has access to their own submissions
                $hasAccess = true;
            }
        }
    }
    
    if (!$hasAccess) {
        http_response_code(403);
        die('Access denied');
    }
    
    // Determine file path
    $basePath = __DIR__ . '/../teacher/homework-files/';
    if ($file->SubmissionID) {
        $basePath .= 'submissions/';
    }
    $filePath = $basePath . $file->FileName;
    
    // Check if file exists
    if (!file_exists($filePath)) {
        http_response_code(404);
        die('File not found on disk');
    }
    
    // Get file mime type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $filePath);
    finfo_close($finfo);
    
    // Set headers for download
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . basename($file->FileName) . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: private');
    
    // Output file
    readfile($filePath);
    exit;
    
} catch (Exception $e) {
    http_response_code(500);
    die('Server error: ' . $e->getMessage());
}
?>
