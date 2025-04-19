<?php
function getHomeworkNotifications($dbh, $studentId) {
    try {
        // Get student's class
        $sql = "SELECT StudentClass FROM tblstudent WHERE ID = :studentId";
        $query = $dbh->prepare($sql);
        $query->bindParam(':studentId', $studentId, PDO::PARAM_INT);
        $query->execute();
        $classId = $query->fetch(PDO::FETCH_OBJ)->StudentClass;
        
        $notifications = [];
        
        // Get upcoming homework (due in next 7 days)
        $sql = "SELECT h.ID, h.Title, h.SubmissionDate, s.SubjectName,
               DATEDIFF(h.SubmissionDate, CURDATE()) as DaysLeft
               FROM tblhomework h
               JOIN tblsubjects s ON h.SubjectID = s.ID
               LEFT JOIN tblhomeworksubmissions hs ON h.ID = hs.HomeworkID 
               AND hs.StudentID = :studentId
               WHERE h.ClassID = :classId
               AND h.SubmissionDate >= CURDATE()
               AND h.SubmissionDate <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
               AND hs.ID IS NULL
               ORDER BY h.SubmissionDate ASC";
        $query = $dbh->prepare($sql);
        $query->bindParam(':studentId', $studentId, PDO::PARAM_INT);
        $query->bindParam(':classId', $classId, PDO::PARAM_INT);
        $query->execute();
        $upcomingHomework = $query->fetchAll(PDO::FETCH_OBJ);
        
        foreach($upcomingHomework as $hw) {
            $notifications[] = [
                'type' => 'upcoming',
                'title' => $hw->Title,
                'subject' => $hw->SubjectName,
                'daysLeft' => $hw->DaysLeft,
                'dueDate' => $hw->SubmissionDate,
                'message' => sprintf(
                    '%s homework "%s" is due in %d days',
                    $hw->SubjectName,
                    $hw->Title,
                    $hw->DaysLeft
                ),
                'link' => 'submit-homework.php?id=' . $hw->ID
            ];
        }
        
        // Get overdue homework
        $sql = "SELECT h.ID, h.Title, h.SubmissionDate, s.SubjectName,
               DATEDIFF(CURDATE(), h.SubmissionDate) as DaysOverdue
               FROM tblhomework h
               JOIN tblsubjects s ON h.SubjectID = s.ID
               LEFT JOIN tblhomeworksubmissions hs ON h.ID = hs.HomeworkID 
               AND hs.StudentID = :studentId
               WHERE h.ClassID = :classId
               AND h.SubmissionDate < CURDATE()
               AND hs.ID IS NULL
               ORDER BY h.SubmissionDate DESC";
        $query = $dbh->prepare($sql);
        $query->bindParam(':studentId', $studentId, PDO::PARAM_INT);
        $query->bindParam(':classId', $classId, PDO::PARAM_INT);
        $query->execute();
        $overdueHomework = $query->fetchAll(PDO::FETCH_OBJ);
        
        foreach($overdueHomework as $hw) {
            $notifications[] = [
                'type' => 'overdue',
                'title' => $hw->Title,
                'subject' => $hw->SubjectName,
                'daysOverdue' => $hw->DaysOverdue,
                'dueDate' => $hw->SubmissionDate,
                'message' => sprintf(
                    '%s homework "%s" is overdue by %d days',
                    $hw->SubjectName,
                    $hw->Title,
                    $hw->DaysOverdue
                ),
                'link' => 'submit-homework.php?id=' . $hw->ID
            ];
        }
        
        // Get recently graded homework
        $sql = "SELECT h.ID, h.Title, s.SubjectName, hs.Grade, hs.TeacherComments,
               hs.SubmissionDate as GradedDate
               FROM tblhomeworksubmissions hs
               JOIN tblhomework h ON hs.HomeworkID = h.ID
               JOIN tblsubjects s ON h.SubjectID = s.ID
               WHERE hs.StudentID = :studentId
               AND hs.Status = 'Graded'
               AND DATEDIFF(CURDATE(), hs.SubmissionDate) <= 7
               ORDER BY hs.SubmissionDate DESC";
        $query = $dbh->prepare($sql);
        $query->bindParam(':studentId', $studentId, PDO::PARAM_INT);
        $query->execute();
        $gradedHomework = $query->fetchAll(PDO::FETCH_OBJ);
        
        foreach($gradedHomework as $hw) {
            $notifications[] = [
                'type' => 'graded',
                'title' => $hw->Title,
                'subject' => $hw->SubjectName,
                'grade' => $hw->Grade,
                'comments' => $hw->TeacherComments,
                'gradedDate' => $hw->GradedDate,
                'message' => sprintf(
                    'Your %s homework "%s" has been graded: %s',
                    $hw->SubjectName,
                    $hw->Title,
                    $hw->Grade
                ),
                'link' => 'homework-report.php'
            ];
        }
        
        // Get new homework assignments
        $sql = "SELECT h.ID, h.Title, s.SubjectName, h.CreationDate
               FROM tblhomework h
               JOIN tblsubjects s ON h.SubjectID = s.ID
               WHERE h.ClassID = :classId
               AND DATEDIFF(CURDATE(), h.CreationDate) <= 2
               ORDER BY h.CreationDate DESC";
        $query = $dbh->prepare($sql);
        $query->bindParam(':classId', $classId, PDO::PARAM_INT);
        $query->execute();
        $newHomework = $query->fetchAll(PDO::FETCH_OBJ);
        
        foreach($newHomework as $hw) {
            $notifications[] = [
                'type' => 'new',
                'title' => $hw->Title,
                'subject' => $hw->SubjectName,
                'assignedDate' => $hw->CreationDate,
                'message' => sprintf(
                    'New %s homework assigned: "%s"',
                    $hw->SubjectName,
                    $hw->Title
                ),
                'link' => 'view-homework.php?id=' . $hw->ID
            ];
        }
        
        return [
            'success' => true,
            'notifications' => $notifications
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

function renderNotifications($notifications) {
    if(empty($notifications)) {
        return '<div class="text-muted">No new notifications</div>';
    }
    
    $html = '<div class="list-group">';
    foreach($notifications as $notif) {
        $icon = '';
        $color = '';
        
        switch($notif['type']) {
            case 'upcoming':
                $icon = 'icon-clock';
                $color = 'text-warning';
                break;
            case 'overdue':
                $icon = 'icon-exclamation';
                $color = 'text-danger';
                break;
            case 'graded':
                $icon = 'icon-check';
                $color = 'text-success';
                break;
            case 'new':
                $icon = 'icon-plus';
                $color = 'text-info';
                break;
        }
        
        $html .= sprintf(
            '<a href="%s" class="list-group-item list-group-item-action">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1 %s">
                        <i class="%s"></i> %s
                    </h6>
                    <small>%s</small>
                </div>
                <p class="mb-1">%s</p>
            </a>',
            htmlspecialchars($notif['link']),
            $color,
            $icon,
            htmlspecialchars($notif['subject']),
            htmlspecialchars($notif['title']),
            htmlspecialchars($notif['message'])
        );
    }
    $html .= '</div>';
    
    return $html;
}

// Example usage:
/*
$result = getHomeworkNotifications($dbh, $_SESSION['sturecmsuid']);
if($result['success']) {
    echo renderNotifications($result['notifications']);
} else {
    echo '<div class="alert alert-danger">' . $result['error'] . '</div>';
}
*/
?>
