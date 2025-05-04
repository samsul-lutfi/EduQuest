<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once 'course_functions.php';

// Ensure user is logged in
requireLogin();

// Only teachers and admins can access this page
if ($_SESSION['user_role'] !== 'teacher' && $_SESSION['user_role'] !== 'admin') {
    $_SESSION['error'] = 'You do not have permission to access that page.';
    redirect('/dashboard/index.php');
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

// Handle course actions (add, edit, delete)
$action = $_GET['action'] ?? '';
$courseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_course']) || isset($_POST['edit_course'])) {
        $name = sanitizeInput($_POST['name'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        
        if (empty($name)) {
            $_SESSION['error'] = 'Course name is required.';
        } else {
            if (isset($_POST['add_course'])) {
                // Add new course
                $result = addCourse($name, $description, $userId);
                
                if ($result['success']) {
                    $_SESSION['success'] = $result['message'];
                    redirect('/courses/index.php');
                } else {
                    $_SESSION['error'] = $result['message'];
                }
            } elseif (isset($_POST['edit_course'])) {
                // Edit existing course
                $courseId = (int)$_POST['course_id'];
                $result = updateCourse($courseId, $name, $description);
                
                if ($result['success']) {
                    $_SESSION['success'] = $result['message'];
                    redirect('/courses/index.php');
                } else {
                    $_SESSION['error'] = $result['message'];
                }
            }
        }
    } elseif (isset($_POST['delete_course'])) {
        // Delete course
        $courseId = (int)$_POST['course_id'];
        $result = deleteCourse($courseId);
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }
        
        redirect('/courses/index.php');
    }
}

// Get courses based on user role and action
$courses = [];
$course = null;

if ($userRole === 'admin') {
    // Admins can see all courses
    $courses = getAllCourses();
} else {
    // Teachers see only their courses
    $courses = getTeacherCourses($userId);
}

// If editing, get the course details
if ($action === 'edit' && $courseId > 0) {
    $course = getCourseById($courseId);
    
    // Make sure the teacher owns the course or is an admin
    if ($course && $userRole !== 'admin' && $course['teacher_id'] != $userId) {
        $_SESSION['error'] = 'You do not have permission to edit this course.';
        redirect('/courses/index.php');
    }
}

// Page title
$pageTitle = 'Manage Courses - EduQuest';
?>

<?php include_once '../includes/header.php'; ?>

<div class="row">
    <!-- Sidebar -->
    <div class="col-lg-3">
        <?php include_once '../includes/sidebar.php'; ?>
    </div>
    
    <!-- Main content -->
    <div class="col-lg-9">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-book me-2"></i><?php echo __('courses'); ?></h1>
            
            <a href="?action=add" class="btn btn-primary">
                <i class="fas fa-plus-circle me-1"></i><?php echo __('add_course'); ?>
            </a>
        </div>
        
        <?php if ($action === 'add' || $action === 'edit'): ?>
            <!-- Add/Edit Course Form -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <?php echo $action === 'add' ? __('add_course') : __('edit_course'); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <?php if ($action === 'edit' && $course): ?>
                            <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label"><?php echo __('course_name'); ?></label>
                            <input type="text" class="form-control" id="name" name="name" required
                                   value="<?php echo $course ? htmlspecialchars($course['name']) : ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label"><?php echo __('course_description'); ?></label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo $course ? htmlspecialchars($course['description']) : ''; ?></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="/courses/index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i><?php echo __('cancel'); ?>
                            </a>
                            
                            <button type="submit" class="btn btn-primary" name="<?php echo $action === 'add' ? 'add_course' : 'edit_course'; ?>">
                                <i class="fas fa-save me-1"></i><?php echo __('save'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <!-- Course List -->
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <?php if (empty($courses)): ?>
                        <div class="text-center p-4">
                            <p class="mb-0"><?php echo __('no_courses'); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th><?php echo __('course_name'); ?></th>
                                        <th><?php echo __('students'); ?></th>
                                        <th><?php echo __('quizzes'); ?></th>
                                        <th><?php echo __('created_at'); ?></th>
                                        <th><?php echo __('actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($courses as $course): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($course['name']); ?></strong>
                                                <div class="small text-muted"><?php echo htmlspecialchars($course['description']); ?></div>
                                            </td>
                                            <td>
                                                <?php 
                                                    $enrolledStudents = getEnrolledStudents($course['id']);
                                                    echo count($enrolledStudents);
                                                ?>
                                            </td>
                                            <td>
                                                <?php echo countCourseQuizzes($course['id']); ?>
                                            </td>
                                            <td><?php echo formatDate($course['created_at']); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="/courses/view.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="/courses/quizzes.php?course_id=<?php echo $course['id']; ?>" class="btn btn-sm btn-outline-info">
                                                        <i class="fas fa-question-circle"></i>
                                                    </a>
                                                    <?php if ($_SESSION['user_role'] === 'teacher' && $course['teacher_id'] == $_SESSION['user_id'] || $_SESSION['user_role'] === 'admin'): ?>
                                                        <a href="/courses/index.php?action=edit&id=<?php echo $course['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                data-bs-toggle="modal" data-bs-target="#deleteCourseModal<?php echo $course['id']; ?>">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <!-- Delete Modal -->
                                                <div class="modal fade" id="deleteCourseModal<?php echo $course['id']; ?>" tabindex="-1" aria-hidden="true">
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
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                                    <?php echo __('cancel'); ?>
                                                                </button>
                                                                <form method="POST" action="">
                                                                    <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                                                    <button type="submit" class="btn btn-danger" name="delete_course">
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