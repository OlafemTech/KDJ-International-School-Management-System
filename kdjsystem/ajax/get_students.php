<?php
include('../includes/config.php');

if (isset($_GET['class_id'])) {
    $classId = intval($_GET['class_id']);
    
    $sql = "SELECT s.ID, s.FirstName, s.LastName, s.AdmissionNumber 
            FROM tblstudents s 
            WHERE s.ClassID = ? 
            ORDER BY s.LastName, s.FirstName";
    
    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "i", $classId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    echo '<option value="">Select Student</option>';
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<option value='{$row['ID']}'>{$row['LastName']}, {$row['FirstName']} ({$row['AdmissionNumber']})</option>";
    }
} else {
    echo '<option value="">First Select Class</option>';
}
?>
