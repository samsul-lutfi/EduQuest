<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once 'course_functions.php';
require_once 'video_conference_functions.php';

// Ensure user is logged in
requireLogin();

// Get course ID from URL parameter
$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

// Get action from URL parameter
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// Get conference ID from URL parameter (for view/edit actions)
$conferenceId = isset($_GET['conference_id']) ? (int)$_GET['conference_id'] : 0;

// Validate course ID for all actions except 'dashboard' and 'list_all'
if ($courseId <= 0 && !in_array($action, ['dashboard', 'list_all'])) {
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

// Get conference details if conference ID is provided
$conference = null;
if ($conferenceId > 0) {
    $conference = getVideoConferenceById($conferenceId);

    // Check if conference exists
    if (!$conference) {
        $_SESSION['error'] = 'Video conference not found.';
        redirect("/courses/video_conference.php?course_id=$courseId");
    }

    // Check if user has access to this conference
    if ($_SESSION['user_role'] === 'student') {
        // Students can only view conferences in courses they're enrolled in
        $isEnrolled = isStudentEnrolled($_SESSION['user_id'], $conference['course_id']);

        if (!$isEnrolled) {
            $_SESSION['error'] = 'You do not have permission to view this conference.';
            redirect('/courses/index.php');
        }

        // Check if student is a participant
        $sql = "SELECT * FROM video_conference_participants 
                WHERE conference_id = $conferenceId AND user_id = " . $_SESSION['user_id'];
        $result = executeQuery($sql);

        if (!$result || pg_num_rows($result) == 0) {
            $_SESSION['error'] = 'You are not invited to this conference.';
            redirect("/courses/video_conference.php?course_id=" . $conference['course_id']);
        }
    } elseif ($_SESSION['user_role'] === 'teacher') {
        // Teachers can only manage conferences in their own courses
        if ($conference['host_id'] != $_SESSION['user_id'] && 
            !isCourseTeacher($_SESSION['user_id'], $conference['course_id'])) {
            $_SESSION['error'] = 'You do not have permission to manage this conference.';
            redirect('/courses/index.php');
        }
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create new conference
    if (isset($_POST['create_conference'])) {
        $startTime = $_POST['start_date'] . ' ' . $_POST['start_time'] . ':00';

        $conferenceData = [
            'course_id' => $_POST['course_id'], // Use course_id from the form
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'provider_id' => $_POST['provider_id'],
            'start_time' => $startTime,
            'duration' => $_POST['duration'],
            'host_id' => $_SESSION['user_id'],
            'status' => 'scheduled'
        ];

        if (isset($_POST['meeting_id']) && !empty($_POST['meeting_id'])) {
            $conferenceData['meeting_id'] = $_POST['meeting_id'];
        }

        if (isset($_POST['password']) && !empty($_POST['password'])) {
            $conferenceData['password'] = $_POST['password'];
        }

        if (isset($_POST['join_url']) && !empty($_POST['join_url'])) {
            $conferenceData['join_url'] = $_POST['join_url'];
        }

        $result = createVideoConference($conferenceData);

        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
            redirect("/courses/video_conference.php?course_id=" . $_POST['course_id']); // Redirect to the selected course
        } else {
            $_SESSION['error'] = $result['message'];
        }
    }

    // Update conference
    if (isset($_POST['update_conference'])) {
        $startTime = $_POST['start_date'] . ' ' . $_POST['start_time'] . ':00';

        $conferenceData = [
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'provider_id' => $_POST['provider_id'],
            'start_time' => $startTime,
            'duration' => $_POST['duration'],
            'status' => $_POST['status']
        ];

        if (isset($_POST['meeting_id']) && !empty($_POST['meeting_id'])) {
            $conferenceData['meeting_id'] = $_POST['meeting_id'];
        }

        if (isset($_POST['password']) && !empty($_POST['password'])) {
            $conferenceData['password'] = $_POST['password'];
        }

        if (isset($_POST['join_url']) && !empty($_POST['join_url'])) {
            $conferenceData['join_url'] = $_POST['join_url'];
        }

        if (isset($_POST['recording_url']) && !empty($_POST['recording_url'])) {
            $conferenceData['recording_url'] = $_POST['recording_url'];
        }

        $result = updateVideoConference($conferenceId, $conferenceData);

        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
            redirect("/courses/video_conference.php?course_id=" . $conference['course_id']);
        } else {
            $_SESSION['error'] = $result['message'];
        }
    }

    // Add participant
    if (isset($_POST['add_participant'])) {
        $userId = (int)$_POST['user_id'];

        $result = addVideoConferenceParticipant($conferenceId, $userId);

        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
            redirect("/courses/video_conference.php?action=view&conference_id=$conferenceId&course_id=" . $conference['course_id']);
        } else {
            $_SESSION['error'] = $result['message'];
        }
    }

    // Update provider settings
    if (isset($_POST['update_provider'])) {
        $providerId = (int)$_POST['provider_id'];

        $providerData = [
            'api_key' => $_POST['api_key'],
            'api_secret' => $_POST['api_secret'],
            'enabled' => isset($_POST['enabled']) ? true : false,
            'config' => $_POST['config']
        ];

        $result = updateVideoConferenceProvider($providerId, $providerData);

        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
            redirect("/courses/video_conference.php?action=settings");
        } else {
            $_SESSION['error'] = $result['message'];
        }
    }
}

