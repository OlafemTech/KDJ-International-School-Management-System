<nav class="sidebar sidebar-offcanvas" id="sidebar">
          <ul class="nav">
            <li class="nav-item nav-profile">
              <a href="#" class="nav-link">
                <div class="profile-image">
                  <?php
                  $aid = $_SESSION['sturecmsaid'];
                  $sql = "SELECT * from tbladmin where ID=:aid";
                  $query = $dbh->prepare($sql);
                  $query->bindParam(':aid', $aid, PDO::PARAM_STR);
                  $query->execute();
                  $admin = $query->fetch(PDO::FETCH_OBJ);
                  ?>
                  <img class="img-xs rounded-circle" src="<?php echo !empty($admin->Avatar) ? 'images/avatars/'.$admin->Avatar : 'images/avatars/default-avatar.png'; ?>" alt="profile image">
                  <div class="dot-indicator bg-success"></div>
                </div>
                <div class="text-wrapper">
                  <p class="profile-name"><?php echo htmlentities($admin->AdminName); ?></p>
                  <p class="designation"><?php echo htmlentities($admin->Email); ?></p>
                </div>
              </a>
            </li>
            <li class="nav-item nav-category">
              <span class="nav-link">Main Menu</span>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="dashboard.php">
                <span class="menu-title">Dashboard</span>
                <i class="mdi mdi-view-dashboard menu-icon"></i>
              </a>
            </li>
            
            <li class="nav-item">
              <a class="nav-link" data-toggle="collapse" href="#ui-basic" aria-expanded="false" aria-controls="ui-basic">
                <span class="menu-title">Class Management</span>
                <i class="mdi mdi-school menu-icon"></i>
              </a>
              <div class="collapse" id="ui-basic">
                <ul class="nav flex-column sub-menu">
                  <li class="nav-item">
                    <a class="nav-link" href="add-class.php">
                      <i class="mdi mdi-plus-circle-outline menu-icon"></i>
                      <span>Add Class</span>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" href="manage-class.php">
                      <i class="mdi mdi-format-list-bulleted menu-icon"></i>
                      <span>Manage Classes</span>
                    </a>
                  </li>
                </ul>
              </div>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-toggle="collapse" href="#ui-basic1" aria-expanded="false" aria-controls="ui-basic1">
                <span class="menu-title">Students Management</span>
                <i class="mdi mdi-account-multiple menu-icon"></i>
              </a>
              <div class="collapse" id="ui-basic1">
                <ul class="nav flex-column sub-menu">
                  <li class="nav-item">
                    <a class="nav-link" href="add-students.php">
                      <i class="mdi mdi-account-plus menu-icon"></i>
                      <span>Add Student</span>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" href="manage-students.php">
                      <i class="mdi mdi-account-settings menu-icon"></i>
                      <span>Manage Students</span>
                    </a>
                  </li>
                </ul>
              </div>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-toggle="collapse" href="#teachers" aria-expanded="false" aria-controls="teachers">
                <span class="menu-title">Teachers Management</span>
                <i class="mdi mdi-teach menu-icon"></i>
              </a>
              <div class="collapse" id="teachers">
                <ul class="nav flex-column sub-menu">
                  <li class="nav-item">
                    <a class="nav-link" href="add-teacher.php">
                      <i class="mdi mdi-account-plus menu-icon"></i>
                      <span>Add Teacher</span>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" href="manage-teachers.php">
                      <i class="mdi mdi-account-settings menu-icon"></i>
                      <span>Manage Teachers</span>
                    </a>
                  </li>
                </ul>
              </div>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-toggle="collapse" href="#subjects" aria-expanded="false" aria-controls="subjects">
                <span class="menu-title">Subjects Management</span>
                <i class="mdi mdi-book-open-page-variant menu-icon"></i>
              </a>
              <div class="collapse" id="subjects">
                <ul class="nav flex-column sub-menu">
                  <li class="nav-item">
                    <a class="nav-link" href="add-subject.php">
                      <i class="mdi mdi-plus-circle-outline menu-icon"></i>
                      <span>Add Subject</span>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" href="manage-subjects.php">
                      <i class="mdi mdi-format-list-bulleted menu-icon"></i>
                      <span>Manage Subjects</span>
                    </a>
                  </li>
                </ul>
              </div>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-toggle="collapse" href="#grades" aria-expanded="false" aria-controls="grades">
                <span class="menu-title">Grade Management</span>
                <i class="mdi mdi-chart-bar menu-icon"></i>
              </a>
              <div class="collapse" id="grades">
                <ul class="nav flex-column sub-menu">
                  <li class="nav-item">
                    <a class="nav-link" href="record-grades.php">
                      <i class="mdi mdi-pencil menu-icon"></i>
                      <span>Record Grades</span>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" href="view-grades.php">
                      <i class="mdi mdi-view-list menu-icon"></i>
                      <span>View Grades</span>
                    </a>
                  </li>
                </ul>
              </div>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="search.php">
                <span class="menu-title">Search</span>
                <i class="mdi mdi-magnify menu-icon"></i>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-toggle="collapse" href="#hw" aria-expanded="false" aria-controls="hw">
                <span class="menu-title">Homework</span>
                <i class="mdi mdi-clipboard-text menu-icon"></i>
              </a>
              <div class="collapse" id="hw">
                <ul class="nav flex-column sub-menu">
                  <li class="nav-item">
                    <a class="nav-link" href="add-homework.php">
                      <i class="mdi mdi-plus-circle-outline menu-icon"></i>
                      <span>Add Homework</span>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" href="manage-homeworks.php">
                      <i class="mdi mdi-format-list-bulleted menu-icon"></i>
                      <span>Manage Homework</span>
                    </a>
                  </li>
                </ul>
              </div>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-toggle="collapse" href="#auth" aria-expanded="false" aria-controls="auth">
                <span class="menu-title">Notice Board</span>
                <i class="mdi mdi-bulletin-board menu-icon"></i>
              </a>
              <div class="collapse" id="auth">
                <ul class="nav flex-column sub-menu">
                  <li class="nav-item">
                    <a class="nav-link" href="add-notice.php">
                      <i class="mdi mdi-plus-circle-outline menu-icon"></i>
                      <span>Add Notice</span>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" href="manage-notice.php">
                      <i class="mdi mdi-format-list-bulleted menu-icon"></i>
                      <span>Manage Notices</span>
                    </a>
                  </li>
                </ul>
              </div>
            </li>
          </ul>
        </nav>
        <style>
          .sidebar {
            background: #000;
            color: #fff;
          }
          
          .sidebar .nav .nav-item .nav-link {
            color: #fff;
          }
          
          .sidebar .nav .nav-item .nav-link:hover {
            background: #333;
          }
          
          .sidebar .nav .nav-item.active .nav-link {
            background: #333;
            color: whitesmoke;
          }
          
          .sidebar .nav .nav-item .nav-link i.menu-icon {
            color: whitesmoke;
          }
          
          .sidebar .nav .nav-item.nav-profile .nav-link .profile-name {
            color: #fff;
          }
          
          .sidebar .nav .nav-item.nav-profile .nav-link .designation {
            color: #ccc;
          }
          
          .sidebar .nav .nav-item.nav-category {
            color: whitesmoke;
          }
          
          .sidebar .nav .nav-item .collapse .nav.sub-menu {
            background: #000;
          }
          
          .sidebar .nav .nav-item .collapse .nav.sub-menu .nav-item .nav-link {
            color: #fff;
          }
          
          .sidebar .nav .nav-item .collapse .nav.sub-menu .nav-item .nav-link:hover {
            color: whitesmoke;
            background: #333;
          }
          
          .sidebar .nav .nav-item .collapse .nav.sub-menu .nav-item .nav-link.active {
            color: whitesmoke;
            background: #333;
          }
        </style>