<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require('../includes/dbconnection.php');
require('../vendor/autoload.php'); // For TCPDF

// Check if admin is logged in
if (!isset($_SESSION['sturecmsaid']) || strlen($_SESSION['sturecmsaid']) == 0) {
    header('location: login.php');
    exit();
}

$classId = isset($_GET['class']) ? intval($_GET['class']) : 0;

if($classId == 0) {
    header('location: view-grade-report.php');
    exit();
}

// Get class details
$sql = "SELECT * FROM tblclass WHERE ID = :classId";
$query = $dbh->prepare($sql);
$query->bindParam(':classId', $classId, PDO::PARAM_INT);
$query->execute();
$classDetails = $query->fetch(PDO::FETCH_ASSOC);

if(!$classDetails) {
    header('location: view-grade-report.php');
    exit();
}

// Function to calculate grade point
function calculateGradePoint($percentage) {
    if ($percentage >= 90) return ['A+', 4.0];
    if ($percentage >= 85) return ['A', 4.0];
    if ($percentage >= 80) return ['A-', 3.7];
    if ($percentage >= 75) return ['B+', 3.3];
    if ($percentage >= 70) return ['B', 3.0];
    if ($percentage >= 65) return ['B-', 2.7];
    if ($percentage >= 60) return ['C+', 2.3];
    if ($percentage >= 55) return ['C', 2.0];
    if ($percentage >= 50) return ['C-', 1.7];
    if ($percentage >= 45) return ['D+', 1.3];
    if ($percentage >= 40) return ['D', 1.0];
    return ['F', 0.0];
}

// Get all students in the class
$sql = "SELECT * FROM tblstudent WHERE StudentClass = :classId ORDER BY RollNumber";
$query = $dbh->prepare($sql);
$query->bindParam(':classId', $classId, PDO::PARAM_INT);
$query->execute();
$students = $query->fetchAll(PDO::FETCH_ASSOC);

// Get all subjects assigned to this class
$sql = "SELECT DISTINCT s.* 
        FROM tblsubjects s
        JOIN tblsubjectteacherclass stc ON s.SubjectID = stc.SubjectID
        WHERE stc.ClassID = :classId
        ORDER BY s.SubjectName";
$query = $dbh->prepare($sql);
$query->bindParam(':classId', $classId, PDO::PARAM_INT);
$query->execute();
$subjects = $query->fetchAll(PDO::FETCH_ASSOC);

// Calculate class statistics
$classStats = [
    'totalStudents' => count($students),
    'gradeDistribution' => array_fill_keys(['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D+', 'D', 'F'], 0),
    'subjectAverages' => [],
    'highestAverage' => 0,
    'lowestAverage' => 100,
    'classAverage' => 0
];

// Create PDF
class MYPDF extends TCPDF {
    public function Header() {
        $this->SetFont('helvetica', 'B', 15);
        $this->Cell(0, 15, 'Class Grade Report', 0, true, 'C');
        $this->SetFont('helvetica', '', 12);
        $this->Ln(5);
    }
    
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C');
    }
}

$pdf = new MYPDF('L', 'mm', 'A4', true, 'UTF-8', false);

$pdf->SetCreator('Student Management System');
$pdf->SetAuthor('Admin');
$pdf->SetTitle('Class Report - ' . $classDetails['ClassName'] . ' ' . $classDetails['Section']);

$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(TRUE, 15);

$pdf->AddPage('L'); // Landscape orientation for wider tables

// Class Information
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Class Information', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);

$pdf->Cell(40, 7, 'Class:', 0, 0);
$pdf->Cell(60, 7, $classDetails['ClassName'] . ' - ' . $classDetails['Section'], 0, 0);
$pdf->Cell(40, 7, 'Total Students:', 0, 0);
$pdf->Cell(50, 7, $classStats['totalStudents'], 0, 1);

$pdf->Ln(5);

// Subject-wise Performance
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 10, 'Subject-wise Performance', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 9);

// Table Header
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(20, 7, 'Roll No', 1, 0, 'C', true);
$pdf->Cell(40, 7, 'Student Name', 1, 0, 'L', true);

foreach($subjects as $subject) {
    $pdf->Cell(25, 7, $subject['SubjectCode'], 1, 0, 'C', true);
    $classStats['subjectAverages'][$subject['SubjectID']] = ['total' => 0, 'count' => 0];
}

