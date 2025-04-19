<?php
session_start();
include('../includes/config.php');
include('../includes/functions.php');
ensure_admin_logged_in();

if (isset($_POST['submit'])) {
    $studentId = intval($_POST['student_id']);
    $subjectId = intval($_POST['subject_id']);
    $ca1 = floatval($_POST['ca1']);
    $ca2 = floatval($_POST['ca2']);
    $exam = floatval($_POST['exam']);
    $comment = mysqli_real_escape_string($con, $_POST['teacher_comment']);

    // Validate scores
    if ($ca1 < 0 || $ca1 > 20 || $ca2 < 0 || $ca2 > 20 || $exam < 0 || $exam > 100) {
        $_SESSION['error'] = "Invalid score values. Please check and try again.";
    } else {
        $sql = "INSERT INTO tblgrades (StudentID, SubjectID, CA1, CA2, Exam, TeacherComment) 
                VALUES (?, ?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                CA1 = VALUES(CA1), 
                CA2 = VALUES(CA2), 
                Exam = VALUES(Exam), 
                TeacherComment = VALUES(TeacherComment)";
        
        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, "iiddds", $studentId, $subjectId, $ca1, $ca2, $exam, $comment);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Grades recorded successfully";
        } else {
            $_SESSION['error'] = "Error recording grades: " . mysqli_error($con);
        }
    }
}

$pageTitle = "Record Student Grades";
include('../includes/header.php');
?>

<div class="container-fluid px-4">
    <h2 class="mt-4">Record Student Grades</h2>
    
    <?php include('../includes/alert.php'); ?>

    <div class="card mb-4">
        <div class="card-body">
            <form id="gradeForm" method="POST">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Select Class</label>
                        <select class="form-select" id="classSelect" required>
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
                    <div class="col-md-6">
                        <label class="form-label">Select Subject</label>
                        <select class="form-select" id="subjectSelect" name="subject_id" required>
                            <option value="">First Select Class</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label">Select Student</label>
                        <select class="form-select" id="studentSelect" name="student_id" required>
                            <option value="">First Select Class</option>
                        </select>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">CA 1 (Max: 20)</label>
                                <input type="number" class="form-control score-input" name="ca1" id="ca1" min="0" max="20" step="0.5" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">CA 2 (Max: 20)</label>
                                <input type="number" class="form-control score-input" name="ca2" id="ca2" min="0" max="20" step="0.5" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Total Test (40%)</label>
                                <input type="text" class="form-control" id="totalTest" readonly>
                                <div class="progress mt-2" style="height: 5px;">
                                    <div class="progress-bar" id="testProgress" role="progressbar" style="width: 0%"></div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Exam (Max: 100)</label>
                                <input type="number" class="form-control score-input" name="exam" id="exam" min="0" max="100" step="0.5" required>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Total Score (100%)</label>
                                <input type="text" class="form-control" id="totalScore" readonly>
                                <div class="progress mt-2" style="height: 10px;">
                                    <div class="progress-bar" id="totalProgress" role="progressbar" style="width: 0%"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Performance Level</label>
                                <input type="text" class="form-control" id="performanceLevel" readonly>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <label class="form-label">Teacher's Comment</label>
                                <textarea class="form-control" name="teacher_comment" id="teacherComment" rows="3" required></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <button type="submit" name="submit" class="btn btn-primary">Save Grades</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Handle class selection
    $('#classSelect').change(function() {
        const classId = $(this).val();
        if (classId) {
            // Load subjects for the selected class
            $.get('../ajax/get_subjects.php', {class_id: classId}, function(data) {
                $('#subjectSelect').html(data);
            });
            
            // Load students for the selected class
            $.get('../ajax/get_students.php', {class_id: classId}, function(data) {
                $('#studentSelect').html(data);
            });
        } else {
            $('#subjectSelect').html('<option value="">First Select Class</option>');
            $('#studentSelect').html('<option value="">First Select Class</option>');
        }
    });

    // Calculate scores in real-time
    $('.score-input').on('input', function() {
        calculateScores();
    });

    function calculateScores() {
        const ca1 = parseFloat($('#ca1').val()) || 0;
        const ca2 = parseFloat($('#ca2').val()) || 0;
        const exam = parseFloat($('#exam').val()) || 0;

        // Calculate total test (CA1 + CA2)
        const totalTest = ca1 + ca2;
        $('#totalTest').val(totalTest.toFixed(2));
        $('#testProgress').css('width', (totalTest/40*100) + '%');

        // Calculate final score (40% of total test + 60% of exam)
        const totalScore = (totalTest * 0.4) + (exam * 0.6);
        $('#totalScore').val(totalScore.toFixed(2));
        $('#totalProgress').css('width', totalScore + '%');

        // Determine performance level
        let performanceLevel;
        if (totalScore >= 75) performanceLevel = 'Excellent';
        else if (totalScore >= 65) performanceLevel = 'Very Good';
        else if (totalScore >= 55) performanceLevel = 'Good';
        else if (totalScore >= 45) performanceLevel = 'Average';
        else performanceLevel = 'Needs Improvement';

        $('#performanceLevel').val(performanceLevel);
        
        // Set progress bar color based on performance
        const progressBar = $('#totalProgress');
        if (totalScore >= 75) progressBar.removeClass().addClass('progress-bar bg-success');
        else if (totalScore >= 55) progressBar.removeClass().addClass('progress-bar bg-info');
        else if (totalScore >= 45) progressBar.removeClass().addClass('progress-bar bg-warning');
        else progressBar.removeClass().addClass('progress-bar bg-danger');
    }
});
</script>

<?php include('../includes/footer.php'); ?>
