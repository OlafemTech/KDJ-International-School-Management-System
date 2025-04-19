<?php 
// DB credentials for studentmsdb
if (!defined('DB_HOST')) define('DB_HOST','localhost');
if (!defined('DB_USER')) define('DB_USER','root');
if (!defined('DB_PASS')) define('DB_PASS','');
if (!defined('DB_NAME')) define('DB_NAME','studentmsdb');

try {
    // Connect to studentmsdb as per memory requirements
    $dbh = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbh->exec("SET NAMES utf8");
} catch(PDOException $e) {
    // Log error for debugging
    error_log("Database connection error: " . $e->getMessage());
    
    // Show user-friendly error message with import instructions
    echo "<div style='margin:20px; padding:20px; border:1px solid red; background:#fee; color:#900;'>
        <h3>Database Connection Error</h3>
        <p>Please import the studentmsdb.sql file:</p>
        <ol>
            <li>Open phpMyAdmin (http://localhost/phpmyadmin)</li>
            <li>Create a new database named 'studentmsdb'</li>
            <li>Import the file 'studentmsdb.sql' from your project directory</li>
        </ol>
        <p><strong>Note:</strong> As per requirements, only use the studentmsdb.sql file.</p>
    </div>";
    exit();
}
?>