$pdf->Cell(25, 7, 'Average', 1, 0, 'C', true);
$pdf->Cell(20, 7, 'Grade', 1, 1, 'C', true);

// Student Grades
foreach($students as $student) {
    $studentTotal = 0;
    $subjectCount = 0;
    
    $pdf->Cell(20, 6, $student['RollNumber'], 1, 0, 'C');
    $pdf->Cell(40, 6, $student['StudentName'], 1, 0, 'L');
    
    foreach($subjects as $subject) {
        // Get student's average grade for this subject
        $sql = "SELECT AVG((sg.Score / gi.MaxScore) * 100) as average
                FROM tblgradeitems gi
                LEFT JOIN tblstudentgrades sg ON gi.GradeItemID = sg.GradeItemID AND sg.StudentID = :studentId
                WHERE gi.SubjectID = :subjectId";
        $query = $dbh->prepare($sql);
        $query->bindParam(':studentId', $student['StudentID'], PDO::PARAM_INT);
        $query->bindParam(':subjectId', $subject['SubjectID'], PDO::PARAM_INT);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        
        $average = $result['average'] ? round($result['average'], 1) : 0;
        
        if($average > 0) {
            $studentTotal += $average;
            $subjectCount++;
            $classStats['subjectAverages'][$subject['SubjectID']]['total'] += $average;
            $classStats['subjectAverages'][$subject['SubjectID']]['count']++;
        }
        
        $pdf->Cell(25, 6, $average . '%', 1, 0, 'C');
    }
    
    // Calculate student's overall average
    $studentAverage = $subjectCount > 0 ? ($studentTotal / $subjectCount) : 0;
    $grade = calculateGradePoint($studentAverage);
    
    $pdf->Cell(25, 6, round($studentAverage, 1) . '%', 1, 0, 'C');
    $pdf->Cell(20, 6, $grade[0], 1, 1, 'C');
    
    // Update class statistics
    if($studentAverage > 0) {
        $classStats['gradeDistribution'][$grade[0]]++;
        $classStats['highestAverage'] = max($classStats['highestAverage'], $studentAverage);
        $classStats['lowestAverage'] = min($classStats['lowestAverage'], $studentAverage);
        $classStats['classAverage'] += $studentAverage;
    }
}

$pdf->Ln(10);

// Class Statistics
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 10, 'Class Statistics', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 9);

// Subject Averages
$pdf->Cell(0, 7, 'Subject Averages:', 0, 1);
foreach($subjects as $subject) {
    $stats = $classStats['subjectAverages'][$subject['SubjectID']];
    $average = $stats['count'] > 0 ? ($stats['total'] / $stats['count']) : 0;
    $pdf->Cell(100, 6, $subject['SubjectName'] . ' (' . $subject['SubjectCode'] . '): ' . 
               round($average, 1) . '%', 0, 1);
}

$pdf->Ln(5);

// Grade Distribution
$pdf->Cell(0, 7, 'Grade Distribution:', 0, 1);
foreach($classStats['gradeDistribution'] as $grade => $count) {
    if($count > 0) {
        $percentage = ($count / $classStats['totalStudents']) * 100;
        $pdf->Cell(100, 6, $grade . ': ' . $count . ' students (' . 
                   round($percentage, 1) . '%)', 0, 1);
    }
}

$pdf->Ln(5);

// Overall Statistics
$classStats['classAverage'] = $classStats['totalStudents'] > 0 ? 
                             ($classStats['classAverage'] / $classStats['totalStudents']) : 0;

$pdf->Cell(100, 6, 'Class Average: ' . round($classStats['classAverage'], 1) . '%', 0, 1);
$pdf->Cell(100, 6, 'Highest Average: ' . round($classStats['highestAverage'], 1) . '%', 0, 1);
$pdf->Cell(100, 6, 'Lowest Average: ' . round($classStats['lowestAverage'], 1) . '%', 0, 1);

// Generate Report Date
$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0, 10, 'Report generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'R');

// Output PDF
$filename = 'Class_Report_' . $classDetails['ClassName'] . '_' . $classDetails['Section'] . '_' . date('Ymd') . '.pdf';
$pdf->Output($filename, 'D');
?>
