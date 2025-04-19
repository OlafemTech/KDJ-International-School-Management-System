<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
} else {
    $classId = filter_var($_GET['class'], FILTER_VALIDATE_INT);
    $term = filter_var($_GET['term'], FILTER_SANITIZE_STRING);

    if (!$classId || !$term) {
        echo "<script>alert('Invalid parameters');</script>";
        echo "<script>window.location.href='view-grades.php';</script>";
        exit();
    }

    // Get class details and current session
    $sql = "SELECT * FROM tblclass WHERE ID = :classId";
    $query = $dbh->prepare($sql);
    $query->bindParam(':classId', $classId, PDO::PARAM_INT);
    $query->execute();
    $classDetails = $query->fetch(PDO::FETCH_ASSOC);

    if (!$classDetails) {
        echo "<script>alert('Class not found');</script>";
        echo "<script>window.location.href='view-grades.php';</script>";
        exit();
    }

    $currentSession = $classDetails['Session'];
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
            padding: 10px;
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
            .page-break {
                page-break-after: always;
            }
            .action-buttons {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="action-buttons no-print">
        <a href="view-grades.php" class="btn-back">← Back to Grades</a>
        <button onclick="window.print()" class="btn-print">Print Result</button>
    </div>
    
    <?php
    // Get student information
    $studentSql = "SELECT s.*, c.ClassName, c.Level 
                  FROM tblstudent s 
                  JOIN tblclass c ON s.ClassID = c.ID 
                  WHERE s.ClassID = :classId 
                  ORDER BY s.StudentName";
    $studentQuery = $dbh->prepare($studentSql);
    $studentQuery->bindParam(':classId', $classId, PDO::PARAM_INT);
    $studentQuery->execute();
    $students = $studentQuery->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <?php foreach ($students as $student) { ?>
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
                    <span><?php echo htmlspecialchars($student['StudentName']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Student ID:</span>
                    <span><?php echo htmlspecialchars($student['StudentId']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Class:</span>
                    <span><?php echo htmlspecialchars($classDetails['ClassName'].' '.$classDetails['Level']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Term:</span>
                    <span><?php echo htmlspecialchars($term); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Session:</span>
                    <span><?php echo htmlspecialchars($currentSession); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Gender:</span>
                    <span><?php echo htmlspecialchars($student['Gender']); ?></span>
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
                $sql = "SELECT 
                       g.*, sub.SubjectName,
                       (g.CA1 + g.CA2) as TotalTest,
                       (g.CA1 + g.CA2 + g.Exam) as TotalScore,
                       CASE 
                           WHEN (g.CA1 + g.CA2 + g.Exam) >= 75 THEN 'A'
                           WHEN (g.CA1 + g.CA2 + g.Exam) >= 65 THEN 'B'
                           WHEN (g.CA1 + g.CA2 + g.Exam) >= 55 THEN 'C'
                           WHEN (g.CA1 + g.CA2 + g.Exam) >= 45 THEN 'D'
                           ELSE 'F'
                       END as Grade,
                       CASE 
                           WHEN (g.CA1 + g.CA2 + g.Exam) >= 75 THEN 'Excellent'
                           WHEN (g.CA1 + g.CA2 + g.Exam) >= 65 THEN 'Very Good'
                           WHEN (g.CA1 + g.CA2 + g.Exam) >= 55 THEN 'Good'
                           WHEN (g.CA1 + g.CA2 + g.Exam) >= 45 THEN 'Average'
                           ELSE 'Poor'
                       END as Remark
                       FROM tblgrades g
                       JOIN tblsubjects sub ON g.SubjectID = sub.ID
                       WHERE g.StudentID = :studentId 
                       AND g.ClassID = :classId 
                       AND g.Term = :term
                       AND g.Session = :session
                       ORDER BY sub.SubjectName";
                
                $gradeQuery = $dbh->prepare($sql);
                $gradeQuery->bindParam(':studentId', $student['ID'], PDO::PARAM_INT);
                $gradeQuery->bindParam(':classId', $classId, PDO::PARAM_INT);
                $gradeQuery->bindParam(':term', $term, PDO::PARAM_STR);
                $gradeQuery->bindParam(':session', $currentSession, PDO::PARAM_STR);
                $gradeQuery->execute();
                $grades = $gradeQuery->fetchAll(PDO::FETCH_ASSOC);

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
                    echo "<td>" . $grade['Grade'] . "</td>";
                    echo "<td>" . $grade['Remark'] . "</td>";
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
                    
                    $overallGrade = '';
                    $overallRemark = '';
                    
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
    <div class="page-break"></div>
    <?php } ?>
</body>
</html>
<?php } ?>
