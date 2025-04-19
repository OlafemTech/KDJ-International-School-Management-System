<?php
function handleFileUpload($file, $homeworkId = null, $submissionId = null, $allowedTypes = ['pdf', 'doc', 'docx', 'txt', 'zip', 'rar']) {
    global $dbh;
    
    try {
        // Validate file
        if($file['error'] !== 0) {
            throw new Exception('File upload error: ' . $file['error']);
        }
        
        // Get file extension and check if allowed
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if(!in_array($ext, $allowedTypes)) {
            throw new Exception('Invalid file type. Allowed types: ' . implode(', ', $allowedTypes));
        }
        
        // Generate unique filename
        $timestamp = time();
        $newFileName = $timestamp . '_' . ($homeworkId ?? $submissionId) . '_' . basename($file['name']);
        
        // Determine target directory
        if($homeworkId) {
            $targetDir = __DIR__ . '/../teacher/homework-files/';
        } else {
            $targetDir = __DIR__ . '/../teacher/homework-files/submissions/';
        }
        
        // Create directory if it doesn't exist
        if(!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        // Move uploaded file
        $targetPath = $targetDir . $newFileName;
        if(!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception('Failed to move uploaded file');
        }
        
        // Insert file metadata into database
        $sql = "INSERT INTO tblhomeworkattachments 
                (HomeworkID, SubmissionID, FileName, FileType, FileSize)
                VALUES (:homeworkId, :submissionId, :fileName, :fileType, :fileSize)";
        $query = $dbh->prepare($sql);
        $query->bindParam(':homeworkId', $homeworkId, PDO::PARAM_INT);
        $query->bindParam(':submissionId', $submissionId, PDO::PARAM_INT);
        $query->bindParam(':fileName', $newFileName, PDO::PARAM_STR);
        $query->bindParam(':fileType', $ext, PDO::PARAM_STR);
        $query->bindParam(':fileSize', $file['size'], PDO::PARAM_INT);
        $query->execute();
        
        return [
            'success' => true,
            'fileName' => $newFileName,
            'fileId' => $dbh->lastInsertId()
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

function deleteFile($fileId) {
    global $dbh;
    
    try {
        // Get file info
        $sql = "SELECT * FROM tblhomeworkattachments WHERE ID = :fileId";
        $query = $dbh->prepare($sql);
        $query->bindParam(':fileId', $fileId, PDO::PARAM_INT);
        $query->execute();
        $file = $query->fetch(PDO::FETCH_OBJ);
        
        if(!$file) {
            throw new Exception('File not found');
        }
        
        // Determine file path
        $basePath = __DIR__ . '/../teacher/homework-files/';
        if($file->SubmissionID) {
            $basePath .= 'submissions/';
        }
        $filePath = $basePath . $file->FileName;
        
        // Delete file from disk if it exists
        if(file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Delete from database
        $sql = "DELETE FROM tblhomeworkattachments WHERE ID = :fileId";
        $query = $dbh->prepare($sql);
        $query->bindParam(':fileId', $fileId, PDO::PARAM_INT);
        $query->execute();
        
        return [
            'success' => true
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

function getFileInfo($fileId) {
    global $dbh;
    
    try {
        $sql = "SELECT * FROM tblhomeworkattachments WHERE ID = :fileId";
        $query = $dbh->prepare($sql);
        $query->bindParam(':fileId', $fileId, PDO::PARAM_INT);
        $query->execute();
        $file = $query->fetch(PDO::FETCH_OBJ);
        
        if(!$file) {
            throw new Exception('File not found');
        }
        
        // Add download URL
        $file->downloadUrl = $file->SubmissionID ? 
            'homework-files/submissions/' . $file->FileName :
            'homework-files/' . $file->FileName;
        
        return [
            'success' => true,
            'file' => $file
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Example usage:
/*
// Upload a homework file
if(isset($_FILES['homework_file'])) {
    $result = handleFileUpload($_FILES['homework_file'], $homeworkId);
    if($result['success']) {
        $fileName = $result['fileName'];
        // Use $fileName in your application
    } else {
        $error = $result['error'];
        // Handle error
    }
}

// Upload a submission file
if(isset($_FILES['submission_file'])) {
    $result = handleFileUpload($_FILES['submission_file'], null, $submissionId);
    if($result['success']) {
        $fileName = $result['fileName'];
        // Use $fileName in your application
    } else {
        $error = $result['error'];
        // Handle error
    }
}

// Delete a file
$result = deleteFile($fileId);
if(!$result['success']) {
    $error = $result['error'];
    // Handle error
}

// Get file info
$result = getFileInfo($fileId);
if($result['success']) {
    $file = $result['file'];
    // Use file info
} else {
    $error = $result['error'];
    // Handle error
}
*/
?>
