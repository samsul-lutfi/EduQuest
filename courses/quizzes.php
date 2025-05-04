<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once 'course_functions.php';
require_once 'quiz_functions.php';

// Ensure user is logged in
requireLogin();

// Only teachers and admins can access this page
if ($_SESSION['user_role'] !== 'teacher' && $_SESSION['user_role'] !== 'admin') {
    $_SESSION['error'] = 'You do not have permission to access that page.';
    redirect('/dashboard/index.php');
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];
$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

// Validate course ID
if ($courseId <= 0) {
    $_SESSION['error'] = 'Invalid course ID.';
    redirect('/courses/index.php');
}

// Get course details
$course = getCourseById($courseId);

// Check if course exists and user has permission
if (!$course || ($userRole !== 'admin' && $course['teacher_id'] != $userId)) {
    $_SESSION['error'] = 'You do not have permission to view this course.';
    redirect('/courses/index.php');
}

// Handle quiz actions (add, edit, delete)
$action = $_GET['action'] ?? '';
$quizId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_quiz']) || isset($_POST['edit_quiz'])) {
        $name = sanitizeInput($_POST['name'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $type = sanitizeInput($_POST['type'] ?? '');
        $startDate = sanitizeInput($_POST['start_date'] ?? '');
        $endDate = sanitizeInput($_POST['end_date'] ?? '');
        
        if (empty($name)) {
            $_SESSION['error'] = 'Quiz name is required.';
        } else {
            if (isset($_POST['add_quiz'])) {
                // Add new quiz
                $result = addQuiz($courseId, $name, $description, $type, $startDate, $endDate);
                
                if ($result['success']) {
                    $_SESSION['success'] = $result['message'];
                    redirect("/courses/quizzes.php?course_id=$courseId");
                } else {
                    $_SESSION['error'] = $result['message'];
                }
            } elseif (isset($_POST['edit_quiz'])) {
                // Edit existing quiz
                $quizId = (int)$_POST['quiz_id'];
                $result = updateQuiz($quizId, $name, $description, $type, $startDate, $endDate);
                
                if ($result['success']) {
                    $_SESSION['success'] = $result['message'];
                    redirect("/courses/quizzes.php?course_id=$courseId");
                } else {
                    $_SESSION['error'] = $result['message'];
                }
            }
        }
    } elseif (isset($_POST['delete_quiz'])) {
        // Delete quiz
        $quizId = (int)$_POST['quiz_id'];
        $result = deleteQuiz($quizId);
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }
        
        redirect("/courses/quizzes.php?course_id=$courseId");
    }
}

// Get quizzes for the course
$quizzes = getCourseQuizzes($courseId);

// If editing, get the quiz details
$quiz = null;
if ($action === 'edit' && $quizId > 0) {
    $quiz = getQuizById($quizId);
    
    // Make sure the quiz belongs to this course
    if ($quiz && $quiz['course_id'] != $courseId) {
        $_SESSION['error'] = 'You do not have permission to edit this quiz.';
        redirect("/courses/quizzes.php?course_id=$courseId");
    }
}

// Page title
$pageTitle = 'Manage Quizzes - ' . htmlspecialchars($course['name']) . ' - EduQuest';
?>

<?php include_once '../includes/header.php'; ?>

