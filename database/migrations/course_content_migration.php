<?php
// Connect to the database
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// Create tables if they don't exist
$createTableQueries = [
    // Updating courses table with additional fields
    "ALTER TABLE courses ADD COLUMN IF NOT EXISTS course_code VARCHAR(50) DEFAULT NULL",
    "ALTER TABLE courses ADD COLUMN IF NOT EXISTS semester VARCHAR(50) DEFAULT NULL",
    "ALTER TABLE courses ADD COLUMN IF NOT EXISTS academic_year VARCHAR(50) DEFAULT NULL", 
    "ALTER TABLE courses ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT 'active'",

    // Create course_contents (activities) table
    "CREATE TABLE IF NOT EXISTS course_contents (
        id SERIAL PRIMARY KEY,
        course_id INTEGER NOT NULL,
        title VARCHAR(255) NOT NULL,
        content_type VARCHAR(50) NOT NULL,
        description TEXT,
        file_path VARCHAR(255) DEFAULT NULL,
        start_date TIMESTAMP DEFAULT NULL,
        end_date TIMESTAMP DEFAULT NULL,
        status VARCHAR(20) DEFAULT 'draft',
        order_index INTEGER DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT NULL,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    )",

    // Update enrollments (student_courses) table with additional fields 
    "ALTER TABLE student_courses ADD COLUMN IF NOT EXISTS role VARCHAR(20) DEFAULT 'student'",
    "ALTER TABLE student_courses ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT 'active'",
    "ALTER TABLE student_courses ADD COLUMN IF NOT EXISTS completion_date TIMESTAMP DEFAULT NULL",
    
    // Create content_completions table to track student progress
    "CREATE TABLE IF NOT EXISTS content_completions (
        id SERIAL PRIMARY KEY,
        student_id INTEGER NOT NULL,
        content_id INTEGER NOT NULL,
        completion_status VARCHAR(20) DEFAULT 'incomplete',
        grade INTEGER DEFAULT NULL, 
        feedback TEXT DEFAULT NULL,
        completed_at TIMESTAMP DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT NULL,
        FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (content_id) REFERENCES course_contents(id) ON DELETE CASCADE,
        UNIQUE(student_id, content_id)
    )",
    
    // Create content_comments table for discussions
    "CREATE TABLE IF NOT EXISTS content_comments (
        id SERIAL PRIMARY KEY,
        content_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        parent_id INTEGER DEFAULT NULL,
        comment TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT NULL,
        FOREIGN KEY (content_id) REFERENCES course_contents(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (parent_id) REFERENCES content_comments(id) ON DELETE CASCADE
    )"
];

// Execute the queries
$results = [];
foreach ($createTableQueries as $query) {
    $result = executeQuery($query);
    $results[] = $result !== false;
}

// Output results
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Migration - EduQuest</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
    <link rel='stylesheet' href='/assets/css/style.css'>
</head>
<body>
    <div class='container mt-4'>
        <div class='mb-4'>
            <a href='/database/run_migration.php' class='btn btn-secondary'>
                <i class='fas fa-arrow-left me-1'></i>Back to Migrations
            </a>
        </div>
        
        <h1>Database Migration</h1>
        <h2>Course Content System Tables</h2>";

if (!in_array(false, $results)) {
    echo "<div class='alert alert-success mt-4'>
            <i class='fas fa-check-circle me-2'></i>All tables were created or updated successfully!
          </div>
          
          <div class='mt-4'>
            <a href='/dashboard/index.php' class='btn btn-primary'>
                <i class='fas fa-home me-1'></i>Back to Dashboard
            </a>
          </div>";
} else {
    echo "<div class='alert alert-danger mt-4'>
            <i class='fas fa-exclamation-triangle me-2'></i>There were errors creating some tables:
          </div>";
          
    foreach ($createTableQueries as $index => $query) {
        $status = $results[$index] ? "Success" : "Failed";
        $alertClass = $results[$index] ? "alert-success" : "alert-danger";
        $icon = $results[$index] ? "check-circle" : "times-circle";
        
        echo "<div class='card mb-3'>
                <div class='card-header {$alertClass}'>
                    <i class='fas fa-{$icon} me-2'></i>{$status}
                </div>
                <div class='card-body'>
                    <pre class='mb-0'>{$query}</pre>
                </div>
              </div>";
    }
    
    echo "<div class='mt-4'>
            <a href='/database/run_migration.php' class='btn btn-primary'>
                <i class='fas fa-arrow-left me-1'></i>Back to Migrations
            </a>
          </div>";
}

echo "</div>
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js'></script>
</body>
</html>";
exit;
?>