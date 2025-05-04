<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once 'course_functions.php';

// Ensure user is logged in
requireLogin();

// Get course ID from URL parameter
$courseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Validate course ID
if ($courseId <= 0) {
    $_SESSION['error'] = 'Invalid course ID.';
    redirect('/courses/index.php');
}

// Get course details
$course = getCourseById($courseId);

// Check if course exists
if (!$course) {
    $_SESSION['error'] = 'Course not found.';
    redirect('/courses/index.php');
}

// Determine if the user has access to this course
$hasAccess = false;
$enrolled = false;

if ($_SESSION['user_role'] === 'admin') {
    // Admins can access all courses
    $hasAccess = true;
} elseif ($_SESSION['user_role'] === 'teacher') {
    // Teachers can access their own courses
    $hasAccess = ($course['teacher_id'] == $_SESSION['user_id']);
} elseif ($_SESSION['user_role'] === 'student') {
    // Students can only access courses they're enrolled in
    $enrolled = isStudentEnrolled($_SESSION['user_id'], $courseId);
    $hasAccess = $enrolled;
}

// If user doesn't have access, redirect with error
if (!$hasAccess) {
    $_SESSION['error'] = 'You do not have permission to access this course.';
    redirect('/courses/index.php');
}

// Get enrolled students
$enrolledStudents = getEnrolledStudents($courseId);

// Get quizzes for this course
require_once 'quiz_functions.php';
$quizzes = getCourseQuizzes($courseId);

// Page title
$pageTitle = htmlspecialchars($course['name']) . ' - EduQuest';
?>

<?php include_once '../includes/header.php'; ?>

