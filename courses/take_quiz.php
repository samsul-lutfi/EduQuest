<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once 'course_functions.php';
require_once 'quiz_functions.php';

// Ensure user is logged in
requireLogin();

// Get quiz ID from URL parameter
$quizId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Validate quiz ID
if ($quizId <= 0) {
    $_SESSION['error'] = 'Invalid quiz ID.';
    redirect('/courses/index.php');
}

// Get quiz details
$quiz = getQuizById($quizId);

// Check if quiz exists
if (!$quiz) {
    $_SESSION['error'] = 'Quiz not found.';
    redirect('/courses/index.php');
}

// Get course details
$course = getCourseById($quiz['course_id']);

// Check if course exists
if (!$course) {
    $_SESSION['error'] = 'Course not found.';
    redirect('/courses/index.php');
}

// Determine if the user has access to this quiz
$hasAccess = false;

if ($_SESSION['user_role'] === 'admin') {
    // Admins can access all quizzes
    $hasAccess = true;
} elseif ($_SESSION['user_role'] === 'teacher') {
    // Teachers can access quizzes in their own courses
    $hasAccess = ($course['teacher_id'] == $_SESSION['user_id']);
} elseif ($_SESSION['user_role'] === 'student') {
    // Students can only access quizzes in courses they're enrolled in
    $enrolled = isStudentEnrolled($_SESSION['user_id'], $course['id']);
    $hasAccess = $enrolled;
    
    // Check if the quiz is currently available (between start and end dates)
    $now = new DateTime();
    $startDate = new DateTime($quiz['start_date']);
    $endDate = new DateTime($quiz['end_date']);
    
    if ($now < $startDate || $now > $endDate) {
        $hasAccess = false;
        $_SESSION['error'] = 'This quiz is not currently available.';
        redirect('/courses/view.php?id=' . $course['id']);
    }
}

// If user doesn't have access, redirect with error
if (!$hasAccess) {
    $_SESSION['error'] = 'You do not have permission to access this quiz.';
    redirect('/courses/view.php?id=' . $course['id']);
}

// Get quiz questions
$questions = getQuizQuestions($quizId);

// Page title
$pageTitle = htmlspecialchars($quiz['name']) . ' - ' . htmlspecialchars($course['name']) . ' - EduQuest';
?>

<?php include_once '../includes/header.php'; ?>

<div class="row">
    <!-- Sidebar -->
    <div class="col-lg-3">
        <?php include_once '../includes/sidebar.php'; ?>
    </div>
    
    <!-- Main content -->
    <div class="col-lg-9">
        <!-- Quiz header -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/courses/index.php"><?php echo __('courses'); ?></a></li>
                <li class="breadcrumb-item"><a href="/courses/view.php?id=<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['name']); ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($quiz['name']); ?></li>
            </ol>
        </nav>
        
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0"><?php echo htmlspecialchars($quiz['name']); ?></h3>
                    
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
                </div>
            </div>
            <div class="card-body">
                <p class="card-text">
                    <?php echo !empty($quiz['description']) ? nl2br(htmlspecialchars($quiz['description'])) : 'No description provided.'; ?>
                </p>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p><strong><?php echo __('start_date'); ?>:</strong> <?php echo formatDate($quiz['start_date']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong><?php echo __('end_date'); ?>:</strong> <?php echo formatDate($quiz['end_date']); ?></p>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <?php echo __('quiz_info_message'); ?>
                </div>
            </div>
        </div>
        
        <!-- Display a message if there are no questions yet -->
        <?php if (empty($questions)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo __('no_questions'); ?>
            </div>
            
            <?php if ($_SESSION['user_role'] === 'teacher' && $course['teacher_id'] == $_SESSION['user_id'] || $_SESSION['user_role'] === 'admin'): ?>
                <div class="text-center my-4">
                    <a href="/courses/quiz_questions.php?quiz_id=<?php echo $quizId; ?>&action=add" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-1"></i><?php echo __('add_question'); ?>
                    </a>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <!-- Quiz questions form -->
            <form method="POST" action="/courses/submit_quiz.php">
                <input type="hidden" name="quiz_id" value="<?php echo $quizId; ?>">
                
                <?php foreach ($questions as $index => $question): ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">
                                <span class="badge bg-primary me-2"><?php echo $index + 1; ?></span>
                                <?php echo htmlspecialchars($question['question_text']); ?>
                                <span class="float-end badge bg-light text-dark">
                                    <?php echo $question['points']; ?> <?php echo __('points'); ?>
                                </span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if ($question['question_type'] === 'multiple_choice'): ?>
                                <?php 
                                    // Get options for this question
                                    $options = isset($question['options']) ? $question['options'] : getQuestionOptions($question['id']);
                                ?>
                                
                                <?php if (!empty($options)): ?>
                                    <div class="list-group">
                                        <?php foreach ($options as $option): ?>
                                            <label class="list-group-item">
                                                <input class="form-check-input me-2" type="radio" name="answers[<?php echo $question['id']; ?>]" value="<?php echo $option['id']; ?>">
                                                <?php echo htmlspecialchars($option['option_text']); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        <?php echo __('no_options'); ?>
                                    </div>
                                <?php endif; ?>
                                
                            <?php elseif ($question['question_type'] === 'essay'): ?>
                                <div class="mb-3">
                                    <textarea class="form-control" name="answers[<?php echo $question['id']; ?>]" rows="5" placeholder="<?php echo __('enter_your_answer'); ?>"></textarea>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="d-grid gap-2 mb-5">
                    <button type="submit" class="btn btn-primary btn-lg" name="submit_quiz">
                        <i class="fas fa-paper-plane me-1"></i><?php echo __('submit_quiz'); ?>
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>