<?php
session_start();
include('../includes/dbconnection.php');

// Check if admin is logged in
if (!isset($_SESSION['sturecmsaid'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $stats = [];
    
    // Get total classes
    $sql1 = "SELECT COUNT(*) as total FROM tblclass";
    $query1 = $dbh->prepare($sql1);
    $query1->execute();
    $stats['totalclass'] = $query1->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Get student statistics
    $sql2 = "SELECT 
             COUNT(*) as total,
             SUM(CASE WHEN Gender = 'Male' THEN 1 ELSE 0 END) as male_count,
             SUM(CASE WHEN Gender = 'Female' THEN 1 ELSE 0 END) as female_count
             FROM tblstudent";
    $query2 = $dbh->prepare($sql2);
    $query2->execute();
    $result2 = $query2->fetch(PDO::FETCH_ASSOC);
    $stats['totalstudents'] = $result2['total'] ?? 0;
    $stats['malestudents'] = $result2['male_count'] ?? 0;
    $stats['femalestudents'] = $result2['female_count'] ?? 0;

    // Get total notices
    $sql3 = "SELECT COUNT(*) as total FROM tblnotice";
    $query3 = $dbh->prepare($sql3);
    $query3->execute();
    $stats['totalnotice'] = $query3->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Get teacher statistics
    $sql4 = "SELECT 
             COUNT(*) as total,
             SUM(CASE WHEN Gender = 'Male' THEN 1 ELSE 0 END) as male_count,
             SUM(CASE WHEN Gender = 'Female' THEN 1 ELSE 0 END) as female_count
             FROM tblteacher";
    $query4 = $dbh->prepare($sql4);
    $query4->execute();
    $result4 = $query4->fetch(PDO::FETCH_ASSOC);
    $stats['totalteachers'] = $result4['total'] ?? 0;
    $stats['maleteachers'] = $result4['male_count'] ?? 0;
    $stats['femaleteachers'] = $result4['female_count'] ?? 0;

    // Get teacher qualifications
    $sql5 = "SELECT Qualification, COUNT(*) as count 
             FROM tblteacher 
             GROUP BY Qualification 
             ORDER BY FIELD(Qualification, 'SSCE/Tech', 'NSCE/ND', 'HND/Bsc', 'Msc')";
    $query5 = $dbh->prepare($sql5);
    $query5->execute();
    $stats['qualifications'] = [];
    while ($row = $query5->fetch(PDO::FETCH_ASSOC)) {
        $percentage = ($row['count'] / $stats['totalteachers']) * 100;
        $stats['qualifications'][] = [
            'qualification' => $row['Qualification'],
            'count' => $row['count'],
            'percentage' => number_format($percentage, 1)
        ];
    }

    // Set last updated time
    $stats['lastUpdated'] = date('M d, Y H:i:s');

    echo json_encode(['success' => true, 'data' => $stats]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred']);
}
?>
