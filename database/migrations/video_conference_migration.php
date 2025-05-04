<?php
// Connect to the database
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// Create tables if they don't exist
$createTableQueries = [
    // Create video_conference_providers table
    "CREATE TABLE IF NOT EXISTS video_conference_providers (
        id SERIAL PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE,
        api_key VARCHAR(255),
        api_secret VARCHAR(255),
        enabled BOOLEAN DEFAULT TRUE,
        config TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT NULL
    )",

    // Insert default providers if they don't exist
    "INSERT INTO video_conference_providers (name)
    VALUES 
        ('BigBlueButton') 
    ON CONFLICT (name) DO NOTHING",
    
    "INSERT INTO video_conference_providers (name)
    VALUES 
        ('Zoom') 
    ON CONFLICT (name) DO NOTHING",
    
    "INSERT INTO video_conference_providers (name)
    VALUES 
        ('Google Meet') 
    ON CONFLICT (name) DO NOTHING",
    
    // Create video_conferences table
    "CREATE TABLE IF NOT EXISTS video_conferences (
        id SERIAL PRIMARY KEY,
        course_id INTEGER NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        provider_id INTEGER NOT NULL,
        meeting_id VARCHAR(255),
        password VARCHAR(255),
        join_url VARCHAR(255),
        start_time TIMESTAMP NOT NULL,
        duration INTEGER NOT NULL, -- in minutes
        host_id INTEGER NOT NULL,
        status VARCHAR(20) DEFAULT 'scheduled', -- scheduled, ongoing, completed, canceled
        recording_url VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT NULL,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
        FOREIGN KEY (provider_id) REFERENCES video_conference_providers(id) ON DELETE RESTRICT,
        FOREIGN KEY (host_id) REFERENCES users(id) ON DELETE RESTRICT
    )",
    
    // Create video_conference_participants table
    "CREATE TABLE IF NOT EXISTS video_conference_participants (
        id SERIAL PRIMARY KEY,
        conference_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        attendance_status VARCHAR(20) DEFAULT 'invited', -- invited, joined, absent, late
        join_time TIMESTAMP,
        leave_time TIMESTAMP,
        duration INTEGER, -- in minutes
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT NULL,
        FOREIGN KEY (conference_id) REFERENCES video_conferences(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE(conference_id, user_id)
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
        <h2>Video Conference System</h2>";

if (!in_array(false, $results)) {
    echo "<div class='alert alert-success mt-4'>
            <i class='fas fa-check-circle me-2'></i>All video conference tables were created or updated successfully!
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