<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
} else {
    $error = '';
    
    // Check for success message
    if(isset($_SESSION['msg'])) {
        $msg = $_SESSION['msg'];
        unset($_SESSION['msg']);
    } else {
        $msg = '';
    }
    
    if(isset($_POST['submit'])) {
        try {
            // Get form data
            $studentId = isset($_POST['StudentID']) ? intval($_POST['StudentID']) : 0;
            $classId = isset($_POST['ClassID']) ? intval($_POST['ClassID']) : 0;
            $session = isset($_POST['Session']) ? trim($_POST['Session']) : '';
            $term = isset($_POST['Term']) ? trim($_POST['Term']) : '';
            $teacherId = isset($_SESSION['sturecmsaid']) ? intval($_SESSION['sturecmsaid']) : 0;
            
            // Debug log
            error_log("Form Data: " . print_r($_POST, true));
            
            // Basic validation
            if(empty($studentId) || empty($classId) || empty($session) || empty($term)) {
                throw new Exception("Please fill all required fields");
            }
            
            // Check if arrays are set
            if(!isset($_POST['SubjectID']) || !isset($_POST['CA1']) || !isset($_POST['CA2']) || 
               !isset($_POST['Exam']) || !isset($_POST['TeacherComment'])) {
                throw new Exception("Missing required grade data");
            }
            
            $subjectIds = array_map('intval', $_POST['SubjectID']);
            $ca1s = array_map('floatval', $_POST['CA1']);
            $ca2s = array_map('floatval', $_POST['CA2']);
            $exams = array_map('floatval', $_POST['Exam']);
            $teacherComments = array_map('trim', $_POST['TeacherComment']);
            
            // Validate arrays have data
            if(empty($subjectIds)) {
                throw new Exception("Please add at least one subject");
            }
            
            // Validate arrays have same length
            $count = count($subjectIds);
            if(count($ca1s) !== $count || count($ca2s) !== $count || 
               count($exams) !== $count || count($teacherComments) !== $count) {
                throw new Exception("Invalid form data - missing values");
            }
            
            // Begin transaction
            $dbh->beginTransaction();
            
            try {
                // Check if grades already exist
                $subjectList = implode(',', $subjectIds);
                $checkSql = "SELECT SubjectID FROM tblgrades 
                            WHERE StudentID = :studentId 
                            AND ClassID = :classId 
                            AND Session = :session 
                            AND Term = :term 
                            AND SubjectID IN ($subjectList)";
                
                $checkQuery = $dbh->prepare($checkSql);
                $checkQuery->bindParam(':studentId', $studentId, PDO::PARAM_INT);
                $checkQuery->bindParam(':classId', $classId, PDO::PARAM_INT);
                $checkQuery->bindParam(':session', $session, PDO::PARAM_STR);
                $checkQuery->bindParam(':term', $term, PDO::PARAM_STR);
                $checkQuery->execute();
                
                if($checkQuery->rowCount() > 0) {
                    throw new Exception("Grades already exist for some subjects in this term");
                }
                
                // Prepare insert statement
                $sql = "INSERT INTO tblgrades (StudentID, SubjectID, ClassID, Session, Term, CA1, CA2, Exam, TeacherComment, TeacherID) 
                        VALUES (:studentId, :subjectId, :classId, :session, :term, :ca1, :ca2, :exam, :teacherComment, :teacherId)";
                $query = $dbh->prepare($sql);
                
                // Insert grades for each subject
                for($i = 0; $i < $count; $i++) {
                    // Validate scores
                    if($ca1s[$i] < 0 || $ca1s[$i] > 20) {
                        throw new Exception("CA1 score must be between 0-20");
                    }
                    if($ca2s[$i] < 0 || $ca2s[$i] > 20) {
                        throw new Exception("CA2 score must be between 0-20");
                    }
                    if($exams[$i] < 0 || $exams[$i] > 60) {
                        throw new Exception("Exam score must be between 0-60");
                    }
                    
                    $query->bindParam(':studentId', $studentId, PDO::PARAM_INT);
                    $query->bindParam(':subjectId', $subjectIds[$i], PDO::PARAM_INT);
                    $query->bindParam(':classId', $classId, PDO::PARAM_INT);
                    $query->bindParam(':session', $session, PDO::PARAM_STR);
                    $query->bindParam(':term', $term, PDO::PARAM_STR);
                    $query->bindParam(':ca1', $ca1s[$i], PDO::PARAM_STR);
                    $query->bindParam(':ca2', $ca2s[$i], PDO::PARAM_STR);
                    $query->bindParam(':exam', $exams[$i], PDO::PARAM_STR);
                    $query->bindParam(':teacherComment', $teacherComments[$i], PDO::PARAM_STR);
                    $query->bindParam(':teacherId', $teacherId, PDO::PARAM_INT);
                    
                    if(!$query->execute()) {
                        throw new Exception("Error saving grades for subject #" . ($i + 1));
                    }
                }
                
                // Commit transaction
                $dbh->commit();
                
                // Success
                $_SESSION['msg'] = "Grades have been recorded successfully";
                header("Location: record-grades.php");
                exit();
                
            } catch(Exception $e) {
                $dbh->rollBack();
                throw $e;
            }
            
        } catch(Exception $e) {
            $error = $e->getMessage();
            error_log("Grade Save Error: " . $e->getMessage());
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Student Grade Management</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <!-- Layout styles -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="vendors/select2/select2.min.css">
    <style>
        .grade-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        .grade-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .score-input {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 8px;
            width: 100%;
            transition: border-color 0.3s ease;
        }
        .score-input:focus {
            border-color: #4B49AC;
            box-shadow: 0 0 0 2px rgba(75, 73, 172, 0.1);
        }
        .progress-container {
            margin: 15px 0;
        }
        .progress-bar {
            height: 8px;
            background: #f0f0f0;
            border-radius: 4px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            transition: width 0.3s ease;
        }
        .progress-fill.ca {
            background: linear-gradient(90deg, #8e44ad, #9b59b6);
        }
        .progress-fill.exam {
            background: linear-gradient(90deg, #2ecc71, #27ae60);
        }
        .score-display {
            text-align: center;
            margin-top: 5px;
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .score-label {
            text-align: center;
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .performance-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 14px;
            color: white;
            margin-top: 10px;
        }
        .performance-badge.excellent { background: #2ecc71; }
        .performance-badge.very-good { background: #3498db; }
        .performance-badge.good { background: #f1c40f; }
        .performance-badge.average { background: #e67e22; }
        .performance-badge.needs-improvement { background: #e74c3c; }
        
        /* Center placeholder text in inputs and selects */
        .form-control::placeholder {
            text-align: center;
            color: #6c757d;
            opacity: 0.8;
        }

        .form-control {
            text-align: center;
        }

        /* Fix select2 alignment */
        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            text-align: center;
            width: 100%;
            display: inline-block;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            text-align: center;
            padding-right: 20px;
        }

        /* Style select2 dropdown */
        .select2-results__option {
            text-align: center;
            padding: 8px;
        }

        /* Improve input appearance */
        input.form-control, select.form-control {
            height: 45px;
            padding: 10px 15px;
        }

        .select2-container .select2-selection--single {
            height: 45px;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 45px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 43px;
        }

        /* Style placeholders for different states */
        .form-control:focus::placeholder {
            opacity: 0.6;
        }

        /* Improve select2 appearance */
        .select2-container--default .select2-selection--single {
            border: 1px solid #ced4da;
            border-radius: 4px;
        }

        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #80bdff;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }
    </style>
</head>
<body>
    <div class="container-scroller">
        <?php include_once('includes/header.php');?>
        <div class="container-fluid page-body-wrapper">
            <?php include_once('includes/sidebar.php');?>
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="row">
                        <div class="col-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Record Student Grades</h4>
                                    <?php if($error) { ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <?php echo $error; ?>
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <?php } ?>
                                    <?php if($msg) { ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <?php echo $msg; ?>
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <?php } ?>
                                    
                                    <form id="gradeForm" method="post" class="form-horizontal mt-4">
                                        <div class="grade-card">
                                            <div class="form-group row">
                                                <label class="col-md-2 control-label">Session</label>
                                                <div class="col-md-10">
                                                    <select name="Session" id="Session" class="form-control select2" required>
                                                        <option value="">-- Select Session --</option>
                                                        <?php
                                                        $sql = "SELECT DISTINCT Session FROM tblclass ORDER BY Session DESC";
                                                        $query = $dbh->prepare($sql);
                                                        $query->execute();
                                                        $results = $query->fetchAll(PDO::FETCH_OBJ);
                                                        if($query->rowCount() > 0) {
                                                            foreach($results as $result) {
                                                                echo '<option value="'.$result->Session.'">'.$result->Session.'</option>';
                                                            }
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="form-group row mt-3">
                                                <label class="col-md-2 control-label">Term</label>
                                                <div class="col-md-10">
                                                    <select name="Term" id="Term" class="form-control select2" required>
                                                        <option value="">-- Select Term --</option>
                                                        <option value="1st Term">1st Term</option>
                                                        <option value="2nd Term">2nd Term</option>
                                                        <option value="3rd Term">3rd Term</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="form-group row mt-3">
                                                <label class="col-md-2 control-label">Class</label>
                                                <div class="col-md-10">
                                                    <select name="ClassID" id="ClassID" class="form-control select2" required>
                                                        <option value="">-- Select Class --</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="form-group row mt-3">
                                                <label class="col-md-2 control-label">Student</label>
                                                <div class="col-md-10">
                                                    <select name="StudentID" id="StudentID" class="form-control select2" required>
                                                        <option value="">-- Select Student --</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="grade-card">
                                            <div class="form-group">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <label>Subject</label>
                                                    <button type="button" class="btn btn-danger btn-sm" onclick="removeSubject(this)">Remove</button>
                                                </div>
                                                <select name="SubjectID[]" class="form-control select2" required>
                                                    <option value="">-- Select Subject --</option>
                                                    <?php
                                                    $sql = "SELECT * FROM tblsubjects ORDER BY SubjectName";
                                                    $query = $dbh->prepare($sql);
                                                    $query->execute();
                                                    $results = $query->fetchAll(PDO::FETCH_OBJ);
                                                    if($query->rowCount() > 0) {
                                                        foreach($results as $result) {
                                                            echo '<option value="'.$result->ID.'">'.$result->SubjectName.'</option>';
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>CA1 Score (20%)</label>
                                                        <input type="number" name="CA1[]" class="form-control" min="0" max="20" step="0.1" required 
                                                               onchange="calculateGrades(this)" onkeyup="calculateGrades(this)">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>CA2 Score (20%)</label>
                                                        <input type="number" name="CA2[]" class="form-control" min="0" max="20" step="0.1" required 
                                                               onchange="calculateGrades(this)" onkeyup="calculateGrades(this)">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Exam Score (60%)</label>
                                                        <input type="number" name="Exam[]" class="form-control" min="0" max="60" step="0.1" required 
                                                               onchange="calculateGrades(this)" onkeyup="calculateGrades(this)">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group row">
                                                <div class="col-md-6">
                                                    <label>Total Test Score (40%)</label>
                                                    <input type="text" class="form-control total-test" readonly>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Total Score (100%)</label>
                                                    <input type="text" class="form-control total-score" readonly>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label>Teacher's Comment</label>
                                                <textarea name="TeacherComment[]" class="form-control" rows="2" required></textarea>
                                            </div>
                                        </div>

                                        <div class="form-group mt-4">
                                            <button type="button" class="btn btn-info" onclick="addSubject()">Add Another Subject</button>
                                            <button type="submit" name="submit" class="btn btn-primary">Save Grades</button>
                                            <button type="reset" class="btn btn-secondary">Reset</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- plugins:js -->
    <script src="vendors/js/vendor.bundle.base.js"></script>
    <script src="vendors/select2/select2.min.js"></script>
    <script>
    $(document).ready(function() {
        // Initialize select2
        initializeSelect2();
        
        // Handle Session and Term change
        $('#Session, #Term').change(function() {
            const session = $('#Session').val();
            const term = $('#Term').val();
            
            if(session && term) {
                // Load classes for selected session and term
                $.ajax({
                    url: 'ajax/get_classes.php',
                    type: 'POST',
                    data: {
                        session: session,
                        term: term
                    },
                    dataType: 'json',
                    success: function(response) {
                        if(response.error) {
                            console.error(response.error);
                            return;
                        }
                        var options = '<option value="">-- Select Class --</option>';
                        response.forEach(function(cls) {
                            options += '<option value="' + cls.ID + '">' + cls.ClassName + ' - ' + cls.Level + '</option>';
                        });
                        $('#ClassID').html(options).trigger('change');
                        $('#StudentID').html('<option value="">-- Select Student --</option>').trigger('change');
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", status, error);
                    }
                });
            } else {
                $('#ClassID').html('<option value="">-- Select Class --</option>').trigger('change');
                $('#StudentID').html('<option value="">-- Select Student --</option>').trigger('change');
            }
        });
        
        // Handle Class change
        $('#ClassID').change(function() {
            var classId = $(this).val();
            var session = $('#Session').val();
            var term = $('#Term').val();
            
            if(classId && session && term) {
                $.ajax({
                    url: 'ajax/get_students.php',
                    type: 'POST',
                    data: {
                        classId: classId,
                        session: session,
                        term: term
                    },
                    dataType: 'json',
                    success: function(response) {
                        if(response.error) {
                            console.error(response.error);
                            return;
                        }
                        var options = '<option value="">-- Select Student --</option>';
                        response.forEach(function(student) {
                            options += '<option value="' + student.ID + '">' + student.StudentName + '</option>';
                        });
                        $('#StudentID').html(options).trigger('change');
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", status, error);
                    }
                });
            } else {
                $('#StudentID').html('<option value="">-- Select Student --</option>').trigger('change');
            }
        });
    });

    // Function to initialize select2
    function initializeSelect2() {
        $('.select2').select2({
            width: '100%',
            placeholder: $(this).data('placeholder'),
            allowClear: true
        });
    }

    // Function to add new subject row
    function addSubject() {
        const template = `
            <div class="grade-card mt-4">
                <div class="form-group">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label>Subject</label>
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeSubject(this)">Remove</button>
                    </div>
                    <select name="SubjectID[]" class="form-control select2" required>
                        <option value="">-- Select Subject --</option>
                        <?php
                        $sql = "SELECT * FROM tblsubjects ORDER BY SubjectName";
                        $query = $dbh->prepare($sql);
                        $query->execute();
                        while($row = $query->fetch(PDO::FETCH_ASSOC)) {
                            echo '<option value="'.$row['ID'].'">'.$row['SubjectName'].'</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group row">
                    <div class="col-md-4">
                        <label>CA1 Score (20%)</label>
                        <input type="number" name="CA1[]" class="form-control" min="0" max="20" step="0.1" required 
                               onchange="calculateGrades(this)" onkeyup="calculateGrades(this)">
                    </div>
                    <div class="col-md-4">
                        <label>CA2 Score (20%)</label>
                        <input type="number" name="CA2[]" class="form-control" min="0" max="20" step="0.1" required 
                               onchange="calculateGrades(this)" onkeyup="calculateGrades(this)">
                    </div>
                    <div class="col-md-4">
                        <label>Exam Score (60%)</label>
                        <input type="number" name="Exam[]" class="form-control" min="0" max="60" step="0.1" required 
                               onchange="calculateGrades(this)" onkeyup="calculateGrades(this)">
                    </div>
                </div>

                <div class="form-group row">
                    <div class="col-md-6">
                        <label>Total Test Score (40%)</label>
                        <input type="text" class="form-control total-test" readonly>
                    </div>
                    <div class="col-md-6">
                        <label>Total Score (100%)</label>
                        <input type="text" class="form-control total-score" readonly>
                    </div>
                </div>

                <div class="form-group">
                    <label>Teacher's Comment</label>
                    <textarea name="TeacherComment[]" class="form-control" rows="2" required></textarea>
                </div>
            </div>
        `;
        
        $('#gradeForm').append(template);
        
        // Initialize select2 for new row
        $('#gradeForm .select2').last().select2({
            width: '100%',
            placeholder: "Select Subject",
            allowClear: true
        });
    }

    // Function to remove subject row
    function removeSubject(button) {
        $(button).closest('.grade-card').remove();
    }

    function calculateGrades(input) {
        const card = $(input).closest('.grade-card');
        
        // Get input values
        const ca1 = parseFloat(card.find('input[name="CA1[]"]').val()) || 0;
        const ca2 = parseFloat(card.find('input[name="CA2[]"]').val()) || 0;
        const exam = parseFloat(card.find('input[name="Exam[]"]').val()) || 0;
        
        // Validate input ranges
        if (ca1 > 20) card.find('input[name="CA1[]"]').val(20);
        if (ca2 > 20) card.find('input[name="CA2[]"]').val(20);
        if (exam > 60) card.find('input[name="Exam[]"]').val(60);
        
        // Calculate scores
        const totalTest = ca1 + ca2; // 40% max (20% + 20%)
        const totalScore = totalTest + exam; // 100% max (40% + 60%)
        
        // Update score displays
        card.find('.total-test').val(totalTest.toFixed(2));
        card.find('.total-score').val(totalScore.toFixed(2));
        
        // Update performance badge
        const badge = card.find('.performance-badge');
        badge.removeClass('excellent very-good good average needs-improvement');
        
        if (totalScore >= 75) {
            badge.addClass('excellent').text('Excellent');
        } else if (totalScore >= 65) {
            badge.addClass('very-good').text('Very Good');
        } else if (totalScore >= 55) {
            badge.addClass('good').text('Good');
        } else if (totalScore >= 45) {
            badge.addClass('average').text('Average');
        } else {
            badge.addClass('needs-improvement').text('Needs Improvement');
        }
    }
    </script>
</body>
</html>
<?php } ?>
