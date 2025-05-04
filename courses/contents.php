<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once 'course_functions.php';
require_once 'content_functions.php';

<button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addModuleModal">
    <i class="fas fa-plus me-2"></i>Tambah Modul Aktivitas
</button>
    
// Ensure user is logged in
requireLogin();

// Get course ID from URL parameter
$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

// Get content ID from URL parameter (for view/edit actions)
$contentId = isset($_GET['content_id']) ? (int)$_GET['content_id'] : 0;

// Get action from URL parameter
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// Validate course ID
if ($courseId <= 0 && $action != 'list_all') {
    $_SESSION['error'] = 'Invalid course ID.';
    redirect('/courses/index.php');
}

// Get course details if course ID is provided
$course = null;
if ($courseId > 0) {
    $course = getCourseById($courseId);
    
    // Check if course exists
    if (!$course) {
        $_SESSION['error'] = 'Course not found.';
        redirect('/courses/index.php');
    }
    
    // Check if user has access to this course
    if ($_SESSION['user_role'] === 'student') {
        // Students can only view courses they're enrolled in
        $isEnrolled = isStudentEnrolled($_SESSION['user_id'], $courseId);
        
        if (!$isEnrolled) {
            $_SESSION['error'] = 'You are not enrolled in this course.';
            redirect('/courses/index.php');
        }
    } elseif ($_SESSION['user_role'] === 'teacher') {
        // Teachers can only manage their own courses
        if ($course['teacher_id'] != $_SESSION['user_id']) {
            $_SESSION['error'] = 'You do not have permission to manage this course.';
            redirect('/courses/index.php');
        }
    }
}

// Get content details if content ID is provided
$content = null;
if ($contentId > 0) {
    $content = getContentById($contentId);
    
    // Check if content exists
    if (!$content) {
        $_SESSION['error'] = 'Content not found.';
        redirect("/courses/contents.php?course_id=$courseId");
    }
    
    // Check if user has access to this content
    if ($_SESSION['user_role'] === 'student') {
        // Students can only view contents in courses they're enrolled in
        $isEnrolled = isStudentEnrolled($_SESSION['user_id'], $content['course_id']);
        
        if (!$isEnrolled) {
            $_SESSION['error'] = 'You do not have permission to view this content.';
            redirect('/courses/index.php');
        }
    } elseif ($_SESSION['user_role'] === 'teacher') {
        // Teachers can only manage contents in their own courses
        if ($content['teacher_id'] != $_SESSION['user_id']) {
            $_SESSION['error'] = 'You do not have permission to manage this content.';
            redirect('/courses/index.php');
        }
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new content
    if (isset($_POST['add_content'])) {
        $contentData = [
            'course_id' => $courseId,
            'title' => $_POST['title'],
            'content_type' => $_POST['content_type'],
            'description' => $_POST['description'],
            'start_date' => $_POST['start_date'] ?? null,
            'end_date' => $_POST['end_date'] ?? null,
            'status' => $_POST['status'] ?? 'draft'
        ];
        
        // Handle file uploads if any
        if (isset($_FILES['content_file']) && $_FILES['content_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/content/';
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileName = time() . '_' . basename($_FILES['content_file']['name']);
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['content_file']['tmp_name'], $targetPath)) {
                $contentData['file_path'] = '/uploads/content/' . $fileName;
            }
        }
        
        // Add the content
        $result = addCourseContent($contentData);
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
            redirect("/courses/contents.php?course_id=$courseId");
        } else {
            $_SESSION['error'] = $result['message'];
        }
    }
    
    // Update content
    if (isset($_POST['update_content'])) {
        $contentData = [
            'title' => $_POST['title'],
            'content_type' => $_POST['content_type'],
            'description' => $_POST['description'],
            'start_date' => $_POST['start_date'] ?? null,
            'end_date' => $_POST['end_date'] ?? null,
            'status' => $_POST['status'] ?? 'draft'
        ];
        
        // Handle file uploads if any
        if (isset($_FILES['content_file']) && $_FILES['content_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/content/';
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileName = time() . '_' . basename($_FILES['content_file']['name']);
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['content_file']['tmp_name'], $targetPath)) {
                $contentData['file_path'] = '/uploads/content/' . $fileName;
            }
        }
        
        // Update the content
        $result = updateCourseContent($contentId, $contentData);
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
            redirect("/courses/contents.php?course_id=$courseId");
        } else {
            $_SESSION['error'] = $result['message'];
        }
    }
    
    // Delete content
    if (isset($_POST['delete_content'])) {
        $result = deleteCourseContent($contentId);
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
            redirect("/courses/contents.php?course_id=$courseId");
        } else {
            $_SESSION['error'] = $result['message'];
        }
    }
    
    // Add comment
    if (isset($_POST['add_comment'])) {
        $commentData = [
            'content_id' => $contentId,
            'user_id' => $_SESSION['user_id'],
            'comment' => $_POST['comment'],
            'parent_id' => isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : null
        ];
        
        $result = addContentComment($commentData);
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
            redirect("/courses/contents.php?action=view&content_id=$contentId&course_id=$courseId");
        } else {
            $_SESSION['error'] = $result['message'];
        }
    }
    
    // Mark content as completed
    if (isset($_POST['mark_completed'])) {
        $result = markContentCompletion($_SESSION['user_id'], $contentId, 'completed');
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
            redirect("/courses/contents.php?action=view&content_id=$contentId&course_id=$courseId");
        } else {
            $_SESSION['error'] = $result['message'];
        }
    }
}

