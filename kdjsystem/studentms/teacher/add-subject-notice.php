<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsteachid']==0)) {
    header('location:logout.php');
    exit();
}

if(isset($_POST['submit'])) {
    $teacherId = $_SESSION['sturecmsteachid'];
    $subjectId = intval($_POST['subjectId']);
    $classId = intval($_POST['classId']);
    $noticeTitle = $_POST['noticeTitle'];
    $noticeMessage = $_POST['noticeMessage'];
    $noticeDate = $_POST['noticeDate'];
    
    try {
        // Verify teacher has access to this subject and class
        $sql = "SELECT 1 FROM tblsubjectteacherclass 
                WHERE SubjectID = :subjectId 
                AND ClassID = :classId 
                AND TeacherID = :teacherId";
        $query = $dbh->prepare($sql);
        $query->bindParam(':subjectId', $subjectId, PDO::PARAM_INT);
        $query->bindParam(':classId', $classId, PDO::PARAM_INT);
        $query->bindParam(':teacherId', $teacherId, PDO::PARAM_INT);
        $query->execute();
        
        if($query->rowCount() > 0) {
            // Insert notice
            $sql = "INSERT INTO tblnotice (SubjectID, ClassID, NoticeTitle, NoticeMessage, NoticeDate) 
                    VALUES (:subjectId, :classId, :noticeTitle, :noticeMessage, :noticeDate)";
            $query = $dbh->prepare($sql);
            $query->bindParam(':subjectId', $subjectId, PDO::PARAM_INT);
            $query->bindParam(':classId', $classId, PDO::PARAM_INT);
            $query->bindParam(':noticeTitle', $noticeTitle, PDO::PARAM_STR);
            $query->bindParam(':noticeMessage', $noticeMessage, PDO::PARAM_STR);
            $query->bindParam(':noticeDate', $noticeDate, PDO::PARAM_STR);
            $query->execute();
            
            $_SESSION['success'] = "Notice added successfully";
        } else {
            $_SESSION['error'] = "You are not authorized to add notices for this subject and class";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error adding notice: " . $e->getMessage();
    }
    
    header("Location: subject-notices.php?subject=" . $subjectId . "&class=" . $classId);
    exit();
}
?>
