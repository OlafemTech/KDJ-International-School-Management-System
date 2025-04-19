<?php
session_start();
include('../includes/config.php');
include('../includes/functions.php');
ensure_admin_logged_in();

$pageTitle = "View Student Grades";
include('../includes/header.php');
?>

<div class="container-fluid px-4">
    <h2 class="mt-4">View Student Grades</h2>
    
    <?php include('../includes/alert.php'); ?>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Select Class</label>
                    <select class="form-select" id="classSelect">
                        <option value="">Select Class</option>
                        <?php
                        $sql = "SELECT * FROM tblclass ORDER BY ClassName, Level";
                        $result = mysqli_query($con, $sql);
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo "<option value='{$row['ID']}'>{$row['ClassName']} {$row['Level']} - {$row['Session']} ({$row['Term']})</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Select Student</label>
                    <select class="form-select" id="studentSelect">
                        <option value="">First Select Class</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" id="viewGrades" class="btn btn-primary d-block">View Grades</button>
                </div>
            </div>

            <div id="gradesContainer" class="mt-4" style="display: none;">
                <h4 class="mb-3">Student Grade Report</h4>
                <div id="studentInfo" class="alert alert-info"></div>
                
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Subject</th>
                                <th>CA 1 (20%)</th>
                                <th>CA 2 (20%)</th>
                                <th>Total Test (40%)</th>
                                <th>Exam (60%)</th>
                                <th>Total Score</th>
                                <th>Grade</th>
                                <th>Teacher's Comment</th>
                            </tr>
                        </thead>
                        <tbody id="gradesTableBody">
                        </tbody>
                    </table>
                </div>

                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Performance Summary</h5>
                                <div id="performanceSummary"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Grade Distribution</h5>
                                <canvas id="gradeChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    let gradeChart = null;

    // Handle class selection
    $('#classSelect').change(function() {
        const classId = $(this).val();
        if (classId) {
            $.get('../ajax/get_students.php', {class_id: classId}, function(data) {
                $('#studentSelect').html(data);
            });
        } else {
            $('#studentSelect').html('<option value="">First Select Class</option>');
        }
        $('#gradesContainer').hide();
    });

    // Handle view grades button
    $('#viewGrades').click(function() {
        const studentId = $('#studentSelect').val();
        if (!studentId) {
            alert('Please select a student first');
            return;
        }

        $.get('../ajax/get_student_grades.php', {student_id: studentId}, function(response) {
            const data = JSON.parse(response);
            
            // Update student info
            $('#studentInfo').html(`
                <strong>Student:</strong> ${data.student.LastName}, ${data.student.FirstName}<br>
                <strong>Class:</strong> ${data.student.ClassName} ${data.student.Level}<br>
                <strong>Session:</strong> ${data.student.Session} - ${data.student.Term}
            `);

            // Update grades table
            let tableHtml = '';
            let gradeDistribution = {
                'Excellent': 0,
                'Very Good': 0,
                'Good': 0,
                'Average': 0,
                'Needs Improvement': 0
            };

            data.grades.forEach(grade => {
                const totalScore = grade.TotalScore;
                let performanceLevel = '';
                
                if (totalScore >= 75) {
                    performanceLevel = 'Excellent';
                    gradeDistribution.Excellent++;
                } else if (totalScore >= 65) {
                    performanceLevel = 'Very Good';
                    gradeDistribution['Very Good']++;
                } else if (totalScore >= 55) {
                    performanceLevel = 'Good';
                    gradeDistribution.Good++;
                } else if (totalScore >= 45) {
                    performanceLevel = 'Average';
                    gradeDistribution.Average++;
                } else {
                    performanceLevel = 'Needs Improvement';
                    gradeDistribution['Needs Improvement']++;
                }

                tableHtml += `
                    <tr>
                        <td>${grade.SubjectName}</td>
                        <td>${grade.CA1}</td>
                        <td>${grade.CA2}</td>
                        <td>${grade.TotalTest}</td>
                        <td>${grade.Exam}</td>
                        <td><strong>${grade.TotalScore}</strong></td>
                        <td>${performanceLevel}</td>
                        <td>${grade.TeacherComment}</td>
                    </tr>
                `;
            });

            $('#gradesTableBody').html(tableHtml);

            // Update performance summary
            const totalSubjects = data.grades.length;
            const summaryHtml = `
                <p><strong>Total Subjects:</strong> ${totalSubjects}</p>
                <p><strong>Grade Distribution:</strong></p>
                <ul class="list-unstyled">
                    <li>Excellent (75-100): ${gradeDistribution.Excellent}</li>
                    <li>Very Good (65-74): ${gradeDistribution['Very Good']}</li>
                    <li>Good (55-64): ${gradeDistribution.Good}</li>
                    <li>Average (45-54): ${gradeDistribution.Average}</li>
                    <li>Needs Improvement (0-44): ${gradeDistribution['Needs Improvement']}</li>
                </ul>
            `;
            $('#performanceSummary').html(summaryHtml);

            // Update grade chart
            if (gradeChart) {
                gradeChart.destroy();
            }

            const ctx = document.getElementById('gradeChart').getContext('2d');
            gradeChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: Object.keys(gradeDistribution),
                    datasets: [{
                        data: Object.values(gradeDistribution),
                        backgroundColor: [
                            '#28a745', // Excellent
                            '#17a2b8', // Very Good
                            '#ffc107', // Good
                            '#fd7e14', // Average
                            '#dc3545'  // Needs Improvement
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            $('#gradesContainer').show();
        });
    });
});
</script>

<?php include('../includes/footer.php'); ?>
