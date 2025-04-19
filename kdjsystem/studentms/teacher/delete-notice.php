<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsteachid']==0)) {
    header('location:logout.php');
    exit();
}

if(isset($_GET['id'])) {
    $teacherId = $_SESSION['sturecmsteachid'];
    $noticeId = intval($_GET['id']);
    
    try {
        // Get notice details and verify teacher's authorization
        $sql = "SELECT n.*, stc.TeacherID, s.ID as SubjectID, c.ID as ClassID
                FROM tblnotice n
                JOIN tblsubjectteacherclass stc ON n.SubjectID = stc.SubjectID AND n.ClassID = stc.ClassID
                JOIN tblsubjects s ON n.SubjectID = s.ID
                JOIN tblclass c ON n.ClassID = c.ID
                WHERE n.ID = :noticeId AND stc.TeacherID = :teacherId";
        $query = $dbh->prepare($sql);
        $query->bindParam(':noticeId', $noticeId, PDO::PARAM_INT);
        $query->bindParam(':teacherId', $teacherId, PDO::PARAM_INT);
        $query->execute();
        $notice = $query->fetch(PDO::FETCH_OBJ);
        
        if($notice) {
            // Delete the notice
            $sql = "DELETE FROM tblnotice WHERE ID = :noticeId";
            $query = $dbh->prepare($sql);
            $query->bindParam(':noticeId', $noticeId, PDO::PARAM_INT);
            $query->execute();
            
            $_SESSION['success'] = "Notice deleted successfully";
            header("Location: subject-notices.php?subject=" . $notice->SubjectID . "&class=" . $notice->ClassID);
        } else {
            $_SESSION['error'] = "You are not authorized to delete this notice";
            header("Location: my-schedule.php");
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting notice: " . $e->getMessage();
        header("Location: my-schedule.php");
    }
} else {
    header("Location: my-schedule.php");
}
exit();
?>
