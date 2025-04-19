<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/dbconnection.php');

if (strlen($_SESSION['sturecmsteachid']==0)) {
  header('location:logout.php');
} else {
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Subject Notices</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="css/style.css">
  </head>
  <body>
    <div class="container-scroller">
      <?php include_once('includes/header.php');?>
      <div class="container-fluid page-body-wrapper">
        <?php include_once('includes/sidebar.php');?>
        <div class="main-panel">
          <div class="content-wrapper">
            <div class="row">
              <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    <?php
                    $teacherId = $_SESSION['sturecmsteachid'];
                    $subjectId = isset($_GET['subject']) ? intval($_GET['subject']) : 0;
                    $classId = isset($_GET['class']) ? intval($_GET['class']) : 0;
                    
                    // Verify teacher has access to this subject and class
                    $sql = "SELECT s.*, c.ClassName, c.Section 
                           FROM tblsubjects s
                           JOIN tblsubjectteacherclass stc ON s.ID = stc.SubjectID
                           JOIN tblclass c ON stc.ClassID = c.ID
                           WHERE s.ID = :subjectId 
                           AND c.ID = :classId
                           AND stc.TeacherID = :teacherId";
                    $query = $dbh->prepare($sql);
                    $query->bindParam(':subjectId', $subjectId, PDO::PARAM_INT);
                    $query->bindParam(':classId', $classId, PDO::PARAM_INT);
                    $query->bindParam(':teacherId', $teacherId, PDO::PARAM_INT);
                    $query->execute();
                    $subjectInfo = $query->fetch(PDO::FETCH_OBJ);
                    
                    if($subjectInfo) {
                    ?>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                      <div>
                        <h4 class="card-title mb-0">
                          <?php echo htmlentities($subjectInfo->SubjectName);?> Notices
                        </h4>
                        <small class="text-muted">
                          Class: <?php echo htmlentities($subjectInfo->ClassName . ' ' . $subjectInfo->Section);?>
                        </small>
                      </div>
                      <div>
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addNoticeModal">
                          <i class="icon-plus"></i> Add Notice
                        </button>
                        <a href="my-schedule.php" class="btn btn-secondary ml-2">Back to Schedule</a>
                      </div>
                    </div>
                    
                    <?php if(isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php 
                            echo htmlentities($_SESSION['success']); 
                            unset($_SESSION['success']);
                            ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="table-responsive">
                      <table class="table">
                        <thead>
                          <tr>
                            <th>Date</th>
                            <th>Title</th>
                            <th>Message</th>
                            <th>Actions</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php
                          $sql = "SELECT * FROM tblnotice 
                                 WHERE SubjectID = :subjectId 
                                 AND ClassID = :classId
                                 ORDER BY NoticeDate DESC";
                          $query = $dbh->prepare($sql);
                          $query->bindParam(':subjectId', $subjectId, PDO::PARAM_INT);
                          $query->bindParam(':classId', $classId, PDO::PARAM_INT);
                          $query->execute();
                          $notices = $query->fetchAll(PDO::FETCH_OBJ);
                          
                          if($query->rowCount() > 0) {
                              foreach($notices as $notice) {
                          ?>
                          <tr>
                            <td><?php echo date('d M Y', strtotime($notice->NoticeDate));?></td>
                            <td><?php echo htmlentities($notice->NoticeTitle);?></td>
                            <td><?php echo htmlentities($notice->NoticeMessage);?></td>
                            <td>
                              <button type="button" class="btn btn-info btn-sm" 
                                      onclick="editNotice(<?php echo $notice->ID;?>)">
                                Edit
                              </button>
                              <a href="delete-notice.php?id=<?php echo $notice->ID;?>" 
                                 class="btn btn-danger btn-sm"
                                 onclick="return confirm('Are you sure you want to delete this notice?');">
                                Delete
                              </a>
                            </td>
                          </tr>
                          <?php }
                          } else { ?>
                          <tr>
                            <td colspan="4" class="text-center">No notices found</td>
                          </tr>
                          <?php } ?>
                        </tbody>
                      </table>
                    </div>
                    
                    <!-- Add Notice Modal -->
                    <div class="modal fade" id="addNoticeModal" tabindex="-1" role="dialog">
                      <div class="modal-dialog" role="document">
                        <div class="modal-content">
                          <form method="post" action="add-subject-notice.php">
                            <div class="modal-header">
                              <h5 class="modal-title">Add New Notice</h5>
                              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                              </button>
                            </div>
                            <div class="modal-body">
                              <input type="hidden" name="subjectId" value="<?php echo $subjectId;?>">
                              <input type="hidden" name="classId" value="<?php echo $classId;?>">
                              <div class="form-group">
                                <label>Notice Title</label>
                                <input type="text" name="noticeTitle" class="form-control" required>
                              </div>
                              <div class="form-group">
                                <label>Notice Message</label>
                                <textarea name="noticeMessage" class="form-control" rows="4" required></textarea>
                              </div>
                              <div class="form-group">
                                <label>Notice Date</label>
                                <input type="date" name="noticeDate" class="form-control" 
                                       value="<?php echo date('Y-m-d');?>" required>
                              </div>
                            </div>
                            <div class="modal-footer">
                              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                              <button type="submit" name="submit" class="btn btn-primary">Add Notice</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
                    
                    <?php } else { ?>
                    <div class="alert alert-warning">
                      <h4 class="alert-heading">Access Denied</h4>
                      <p>You are not authorized to manage notices for this subject and class.</p>
                      <hr>
                      <a href="my-schedule.php" class="btn btn-secondary">Back to Schedule</a>
                    </div>
                    <?php } ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <?php include_once('includes/footer.php');?>
        </div>
      </div>
    </div>
    <script src="vendors/js/vendor.bundle.base.js"></script>
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
  </body>
</html>
<?php } ?>
