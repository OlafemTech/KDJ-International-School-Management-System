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

$studentId = isset($_GET['student']) ? intval($_GET['student']) : 0;

if($studentId == 0) {
    header('location: view-grade-report.php');
    exit();
}

// Get student details
$sql = "SELECT s.*, c.ClassName, c.Section 
        FROM tblstudent s 
        JOIN tblclass c ON s.StudentClass = c.ID 
        WHERE s.StudentID = :studentId";
$query = $dbh->prepare($sql);
$query->bindParam(':studentId', $studentId, PDO::PARAM_INT);
$query->execute();
$student = $query->fetch(PDO::FETCH_ASSOC);

if(!$student) {
    header('location: view-grade-report.php');
    exit();
}

// Function to calculate grade point (same as in view-grade-report.php)
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

// Get student grades by category
$grades = [];
$sql = "SELECT gc.*, gi.ItemName, gi.MaxScore, gi.Weight as ItemWeight, 
               s.SubjectName, s.SubjectCode, COALESCE(sg.Score, 0) as Score, 
               sg.Remarks, sg.UpdatedAt
        FROM tblgradecategories gc
        JOIN tblgradeitems gi ON gc.CategoryID = gi.CategoryID
        JOIN tblsubjects s ON gi.SubjectID = s.SubjectID
        LEFT JOIN tblstudentgrades sg ON gi.GradeItemID = sg.GradeItemID AND sg.StudentID = :studentId
        ORDER BY gc.CategoryName, s.SubjectName, gi.DueDate";
$query = $dbh->prepare($sql);
$query->bindParam(':studentId', $studentId, PDO::PARAM_INT);
$query->execute();
$results = $query->fetchAll(PDO::FETCH_ASSOC);

foreach($results as $row) {
    $categoryName = $row['CategoryName'];
    if(!isset($grades[$categoryName])) {
        $grades[$categoryName] = [
            'weight' => $row['Weight'],
            'items' => []
        ];
    }
    $grades[$categoryName]['items'][] = $row;
}

// Calculate overall grade
$totalWeightedScore = 0;
$totalWeight = 0;

foreach($grades as $categoryName => $category) {
    $categoryWeight = $category['weight'];
    $categoryScore = 0;
    $categoryMaxScore = 0;
    
    foreach($category['items'] as $item) {
        $itemWeight = $item['ItemWeight'];
        $maxScore = $item['MaxScore'];
        $score = $item['Score'];
        
        $categoryScore += ($score / $maxScore) * $itemWeight;
        $categoryMaxScore += $itemWeight;
    }
    
    if($categoryMaxScore > 0) {
        $categoryPercentage = ($categoryScore / $categoryMaxScore) * 100;
        $totalWeightedScore += ($categoryPercentage * $categoryWeight / 100);
        $totalWeight += $categoryWeight;
    }
}

$finalPercentage = $totalWeight > 0 ? ($totalWeightedScore / $totalWeight) : 0;
$finalGrade = calculateGradePoint($finalPercentage);

// Create PDF using TCPDF
class MYPDF extends TCPDF {
    public function Header() {
        $this->SetFont('helvetica', 'B', 15);
        $this->Cell(0, 15, 'Student Grade Report', 0, true, 'C');
        $this->SetFont('helvetica', '', 12);
        $this->Ln(5);
    }
    
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C');
    }
}

$pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);

$pdf->SetCreator('Student Management System');
$pdf->SetAuthor('Admin');
$pdf->SetTitle('Grade Report - ' . $student['StudentName']);

$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(TRUE, 15);

$pdf->AddPage();

// Student Information
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Student Information', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);

$pdf->Cell(40, 7, 'Name:', 0, 0);
$pdf->Cell(60, 7, $student['StudentName'], 0, 0);
$pdf->Cell(40, 7, 'Roll Number:', 0, 0);
$pdf->Cell(50, 7, $student['RollNumber'], 0, 1);

$pdf->Cell(40, 7, 'Class:', 0, 0);
$pdf->Cell(60, 7, $student['ClassName'] . ' - ' . $student['Section'], 0, 0);
$pdf->Cell(40, 7, 'Overall Grade:', 0, 0);
$pdf->Cell(50, 7, $finalGrade[0] . ' (' . round($finalPercentage, 2) . '%)', 0, 1);

$pdf->Ln(5);

// Grade Details by Category
foreach($grades as $categoryName => $category) {
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 10, $categoryName . ' (' . $category['weight'] . '%)', 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 9);
    
    // Table Header
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(50, 7, 'Subject', 1, 0, 'L', true);
    $pdf->Cell(50, 7, 'Item', 1, 0, 'L', true);
    $pdf->Cell(20, 7, 'Score', 1, 0, 'C', true);
    $pdf->Cell(20, 7, 'Max', 1, 0, 'C', true);
    $pdf->Cell(20, 7, 'Weight', 1, 0, 'C', true);
    $pdf->Cell(30, 7, 'Grade', 1, 1, 'C', true);
    
    foreach($category['items'] as $item) {
        $percentage = $item['MaxScore'] > 0 ? ($item['Score'] / $item['MaxScore'] * 100) : 0;
        $gradePoint = calculateGradePoint($percentage);
        
        $pdf->Cell(50, 6, $item['SubjectName'] . ' (' . $item['SubjectCode'] . ')', 1);
        $pdf->Cell(50, 6, $item['ItemName'], 1);
        $pdf->Cell(20, 6, $item['Score'], 1, 0, 'C');
        $pdf->Cell(20, 6, $item['MaxScore'], 1, 0, 'C');
        $pdf->Cell(20, 6, $item['ItemWeight'] . '%', 1, 0, 'C');
        $pdf->Cell(30, 6, $gradePoint[0] . ' (' . round($percentage, 1) . '%)', 1, 1, 'C');
        
        if($item['Remarks']) {
            $pdf->SetFont('helvetica', 'I', 8);
            $pdf->Cell(0, 5, 'Remarks: ' . $item['Remarks'], 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 9);
        }
    }
    
    $pdf->Ln(5);
}

// Generate Report Date
$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0, 10, 'Report generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'R');

// Output PDF
$filename = 'Grade_Report_' . $student['RollNumber'] . '_' . date('Ymd') . '.pdf';
$pdf->Output($filename, 'D');
?>
