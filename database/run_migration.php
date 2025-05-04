<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Ensure user is admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['error'] = 'You must be an administrator to access this page.';
    redirect('/dashboard/index.php');
}

// Display header
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration - EduQuest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 p-4">
                <h1>Database Migration Tools</h1>
                <p class="text-muted">This page allows administrators to run database migrations.</p>
                
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> Running migrations may modify the database structure. Always back up your database before proceeding.
                </div>
                
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h3 class="mb-0">Available Migrations</h3>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <a href="migrations/course_content_migration.php" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1">Course Content System</h5>
                                    <small class="text-muted">New</small>
                                </div>
                                <p class="mb-1">Adds course contents, improved enrollment tracking, and content completions.</p>
                                <small class="text-muted">Changes course and student_courses tables, adds course_contents, content_completions, and content_comments tables.</small>
                            </a>
                            
                            <a href="migrations/user_roles_migration.php" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1">Enhanced User Roles</h5>
                                    <small class="text-muted">New</small>
                                </div>
                                <p class="mb-1">Implements a refined role system with Admin, Teacher, Student, Observer, and Class Admin roles.</p>
                                <small class="text-muted">Creates roles table with detailed descriptions and permissions for each role type.</small>
                            </a>
                            
                            <a href="migrations/video_conference_migration.php" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1">Video Conference Integration</h5>
                                    <small class="text-muted">New</small>
                                </div>
                                <p class="mb-1">Adds support for BigBlueButton, Zoom, and Google Meet video conferencing.</p>
                                <small class="text-muted">Creates tables for conference scheduling, provider configuration, and participant tracking.</small>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="/dashboard/index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script src="/assets/js/script.js"></script>
</body>
</html>