// Page title
$pageTitle = $action === 'list_all' ? 'All Courses' : ($course ? htmlspecialchars($course['name']) . ' - ' : '') . 'Course Content - EduQuest';

include_once '../includes/header.php';
?>

<div class="row">
    <!-- Sidebar -->
    <div class="col-lg-3">
        <?php include_once '../includes/sidebar.php'; ?>
    </div>
    
    <!-- Main content -->
    <div class="col-lg-9">
        <?php
        // Determine what to display based on action
        switch ($action) {
            case 'list_all':
                // Display all courses (for instructors/admins)
                if ($_SESSION['user_role'] === 'student') {
                    $_SESSION['error'] = 'You do not have permission to access this page.';
                    redirect('/courses/index.php');
                }
                
                // Get courses with enrollment counts
                $teacherId = $_SESSION['user_role'] === 'teacher' ? $_SESSION['user_id'] : null;
                $courses = getCoursesWithEnrollmentCounts($teacherId);
                ?>
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><?php echo __('courses'); ?></h2>
                    
                    <?php if ($_SESSION['user_role'] === 'teacher' || $_SESSION['user_role'] === 'admin'): ?>
                        <a href="/courses/add.php" class="btn btn-primary">
                            <i class="fas fa-plus-circle me-1"></i><?php echo __('add_course'); ?>
                        </a>
                    <?php endif; ?>
                </div>
                
                <?php if (empty($courses)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <?php echo __('no_courses'); ?>
                    </div>
                <?php else: ?>
                    <div class="row row-cols-1 row-cols-md-2 g-4 mb-4">
                        <?php foreach ($courses as $course): ?>
                            <div class="col">
                                <div class="card h-100 shadow-sm">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($course['name']); ?></h5>
                                        <p class="card-text small text-muted mb-2">
                                            <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($course['teacher_name']); ?>
                                        </p>
                                        <p class="card-text">
                                            <?php echo !empty($course['description']) ? mb_substr(htmlspecialchars($course['description']), 0, 100) . '...' : 'No description provided.'; ?>
                                        </p>
                                        
                                        <div class="d-flex justify-content-between align-items-center mt-3">
                                            <div>
                                                <span class="badge bg-primary">
                                                    <i class="fas fa-users me-1"></i> <?php echo $course['student_count']; ?> <?php echo __('students'); ?>
                                                </span>
                                                <?php if (isset($course['status']) && $course['status'] !== 'active'): ?>
                                                    <span class="badge bg-warning ms-1">
                                                        <?php echo ucfirst($course['status']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <a href="/courses/contents.php?course_id=<?php echo $course['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-folder-open me-1"></i><?php echo __('view_contents'); ?>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="card-footer text-muted small">
                                        <i class="far fa-calendar-alt me-1"></i> <?php echo __('created_at'); ?>: <?php echo formatDate($course['created_at']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php
                break;
                
            case 'list':
                // Display content list for a course
                $contents = getCourseContents($courseId);
                ?>
                
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/courses/index.php"><?php echo __('courses'); ?></a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($course['name']); ?></li>
                    </ol>
                </nav>
                
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="mb-0"><?php echo htmlspecialchars($course['name']); ?></h3>
                            
                            <?php if ($_SESSION['user_role'] === 'student'): ?>
                                <?php
                                    // Get student progress
                                    $progress = calculateCourseProgress($_SESSION['user_id'], $courseId);
                                ?>
                                <div class="progress" style="width: 150px; height: 25px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $progress['percentage']; ?>%;" 
                                        aria-valuenow="<?php echo $progress['percentage']; ?>" aria-valuemin="0" aria-valuemax="100">
                                        <?php echo $progress['percentage']; ?>%
                                    </div>
                                </div>
                            <?php else: ?>
                                <a href="/courses/view.php?id=<?php echo $courseId; ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-arrow-left me-1"></i><?php echo __('back_to_course'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="card-text">
                            <?php echo !empty($course['description']) ? nl2br(htmlspecialchars($course['description'])) : 'No description provided.'; ?>
                        </p>
                        
                        <?php if ($_SESSION['user_role'] === 'teacher' || $_SESSION['user_role'] === 'admin'): ?>
                            <div class="d-flex justify-content-end mb-3">
                                <a href="/courses/contents.php?action=add&course_id=<?php echo $courseId; ?>" class="btn btn-success">
                                    <i class="fas fa-plus-circle me-1"></i><?php echo __('add_content'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (empty($contents)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <?php echo __('no_contents'); ?>
                            </div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php 
                                    // Initialize content counter
                                    $contentCounter = 1;
                                    
                                    // Get content types
                                    $contentTypes = getContentTypes();
                                    
                                    foreach ($contents as $content): 
                                        // Get icon based on content type
                                        $icon = 'file-alt';
                                        $colorClass = 'primary';
                                        
                                        switch ($content['content_type']) {
                                            case 'lecture':
                                                $icon = 'file-alt';
                                                $colorClass = 'primary';
                                                break;
                                            case 'video':
                                                $icon = 'video';
                                                $colorClass = 'danger';
                                                break;
                                            case 'document':
                                                $icon = 'file-pdf';
                                                $colorClass = 'info';
                                                break;
                                            case 'assignment':
                                                $icon = 'tasks';
                                                $colorClass = 'warning';
                                                break;
                                            case 'link':
                                                $icon = 'link';
                                                $colorClass = 'success';
                                                break;
                                            case 'discussion':
                                                $icon = 'comments';
                                                $colorClass = 'secondary';
                                                break;
                                        }
                                        
                                        // Check if student has completed this content
                                        $completionClass = '';
                                        $completionStatus = null;
                                        
                                        if ($_SESSION['user_role'] === 'student') {
                                            $completion = getContentCompletion($_SESSION['user_id'], $content['id']);
                                            
                                            if ($completion && $completion['completion_status'] === 'completed') {
                                                $completionClass = 'bg-light text-success';
                                                $completionStatus = $completion;
                                            }
                                        }
                                ?>
                                    <a href="/courses/contents.php?action=view&content_id=<?php echo $content['id']; ?>&course_id=<?php echo $courseId; ?>" 
                                       class="list-group-item list-group-item-action <?php echo $completionClass; ?>">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h5 class="mb-1">
                                                <span class="badge bg-<?php echo $colorClass; ?> me-2">
                                                    <i class="fas fa-<?php echo $icon; ?>"></i>
                                                </span>
                                                <?php echo $contentCounter . '. ' . htmlspecialchars($content['title']); ?>
                                                
                                                <?php if ($completionStatus): ?>
                                                    <i class="fas fa-check-circle text-success ms-2"></i>
                                                <?php endif; ?>
                                            </h5>
                                            
                                            <?php if ($content['status'] !== 'published' && ($_SESSION['user_role'] === 'teacher' || $_SESSION['user_role'] === 'admin')): ?>
                                                <span class="badge bg-warning">
                                                    <?php echo ucfirst($content['status']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if (!empty($content['description'])): ?>
                                            <p class="mb-1">
                                                <?php echo mb_substr(htmlspecialchars($content['description']), 0, 100) . (strlen($content['description']) > 100 ? '...' : ''); ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                            <small class="text-muted">
                                                <?php if (isset($contentTypes[$content['content_type']])): ?>
                                                    <i class="fas fa-tag me-1"></i> <?php echo $contentTypes[$content['content_type']]; ?>
                                                <?php endif; ?>
                                            </small>
                                            
                                            <?php if (!empty($content['start_date'])): ?>
                                                <small class="text-muted">
                                                    <i class="far fa-calendar-alt me-1"></i> <?php echo formatDate($content['start_date']); ?>
                                                    <?php if (!empty($content['end_date'])): ?>
                                                        - <?php echo formatDate($content['end_date']); ?>
                                                    <?php endif; ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                <?php 
                                    $contentCounter++;
                                    endforeach; 
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
                break;
                
            case 'add':
                // Display form to add new content
                if ($_SESSION['user_role'] !== 'teacher' && $_SESSION['user_role'] !== 'admin') {
                    $_SESSION['error'] = 'You do not have permission to add content.';
                    redirect("/courses/contents.php?course_id=$courseId");
                }
                
                // Get content types
                $contentTypes = getContentTypes();
                ?>
                
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/courses/index.php"><?php echo __('courses'); ?></a></li>
                        <li class="breadcrumb-item"><a href="/courses/contents.php?course_id=<?php echo $courseId; ?>"><?php echo htmlspecialchars($course['name']); ?></a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo __('add_content'); ?></li>
                    </ol>
                </nav>
                
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h3 class="mb-0"><?php echo __('add_content'); ?></h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="/courses/contents.php?course_id=<?php echo $courseId; ?>&action=add" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="title" class="form-label"><?php echo __('content_title'); ?> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="content_type" class="form-label"><?php echo __('content_type'); ?> <span class="text-danger">*</span></label>
                                <select class="form-select" id="content_type" name="content_type" required>
                                    <option value="" selected disabled><?php echo __('select_content_type'); ?></option>
                                    <?php foreach ($contentTypes as $type => $description): ?>
                                        <option value="<?php echo $type; ?>"><?php echo $description; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label"><?php echo __('content_description'); ?></label>
                                <textarea class="form-control" id="description" name="description" rows="5"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="content_file" class="form-label"><?php echo __('file_upload'); ?></label>
                                <input type="file" class="form-control" id="content_file" name="content_file">
                                <div class="form-text"><?php echo __('file_upload_help'); ?></div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="start_date" class="form-label"><?php echo __('start_date'); ?></label>
                                    <input type="datetime-local" class="form-control" id="start_date" name="start_date">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="end_date" class="form-label"><?php echo __('end_date'); ?></label>
                                    <input type="datetime-local" class="form-control" id="end_date" name="end_date">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="status" class="form-label"><?php echo __('content_status'); ?></label>
                                <select class="form-select" id="status" name="status">
                                    <option value="draft" selected><?php echo __('draft'); ?></option>
                                    <option value="published"><?php echo __('published'); ?></option>
                                </select>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="/courses/contents.php?course_id=<?php echo $courseId; ?>" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i><?php echo __('cancel'); ?>
                                </a>
                                <button type="submit" name="add_content" class="btn btn-primary">
                                    <i class="fas fa-plus-circle me-1"></i><?php echo __('add_content'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php
                break;
                
            case 'edit':
                // Display form to edit content
                if ($_SESSION['user_role'] !== 'teacher' && $_SESSION['user_role'] !== 'admin') {
                    $_SESSION['error'] = 'You do not have permission to edit content.';
                    redirect("/courses/contents.php?course_id=$courseId");
                }
                
                // Get content types
                $contentTypes = getContentTypes();
                ?>
                
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/courses/index.php"><?php echo __('courses'); ?></a></li>
                        <li class="breadcrumb-item"><a href="/courses/contents.php?course_id=<?php echo $courseId; ?>"><?php echo htmlspecialchars($course['name']); ?></a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo __('edit_content'); ?></li>
                    </ol>
                </nav>
                
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h3 class="mb-0"><?php echo __('edit_content'); ?></h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="/courses/contents.php?course_id=<?php echo $courseId; ?>&content_id=<?php echo $contentId; ?>&action=edit" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="title" class="form-label"><?php echo __('content_title'); ?> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($content['title']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="content_type" class="form-label"><?php echo __('content_type'); ?> <span class="text-danger">*</span></label>
                                <select class="form-select" id="content_type" name="content_type" required>
                                    <?php foreach ($contentTypes as $type => $description): ?>
                                        <option value="<?php echo $type; ?>" <?php echo $content['content_type'] === $type ? 'selected' : ''; ?>>
                                            <?php echo $description; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label"><?php echo __('content_description'); ?></label>
                                <textarea class="form-control" id="description" name="description" rows="5"><?php echo htmlspecialchars($content['description']); ?></textarea>
                            </div>
                            
                            <?php if (!empty($content['file_path'])): ?>
                                <div class="mb-3">
                                    <div class="alert alert-info">
                                        <i class="fas fa-file me-2"></i>
                                        <?php echo __('current_file'); ?>: <a href="<?php echo $content['file_path']; ?>" target="_blank"><?php echo basename($content['file_path']); ?></a>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <label for="content_file" class="form-label"><?php echo __('file_upload'); ?></label>
                                <input type="file" class="form-control" id="content_file" name="content_file">
                                <div class="form-text"><?php echo __('file_upload_help'); ?></div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="start_date" class="form-label"><?php echo __('start_date'); ?></label>
                                    <input type="datetime-local" class="form-control" id="start_date" name="start_date" 
                                           value="<?php echo !empty($content['start_date']) ? date('Y-m-d\TH:i', strtotime($content['start_date'])) : ''; ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="end_date" class="form-label"><?php echo __('end_date'); ?></label>
                                    <input type="datetime-local" class="form-control" id="end_date" name="end_date"
                                           value="<?php echo !empty($content['end_date']) ? date('Y-m-d\TH:i', strtotime($content['end_date'])) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="status" class="form-label"><?php echo __('content_status'); ?></label>
                                <select class="form-select" id="status" name="status">
                                    <option value="draft" <?php echo $content['status'] === 'draft' ? 'selected' : ''; ?>><?php echo __('draft'); ?></option>
                                    <option value="published" <?php echo $content['status'] === 'published' ? 'selected' : ''; ?>><?php echo __('published'); ?></option>
                                </select>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="/courses/contents.php?course_id=<?php echo $courseId; ?>" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i><?php echo __('cancel'); ?>
                                </a>
                                <div>
                                    <button type="button" class="btn btn-danger me-2" data-bs-toggle="modal" data-bs-target="#deleteContentModal">
                                        <i class="fas fa-trash-alt me-1"></i><?php echo __('delete_content'); ?>
                                    </button>
                                    <button type="submit" name="update_content" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i><?php echo __('update'); ?>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Delete Content Modal -->
                <div class="modal fade" id="deleteContentModal" tabindex="-1" aria-labelledby="deleteContentModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="deleteContentModalLabel"><?php echo __('confirm_delete_content'); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p><?php echo __('delete_content_warning'); ?></p>
                                <p class="text-danger fw-bold"><?php echo __('this_action_cannot_be_undone'); ?></p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('cancel'); ?></button>
                                <form method="POST" action="/courses/contents.php?course_id=<?php echo $courseId; ?>&content_id=<?php echo $contentId; ?>&action=edit">
                                    <button type="submit" name="delete_content" class="btn btn-danger"><?php echo __('delete'); ?></button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
                break;
                
            case 'view':
                // Display content details
                
                // Get comments
                $comments = getContentComments($contentId);
                
                // Check if student has completed this content
                $completion = null;
                
                if ($_SESSION['user_role'] === 'student') {
                    $completion = getContentCompletion($_SESSION['user_id'], $contentId);
                }
                
                // Get content types
                $contentTypes = getContentTypes();
                ?>
                
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/courses/index.php"><?php echo __('courses'); ?></a></li>
                        <li class="breadcrumb-item"><a href="/courses/contents.php?course_id=<?php echo $courseId; ?>"><?php echo htmlspecialchars($course['name']); ?></a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($content['title']); ?></li>
                    </ol>
                </nav>
                
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="mb-0">
                                <?php 
                                    // Get icon based on content type
                                    $icon = 'file-alt';
                                    
                                    switch ($content['content_type']) {
                                        case 'lecture':
                                            $icon = 'file-alt';
                                            break;
                                        case 'video':
                                            $icon = 'video';
                                            break;
                                        case 'document':
                                            $icon = 'file-pdf';
                                            break;
                                        case 'assignment':
                                            $icon = 'tasks';
                                            break;
                                        case 'link':
                                            $icon = 'link';
                                            break;
                                        case 'discussion':
                                            $icon = 'comments';
                                            break;
                                    }
                                ?>
                                <i class="fas fa-<?php echo $icon; ?> me-2"></i><?php echo htmlspecialchars($content['title']); ?>
                            </h3>
                            
                            <?php if ($_SESSION['user_role'] === 'teacher' || $_SESSION['user_role'] === 'admin'): ?>
                                <a href="/courses/contents.php?action=edit&content_id=<?php echo $contentId; ?>&course_id=<?php echo $courseId; ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-edit me-1"></i><?php echo __('edit'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (isset($contentTypes[$content['content_type']])): ?>
                            <div class="mb-3">
                                <span class="badge bg-secondary fs-6">
                                    <i class="fas fa-tag me-1"></i> <?php echo $contentTypes[$content['content_type']]; ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($content['start_date']) || !empty($content['end_date'])): ?>
                            <div class="row mb-3">
                                <?php if (!empty($content['start_date'])): ?>
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong><?php echo __('start_date'); ?>:</strong></p>
                                        <p><?php echo formatDate($content['start_date']); ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($content['end_date'])): ?>
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong><?php echo __('end_date'); ?>:</strong></p>
                                        <p><?php echo formatDate($content['end_date']); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($content['description'])): ?>
                            <div class="mb-4">
                                <h5><?php echo __('description'); ?></h5>
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <?php echo nl2br(htmlspecialchars($content['description'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($content['file_path'])): ?>
                            <div class="mb-4">
                                <h5><?php echo __('attached_file'); ?></h5>
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <p class="mb-2"><?php echo __('file_name'); ?>: <?php echo basename($content['file_path']); ?></p>
                                        <a href="<?php echo $content['file_path']; ?>" class="btn btn-primary" target="_blank">
                                            <i class="fas fa-download me-1"></i><?php echo __('download_file'); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($_SESSION['user_role'] === 'student'): ?>
                            <div class="mb-4">
                                <?php if ($completion && $completion['completion_status'] === 'completed'): ?>
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <?php echo __('content_completed'); ?> - <?php echo formatDate($completion['completed_at']); ?>
                                    </div>
                                <?php else: ?>
                                    <form method="POST" action="/courses/contents.php?action=view&content_id=<?php echo $contentId; ?>&course_id=<?php echo $courseId; ?>">
                                        <button type="submit" name="mark_completed" class="btn btn-success">
                                            <i class="fas fa-check-circle me-1"></i><?php echo __('mark_as_completed'); ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-4">
                            <h5><?php echo __('discussion'); ?></h5>
                            
                            <div class="card bg-light mb-3">
                                <div class="card-body">
                                    <form method="POST" action="/courses/contents.php?action=view&content_id=<?php echo $contentId; ?>&course_id=<?php echo $courseId; ?>">
                                        <div class="mb-3">
                                            <label for="comment" class="form-label"><?php echo __('add_comment'); ?></label>
                                            <textarea class="form-control" id="comment" name="comment" rows="3" required></textarea>
                                        </div>
                                        <button type="submit" name="add_comment" class="btn btn-primary">
                                            <i class="fas fa-paper-plane me-1"></i><?php echo __('submit'); ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            
                            <?php if (empty($comments)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <?php echo __('no_comments'); ?>
                                </div>
                            <?php else: ?>
                                <div class="comments">
                                    <?php foreach ($comments as $comment): ?>
                                        <div class="card mb-3">
                                            <div class="card-header bg-light">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($comment['full_name']); ?></strong>
                                                        <span class="badge bg-secondary ms-2"><?php echo ucfirst($comment['user_role']); ?></span>
                                                    </div>
                                                    <small class="text-muted"><?php echo formatDate($comment['created_at']); ?></small>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <p class="card-text"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                                                
                                                <button class="btn btn-sm btn-outline-secondary reply-btn" 
                                                        data-bs-toggle="collapse" 
                                                        data-bs-target="#replyForm<?php echo $comment['id']; ?>" 
                                                        aria-expanded="false">
                                                    <i class="fas fa-reply me-1"></i><?php echo __('reply'); ?>
                                                </button>
                                                
                                                <div class="collapse mt-3" id="replyForm<?php echo $comment['id']; ?>">
                                                    <form method="POST" action="/courses/contents.php?action=view&content_id=<?php echo $contentId; ?>&course_id=<?php echo $courseId; ?>">
                                                        <input type="hidden" name="parent_id" value="<?php echo $comment['id']; ?>">
                                                        <div class="mb-3">
                                                            <textarea class="form-control" name="comment" rows="2" required></textarea>
                                                        </div>
                                                        <button type="submit" name="add_comment" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-paper-plane me-1"></i><?php echo __('submit_reply'); ?>
                                                        </button>
                                                    </form>
                                                </div>
                                                
                                                <?php if (!empty($comment['replies'])): ?>
                                                    <div class="ms-4 mt-3">
                                                        <?php foreach ($comment['replies'] as $reply): ?>
                                                            <div class="card mb-2">
                                                                <div class="card-header bg-light">
                                                                    <div class="d-flex justify-content-between align-items-center">
                                                                        <div>
                                                                            <strong><?php echo htmlspecialchars($reply['full_name']); ?></strong>
                                                                            <span class="badge bg-secondary ms-2"><?php echo ucfirst($reply['user_role']); ?></span>
                                                                        </div>
                                                                        <small class="text-muted"><?php echo formatDate($reply['created_at']); ?></small>
                                                                    </div>
                                                                </div>
                                                                <div class="card-body">
                                                                    <p class="card-text"><?php echo nl2br(htmlspecialchars($reply['comment'])); ?></p>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="/courses/contents.php?course_id=<?php echo $courseId; ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i><?php echo __('back_to_contents'); ?>
                        </a>
                    </div>
                </div>
                <?php
                break;
                
            default:
                // Redirect to content list
                redirect("/courses/contents.php?course_id=$courseId");
        }
        ?>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>