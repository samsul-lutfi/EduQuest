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
$quizId = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;

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

// Check if user has permission to access this quiz
if ($userRole !== 'admin' && $course['teacher_id'] != $userId) {
    $_SESSION['error'] = 'You do not have permission to manage this quiz.';
    redirect('/courses/index.php');
}

// Handle question actions (add, edit, delete)
$action = $_GET['action'] ?? '';
$questionId = isset($_GET['question_id']) ? (int)$_GET['question_id'] : 0;
$optionId = isset($_GET['option_id']) ? (int)$_GET['option_id'] : 0;

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_question']) || isset($_POST['edit_question'])) {
        $questionText = sanitizeInput($_POST['question_text'] ?? '');
        $questionType = sanitizeInput($_POST['question_type'] ?? '');
        $points = (int)($_POST['points'] ?? 1);
        
        if (empty($questionText)) {
            $_SESSION['error'] = 'Question text is required.';
        } else {
            if (isset($_POST['add_question'])) {
                // Add new question
                $result = addQuizQuestion($quizId, $questionText, $questionType, $points);
                
                if ($result['success']) {
                    $_SESSION['success'] = $result['message'];
                    
                    // If multiple choice, redirect to add options
                    if ($questionType === 'multiple_choice') {
                        redirect("/courses/quiz_questions.php?quiz_id=$quizId&action=edit_options&question_id=" . $result['question_id']);
                    } else {
                        redirect("/courses/quiz_questions.php?quiz_id=$quizId");
                    }
                } else {
                    $_SESSION['error'] = $result['message'];
                }
            } elseif (isset($_POST['edit_question'])) {
                // Edit existing question
                $questionId = (int)$_POST['question_id'];
                $result = updateQuizQuestion($questionId, $questionText, $questionType, $points);
                
                if ($result['success']) {
                    $_SESSION['success'] = $result['message'];
                    redirect("/courses/quiz_questions.php?quiz_id=$quizId");
                } else {
                    $_SESSION['error'] = $result['message'];
                }
            }
        }
    } elseif (isset($_POST['delete_question'])) {
        // Delete question
        $questionId = (int)$_POST['question_id'];
        $result = deleteQuizQuestion($questionId);
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }
        
        redirect("/courses/quiz_questions.php?quiz_id=$quizId");
    } elseif (isset($_POST['add_option'])) {
        // Add option to a multiple choice question
        $questionId = (int)$_POST['question_id'];
        $optionText = sanitizeInput($_POST['option_text'] ?? '');
        $isCorrect = isset($_POST['is_correct']) && $_POST['is_correct'] === 'on';
        
        if (empty($optionText)) {
            $_SESSION['error'] = 'Option text is required.';
        } else {
            $result = addQuizOption($questionId, $optionText, $isCorrect);
            
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
        }
        
        redirect("/courses/quiz_questions.php?quiz_id=$quizId&action=edit_options&question_id=$questionId");
    } elseif (isset($_POST['edit_option'])) {
        // Edit option
        $optionId = (int)$_POST['option_id'];
        $questionId = (int)$_POST['question_id'];
        $optionText = sanitizeInput($_POST['option_text'] ?? '');
        $isCorrect = isset($_POST['is_correct']) && $_POST['is_correct'] === 'on';
        
        if (empty($optionText)) {
            $_SESSION['error'] = 'Option text is required.';
        } else {
            $result = updateQuizOption($optionId, $optionText, $isCorrect);
            
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
        }
        
        redirect("/courses/quiz_questions.php?quiz_id=$quizId&action=edit_options&question_id=$questionId");
    } elseif (isset($_POST['delete_option'])) {
        // Delete option
        $optionId = (int)$_POST['option_id'];
        $questionId = (int)$_POST['question_id'];
        $result = deleteQuizOption($optionId);
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }
        
        redirect("/courses/quiz_questions.php?quiz_id=$quizId&action=edit_options&question_id=$questionId");
    }
}

// Get questions for this quiz
$questions = getQuizQuestions($quizId);

// If editing a question, get its details
$question = null;
if (($action === 'edit' || $action === 'edit_options') && $questionId > 0) {
    foreach ($questions as $q) {
        if ($q['id'] == $questionId) {
            $question = $q;
            break;
        }
    }
    
    if (!$question) {
        $_SESSION['error'] = 'Question not found.';
        redirect("/courses/quiz_questions.php?quiz_id=$quizId");
    }
}

// If editing options, get the options for the question
$options = [];
if ($action === 'edit_options' && $questionId > 0) {
    $options = getQuestionOptions($questionId);
}

