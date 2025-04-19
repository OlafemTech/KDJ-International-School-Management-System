<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
} else {
    $studentId = filter_var($_GET['student'], FILTER_VALIDATE_INT);
    $classId = filter_var($_GET['class'], FILTER_VALIDATE_INT);
    $term = filter_var($_GET['term'], FILTER_SANITIZE_STRING);

    if (!$studentId || !$classId || !$term) {
        echo "<script>alert('Invalid input');</script>";
        echo "<script>window.location.href='view-grades.php';</script>";
        exit();
    }

    // Get student details
    $sql = "SELECT s.*, c.ClassName, c.Level, c.Session 
            FROM tblstudent s
            JOIN tblclass c ON c.ID = :classId
            WHERE s.ID = :studentId";
    $query = $dbh->prepare($sql);
    $query->bindParam(':classId', $classId, PDO::PARAM_INT);
    $query->bindParam(':studentId', $studentId, PDO::PARAM_INT);
    $query->execute();
    $studentDetails = $query->fetch(PDO::FETCH_ASSOC);

    if (!$studentDetails) {
        echo "<script>alert('Student not found');</script>";
        echo "<script>window.location.href='view-grades.php';</script>";
        exit();
    }

    // Get grades
    $sql = "SELECT 
           g.*, 
           (g.CA1 + g.CA2) as TotalTest,
           (g.CA1 + g.CA2 + g.Exam) as TotalScore,
           sub.SubjectName 
           FROM tblgrades g
           JOIN tblsubjects sub ON g.SubjectID = sub.ID
           WHERE g.StudentID = :studentId 
           AND g.ClassID = :classId 
           AND g.Term = :term
           AND g.Session = :session
           ORDER BY sub.SubjectName";
    
    $query = $dbh->prepare($sql);
    $query->bindParam(':studentId', $studentId, PDO::PARAM_INT);
    $query->bindParam(':classId', $classId, PDO::PARAM_INT);
    $query->bindParam(':term', $term, PDO::PARAM_STR);
    $query->bindParam(':session', $studentDetails['Session'], PDO::PARAM_STR);
    $query->execute();
    
    $grades = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Result Sheet</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: white;
        }
        .print-container {
            max-width: 210mm; /* A4 width */
            margin: 0 auto;
            background: white;
        }
        .school-header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #000;
        }
        .school-logo {
            width: 80px;
            height: 80px;
            margin-bottom: 10px;
        }
        .school-name {
            font-size: 24px;
            font-weight: bold;
            margin: 5px 0;
        }
        .school-address {
            font-size: 14px;
            margin: 5px 0;
        }
        .result-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin: 20px 0;
            text-decoration: underline;
        }
        .student-info {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #000;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        .info-item {
            display: flex;
            gap: 10px;
        }
        .info-label {
            font-weight: bold;
            min-width: 120px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 12px;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
        }
        .summary-row {
            font-weight: bold;
            background-color: #f0f0f0;
        }
        .signature-section {
            margin-top: 50px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        .signature-box {
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 25px;
            padding-top: 5px;
        }
        .action-buttons {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        .action-buttons button, .action-buttons a {
            margin-left: 10px;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }
        .btn-print {
            background: #007bff;
            color: white;
        }
        .btn-back {
            background: #6c757d;
            color: white;
        }
        @media print {
            body {
                padding: 0;
                margin: 0;
            }
            .print-container {
                width: 100%;
                max-width: none;
            }
            .no-print {
                display: none;
            }
            table {
                page-break-inside: avoid;
            }
            .action-buttons {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="action-buttons no-print">
        <a href="view-grades.php?class=<?php echo $classId; ?>&term=<?php echo urlencode($term); ?>" class="btn-back">← Back to Grades</a>
        <button onclick="window.print()" class="btn-print">Print Result</button>
    </div>
    
    <div class="print-container">
        <div class="school-header">
            <img src="../images/logo.png" alt="School Logo" class="school-logo">
            <div class="school-name">KDJ International SCHOOL</div>
            <div class="school-address">Jalingo Road, Behind NEPA Office, Off Abuja Road, Rigasa, Kaduna State</div>
            <div class="school-address">Email: info@kdjschool.com | Website: www.kdjschool.com</div>
            <div class="school-address">Phone: +234(0)816-062-1759 | +234(0)706-279-7229</div>
        </div>

        <div class="result-title">STUDENT RESULT SHEET</div>

        <div class="student-info">
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Student Name:</span>
                    <span><?php echo htmlspecialchars($studentDetails['StudentName']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Student ID:</span>
                    <span><?php echo htmlspecialchars($studentDetails['ID']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Class:</span>
                    <span><?php echo htmlspecialchars($studentDetails['ClassName'].' '.$studentDetails['Level']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Term:</span>
                    <span><?php echo htmlspecialchars($term); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Session:</span>
                    <span><?php echo htmlspecialchars($studentDetails['Session']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Gender:</span>
                    <span><?php echo htmlspecialchars($studentDetails['Gender']); ?></span>
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>CA1 (20)</th>
                    <th>CA2 (20)</th>
                    <th>Total Test (40)</th>
                    <th>Exam (60)</th>
                    <th>Total (100)</th>
                    <th>Grade</th>
                    <th>Remark</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $totalCA1 = 0;
                $totalCA2 = 0;
                $totalExam = 0;
                $totalScore = 0;
                $subjectCount = 0;

                foreach ($grades as $grade) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($grade['SubjectName']) . "</td>";
                    echo "<td>" . number_format($grade['CA1'], 1) . "</td>";
                    echo "<td>" . number_format($grade['CA2'], 1) . "</td>";
                    echo "<td>" . number_format($grade['TotalTest'], 1) . "</td>";
                    echo "<td>" . number_format($grade['Exam'], 1) . "</td>";
                    echo "<td>" . number_format($grade['TotalScore'], 1) . "</td>";
                    
                    // Calculate grade and remark
                    $score = $grade['TotalScore'];
                    if ($score >= 75) { $letterGrade = 'A'; $remark = 'Excellent'; }
                    else if ($score >= 65) { $letterGrade = 'B'; $remark = 'Very Good'; }
                    else if ($score >= 55) { $letterGrade = 'C'; $remark = 'Good'; }
                    else if ($score >= 45) { $letterGrade = 'D'; $remark = 'Average'; }
                    else { $letterGrade = 'F'; $remark = 'Poor'; }
                    
                    echo "<td>" . $letterGrade . "</td>";
                    echo "<td>" . $remark . "</td>";
                    echo "</tr>";

                    $totalCA1 += $grade['CA1'];
                    $totalCA2 += $grade['CA2'];
                    $totalExam += $grade['Exam'];
                    $totalScore += $grade['TotalScore'];
                    $subjectCount++;
                }

                if ($subjectCount > 0) {
                    $avgCA1 = round($totalCA1 / $subjectCount, 1);
                    $avgCA2 = round($totalCA2 / $subjectCount, 1);
                    $avgExam = round($totalExam / $subjectCount, 1);
                    $avgTotal = round($totalScore / $subjectCount, 1);
                    
                    // Calculate overall grade and remark
                    if ($avgTotal >= 75) { $overallGrade = 'A'; $overallRemark = 'Excellent'; }
                    else if ($avgTotal >= 65) { $overallGrade = 'B'; $overallRemark = 'Very Good'; }
                    else if ($avgTotal >= 55) { $overallGrade = 'C'; $overallRemark = 'Good'; }
                    else if ($avgTotal >= 45) { $overallGrade = 'D'; $overallRemark = 'Average'; }
                    else { $overallGrade = 'F'; $overallRemark = 'Poor'; }

                    echo "<tr class='summary-row'>";
                    echo "<td><strong>Average</strong></td>";
                    echo "<td>" . $avgCA1 . "</td>";
                    echo "<td>" . $avgCA2 . "</td>";
                    echo "<td>" . round(($avgCA1 + $avgCA2), 1) . "</td>";
                    echo "<td>" . $avgExam . "</td>";
                    echo "<td>" . $avgTotal . "</td>";
                    echo "<td>" . $overallGrade . "</td>";
                    echo "<td>" . $overallRemark . "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>

        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line">Class Teacher's Signature</div>
            </div>
            <div class="signature-box">
                <div class="signature-line">Principal's Signature</div>
            </div>
            <div class="signature-box">
                <div class="signature-line">Date/Stamp</div>
            </div>
        </div>
    </div>
</body>
</html>
<?php } ?>
