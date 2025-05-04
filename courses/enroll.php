<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once 'course_functions.php';

// Ensure user is logged in
requireLogin();

// Only teachers and admins can enroll/unenroll students
if ($_SESSION['user_role'] !== 'teacher' && $_SESSION['user_role'] !== 'admin') {
    $_SESSION['error'] = 'You do not have permission to perform this action.';
    redirect('/dashboard/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
    $studentId = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
    
    // Validate course and student IDs
    if ($courseId <= 0 || $studentId <= 0) {
        $_SESSION['error'] = 'Invalid course or student ID.';
        redirect('/courses/index.php');
    }
    
    // Get course details
    $course = getCourseById($courseId);
    
    // Check if course exists
    if (!$course) {
        $_SESSION['error'] = 'Course not found.';
        redirect('/courses/index.php');
    }
    
    // Check if user has permission to manage this course
    if ($_SESSION['user_role'] !== 'admin' && $course['teacher_id'] != $_SESSION['user_id']) {
        $_SESSION['error'] = 'You do not have permission to manage this course.';
        redirect('/courses/index.php');
    }
    
    // Enroll student
    if (isset($_POST['enroll'])) {
        $result = enrollStudent($studentId, $courseId);
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }
    }
    
    // Unenroll student
    if (isset($_POST['unenroll'])) {
        $result = unenrollStudent($studentId, $courseId);
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }
    }
    
    // Redirect back to course page
    redirect("/courses/view.php?id=$courseId");
} else {
    // Redirect to courses page if accessed directly
    redirect('/courses/index.php');
}