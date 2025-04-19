<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/dbconnection.php');

if(strlen($_SESSION['sturecmsaid'])==0) {
    header('location:logout.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id'])) {
    try {
        $student_id = intval($_POST['student_id']);
        
        // Validate student exists
        $stmt = $dbh->prepare("SELECT StudentId FROM tblstudent WHERE ID = ?");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch();
        
        if (!$student) {
            throw new Exception("Student not found");
        }

        if (!isset($_FILES['student_image']) || $_FILES['student_image']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("No image uploaded or upload error occurred");
        }

        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        if (!in_array($_FILES['student_image']['type'], $allowedTypes)) {
            throw new Exception('Only JPG, JPEG and PNG files are allowed');
        }

        // Validate file size (5MB max)
        $maxSize = 5 * 1024 * 1024;
        if ($_FILES['student_image']['size'] > $maxSize) {
            throw new Exception('Image must be less than 5MB');
        }

        // Create upload directory if it doesn't exist
        $uploadDir = "../uploads/student_images/";
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Generate unique filename using student ID
        $extension = pathinfo($_FILES['student_image']['name'], PATHINFO_EXTENSION);
        $filename = $student['StudentId'] . '_photo.' . $extension;
        $targetPath = $uploadDir . $filename;

        // Remove old image if exists
        $stmt = $dbh->prepare("SELECT Image FROM tblstudent WHERE ID = ?");
        $stmt->execute([$student_id]);
        $oldImage = $stmt->fetchColumn();
        
        if ($oldImage && file_exists($uploadDir . $oldImage)) {
            unlink($uploadDir . $oldImage);
        }

        // Move uploaded file
        if (!move_uploaded_file($_FILES['student_image']['tmp_name'], $targetPath)) {
            throw new Exception("Failed to save image");
        }

        // Update database
        $stmt = $dbh->prepare("UPDATE tblstudent SET Image = ? WHERE ID = ?");
        if (!$stmt->execute([$filename, $student_id])) {
            // If database update fails, remove uploaded file
            unlink($targetPath);
            throw new Exception("Failed to update student record");
        }

        // Redirect back to student view
        header("Location: view-student.php?viewid=" . $student_id);
        exit();

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: view-student.php?viewid=" . $student_id);
        exit();
    }
} else {
    header("Location: manage-students.php");
    exit();
}
?>