// Page title
$pageTitle = 'Manage Questions - ' . htmlspecialchars($quiz['name']) . ' - EduQuest';
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
                <li class="breadcrumb-item"><a href="/courses/quizzes.php?course_id=<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['name']); ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($quiz['name']); ?></li>
            </ol>
        </nav>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-list-ol me-2"></i><?php echo __('questions'); ?></h1>
            
            <a href="?quiz_id=<?php echo $quizId; ?>&action=add" class="btn btn-primary">
                <i class="fas fa-plus-circle me-1"></i><?php echo __('add_question'); ?>
            </a>
        </div>
        
        <?php if ($action === 'add' || $action === 'edit'): ?>
            <!-- Add/Edit Question Form -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <?php echo $action === 'add' ? __('add_question') : __('edit_question'); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <?php if ($action === 'edit' && $question): ?>
                            <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="question_text" class="form-label"><?php echo __('question_text'); ?></label>
                            <textarea class="form-control" id="question_text" name="question_text" rows="3" required><?php echo $question ? htmlspecialchars($question['question_text']) : ''; ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="question_type" class="form-label"><?php echo __('question_type'); ?></label>
                            <select class="form-select" id="question_type" name="question_type" required <?php echo ($action === 'edit' && $question) ? 'disabled' : ''; ?>>
                                <option value="multiple_choice" <?php echo ($question && $question['question_type'] === 'multiple_choice') ? 'selected' : ''; ?>><?php echo __('multiple_choice'); ?></option>
                                <option value="essay" <?php echo ($question && $question['question_type'] === 'essay') ? 'selected' : ''; ?>><?php echo __('essay'); ?></option>
                            </select>
                            <?php if ($action === 'edit' && $question): ?>
                                <input type="hidden" name="question_type" value="<?php echo htmlspecialchars($question['question_type']); ?>">
                                <div class="form-text text-muted"><?php echo __('question_type_cannot_be_changed'); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="points" class="form-label"><?php echo __('points'); ?></label>
                            <input type="number" class="form-control" id="points" name="points" min="1" max="100" required
                                   value="<?php echo $question ? htmlspecialchars($question['points']) : '1'; ?>">
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="/courses/quiz_questions.php?quiz_id=<?php echo $quizId; ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i><?php echo __('cancel'); ?>
                            </a>
                            
                            <button type="submit" class="btn btn-primary" name="<?php echo $action === 'add' ? 'add_question' : 'edit_question'; ?>">
                                <i class="fas fa-save me-1"></i><?php echo __('save'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php elseif ($action === 'edit_options' && $question): ?>
            <!-- Edit Options Form -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <?php echo __('edit_options'); ?>: <?php echo htmlspecialchars($question['question_text']); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-1"></i><?php echo __('multiple_choice_instructions'); ?>
                    </div>
                    
                    <!-- Existing Options -->
                    <?php if (!empty($options)): ?>
                        <h6 class="mt-3 mb-3"><?php echo __('current_options'); ?>:</h6>
                        <div class="list-group mb-4">
                            <?php foreach ($options as $option): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <?php if ($option['is_correct']): ?>
                                                <span class="badge bg-success me-2"><i class="fas fa-check"></i></span>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($option['option_text']); ?>
                                        </div>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                    data-bs-toggle="modal" data-bs-target="#editOptionModal<?php echo $option['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    data-bs-toggle="modal" data-bs-target="#deleteOptionModal<?php echo $option['id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Edit Option Modal -->
                                <div class="modal fade" id="editOptionModal<?php echo $option['id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title"><?php echo __('edit_option'); ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form method="POST" action="">
                                                <div class="modal-body">
                                                    <input type="hidden" name="option_id" value="<?php echo $option['id']; ?>">
                                                    <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                                                    
                                                    <div class="mb-3">
                                                        <label for="edit_option_text<?php echo $option['id']; ?>" class="form-label"><?php echo __('option_text'); ?></label>
                                                        <textarea class="form-control" id="edit_option_text<?php echo $option['id']; ?>" name="option_text" rows="3" required><?php echo htmlspecialchars($option['option_text']); ?></textarea>
                                                    </div>
                                                    
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="edit_is_correct<?php echo $option['id']; ?>" name="is_correct" <?php echo $option['is_correct'] ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="edit_is_correct<?php echo $option['id']; ?>">
                                                            <?php echo __('is_correct_answer'); ?>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('cancel'); ?></button>
                                                    <button type="submit" class="btn btn-primary" name="edit_option"><?php echo __('save'); ?></button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Delete Option Modal -->
                                <div class="modal fade" id="deleteOptionModal<?php echo $option['id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title"><?php echo __('delete_option'); ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p><?php echo __('confirm_delete_option'); ?></p>
                                                <p><strong><?php echo htmlspecialchars($option['option_text']); ?></strong></p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('cancel'); ?></button>
                                                <form method="POST" action="">
                                                    <input type="hidden" name="option_id" value="<?php echo $option['id']; ?>">
                                                    <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                                                    <button type="submit" class="btn btn-danger" name="delete_option"><?php echo __('delete'); ?></button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Add New Option Form -->
                    <h6 class="mt-3 mb-3"><?php echo __('add_new_option'); ?>:</h6>
                    <form method="POST" action="">
                        <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                        
                        <div class="mb-3">
                            <label for="option_text" class="form-label"><?php echo __('option_text'); ?></label>
                            <textarea class="form-control" id="option_text" name="option_text" rows="2" required></textarea>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input class="form-check-input" type="checkbox" id="is_correct" name="is_correct">
                            <label class="form-check-label" for="is_correct">
                                <?php echo __('is_correct_answer'); ?>
                            </label>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="/courses/quiz_questions.php?quiz_id=<?php echo $quizId; ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i><?php echo __('back_to_questions'); ?>
                            </a>
                            
                            <button type="submit" class="btn btn-primary" name="add_option">
                                <i class="fas fa-plus-circle me-1"></i><?php echo __('add_option'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <!-- Quiz Info -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h4><?php echo htmlspecialchars($quiz['name']); ?></h4>
                            <p class="text-muted"><?php echo htmlspecialchars($quiz['description']); ?></p>
                        </div>
                        <div class="col-md-4">
                            <div class="text-end">
                                <p>
                                    <strong><?php echo __('quiz_type'); ?>:</strong>
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
                                </p>
                                <p><strong><?php echo __('start_date'); ?>:</strong> <?php echo formatDate($quiz['start_date']); ?></p>
                                <p><strong><?php echo __('end_date'); ?>:</strong> <?php echo formatDate($quiz['end_date']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Questions List -->
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <?php if (empty($questions)): ?>
                        <div class="text-center p-4">
                            <p class="mb-0"><?php echo __('no_questions'); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($questions as $index => $question): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="badge bg-primary me-2"><?php echo $index + 1; ?></span>
                                            <span class="badge bg-<?php echo $question['question_type'] === 'multiple_choice' ? 'success' : 'info'; ?> me-2">
                                                <?php echo $question['question_type'] === 'multiple_choice' ? __('multiple_choice') : __('essay'); ?>
                                            </span>
                                            <strong><?php echo htmlspecialchars($question['question_text']); ?></strong>
                                        </div>
                                        <div>
                                            <span class="badge bg-light text-dark me-2">
                                                <?php echo $question['points']; ?> <?php echo __('points'); ?>
                                            </span>
                                            <div class="btn-group">
                                                <?php if ($question['question_type'] === 'multiple_choice'): ?>
                                                    <a href="?quiz_id=<?php echo $quizId; ?>&action=edit_options&question_id=<?php echo $question['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-list me-1"></i><?php echo __('options'); ?>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="?quiz_id=<?php echo $quizId; ?>&action=edit&question_id=<?php echo $question['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        data-bs-toggle="modal" data-bs-target="#deleteQuestionModal<?php echo $question['id']; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if ($question['question_type'] === 'multiple_choice' && isset($question['options']) && !empty($question['options'])): ?>
                                        <div class="mt-2 ps-4">
                                            <div class="row">
                                                <?php foreach ($question['options'] as $option): ?>
                                                    <div class="col-md-6 mb-1">
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas <?php echo $option['is_correct'] ? 'fa-check-circle text-success' : 'fa-circle text-secondary'; ?> me-2"></i>
                                                            <?php echo htmlspecialchars($option['option_text']); ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Delete Question Modal -->
                                    <div class="modal fade" id="deleteQuestionModal<?php echo $question['id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title"><?php echo __('delete_question'); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p><?php echo __('confirm_delete_question'); ?></p>
                                                    <p><strong><?php echo htmlspecialchars($question['question_text']); ?></strong></p>
                                                    <p class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i><?php echo __('delete_question_warning'); ?></p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('cancel'); ?></button>
                                                    <form method="POST" action="">
                                                        <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                                                        <button type="submit" class="btn btn-danger" name="delete_question"><?php echo __('delete'); ?></button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>