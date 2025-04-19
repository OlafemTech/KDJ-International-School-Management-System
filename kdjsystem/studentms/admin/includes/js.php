<!-- plugins:js -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- endinject -->
<!-- Custom js for this page-->
<script>
$(document).ready(function() {
    // Handle class name change - Special case for PG class
    $('#className').change(function() {
        const levelSelect = $('#level');
        const selectedClass = $(this).val();
        
        if (selectedClass === 'PG') {
            // For PG class, level must be PG
            levelSelect.html('<option value="PG" selected>PG</option>');
            levelSelect.prop('disabled', true);
        } else if (selectedClass) {
            // For other classes, show levels 1-5
            levelSelect.html(`
                <option value="">Choose Level</option>
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
                <option value="5">5</option>
            `);
            levelSelect.prop('disabled', false);
        } else {
            // When no class is selected
            levelSelect.html('<option value="">Choose Level</option>');
            levelSelect.prop('disabled', false);
        }

        // Clear any previous validation errors
        levelSelect.removeClass('is-invalid');
        levelSelect.next('.invalid-feedback').remove();
    });

    // Session format validation (YYYY/YYYY with consecutive years)
    $('#session').on('input', function() {
        const value = $(this).val();
        const sessionPattern = /^\d{4}\/\d{4}$/;
        
        if (sessionPattern.test(value)) {
            const years = value.split('/');
            const year1 = parseInt(years[0]);
            const year2 = parseInt(years[1]);
            
            if (year2 - year1 !== 1) {
                $(this).addClass('is-invalid');
                if (!$(this).next('.invalid-feedback').length) {
                    $(this).after('<div class="invalid-feedback">Years must be consecutive (e.g., 2024/2025)</div>');
                }
            } else {
                $(this).removeClass('is-invalid');
                $(this).next('.invalid-feedback').remove();
            }
        } else if (value.length > 0) {
            $(this).addClass('is-invalid');
            if (!$(this).next('.invalid-feedback').length) {
                $(this).after('<div class="invalid-feedback">Please use YYYY/YYYY format</div>');
            }
        }
    });

    // Form validation before submit
    $('.forms-sample').on('submit', function(e) {
        const className = $('#className').val();
        const level = $('#level').val();
        const session = $('#session').val();
        const term = $('#term').val();
        let isValid = true;

        // Clear previous validation states
        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').remove();

        // 1. Validate all fields are required
        if (!className) {
            $('#className').addClass('is-invalid');
            $('#className').after('<div class="invalid-feedback">Please select a class</div>');
            isValid = false;
        }

        if (!level) {
            $('#level').addClass('is-invalid');
            $('#level').after('<div class="invalid-feedback">Please select a level</div>');
            isValid = false;
        }

        if (!session) {
            $('#session').addClass('is-invalid');
            $('#session').after('<div class="invalid-feedback">Please enter a session</div>');
            isValid = false;
        }

        if (!term) {
            $('#term').addClass('is-invalid');
            $('#term').after('<div class="invalid-feedback">Please select a term</div>');
            isValid = false;
        }

        // 2. Validate Class Name options
        const validClassNames = ['SS', 'JS', 'Basic', 'Nursery', 'PG'];
        if (className && !validClassNames.includes(className)) {
            $('#className').addClass('is-invalid');
            $('#className').after('<div class="invalid-feedback">Invalid class name selected</div>');
            isValid = false;
        }

        // 3. Validate Level options (1-5 or PG)
        if (className === 'PG' && level !== 'PG') {
            $('#level').addClass('is-invalid');
            $('#level').after('<div class="invalid-feedback">For PG class, level must be PG</div>');
            isValid = false;
        } else if (className !== 'PG' && !['1', '2', '3', '4', '5'].includes(level)) {
            $('#level').addClass('is-invalid');
            $('#level').after('<div class="invalid-feedback">Level must be between 1 and 5</div>');
            isValid = false;
        }

        // 4. Validate Session format and consecutive years
        const sessionPattern = /^\d{4}\/\d{4}$/;
        if (session) {
            if (!sessionPattern.test(session)) {
                $('#session').addClass('is-invalid');
                $('#session').after('<div class="invalid-feedback">Please use YYYY/YYYY format</div>');
                isValid = false;
            } else {
                const years = session.split('/');
                const year1 = parseInt(years[0]);
                const year2 = parseInt(years[1]);
                if (year2 - year1 !== 1) {
                    $('#session').addClass('is-invalid');
                    $('#session').after('<div class="invalid-feedback">Years must be consecutive (e.g., 2024/2025)</div>');
                    isValid = false;
                }
            }
        }

        // 5. Validate Term options
        const validTerms = ['First', 'Second', 'Third'];
        if (term && !validTerms.includes(term)) {
            $('#term').addClass('is-invalid');
            $('#term').after('<div class="invalid-feedback">Invalid term selected</div>');
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
            return false;
        }
    });

    // Subject form validation
    if ($('.forms-sample').length > 0) {
        // Convert subject code to uppercase as user types
        $('#subjectCode').on('input', function() {
            this.value = this.value.toUpperCase();
        });

        // Validate subject code format
        $('#subjectCode').on('input', function() {
            const value = $(this).val();
            const codePattern = /^[A-Z0-9]+$/;
            
            if (value.length > 0) {
                if (!codePattern.test(value)) {
                    $(this).addClass('is-invalid');
                    if (!$(this).next('.invalid-feedback').length) {
                        $(this).after('<div class="invalid-feedback">Only letters and numbers allowed</div>');
                    }
                } else if (value.length > 20) {
                    $(this).addClass('is-invalid');
                    if (!$(this).next('.invalid-feedback').length) {
                        $(this).after('<div class="invalid-feedback">Subject code cannot exceed 20 characters</div>');
                    }
                } else {
                    $(this).removeClass('is-invalid');
                    $(this).next('.invalid-feedback').remove();
                }
            }
        });

        // Validate subject name length
        $('#subjectName').on('input', function() {
            const value = $(this).val();
            
            if (value.length > 100) {
                $(this).addClass('is-invalid');
                if (!$(this).next('.invalid-feedback').length) {
                    $(this).after('<div class="invalid-feedback">Subject name cannot exceed 100 characters</div>');
                }
            } else {
                $(this).removeClass('is-invalid');
                $(this).next('.invalid-feedback').remove();
            }
        });

        // Form submit validation
        $('.forms-sample').on('submit', function(e) {
            const subjectName = $('#subjectName').val();
            const subjectCode = $('#subjectCode').val();
            const classId = $('#classId').val();
            let isValid = true;

            // Clear previous validation states
            $('.form-control').removeClass('is-invalid');
            $('.invalid-feedback').remove();

            // Validate Subject Name
            if (!subjectName) {
                $('#subjectName').addClass('is-invalid');
                $('#subjectName').after('<div class="invalid-feedback">Please enter subject name</div>');
                isValid = false;
            } else if (subjectName.length > 100) {
                $('#subjectName').addClass('is-invalid');
                $('#subjectName').after('<div class="invalid-feedback">Subject name cannot exceed 100 characters</div>');
                isValid = false;
            }

            // Validate Subject Code
            if (!subjectCode) {
                $('#subjectCode').addClass('is-invalid');
                $('#subjectCode').after('<div class="invalid-feedback">Please enter subject code</div>');
                isValid = false;
            } else {
                const codePattern = /^[A-Z0-9]+$/;
                if (!codePattern.test(subjectCode)) {
                    $('#subjectCode').addClass('is-invalid');
                    $('#subjectCode').after('<div class="invalid-feedback">Only letters and numbers allowed</div>');
                    isValid = false;
                } else if (subjectCode.length > 20) {
                    $('#subjectCode').addClass('is-invalid');
                    $('#subjectCode').after('<div class="invalid-feedback">Subject code cannot exceed 20 characters</div>');
                    isValid = false;
                }
            }

            // Validate Class
            if (!classId) {
                $('#classId').addClass('is-invalid');
                $('#classId').after('<div class="invalid-feedback">Please select a class</div>');
                isValid = false;
            }

            // Validate Teacher if selected
            const teacherId = $('#teacherId').val();
            if (teacherId && !Number.isInteger(parseInt(teacherId))) {
                $('#teacherId').addClass('is-invalid');
                $('#teacherId').after('<div class="invalid-feedback">Invalid teacher selected</div>');
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
                return false;
            }
        });
    }
});
</script>
<!-- End custom js for this page-->
