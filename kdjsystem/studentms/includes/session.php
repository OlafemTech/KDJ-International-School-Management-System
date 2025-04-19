<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set session timeout to 30 minutes
define('SESSION_TIMEOUT', 1800); // 30 minutes in seconds

// Check if user is logged in and session timeout
function checkSession() {
    if (isset($_SESSION['sturecmsuid'])) {
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
            // Session has expired
            session_unset();
            session_destroy();
            header("Location: login.php?timeout=1");
            exit();
        }
        // Update last activity time stamp
        $_SESSION['last_activity'] = time();
    }
}

// Function to check student login status
function checkStudentLogin() {
    if (!isset($_SESSION['sturecmsuid'])) {
        header('location: login.php');
        exit();
    }
    checkSession();
}

// Function to check if account is active
function checkAccountStatus($dbh, $studentId) {
    try {
        $sql = "SELECT Status FROM tblstudent WHERE ID = :id";
        $query = $dbh->prepare($sql);
        $query->bindParam(':id', $studentId, PDO::PARAM_INT);
        $query->execute();
        
        if ($query->rowCount() > 0) {
            $result = $query->fetch(PDO::FETCH_OBJ);
            return $result->Status == 1;
        }
        return false;
    } catch (PDOException $e) {
        error_log("Error checking account status: " . $e->getMessage());
        return false;
    }
}

// Function to update last activity
function updateLastActivity($dbh, $studentId) {
    try {
        $sql = "UPDATE tblstudent SET LastActivity = CURRENT_TIMESTAMP WHERE ID = :id";
        $query = $dbh->prepare($sql);
        $query->bindParam(':id', $studentId, PDO::PARAM_INT);
        $query->execute();
    } catch (PDOException $e) {
        error_log("Error updating last activity: " . $e->getMessage());
    }
}
?>