// Page title
$pageTitle = "Video Conferences - " . ($course ? htmlspecialchars($course['name']) . ' - ' : '') . "EduQuest";

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
            case 'dashboard':
                // Display user's upcoming conferences
                $upcomingConferences = getUserUpcomingConferences($_SESSION['user_id']);

                // Today's date
                $today = date('Y-m-d');
                // Get today's conferences
                $todayConferences = getUserConferencesInDateRange($_SESSION['user_id'], $today, $today);

                // Next 7 days
                $nextWeekEnd = date('Y-m-d', strtotime('+7 days'));
                // Get next week's conferences
                $weekConferences = getUserConferencesInDateRange($_SESSION['user_id'], date('Y-m-d', strtotime('+1 day')), $nextWeekEnd);

                ?>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><?php echo __('video_conferences'); ?></h2>

                    <?php if ($_SESSION['user_role'] === 'teacher' || $_SESSION['user_role'] === 'admin'): ?>
                        <a href="/courses/video_conference.php?action=list_all" class="btn btn-primary">
                            <i class="fas fa-list me-1"></i><?php echo __('all_conferences'); ?>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Today's conferences -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-calendar-day me-2"></i><?php echo __('today_conferences'); ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($todayConferences)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <?php echo __('no_conferences_today'); ?>
                            </div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($todayConferences as $conf): ?>
                                    <div class="list-group-item list-group-item-action">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h5 class="mb-1"><?php echo htmlspecialchars($conf['title']); ?></h5>
                                                <p class="mb-1 text-muted">
                                                    <i class="fas fa-book me-1"></i> <?php echo htmlspecialchars($conf['course_name']); ?> | 
                                                    <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($conf['host_name']); ?>
                                                </p>
                                                <p class="mb-0">
                                                    <i class="fas fa-clock me-1"></i> <?php echo formatConferenceDate($conf['start_time'], 'H:i'); ?> | 
                                                    <i class="fas fa-hourglass-half me-1"></i> <?php echo formatConferenceDuration($conf['duration']); ?>
                                                </p>
                                            </div>
                                            <div>
                                                <?php if (!empty($conf['join_url'])): ?>
                                                    <a href="<?php echo $conf['join_url']; ?>" target="_blank" class="btn btn-primary">
                                                        <i class="fas fa-video me-1"></i><?php echo __('join_now'); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="/courses/video_conference.php?action=view&conference_id=<?php echo $conf['id']; ?>&course_id=<?php echo $conf['course_id']; ?>" class="btn btn-info">
                                                        <i class="fas fa-info-circle me-1"></i><?php echo __('details'); ?>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Upcoming conferences -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i><?php echo __('upcoming_conferences'); ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($weekConferences)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <?php echo __('no_upcoming_conferences'); ?>
                            </div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($weekConferences as $conf): ?>
                                    <div class="list-group-item list-group-item-action">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h5 class="mb-1"><?php echo htmlspecialchars($conf['title']); ?></h5>
                                                <p class="mb-1 text-muted">
                                                    <i class="fas fa-book me-1"></i> <?php echo htmlspecialchars($conf['course_name']); ?> | 
                                                    <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($conf['host_name']); ?>
                                                </p>
                                                <p class="mb-0">
                                                    <i class="fas fa-calendar me-1"></i> <?php echo formatConferenceDate($conf['start_time'], 'd M Y'); ?> | 
                                                    <i class="fas fa-clock me-1"></i> <?php echo formatConferenceDate($conf['start_time'], 'H:i'); ?> | 
                                                    <i class="fas fa-hourglass-half me-1"></i> <?php echo formatConferenceDuration($conf['duration']); ?>
                                                </p>
                                            </div>
                                            <div>
                                                <a href="/courses/video_conference.php?action=view&conference_id=<?php echo $conf['id']; ?>&course_id=<?php echo $conf['course_id']; ?>" class="btn btn-info">
                                                    <i class="fas fa-info-circle me-1"></i><?php echo __('details'); ?>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
                break;

            case 'list':
                // List conferences for a specific course
                $conferences = getCourseVideoConferences($courseId);

                // Group conferences by status
                $upcomingConferences = [];
                $pastConferences = [];
                $now = date('Y-m-d H:i:s');

                foreach ($conferences as $conf) {
                    if ($conf['status'] === 'canceled') {
                        continue; // Skip canceled conferences
                    }

                    if ($conf['start_time'] > $now) {
                        $upcomingConferences[] = $conf;
                    } else {
                        $pastConferences[] = $conf;
                    }
                }
                ?>

                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/courses/index.php"><?php echo __('courses'); ?></a></li>
                        <li class="breadcrumb-item"><a href="/courses/view.php?id=<?php echo $courseId; ?>"><?php echo htmlspecialchars($course['name']); ?></a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo __('video_conferences'); ?></li>
                    </ol>
                </nav>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><?php echo __('video_conferences'); ?></h2>

                    <?php if ($_SESSION['user_role'] === 'teacher' || $_SESSION['user_role'] === 'admin'): ?>
                        <a href="/courses/video_conference.php?action=add&course_id=<?php echo $courseId; ?>" class="btn btn-primary">
                            <i class="fas fa-plus-circle me-1"></i><?php echo __('create_conference'); ?>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Upcoming conferences -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i><?php echo __('upcoming_conferences'); ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($upcomingConferences)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <?php echo __('no_upcoming_conferences'); ?>
                            </div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($upcomingConferences as $conf): ?>
                                    <div class="list-group-item list-group-item-action">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h5 class="mb-1"><?php echo htmlspecialchars($conf['title']); ?></h5>
                                                <p class="mb-1 text-muted">
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($conf['provider_name']); ?></span>
                                                    <i class="fas fa-user ms-2 me-1"></i> <?php echo htmlspecialchars($conf['host_name']); ?> | 
                                                    <i class="fas fa-users me-1"></i> <?php echo $conf['participant_count']; ?> <?php echo __('participants'); ?>
                                                </p>
                                                <p class="mb-0">
                                                    <i class="fas fa-calendar me-1"></i> <?php echo formatConferenceDate($conf['start_time'], 'd M Y'); ?> | 
                                                    <i class="fas fa-clock me-1"></i> <?php echo formatConferenceDate($conf['start_time'], 'H:i'); ?> | 
                                                    <i class="fas fa-hourglass-half me-1"></i> <?php echo formatConferenceDuration($conf['duration']); ?>
                                                </p>
                                            </div>
                                            <div>
                                                <a href="/courses/video_conference.php?action=view&conference_id=<?php echo $conf['id']; ?>&course_id=<?php echo $courseId; ?>" class="btn btn-info">
                                                    <i class="fas fa-info-circle me-1"></i><?php echo __('details'); ?>
                                                </a>
                                                <?php if (!empty($conf['join_url'])): ?>
                                                    <a href="<?php echo $conf['join_url']; ?>" target="_blank" class="btn btn-primary">
                                                        <i class="fas fa-video me-1"></i><?php echo __('join'); ?>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Past conferences -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i><?php echo __('past_conferences'); ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pastConferences)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <?php echo __('no_past_conferences'); ?>
                            </div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php 
                                    // Sort past conferences in reverse chronological order
                                    usort($pastConferences, function($a, $b) {
                                        return strtotime($b['start_time']) - strtotime($a['start_time']);
                                    });

                                    // Display only the 5 most recent past conferences
                                    $recentPastConferences = array_slice($pastConferences, 0, 5);

                                    foreach ($recentPastConferences as $conf): 
                                ?>
                                    <div class="list-group-item list-group-item-action">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h5 class="mb-1"><?php echo htmlspecialchars($conf['title']); ?></h5>
                                                <p class="mb-1 text-muted">
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($conf['provider_name']); ?></span>
                                                    <i class="fas fa-user ms-2 me-1"></i> <?php echo htmlspecialchars($conf['host_name']); ?>
                                                </p>
                                                <p class="mb-0">
                                                    <i class="fas fa-calendar me-1"></i> <?php echo formatConferenceDate($conf['start_time'], 'd M Y'); ?> | 
                                                    <i class="fas fa-clock me-1"></i> <?php echo formatConferenceDate($conf['start_time'], 'H:i'); ?>
                                                </p>
                                            </div>
                                            <div>
                                                <a href="/courses/video_conference.php?action=view&conference_id=<?php echo $conf['id']; ?>&course_id=<?php echo $courseId; ?>" class="btn btn-info">
                                                    <i class="fas fa-info-circle me-1"></i><?php echo __('details'); ?>
                                                </a>
                                                <?php if (!empty($conf['recording_url'])): ?>
                                                    <a href="<?php echo $conf['recording_url']; ?>" target="_blank" class="btn btn-success">
                                                        <i class="fas fa-play-circle me-1"></i><?php echo __('view_recording'); ?>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <?php if (count($pastConferences) > 5): ?>
                                    <div class="text-center mt-3">
                                        <a href="/courses/video_conference.php?action=archive&course_id=<?php echo $courseId; ?>" class="btn btn-outline-secondary">
                                            <i class="fas fa-history me-1"></i><?php echo __('view_all_past_conferences'); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
                break;

            case 'list_all':
                // List all conferences (for teachers and admins)
                if ($_SESSION['user_role'] !== 'teacher' && $_SESSION['user_role'] !== 'admin') {
                    $_SESSION['error'] = 'You do not have permission to view all conferences.';
                    redirect('/dashboard/index.php');
                }

                // Get teacher's courses
                $teacherId = $_SESSION['user_role'] === 'teacher' ? $_SESSION['user_id'] : null;
                $courses = getCoursesWithEnrollmentCounts($teacherId);

                // Get all upcoming conferences
                $allUpcomingConferences = [];
                $now = date('Y-m-d H:i:s');

                foreach ($courses as $course) {
                    $courseConferences = getCourseVideoConferences($course['id']);

                    foreach ($courseConferences as $conf) {
                        if ($conf['status'] !== 'canceled' && $conf['start_time'] > $now) {
                            $conf['course_name'] = $course['name'];
                            $allUpcomingConferences[] = $conf;
                        }
                    }
                }

                // Sort conferences by start time
                usort($allUpcomingConferences, function($a, $b) {
                    return strtotime($a['start_time']) - strtotime($b['start_time']);
                });
                ?>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><?php echo __('all_video_conferences'); ?></h2>

                    <div>
                        <?php if ($_SESSION['user_role'] === 'teacher' || $_SESSION['user_role'] === 'admin'): ?>
                            <a href="/courses/video_conference.php?action=add" class="btn btn-success me-2">
                                <i class="fas fa-plus-circle me-1"></i><?php echo __('create_conference'); ?>
                            </a>
                        <?php endif; ?>
                        <a href="/courses/video_conference.php?action=dashboard" class="btn btn-primary">
                            <i class="fas fa-tachometer-alt me-1"></i><?php echo __('conference_dashboard'); ?>
                        </a>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i><?php echo __('upcoming_conferences'); ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($allUpcomingConferences)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <?php echo __('no_upcoming_conferences'); ?>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th><?php echo __('title'); ?></th>
                                            <th><?php echo __('course'); ?></th>
                                            <th><?php echo __('date'); ?></th>
                                            <th><?php echo __('time'); ?></th>
                                            <th><?php echo __('duration'); ?></th>
                                            <th><?php echo __('provider'); ?></th>
                                            <th><?php echo __('participants'); ?></th>
                                            <th><?php echo __('actions'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($allUpcomingConferences as $conf): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($conf['title']); ?></td>
                                                <td><?php echo htmlspecialchars($conf['course_name']); ?></td>
                                                <td><?php echo formatConferenceDate($conf['start_time'], 'd M Y'); ?></td>
                                                <td><?php echo formatConferenceDate($conf['start_time'], 'H:i'); ?></td>
                                                <td><?php echo formatConferenceDuration($conf['duration']); ?></td>
                                                <td><?php echo htmlspecialchars($conf['provider_name']); ?></td>
                                                <td><?php echo $conf['participant_count']; ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="/courses/video_conference.php?action=view&conference_id=<?php echo $conf['id']; ?>&course_id=<?php echo $conf['course_id']; ?>" class="btn btn-sm btn-info">
                                                            <i class="fas fa-info-circle"></i>
                                                        </a>
                                                        <?php if (!empty($conf['join_url'])): ?>
                                                            <a href="<?php echo $conf['join_url']; ?>" target="_blank" class="btn btn-sm btn-primary">
                                                                <i class="fas fa-video"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        <?php if ($_SESSION['user_role'] === 'admin' || $conf['host_id'] == $_SESSION['user_id']): ?>
                                                            <a href="/courses/video_conference.php?action=edit&conference_id=<?php echo $conf['id']; ?>&course_id=<?php echo $conf['course_id']; ?>" class="btn btn-sm btn-warning">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                        <?php endif; ?>
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

                <div class="d-flex justify-content-between mb-4">
                    <a href="/courses/index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i><?php echo __('back_to_courses'); ?>
                    </a>

                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                        <a href="/courses/video_conference.php?action=settings" class="btn btn-primary">
                            <i class="fas fa-cog me-1"></i><?php echo __('provider_settings'); ?>
                        </a>
                    <?php endif; ?>
                </div>
                <?php
                break;

            case 'add':
                // Display form to add new conference
                if ($_SESSION['user_role'] !== 'teacher' && $_SESSION['user_role'] !== 'admin') {
                    $_SESSION['error'] = 'You do not have permission to create conferences.';
                    redirect("/courses/video_conference.php?course_id=$courseId");
                }

                // Get available providers
                $providers = getVideoConferenceProviders();
                ?>

                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/courses/index.php"><?php echo __('courses'); ?></a></li>
                        <li class="breadcrumb-item"><a href="/courses/view.php?id=<?php echo $courseId; ?>"><?php echo htmlspecialchars($course['name']); ?></a></li>
                        <li class="breadcrumb-item"><a href="/courses/video_conference.php?course_id=<?php echo $courseId; ?>"><?php echo __('video_conferences'); ?></a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo __('create_conference'); ?></li>
                    </ol>
                </nav>

                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h3 class="mb-0"><?php echo __('create_conference'); ?></h3>
                    </div>
                    <div class="card-body">
                        <?php if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'teacher'): ?>
                            <div class="mb-3">
                                <label for="course_id" class="form-label"><?php echo __('select_course'); ?> <span class="text-danger">*</span></label>
                                <select class="form-select" id="course_id" name="course_id" required>
                                    <option value="" selected disabled><?php echo __('select_course'); ?></option>
                                    <?php
                                    $teacherCourses = getTeacherCourses($_SESSION['user_id']);
                                    foreach ($teacherCourses as $course): ?>
                                        <option value="<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <?php if (empty($providers)): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo __('no_providers_enabled'); ?>

                                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                    <p class="mt-2 mb-0">
                                        <a href="/courses/video_conference.php?action=settings" class="btn btn-primary btn-sm">
                                            <i class="fas fa-cog me-1"></i><?php echo __('configure_providers'); ?>
                                        </a>
                                    </p>
                                <?php else: ?>
                                    <p class="mt-2 mb-0">
                                        <?php echo __('contact_admin_configure_providers'); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <form method="POST" action="/courses/video_conference.php?action=add&course_id=<?php echo $courseId; ?>">
                                <div class="mb-3">
                                    <label for="title" class="form-label"><?php echo __('conference_title'); ?> <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label"><?php echo __('description'); ?></label>
                                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="provider_id" class="form-label"><?php echo __('video_provider'); ?> <span class="text-danger">*</span></label>
                                    <select class="form-select" id="provider_id" name="provider_id" required>
                                        <option value="" selected disabled><?php echo __('select_provider'); ?></option>
                                        <?php foreach ($providers as $provider): ?>
                                            <option value="<?php echo $provider['id']; ?>"><?php echo htmlspecialchars($provider['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="start_date" class="form-label"><?php echo __('start_date'); ?> <span class="text-danger">*</span></label>                                        <input type="date" class="form-control" id="start_date" name="start_date" min="<?php echo date('Y-m-d'); ?>" required>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="start_time" class="form-label"><?php echo __('start_time'); ?> <span class="text-danger">*</span></label>
                                        <input type="time" class="form-control" id="start_time" name="start_time" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="duration" class="form-label"><?php echo __('duration_minutes'); ?> <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="duration" name="duration" min="15" max="240" value="60" required>
                                    <div class="form-text"><?php echo __('duration_help'); ?></div>
                                </div>

                                <div class="mb-3" id="additional_info">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <span id="provider_instructions"><?php echo __('select_provider_instructions'); ?></span>
                                    </div>
                                </div>

                                <div class="mb-3 d-none" id="manual_details">
                                    <h5><?php echo __('manual_meeting_details'); ?></h5>
                                    <div class="form-text mb-3"><?php echo __('manual_meeting_help'); ?></div>

                                    <div class="mb-3">
                                        <label for="meeting_id" class="form-label"><?php echo __('meeting_id'); ?></label>
                                        <input type="text" class="form-control" id="meeting_id" name="meeting_id">
                                    </div>

                                    <div class="mb-3">
                                        <label for="password" class="form-label"><?php echo __('meeting_password'); ?></label>
                                        <input type="text" class="form-control" id="password" name="password">
                                    </div>

                                    <div class="mb-3">
                                        <label for="join_url" class="form-label"><?php echo __('join_url'); ?></label>
                                        <input type="url" class="form-control" id="join_url" name="join_url">
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="/courses/video_conference.php?course_id=<?php echo $courseId; ?>" class="btn btn-secondary">
                                        <i class="fas fa-times me-1"></i><?php echo __('cancel'); ?>
                                    </a>
                                    <button type="submit" name="create_conference" class="btn btn-primary">
                                        <i class="fas fa-plus-circle me-1"></i><?php echo __('create_conference'); ?>
                                    </button>
                                </div>
                            </form>

                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const providerSelect = document.getElementById('provider_id');
                                    const additionalInfo = document.getElementById('additional_info');
                                    const providerInstructions = document.getElementById('provider_instructions');
                                    const manualDetails = document.getElementById('manual_details');

                                    providerSelect.addEventListener('change', function() {
                                        const providerId = this.value;
                                        const providerName = this.options[this.selectedIndex].text;

                                        // Show provider-specific instructions
                                        if (providerId) {
                                            additionalInfo.classList.remove('d-none');
                                            manualDetails.classList.remove('d-none');

                                            if (providerName === 'BigBlueButton') {
                                                providerInstructions.textContent = "<?php echo __('bbb_instructions'); ?>";
                                            } else if (providerName === 'Zoom') {
                                                providerInstructions.textContent = "<?php echo __('zoom_instructions'); ?>";
                                            } else if (providerName === 'Google Meet') {
                                                providerInstructions.textContent = "<?php echo __('google_meet_instructions'); ?>";
                                            } else {
                                                providerInstructions.textContent = "<?php echo __('provider_instructions'); ?>";
                                            }
                                        } else {
                                            additionalInfo.classList.add('d-none');
                                            manualDetails.classList.add('d-none');
                                        }
                                    });
                                });
                            </script>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
                break;

            case 'edit':
                // Display form to edit conference
                if ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_id'] != $conference['host_id']) {
                    $_SESSION['error'] = 'You do not have permission to edit this conference.';
                    redirect("/courses/video_conference.php?course_id=" . $conference['course_id']);
                }

                // Get available providers
                $providers = getVideoConferenceProviders(false);
                ?>

                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/courses/index.php"><?php echo __('courses'); ?></a></li>
                        <li class="breadcrumb-item"><a href="/courses/view.php?id=<?php echo $conference['course_id']; ?>"><?php echo htmlspecialchars($conference['course_name']); ?></a></li>
                        <li class="breadcrumb-item"><a href="/courses/video_conference.php?course_id=<?php echo $conference['course_id']; ?>"><?php echo __('video_conferences'); ?></a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo __('edit_conference'); ?></li>
                    </ol>
                </nav>

                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h3 class="mb-0"><?php echo __('edit_conference'); ?></h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="/courses/video_conference.php?action=edit&conference_id=<?php echo $conferenceId; ?>&course_id=<?php echo $conference['course_id']; ?>">
                            <div class="mb-3">
                                <label for="title" class="form-label"><?php echo __('conference_title'); ?> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($conference['title']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label"><?php echo __('description'); ?></label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($conference['description']); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="provider_id" class="form-label"><?php echo __('video_provider'); ?> <span class="text-danger">*</span></label>
                                <select class="form-select" id="provider_id" name="provider_id" required>
                                    <?php foreach ($providers as $provider): ?>
                                        <option value="<?php echo $provider['id']; ?>" <?php echo $conference['provider_id'] == $provider['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($provider['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="start_date" class="form-label"><?php echo __('start_date'); ?> <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo date('Y-m-d', strtotime($conference['start_time'])); ?>" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="start_time" class="form-label"><?php echo __('start_time'); ?> <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="start_time" name="start_time" value="<?php echo date('H:i', strtotime($conference['start_time'])); ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="duration" class="form-label"><?php echo __('duration_minutes'); ?> <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="duration" name="duration" min="15" max="240" value="<?php echo $conference['duration']; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label"><?php echo __('status'); ?> <span class="text-danger">*</span></label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="scheduled" <?php echo $conference['status'] === 'scheduled' ? 'selected' : ''; ?>><?php echo __('scheduled'); ?></option>
                                    <option value="ongoing" <?php echo $conference['status'] === 'ongoing' ? 'selected' : ''; ?>><?php echo __('ongoing'); ?></option>
                                    <option value="completed" <?php echo $conference['status'] === 'completed' ? 'selected' : ''; ?>><?php echo __('completed'); ?></option>
                                    <option value="canceled" <?php echo $conference['status'] === 'canceled' ? 'selected' : ''; ?>><?php echo __('canceled'); ?></option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="meeting_id" class="form-label"><?php echo __('meeting_id'); ?></label>
                                <input type="text" class="form-control" id="meeting_id" name="meeting_id" value="<?php echo htmlspecialchars($conference['meeting_id']); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label"><?php echo __('meeting_password'); ?></label>
                                <input type="text" class="form-control" id="password" name="password" value="<?php echo htmlspecialchars($conference['password']); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="join_url" class="form-label"><?php echo __('join_url'); ?></label>
                                <input type="url" class="form-control" id="join_url" name="join_url" value="<?php echo htmlspecialchars($conference['join_url']); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="recording_url" class="form-label"><?php echo __('recording_url'); ?></label>
                                <input type="url" class="form-control" id="recording_url" name="recording_url" value="<?php echo htmlspecialchars($conference['recording_url']); ?>">
                                <div class="form-text"><?php echo __('recording_url_help'); ?></div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="/courses/video_conference.php?action=view&conference_id=<?php echo $conferenceId; ?>&course_id=<?php echo $conference['course_id']; ?>" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i><?php echo __('cancel'); ?>
                                </a>
                                <button type="submit" name="update_conference" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i><?php echo __('update_conference'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php
                break;

            case 'view':
                // Display conference details
                $participants = getVideoConferenceParticipants($conferenceId);

                // Check if user is host or has edit rights
                $canEdit = $_SESSION['user_role'] === 'admin' || $conference['host_id'] == $_SESSION['user_id'];

                // Determine if the conference is active
                $isScheduled = $conference['status'] === 'scheduled';
                $isActive = $conference['status'] === 'ongoing';
                $isCompleted = $conference['status'] === 'completed';
                $isCanceled = $conference['status'] === 'canceled';

                // Format dates
                $formattedDate = formatConferenceDate($conference['start_time'], 'd M Y');
                $formattedTime = formatConferenceDate($conference['start_time'], 'H:i');
                $formattedDuration = formatConferenceDuration($conference['duration']);
                ?>

                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/courses/index.php"><?php echo __('courses'); ?></a></li>
                        <li class="breadcrumb-item"><a href="/courses/view.php?id=<?php echo $conference['course_id']; ?>"><?php echo htmlspecialchars($conference['course_name']); ?></a></li>
                        <li class="breadcrumb-item"><a href="/courses/video_conference.php?course_id=<?php echo $conference['course_id']; ?>"><?php echo __('video_conferences'); ?></a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($conference['title']); ?></li>
                    </ol>
                </nav>

                <div class="row">
                    <div class="col-lg-8">
                        <!-- Conference details -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h3 class="mb-0"><?php echo htmlspecialchars($conference['title']); ?></h3>

                                    <div>
                                        <?php if ($isCanceled): ?>
                                            <span class="badge bg-danger"><?php echo __('canceled'); ?></span>
                                        <?php elseif ($isCompleted): ?>
                                            <span class="badge bg-success"><?php echo __('completed'); ?></span>
                                        <?php elseif ($isActive): ?>
                                            <span class="badge bg-primary"><?php echo __('ongoing'); ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-info"><?php echo __('scheduled'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($conference['description'])): ?>
                                    <div class="mb-4">
                                        <h5><?php echo __('description'); ?></h5>
                                        <p><?php echo nl2br(htmlspecialchars($conference['description'])); ?></p>
                                    </div>
                                <?php endif; ?>

                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <h5><?php echo __('conference_details'); ?></h5>
                                        <ul class="list-group">
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span><i class="fas fa-calendar me-2"></i><?php echo __('date'); ?>:</span>
                                                <span class="fw-bold"><?php echo $formattedDate; ?></span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span><i class="fas fa-clock me-2"></i><?php echo __('time'); ?>:</span>
                                                <span class="fw-bold"><?php echo $formattedTime; ?></span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span><i class="fas fa-hourglass-half me-2"></i><?php echo __('duration'); ?>:</span>
                                                <span class="fw-bold"><?php echo $formattedDuration; ?></span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span><i class="fas fa-video me-2"></i><?php echo __('provider'); ?>:</span>
                                                <span class="fw-bold"><?php echo htmlspecialchars($conference['provider_name']); ?></span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span><i class="fas fa-user me-2"></i><?php echo __('host'); ?>:</span>
                                                <span class="fw-bold"><?php echo htmlspecialchars($conference['host_name']); ?></span>
                                            </li>
                                        </ul>
                                    </div>

                                    <?php if (!empty($conference['meeting_id']) || !empty($conference['password'])): ?>
                                        <div class="col-md-6">
                                            <h5><?php echo __('connection_details'); ?></h5>
                                            <ul class="list-group">
                                                <?php if (!empty($conference['meeting_id'])): ?>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <span><i class="fas fa-hashtag me-2"></i><?php echo __('meeting_id'); ?>:</span>
                                                        <span class="fw-bold"><?php echo htmlspecialchars($conference['meeting_id']); ?></span>
                                                    </li>
                                                <?php endif; ?>

                                                <?php if (!empty($conference['password'])): ?>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <span><i class="fas fa-key me-2"></i><?php echo __('password'); ?>:</span>
                                                        <span class="fw-bold"><?php echo htmlspecialchars($conference['password']); ?></span>
                                                    </li>
                                                <?php endif; ?>

                                                <?php if (!empty($conference['recording_url'])): ?>
                                                    <li class="list-group-item">
                                                        <div>
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <span><i class="fas fa-play-circle me-2"></i><?php echo __('recording'); ?>:</span>
                                                            </div>
                                                            <a href="<?php echo $conference['recording_url']; ?>" target="_blank" class="btn btn-outline-success btn-sm mt-2 w-100">
                                                                <i class="fas fa-external-link-alt me-1"></i><?php echo __('view_recording'); ?>
                                                            </a>
                                                        </div>
                                                    </li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if (!$isCanceled && !$isCompleted): ?>
                                    <div class="d-grid gap-2">
                                        <?php if (!empty($conference['join_url'])): ?>
                                            <a href="<?php echo $conference['join_url']; ?>" target="_blank" class="btn btn-primary btn-lg">
                                                <i class="fas fa-video me-1"></i><?php echo __('join_conference'); ?>
                                            </a>
                                        <?php else: ?>
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle me-2"></i>
                                                <?php echo __('no_join_url'); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php elseif ($isCanceled): ?>
                                    <div class="alert alert-danger">
                                        <i class="fas fa-ban me-2"></i>
                                        <?php echo __('conference_canceled'); ?>
                                    </div>
                                <?php elseif ($isCompleted && empty($conference['recording_url'])): ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <?php echo __('conference_completed_no_recording'); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer d-flex justify-content-between">
                                <a href="/courses/video_conference.php?course_id=<?php echo $conference['course_id']; ?>" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-1"></i><?php echo __('back'); ?>
                                </a>

                                <?php if ($canEdit): ?>
                                    <a href="/courses/video_conference.php?action=edit&conference_id=<?php echo $conferenceId; ?>&course_id=<?php echo $conference['course_id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-edit me-1"></i><?php echo __('edit_conference'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <!-- Participants list -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <i class="fas fa-users me-2"></i><?php echo __('participants'); ?> (<?php echo count($participants); ?>)
                                    </h5>

                                    <?php if ($_SESSION['user_role'] === 'teacher' || $_SESSION['user_role'] === 'admin'): ?>
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addParticipantModal">
                                            <i class="fas fa-user-plus me-1"></i><?php echo __('add'); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($participants)): ?>
                                    <div class="p-4 text-center">
                                        <p class="mb-0"><?php echo __('no_participants'); ?></p>
                                    </div>
                                <?php else: ?>
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($participants as $participant): ?>
                                            <li class="list-group-item">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <div class="fw-bold">
                                                            <?php echo htmlspecialchars($participant['name']); ?>

                                                            <?php if ($participant['user_id'] == $conference['host_id']): ?>
                                                                <span class="badge bg-primary ms-1"><?php echo __('host'); ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="small text-muted">
                                                            <?php echo ucfirst($participant['user_role']); ?> | <?php echo htmlspecialchars($participant['email']); ?>
                                                        </div>
                                                    </div>

                                                    <?php 
                                                        $statusClass = 'bg-secondary';
                                                        $statusText = ucfirst($participant['attendance_status']);

                                                        if ($participant['attendance_status'] === 'joined') {
                                                            $statusClass = 'bg-success';
                                                        } elseif ($participant['attendance_status'] === 'absent') {
                                                            $statusClass = 'bg-danger';
                                                        } elseif ($participant['attendance_status'] === 'late') {
                                                            $statusClass = 'bg-warning';
                                                        }
                                                    ?>
                                                    <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                                </div>

                                                <?php if ($participant['join_time']): ?>
                                                    <div class="small mt-1">
                                                        <i class="fas fa-sign-in-alt me-1"></i> <?php echo formatConferenceDate($participant['join_time']); ?>
                                                        <?php if ($participant['leave_time']): ?>
                                                            <i class="fas fa-sign-out-alt ms-2 me-1"></i> <?php echo formatConferenceDate($participant['leave_time']); ?>
                                                        <?php endif; ?>
                                                        <?php if ($participant['duration']): ?>
                                                            <i class="fas fa-clock ms-2 me-1"></i> <?php echo $participant['duration']; ?> <?php echo __('minutes'); ?>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($_SESSION['user_role'] === 'teacher' || $_SESSION['user_role'] === 'admin'): ?>
                    <!-- Add Participant Modal -->
                    <div class="modal fade" id="addParticipantModal" tabindex="-1" aria-labelledby="addParticipantModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="addParticipantModalLabel"><?php echo __('add_participant'); ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form method="POST" action="/courses/video_conference.php?action=view&conference_id=<?php echo $conferenceId; ?>&course_id=<?php echo $conference['course_id']; ?>" id="addParticipantForm">
                                        <div class="mb-3">
                                            <label for="user_id" class="form-label"><?php echo __('select_user'); ?></label>
                                            <select class="form-select" id="user_id" name="user_id" required>
                                                <option value="" selected disabled><?php echo __('select_user'); ?></option>

                                                <?php
                                                // Get all users who are not already participants
                                                $existingParticipantIds = array_column($participants, 'user_id');
                                                $existingParticipantIdsStr = implode(',', $existingParticipantIds);
                                                $existingClause = !empty($existingParticipantIdsStr) ? "AND id NOT IN ($existingParticipantIdsStr)" : "";

                                                $sql = "SELECT id, name, email, role FROM users 
                                                        WHERE (role = 'student' OR role = 'teacher' OR role = 'observer') 
                                                        $existingClause
                                                        ORDER BY role, name";
                                                $result = executeQuery($sql);

                                                $usersByRole = [
                                                    'teacher' => [],
                                                    'student' => [],
                                                    'observer' => [],
                                                    'class_admin' => []
                                                ];

                                                if ($result) {
                                                    while ($user = pg_fetch_assoc($result)) {
                                                        $usersByRole[$user['role']][] = $user;
                                                    }
                                                }

                                                // Output users grouped by role
                                                foreach ($usersByRole as $role => $users) {
                                                    if (!empty($users)) {
                                                        echo '<optgroup label="' . ucfirst($role) . 's">';
                                                        foreach ($users as $user) {
                                                            echo '<option value="' . $user['id'] . '">' . 
                                                                htmlspecialchars($user['name']) . ' (' . htmlspecialchars($user['email']) . ')</option>';
                                                        }
                                                        echo '</optgroup>';
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('cancel'); ?></button>
                                    <button type="submit" form="addParticipantForm" name="add_participant" class="btn btn-primary"><?php echo __('add_participant'); ?></button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                <?php
                break;

            case 'settings':
                // Display provider settings (admin only)
                if ($_SESSION['user_role'] !== 'admin') {
                    $_SESSION['error'] = 'You do not have permission to access provider settings.';
                    redirect('/dashboard/index.php');
                }

                // Get all providers
                $providers = getVideoConferenceProviders(false);
                ?>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><?php echo __('video_provider_settings'); ?></h2>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-cogs me-2"></i><?php echo __('configure_providers'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <?php echo __('provider_settings_help'); ?>
                        </div>

                        <div class="accordion" id="providersAccordion">
                            <?php foreach ($providers as $provider): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading<?php echo $provider['id']; ?>">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $provider['id']; ?>" aria-expanded="false" aria-controls="collapse<?php echo $provider['id']; ?>">
                                            <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                                <span><?php echo htmlspecialchars($provider['name']); ?></span>
                                                <?php if ($provider['enabled']): ?>
                                                    <span class="badge bg-success"><?php echo __('enabled'); ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger"><?php echo __('disabled'); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="collapse<?php echo $provider['id']; ?>" class="accordion-collapse collapse" aria-labelledby="heading<?php echo $provider['id']; ?>" data-bs-parent="#providersAccordion">
                                        <div class="accordion-body">
                                            <form method="POST" action="/courses/video_conference.php?action=settings">
                                                <input type="hidden" name="provider_id" value="<?php echo $provider['id']; ?>">

                                                <div class="mb-3 form-check">
                                                    <input type="checkbox" class="form-check-input" id="enabled<?php echo $provider['id']; ?>" name="enabled" <?php echo $provider['enabled'] ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="enabled<?php echo $provider['id']; ?>"><?php echo __('enable_provider'); ?></label>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="api_key<?php echo $provider['id']; ?>" class="form-label"><?php echo __('api_key'); ?></label>
                                                    <input type="text" class="form-control" id="api_key<?php echo $provider['id']; ?>" name="api_key" value="<?php echo htmlspecialchars($provider['api_key']); ?>">
                                                </div>

                                                <div class="mb-3">
                                                    <label for="api_secret<?php echo $provider['id']; ?>" class="form-label"><?php echo __('api_secret'); ?></label>
                                                    <input type="password" class="form-control" id="api_secret<?php echo $provider['id']; ?>" name="api_secret" value="<?php echo htmlspecialchars($provider['api_secret']); ?>">
                                                </div>

                                                <div class="mb-3">
                                                    <label for="config<?php echo $provider['id']; ?>" class="form-label"><?php echo __('additional_config'); ?></label>
                                                    <textarea class="form-control" id="config<?php echo $provider['id']; ?>" name="config" rows="4"><?php echo htmlspecialchars($provider['config']); ?></textarea>
                                                    <div class="form-text">
                                                        <?php 
                                                            $configHelp = '';

                                                            switch ($provider['name']) {
                                                                case 'BigBlueButton':
                                                                    $configHelp = __('bbb_config_help');
                                                                    break;
                                                                case 'Zoom':
                                                                    $configHelp = __('zoom_config_help');
                                                                    break;
                                                                case 'Google Meet':
                                                                    $configHelp = __('google_meet_config_help');
                                                                    break;
                                                                default:
                                                                    $configHelp = __('default_config_help');
                                                            }

                                                            echo $configHelp;
                                                        ?>
                                                    </div>
                                                </div>

                                                <div class="d-grid">
                                                    <button type="submit" name="update_provider" class="btn btn-primary">
                                                        <i class="fas fa-save me-1"></i><?php echo __('save_settings'); ?>
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between mb-4">
                    <a href="/courses/video_conference.php?action=dashboard" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i><?php echo __('back_to_dashboard'); ?>
                    </a>
                </div>
                <?php
                break;

            default:
                // Redirect to the course page
                redirect("/courses/video_conference.php?course_id=$courseId");
        }
        ?>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>