<div class="row">
    <!-- Sidebar -->
    <div class="col-lg-3">
        <?php include_once '../includes/sidebar.php'; ?>
    </div>
    
    <!-- Main content -->
    <div class="col-lg-9">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/courses/index.php"><?php echo __('courses'); ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($course['name']); ?></li>
            </ol>
        </nav>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-question-circle me-2"></i><?php echo __('quizzes'); ?></h1>
            
            <a href="?course_id=<?php echo $courseId; ?>&action=add" class="btn btn-primary">
                <i class="fas fa-plus-circle me-1"></i><?php echo __('add_quiz'); ?>
            </a>
        </div>
        
        <?php if ($action === 'add' || $action === 'edit'): ?>
            <!-- Add/Edit Quiz Form -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <?php echo $action === 'add' ? __('add_quiz') : __('edit_quiz'); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <?php if ($action === 'edit' && $quiz): ?>
                            <input type="hidden" name="quiz_id" value="<?php echo $quiz['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label"><?php echo __('quiz_name'); ?></label>
                            <input type="text" class="form-control" id="name" name="name" required
                                   value="<?php echo $quiz ? htmlspecialchars($quiz['name']) : ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label"><?php echo __('quiz_description'); ?></label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo $quiz ? htmlspecialchars($quiz['description']) : ''; ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="type" class="form-label"><?php echo __('quiz_type'); ?></label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="daily" <?php echo ($quiz && $quiz['type'] === 'daily') ? 'selected' : ''; ?>><?php echo __('daily_quiz'); ?></option>
                                <option value="weekly" <?php echo ($quiz && $quiz['type'] === 'weekly') ? 'selected' : ''; ?>><?php echo __('weekly_quiz'); ?></option>
                                <option value="midterm" <?php echo ($quiz && $quiz['type'] === 'midterm') ? 'selected' : ''; ?>><?php echo __('midterm_exam'); ?></option>
                                <option value="final" <?php echo ($quiz && $quiz['type'] === 'final') ? 'selected' : ''; ?>><?php echo __('final_exam'); ?></option>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="start_date" class="form-label"><?php echo __('start_date'); ?></label>
                                <input type="datetime-local" class="form-control" id="start_date" name="start_date" required
                                       value="<?php echo $quiz ? htmlspecialchars(date('Y-m-d\TH:i', strtotime($quiz['start_date']))) : ''; ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="end_date" class="form-label"><?php echo __('end_date'); ?></label>
                                <input type="datetime-local" class="form-control" id="end_date" name="end_date" required
                                       value="<?php echo $quiz ? htmlspecialchars(date('Y-m-d\TH:i', strtotime($quiz['end_date']))) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="/courses/quizzes.php?course_id=<?php echo $courseId; ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i><?php echo __('cancel'); ?>
                            </a>
                            
                            <button type="submit" class="btn btn-primary" name="<?php echo $action === 'add' ? 'add_quiz' : 'edit_quiz'; ?>">
                                <i class="fas fa-save me-1"></i><?php echo __('save'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <!-- Course Info -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h4><?php echo htmlspecialchars($course['name']); ?></h4>
                            <p class="text-muted"><?php echo htmlspecialchars($course['description']); ?></p>
                        </div>
                        <div class="col-md-4">
                            <div class="text-end">
                                <p><strong><?php echo __('teacher'); ?>:</strong> <?php echo htmlspecialchars($course['teacher_name']); ?></p>
                                <p><strong><?php echo __('students'); ?>:</strong> <?php echo count(getEnrolledStudents($courseId)); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quizzes List -->
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <?php if (empty($quizzes)): ?>
                        <div class="text-center p-4">
                            <p class="mb-0"><?php echo __('no_quizzes'); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th><?php echo __('quiz_name'); ?></th>
                                        <th><?php echo __('quiz_type'); ?></th>
                                        <th><?php echo __('start_date'); ?></th>
                                        <th><?php echo __('end_date'); ?></th>
                                        <th><?php echo __('actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($quizzes as $quiz): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($quiz['name']); ?></strong>
                                                <div class="small text-muted"><?php echo htmlspecialchars($quiz['description']); ?></div>
                                            </td>
                                            <td>
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
                                            </td>
                                            <td><?php echo formatDate($quiz['start_date']); ?></td>
                                            <td><?php echo formatDate($quiz['end_date']); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="/courses/quiz_questions.php?quiz_id=<?php echo $quiz['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-list-ol"></i>
                                                    </a>
                                                    <a href="/courses/quiz_submissions.php?quiz_id=<?php echo $quiz['id']; ?>" class="btn btn-sm btn-outline-info">
                                                        <i class="fas fa-clipboard-check"></i>
                                                    </a>
                                                    <a href="?course_id=<?php echo $courseId; ?>&action=edit&id=<?php echo $quiz['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            data-bs-toggle="modal" data-bs-target="#deleteQuizModal<?php echo $quiz['id']; ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                                
                                                <!-- Delete Modal -->
                                                <div class="modal fade" id="deleteQuizModal<?php echo $quiz['id']; ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title"><?php echo __('delete_quiz'); ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p><?php echo __('confirm_delete_quiz'); ?></p>
                                                                <p><strong><?php echo htmlspecialchars($quiz['name']); ?></strong></p>
                                                                <p class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i><?php echo __('delete_quiz_warning'); ?></p>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                                    <?php echo __('cancel'); ?>
                                                                </button>
                                                                <form method="POST" action="">
                                                                    <input type="hidden" name="quiz_id" value="<?php echo $quiz['id']; ?>">
                                                                    <button type="submit" class="btn btn-danger" name="delete_quiz">
                                                                        <?php echo __('delete'); ?>
                                                                    </button>
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
        <?php endif; ?>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>