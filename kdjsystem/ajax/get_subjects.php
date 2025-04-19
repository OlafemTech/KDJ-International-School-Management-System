<?php
include('../includes/config.php');

if (isset($_GET['class_id'])) {
    $classId = intval($_GET['class_id']);
    
    $sql = "SELECT ID, SubjectName, SubjectCode FROM tblsubjects WHERE ClassID = ? ORDER BY SubjectName";
    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "i", $classId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    echo '<option value="">Select Subject</option>';
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<option value='{$row['ID']}'>{$row['SubjectName']} ({$row['SubjectCode']})</option>";
    }
} else {
    echo '<option value="">First Select Class</option>';
}
?>
