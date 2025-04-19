<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/header.php'); // This will handle session and DB connection

// Security check - only allow admin and teachers to view this page
if (!isset($_SESSION['teacherid']) && !isset($_SESSION['adminid'])) {
    header('location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>KDJ - Database Tables Check</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <?php
        try {
            // Check if tables exist and create if they don't
            $tables = [
                'tblteacher' => "
                    CREATE TABLE IF NOT EXISTS tblteacher (
                        TeacherID varchar(20) PRIMARY KEY,
                        FirstName varchar(50) NOT NULL,
                        LastName varchar(50) NOT NULL,
                        Email varchar(100) UNIQUE NOT NULL,
                        MobileNumber varchar(20),
                        Gender enum('Male', 'Female', 'Other'),
                        DateOfBirth date,
                        Address text,
                        Qualification text,
                        TeachingExperience int,
                        SubjectSpecialization text,
                        JoiningDate date,
                        PassportPhoto varchar(200),
                        Status enum('Active', 'Inactive') DEFAULT 'Active',
                        CreatedAt timestamp DEFAULT CURRENT_TIMESTAMP,
                        UpdatedAt timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    )",
                'tblteacherlogin' => "
                    CREATE TABLE IF NOT EXISTS tblteacherlogin (
                        TeacherID varchar(20) PRIMARY KEY,
                        Password varchar(255) NOT NULL,
                        RememberToken varchar(64),
                        TokenExpiry datetime,
                        LastLogin datetime,
                        LastLogout datetime,
                        FailedAttempts int DEFAULT 0,
                        LockoutUntil datetime,
                        PasswordChangedAt datetime,
                        PasswordExpiresAt datetime,
                        CreatedAt timestamp DEFAULT CURRENT_TIMESTAMP,
                        UpdatedAt timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (TeacherID) REFERENCES tblteacher(TeacherID) ON DELETE CASCADE
                    )"
            ];

            // Create tables if they don't exist
            foreach ($tables as $table => $sql) {
                try {
                    $dbh->exec($sql);
                    echo "<div class='alert alert-success'>Table $table exists or was created successfully.</div>";
                } catch (PDOException $e) {
                    echo "<div class='alert alert-danger'>Error creating table $table: " . htmlspecialchars($e->getMessage()) . "</div>";
                }
            }

            // Check tblteacher structure
            echo '<div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">Teacher Table Structure</h3>
                    </div>
                    <div class="card-body">';
            
            $sql = "DESCRIBE tblteacher";
            $query = $dbh->query($sql);
            $columns = $query->fetchAll(PDO::FETCH_ASSOC);
            echo '<table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Field</th>
                            <th>Type</th>
                            <th>Null</th>
                            <th>Key</th>
                            <th>Default</th>
                            <th>Extra</th>
                        </tr>
                    </thead>
                    <tbody>';
            foreach($columns as $column) {
                echo "<tr>";
                foreach($column as $key => $value) {
                    echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                }
                echo "</tr>";
            }
            echo '</tbody></table></div></div>';

            // Check tblteacherlogin structure
            echo '<div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">Teacher Login Table Structure</h3>
                    </div>
                    <div class="card-body">';
            
            $sql = "DESCRIBE tblteacherlogin";
            $query = $dbh->query($sql);
            $columns = $query->fetchAll(PDO::FETCH_ASSOC);
            echo '<table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Field</th>
                            <th>Type</th>
                            <th>Null</th>
                            <th>Key</th>
                            <th>Default</th>
                            <th>Extra</th>
                        </tr>
                    </thead>
                    <tbody>';
            foreach($columns as $column) {
                echo "<tr>";
                foreach($column as $key => $value) {
                    echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                }
                echo "</tr>";
            }
            echo '</tbody></table></div></div>';

            // Show sample teacher data (excluding sensitive info)
            echo '<div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">Sample Teacher Data</h3>
                    </div>
                    <div class="card-body">';
            
            $sql = "SELECT 
                        t.TeacherID,
                        t.FirstName,
                        t.LastName,
                        t.Email,
                        t.Status,
                        tl.LastLogin,
                        tl.LastLogout,
                        CASE 
                            WHEN tl.RememberToken IS NOT NULL THEN 'Yes'
                            ELSE 'No'
                        END as HasRememberToken,
                        CASE 
                            WHEN tl.TokenExpiry > CURRENT_TIMESTAMP THEN 'Valid'
                            WHEN tl.TokenExpiry IS NOT NULL THEN 'Expired'
                            ELSE 'N/A'
                        END as TokenStatus
                    FROM tblteacher t 
                    LEFT JOIN tblteacherlogin tl ON t.TeacherID = tl.TeacherID 
                    LIMIT 1";
            $query = $dbh->query($sql);
            $teacher = $query->fetch(PDO::FETCH_ASSOC);
            
            if ($teacher) {
                echo '<table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Field</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>';
                foreach($teacher as $key => $value) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($key) . "</td>";
                    echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                    echo "</tr>";
                }
                echo '</tbody></table>';
            } else {
                echo '<div class="alert alert-warning">No teacher records found</div>';
            }
            echo '</div></div>';

            // Check for required tables
            echo '<div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">Required Tables Check</h3>
                    </div>
                    <div class="card-body">';
            
            $required_tables = [
                'tblteacher' => 'Teacher Information',
                'tblteacherlogin' => 'Teacher Login Credentials',
                'tblsubjects' => 'Subject Information',
                'tblclass' => 'Class Information',
                'tblsubjectteacherclass' => 'Subject-Teacher-Class Mapping',
                'tblschedule' => 'Class Schedules',
                'tblstudent' => 'Student Information',
                'tblhomework' => 'Homework Assignments'
            ];

            echo '<table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Table Name</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Record Count</th>
                        </tr>
                    </thead>
                    <tbody>';
            
            foreach($required_tables as $table => $description) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($table) . "</td>";
                echo "<td>" . htmlspecialchars($description) . "</td>";
                
                try {
                    $sql = "SELECT COUNT(*) as count FROM $table";
                    $query = $dbh->query($sql);
                    $count = $query->fetch(PDO::FETCH_ASSOC)['count'];
                    echo "<td><span class='badge bg-success'>Exists</span></td>";
                    echo "<td>" . htmlspecialchars($count) . "</td>";
                } catch(Exception $e) {
                    echo "<td><span class='badge bg-danger'>Missing</span></td>";
                    echo "<td>N/A</td>";
                }
                echo "</tr>";
            }
            echo '</tbody></table></div></div>';

            // Check database indexes
            echo '<div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">Database Indexes</h3>
                    </div>
                    <div class="card-body">';
            
            $tables_to_check = ['tblteacher', 'tblteacherlogin'];
            foreach($tables_to_check as $table) {
                echo "<h5>Indexes for $table:</h5>";
                $sql = "SHOW INDEXES FROM $table";
                $query = $dbh->query($sql);
                $indexes = $query->fetchAll(PDO::FETCH_ASSOC);
                
                echo '<table class="table table-bordered mb-4">
                        <thead>
                            <tr>
                                <th>Key Name</th>
                                <th>Column</th>
                                <th>Type</th>
                                <th>Unique</th>
                            </tr>
                        </thead>
                        <tbody>';
                foreach($indexes as $index) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($index['Key_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($index['Column_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($index['Index_type']) . "</td>";
                    echo "<td>" . ($index['Non_unique'] ? 'No' : 'Yes') . "</td>";
                    echo "</tr>";
                }
                echo '</tbody></table>';
            }
            echo '</div></div>';

        } catch(Exception $e) {
            echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            error_log("Error in check-tables.php: " . $e->getMessage());
        }
        ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