<div class="row">
    <!-- Sidebar -->
    <div class="col-lg-3">
        <?php include_once '../includes/sidebar.php'; ?>
    </div>
    
    <!-- Main content -->
    <div class="col-lg-9">
        <!-- Course header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-book me-2"></i><?php echo htmlspecialchars($course['name']); ?></h1>
            
            <div>
                <?php if ($_SESSION['user_role'] === 'teacher' && $course['teacher_id'] == $_SESSION['user_id'] || $_SESSION['user_role'] === 'admin'): ?>
                    <a href="/courses/quizzes.php?course_id=<?php echo $courseId; ?>" class="btn btn-success">
                        <i class="fas fa-question-circle me-1"></i><?php echo __('quizzes'); ?>
                    </a>
                    <a href="/courses/index.php?action=edit&id=<?php echo $courseId; ?>" class="btn btn-primary">
                        <i class="fas fa-edit me-1"></i><?php echo __('edit'); ?>
                    </a>
                    <button type="button" class="btn btn-danger" 
                            data-bs-toggle="modal" data-bs-target="#deleteCourseModal">
                        <i class="fas fa-trash me-1"></i><?php echo __('delete'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Course details -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <h4 class="card-title mb-3"><?php echo __('course_description'); ?></h4>
                        <p class="card-text">
                            <?php echo !empty($course['description']) ? nl2br(htmlspecialchars($course['description'])) : 'No description provided.'; ?>
                        </p>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-light p-3 rounded">
                            <h5><?php echo __('teacher'); ?></h5>
                            <p class="mb-3">
                                <strong><?php echo htmlspecialchars($course['teacher_name']); ?></strong><br>
                                <small><?php echo htmlspecialchars($course['teacher_email']); ?></small>
                            </p>
                            
                            <h5><?php echo __('created_at'); ?></h5>
                            <p class="mb-3"><?php echo formatDate($course['created_at']); ?></p>
                            
                            <h5><?php echo __('students'); ?></h5>
                            <p class="mb-0"><?php echo count($enrolledStudents); ?> <?php echo __('enrolled'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tabs for different sections -->
        <ul class="nav nav-tabs mb-4" id="courseTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="contents-tab" data-bs-toggle="tab" data-bs-target="#contents" 
                        type="button" role="tab" aria-controls="contents" aria-selected="true">
                    <i class="fas fa-file-alt me-1"></i><?php echo __('contents'); ?>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="quizzes-tab" data-bs-toggle="tab" data-bs-target="#quizzes" 
                        type="button" role="tab" aria-controls="quizzes" aria-selected="false">
                    <i class="fas fa-question-circle me-1"></i><?php echo __('quizzes'); ?>
                </button>
            </li>
            <?php if ($_SESSION['user_role'] === 'teacher' && $course['teacher_id'] == $_SESSION['user_id'] || $_SESSION['user_role'] === 'admin'): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="students-tab" data-bs-toggle="tab" data-bs-target="#students" 
                            type="button" role="tab" aria-controls="students" aria-selected="false">
                        <i class="fas fa-users me-1"></i><?php echo __('students'); ?>
                    </button>
                </li>
            <?php endif; ?>
        </ul>
        
        <div class="tab-content" id="courseTabContent">
            <!-- Contents Tab -->
            <div class="tab-pane fade" id="contents" role="tabpanel" aria-labelledby="contents-tab">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-file-alt me-2"></i><?php echo __('contents'); ?>
                            </h5>
                            
                            <?php if ($_SESSION['user_role'] === 'teacher' && $course['teacher_id'] == $_SESSION['user_id'] || $_SESSION['user_role'] === 'admin'): ?>
                                <a href="/courses/contents.php?action=add&course_id=<?php echo $courseId; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus-circle me-1"></i><?php echo __('add_content'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="text-center p-4">
                            <a href="/courses/contents.php?course_id=<?php echo $courseId; ?>" class="btn btn-primary">
                                <i class="fas fa-folder-open me-1"></i><?php echo __('view_contents'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quizzes Tab -->
            <div class="tab-pane fade show active" id="quizzes" role="tabpanel" aria-labelledby="quizzes-tab">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-question-circle me-2"></i><?php echo __('quizzes'); ?>
                            </h5>
                            
                            <?php if ($_SESSION['user_role'] === 'teacher' && $course['teacher_id'] == $_SESSION['user_id'] || $_SESSION['user_role'] === 'admin'): ?>
                                <a href="/courses/quizzes.php?course_id=<?php echo $courseId; ?>&action=add" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus-circle me-1"></i><?php echo __('add_quiz'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($quizzes)): ?>
                            <div class="p-4 text-center">
                                <p class="mb-0"><?php echo __('no_quizzes'); ?></p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($quizzes as $quiz): ?>
                                    <a href="/courses/take_quiz.php?id=<?php echo $quiz['id']; ?>" class="list-group-item list-group-item-action">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($quiz['name']); ?></h5>
                                            <small>
                                                <?php 
                                                    $typeClass = '';
                                                    $typeName = '';
                                                    
                                                    switch ($quiz['type']) {
                                                        case 'daily':
                                                            $typeClass = 'bg-info';
                                                            $typeName = __('daily_quiz');
                                                            break;
                                                        case 'weekly':
                                                            $typeClass = 'bg-primary';
                                                            $typeName = __('weekly_quiz');
                                                            break;
                                                        case 'midterm':
                                                            $typeClass = 'bg-warning';
                                                            $typeName = __('midterm_exam');
                                                            break;
                                                        case 'final':
                                                            $typeClass = 'bg-danger';
                                                            $typeName = __('final_exam');
                                                            break;
                                                        default:
                                                            $typeClass = 'bg-secondary';
                                                            $typeName = $quiz['type'];
                                                    }
                                                ?>
                                                <span class="badge <?php echo $typeClass; ?>"><?php echo $typeName; ?></span>
                                            </small>
                                        </div>
                                        <p class="mb-1"><?php echo htmlspecialchars($quiz['description']); ?></p>
                                        <small>
                                            <i class="fas fa-calendar-alt me-1"></i>
                                            <?php echo formatDate($quiz['start_date']); ?> - <?php echo formatDate($quiz['end_date']); ?>
                                        </small>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Students Tab (for teachers and admins only) -->
            <?php if ($_SESSION['user_role'] === 'teacher' && $course['teacher_id'] == $_SESSION['user_id'] || $_SESSION['user_role'] === 'admin'): ?>
                <div class="tab-pane fade" id="students" role="tabpanel" aria-labelledby="students-tab">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-users me-2"></i><?php echo __('enrolled_students'); ?>
                                </h5>
                                
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#enrollStudentModal">
                                    <i class="fas fa-user-plus me-1"></i><?php echo __('enroll_student'); ?>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($enrolledStudents)): ?>
                                <div class="p-4 text-center">
                                    <p class="mb-0"><?php echo __('no_enrolled_students'); ?></p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th><?php echo __('student_name'); ?></th>
                                                <th><?php echo __('email'); ?></th>
                                                <th><?php echo __('enrollment_date'); ?></th>
                                                <th><?php echo __('actions'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($enrolledStudents as $student): ?>
                                                <tr>
                                                    <td>
                                                        <a href="/dashboard/student_profile.php?id=<?php echo $student['id']; ?>">
                                                            <?php echo htmlspecialchars($student['name']); ?>
                                                        </a>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                                    <td><?php echo formatDate($student['enrollment_date']); ?></td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="/dashboard/student_profile.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-user"></i>
                                                            </a>
                                                            <a href="/achievements/view.php?student_id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-info">
                                                                <i class="fas fa-trophy"></i>
                                                            </a>
                                                            <a href="/achievements/add.php?student_id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-success">
                                                                <i class="fas fa-award"></i>
                                                            </a>
                                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                    data-bs-toggle="modal" data-bs-target="#unenrollStudentModal<?php echo $student['id']; ?>">
                                                                <i class="fas fa-user-minus"></i>
                                                            </button>
                                                        </div>
                                                        
                                                        <!-- Unenroll Modal -->
                                                        <div class="modal fade" id="unenrollStudentModal<?php echo $student['id']; ?>" tabindex="-1" aria-hidden="true">
                                                            <div class="modal-dialog">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title"><?php echo __('unenroll_student'); ?></h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <p><?php echo __('confirm_unenroll_student'); ?></p>
                                                                        <p><strong><?php echo htmlspecialchars($student['name']); ?></strong></p>
                                                                        <p><?php echo __('unenroll_student_warning'); ?></p>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('cancel'); ?></button>
                                                                        <form method="POST" action="/courses/enroll.php">
                                                                            <input type="hidden" name="course_id" value="<?php echo $courseId; ?>">
                                                                            <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                                                            <button type="submit" class="btn btn-danger" name="unenroll"><?php echo __('unenroll'); ?></button>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Delete Course Modal -->
<div class="modal fade" id="deleteCourseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo __('delete_course'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><?php echo __('confirm_delete_course'); ?></p>
                <p><strong><?php echo htmlspecialchars($course['name']); ?></strong></p>
                <p class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i><?php echo __('delete_course_warning'); ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('cancel'); ?></button>
                <form method="POST" action="/courses/index.php">
                    <input type="hidden" name="course_id" value="<?php echo $courseId; ?>">
                    <button type="submit" class="btn btn-danger" name="delete_course"><?php echo __('delete'); ?></button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Enroll Student Modal -->
<div class="modal fade" id="enrollStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo __('enroll_student'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="/courses/enroll.php">
                <div class="modal-body">
                    <input type="hidden" name="course_id" value="<?php echo $courseId; ?>">
                    
                    <div class="mb-3">
                        <label for="student_id" class="form-label"><?php echo __('select_student'); ?></label>
                        <select class="form-select" id="student_id" name="student_id" required>
                            <option value=""><?php echo __('select_student'); ?>...</option>
                            <?php
                                // Get all students who are not enrolled in this course
                                $sql = "SELECT u.id, u.name 
                                        FROM users u 
                                        WHERE u.role = 'student' 
                                        AND u.id NOT IN (
                                            SELECT sc.student_id 
                                            FROM student_courses sc 
                                            WHERE sc.course_id = $courseId
                                        )
                                        ORDER BY u.name ASC";
                                $result = executeQuery($sql);
                                $students = $result ? fetchAllAssoc($result) : [];
                                
                                foreach ($students as $student):
                            ?>
                                <option value="<?php echo $student['id']; ?>"><?php echo htmlspecialchars($student['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('cancel'); ?></button>
                    <button type="submit" class="btn btn-primary" name="enroll"><?php echo __('enroll'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>