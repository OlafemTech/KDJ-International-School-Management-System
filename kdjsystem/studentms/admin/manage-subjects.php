<?php 
if(session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(0);
include('includes/dbconnection.php');

if(strlen($_SESSION['sturecmsaid'])==0) {
    header('location:logout.php');
} else {
    // Handle subject deletion
    if(isset($_GET['delid'])) {
        $rid=intval($_GET['delid']);
        $sql="delete from tblsubjects where ID=:rid";
        $query=$dbh->prepare($sql);
        $query->bindParam(':rid',$rid,PDO::PARAM_STR);
        $query->execute();
        echo "<script>alert('Data deleted');</script>"; 
        echo "<script>window.location.href = 'manage-subjects.php'</script>";
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Student Management System | Manage Subjects</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="vendors/datatables.net-bs4/dataTables.bootstrap4.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/custom-header.css">
    <link rel="stylesheet" href="css/custom-buttons.css">
    <style>
        .class-header {
            background: linear-gradient(to right, #f8f9ff, #fff);
            cursor: pointer;
            transition: background-color 0.3s ease;
            border-left: 4px solid #6c7ae0;
        }
        .class-header:hover {
            background: linear-gradient(to right, #f0f2ff, #fff);
        }
        .class-header .header-content {
            display: flex;
            align-items: center;
            padding: 1rem;
            gap: 1rem;
        }
        .class-header .toggle-icon {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c7ae0;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            flex-shrink: 0;
        }
        .class-header.collapsed .toggle-icon {
            transform: rotate(-90deg);
        }
        .class-header .class-name {
            font-weight: 600;
            color: #2a2a2a;
            flex: 1;
            font-size: 1.1em;
        }
        .class-header .subject-count {
            background-color: #6c7ae0;
            color: white;
            padding: 0.3rem 1rem;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
        }
        .subject-group {
            background: white;
        }
        .subject-row {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
        }
        .subject-row.hidden {
            display: none;
        }
        .expand-collapse-all {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border: 2px solid #6c7ae0;
            border-radius: 6px;
            color: #6c7ae0;
            background: transparent;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .expand-collapse-all:hover {
            background: #6c7ae0;
            color: white;
        }
        .expand-collapse-all .toggle-icon {
            transition: transform 0.3s ease;
        }
        .expand-collapse-all.collapsed .toggle-icon {
            transform: rotate(-90deg);
        }
        .info-text {
            color: #666;
            font-size: 0.9em;
            margin-left: 1rem;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeOut {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-10px); }
        }
        .fade-enter {
            animation: fadeIn 0.3s ease forwards;
        }
        .fade-exit {
            animation: fadeOut 0.3s ease forwards;
        }
    </style>
</head>
<body>
    <div class="container-scroller">
        <?php include('includes/header.php');?>
        <div class="container-fluid page-body-wrapper">
            <?php include('includes/sidebar.php');?>
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="row">
                        <div class="col-md-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-sm-flex align-items-center mb-4">
                                        <h4 class="card-title mb-sm-0">Manage Subjects</h4>
                                        <div class="header-controls">
                                            <button type="button" class="expand-collapse-all">
                                                <i class="icon-arrow-down toggle-icon"></i>
                                                <span class="button-text">Collapse All</span>
                                            </button>
                                            <div class="info-text">
                                                <i class="icon-info"></i>
                                                Subjects are grouped by class level
                                            </div>
                                        </div>
                                        <a href="add-subject.php" class="btn btn-action btn-edit ml-auto mb-3 mb-sm-0">
                                            <i class="icon-plus"></i> Add New Subject
                                        </a>
                                    </div>
                                    <div class="table-container">
                                        <table id="example" class="table">
                                            <thead>
                                                <tr>
                                                    <th>S.No</th>
                                                    <th>Subject Name</th>
                                                    <th>Subject Code</th>
                                                    <th>Class Name</th>
                                                    <th>Level</th>
                                                    <th>Session</th>
                                                    <th>Term</th>
                                                    <th>Teacher Name</th>
                                                    <th>Creation Date</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $sql = "SELECT s.*, c.ClassName, c.Level, c.Session, c.Term, 
                                                        COALESCE(t.FullName, 'Not Assigned') as TeacherName 
                                                        FROM tblsubjects s
                                                        LEFT JOIN tblclass c ON s.ClassID = c.ID 
                                                        LEFT JOIN tblteacher t ON s.TeacherID = t.ID 
                                                        ORDER BY 
                                                        CASE c.ClassName 
                                                            WHEN 'SS' THEN 1 
                                                            WHEN 'JS' THEN 2 
                                                            WHEN 'Basic' THEN 3 
                                                            WHEN 'Nursery' THEN 4 
                                                            WHEN 'PG' THEN 5 
                                                        END,
                                                        c.Level ASC,
                                                        s.SubjectName ASC";
                                                $query = $dbh->prepare($sql);
                                                $query->execute();
                                                $results = $query->fetchAll(PDO::FETCH_OBJ);
                                                $cnt = 1;

                                                if($query->rowCount() > 0) {
                                                    $currentClass = '';
                                                    foreach($results as $row) {
                                                        $classKey = $row->ClassName . $row->Level;
                                                        
                                                        // Add class header if it's a new class
                                                        if($currentClass !== $classKey) {
                                                            $currentClass = $classKey;
                                                            
                                                            // Count subjects in this class
                                                            $subjectCount = 0;
                                                            foreach($results as $countRow) {
                                                                if($countRow->ClassName . $countRow->Level === $classKey) {
                                                                    $subjectCount++;
                                                                }
                                                            }
                                                    ?>
                                                    <tr class="class-header" data-class="<?php echo htmlentities($classKey); ?>">
                                                        <td colspan="10">
                                                            <div class="header-content">
                                                                <i class="icon-arrow-down toggle-icon"></i>
                                                                <span class="class-name">
                                                                    <?php 
                                                                    $classNames = array(
                                                                        'SS' => 'Senior Secondary',
                                                                        'JS' => 'Junior Secondary',
                                                                        'Basic' => 'Basic',
                                                                        'Nursery' => 'Nursery',
                                                                        'PG' => 'Play Group'
                                                                    );
                                                                    echo $classNames[$row->ClassName] . ' ' . $row->Level;
                                                                    ?>
                                                                </span>
                                                                <span class="subject-count"><?php echo $subjectCount; ?> subjects</span>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                        }
                                                    ?>
                                                    <tr class="subject-row" data-class="<?php echo htmlentities($classKey); ?>">
                                                        <td><?php echo htmlentities($cnt);?></td>
                                                        <td><?php echo htmlentities($row->SubjectName);?></td>
                                                        <td><?php echo htmlentities($row->SubjectCode);?></td>
                                                        <td><?php echo htmlentities($row->ClassName);?></td>
                                                        <td><?php echo htmlentities($row->Level);?></td>
                                                        <td><?php echo htmlentities($row->Session);?></td>
                                                        <td><?php echo htmlentities($row->Term);?></td>
                                                        <td><?php echo htmlentities($row->TeacherName);?></td>
                                                        <td><?php echo htmlentities($row->CreationDate);?></td>
                                                        <td>
                                                            <a href="edit-subject.php?editid=<?php echo htmlentities($row->ID);?>" class="btn btn-primary btn-sm" title="Edit Subject">
                                                                <i class="icon-pencil"></i>
                                                            </a>
                                                            <a href="manage-subjects.php?delid=<?php echo htmlentities($row->ID);?>" onclick="return confirm('Do you really want to delete this subject?');" class="btn btn-danger btn-sm" title="Delete Subject">
                                                                <i class="icon-trash"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <?php 
                                                        $cnt++;
                                                    }
                                                } else {
                                                    echo '<tr><td colspan="10" class="text-center">No subjects found</td></tr>';
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="vendors/js/vendor.bundle.base.js"></script>
    <script src="vendors/datatables.net/jquery.dataTables.js"></script>
    <script src="vendors/datatables.net-bs4/dataTables.bootstrap4.js"></script>
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
    <script>
        class SubjectManager {
            constructor() {
                this.collapsedState = new Map();
                this.init();
            }

            init() {
                this.setupEventListeners();
                this.loadSavedState();
                this.updateUI();
            }

            setupEventListeners() {
                // Class header click events
                document.querySelectorAll('.class-header').forEach(header => {
                    header.addEventListener('click', (e) => this.handleHeaderClick(e));
                });

                // Expand/Collapse all button
                const expandCollapseBtn = document.querySelector('.expand-collapse-all');
                if (expandCollapseBtn) {
                    expandCollapseBtn.addEventListener('click', (e) => this.handleExpandCollapseAll(e));
                }

                // Handle search input
                const searchInput = document.querySelector('#searchSubjects');
                if (searchInput) {
                    searchInput.addEventListener('input', (e) => this.handleSearch(e));
                }
            }

            handleHeaderClick(e) {
                const header = e.currentTarget;
                const classKey = header.dataset.class;
                const isCollapsed = header.classList.contains('collapsed');

                this.toggleSection(classKey, !isCollapsed);
                this.saveState();
                this.updateExpandCollapseButton();
            }

            toggleSection(classKey, collapse) {
                const header = document.querySelector(`.class-header[data-class="${classKey}"]`);
                const rows = document.querySelectorAll(`.subject-row[data-class="${classKey}"]`);
                
                if (collapse) {
                    header.classList.add('collapsed');
                    this.collapsedState.set(classKey, true);
                    rows.forEach(row => {
                        row.classList.add('fade-exit');
                        row.addEventListener('animationend', () => {
                            row.classList.add('hidden');
                            row.classList.remove('fade-exit');
                        }, { once: true });
                    });
                } else {
                    header.classList.remove('collapsed');
                    this.collapsedState.set(classKey, false);
                    rows.forEach(row => {
                        row.classList.remove('hidden');
                        row.classList.add('fade-enter');
                        row.addEventListener('animationend', () => {
                            row.classList.remove('fade-enter');
                        }, { once: true });
                    });
                }
            }

            handleExpandCollapseAll(e) {
                e.preventDefault();
                const btn = e.currentTarget;
                const allCollapsed = btn.classList.contains('collapsed');
                
                document.querySelectorAll('.class-header').forEach(header => {
                    const classKey = header.dataset.class;
                    this.toggleSection(classKey, !allCollapsed);
                });

                btn.classList.toggle('collapsed');
                this.saveState();
                this.updateExpandCollapseButton();
            }

            handleSearch(e) {
                const searchTerm = e.target.value.toLowerCase();
                let hasResults = false;

                document.querySelectorAll('.subject-row').forEach(row => {
                    const text = row.textContent.toLowerCase();
                    const matches = text.includes(searchTerm);
                    row.style.display = matches ? '' : 'none';
                    if (matches) hasResults = true;
                });

                // Show/hide no results message
                const noResults = document.querySelector('.no-results');
                if (noResults) {
                    noResults.style.display = hasResults ? 'none' : 'block';
                }

                // Update subject counts
                this.updateSubjectCounts();
            }

            updateSubjectCounts() {
                document.querySelectorAll('.class-header').forEach(header => {
                    const classKey = header.dataset.class;
                    const visibleSubjects = document.querySelectorAll(`.subject-row[data-class="${classKey}"]:not([style*="display: none"])`).length;
                    const countElement = header.querySelector('.subject-count');
                    if (countElement) {
                        countElement.textContent = `${visibleSubjects} subject${visibleSubjects !== 1 ? 's' : ''}`;
                    }
                });
            }

            updateExpandCollapseButton() {
                const btn = document.querySelector('.expand-collapse-all');
                if (!btn) return;

                const allCollapsed = Array.from(document.querySelectorAll('.class-header'))
                    .every(header => header.classList.contains('collapsed'));

                btn.classList.toggle('collapsed', allCollapsed);
                btn.querySelector('.button-text').textContent = allCollapsed ? 'Expand All' : 'Collapse All';
            }

            saveState() {
                const state = {};
                this.collapsedState.forEach((value, key) => {
                    state[key] = value;
                });
                localStorage.setItem('subjectManagerState', JSON.stringify(state));
            }

            loadSavedState() {
                try {
                    const savedState = JSON.parse(localStorage.getItem('subjectManagerState')) || {};
                    Object.entries(savedState).forEach(([classKey, isCollapsed]) => {
                        this.collapsedState.set(classKey, isCollapsed);
                    });
                } catch (e) {
                    console.error('Error loading saved state:', e);
                }
            }

            updateUI() {
                this.collapsedState.forEach((isCollapsed, classKey) => {
                    if (isCollapsed) {
                        const header = document.querySelector(`.class-header[data-class="${classKey}"]`);
                        const rows = document.querySelectorAll(`.subject-row[data-class="${classKey}"]`);
                        if (header) header.classList.add('collapsed');
                        rows.forEach(row => row.classList.add('hidden'));
                    }
                });
                this.updateExpandCollapseButton();
                this.updateSubjectCounts();
            }
        }

        // Initialize the subject manager when the document is ready
        document.addEventListener('DOMContentLoaded', () => {
            const subjectManager = new SubjectManager();
            
            // Initialize tooltips if Bootstrap is present
            if (typeof $().tooltip === 'function') {
                $('[data-toggle="tooltip"]').tooltip();
            }

            // Handle deletion animations
            document.querySelectorAll('.delete-subject').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const link = e.currentTarget.href;
                    
                    if (confirm('Are you sure you want to delete this subject?')) {
                        const row = e.currentTarget.closest('tr');
                        row.classList.add('fade-exit');
                        row.addEventListener('animationend', () => {
                            window.location.href = link;
                        }, { once: true });
                    }
                });
            });
        });
    </script>
</body>
</html>
<?php } ?>
