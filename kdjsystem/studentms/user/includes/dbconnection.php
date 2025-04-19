<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
try {
    // Set up error handler to catch connection issues
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
    });

    // Database connection parameters
    $host = 'localhost';
    $dbname = 'studentmsdb';
    $username = 'root';
    $password = '';
    $charset = 'utf8mb4';

    // Create DSN
    $dsn = "mysql:host=$host;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    // First connect without database to create it if needed
    $pdo = new PDO($dsn, $username, $password, $options);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    $pdo->exec("USE `$dbname`");

    // Now connect with the database selected
    $dsn .= ";dbname=$dbname";
    $dbh = new PDO($dsn, $username, $password, $options);

    // Test the connection
    $stmt = $dbh->query('SELECT 1');
    if (!$stmt) {
        throw new Exception('Failed to execute test query');
    }

    // Restore default error handler
    restore_error_handler();

} catch (Exception $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        // AJAX request
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Database connection failed']);
    } else {
        // Normal request
        echo '<div style="padding: 20px; background-color: #f8d7da; color: #721c24; margin: 10px; border-radius: 5px;">';
        echo 'Database error occurred. Please check if MySQL is running and try again later.';
        echo '</div>';
    }
    exit();
}