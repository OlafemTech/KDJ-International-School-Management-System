<?php
session_start();

// Clear all session data
$_SESSION = array();

// Delete the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), "", time() - 3600, "/");
}

// Destroy the session
session_destroy();

// Redirect to login page
header('location: ../index.php');
exit();
?>
