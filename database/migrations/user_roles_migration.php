<?php
// Connect to the database
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// Create tables if they don't exist
$createTableQueries = [
    // Create roles table
    "CREATE TABLE IF NOT EXISTS roles (
        id SERIAL PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    // Insert default roles if they don't exist
    "INSERT INTO roles (name, description)
    VALUES 
        ('admin', 'Administrator sistem dengan akses penuh untuk mengelola pengguna, kelas, dan konfigurasi sistem.') 
    ON CONFLICT (name) DO NOTHING",
    
    "INSERT INTO roles (name, description)
    VALUES 
        ('teacher', 'Instruktur yang dapat membuat dan mengelola materi pembelajaran, video conference, dan penilaian.') 
    ON CONFLICT (name) DO NOTHING",
    
    "INSERT INTO roles (name, description)
    VALUES 
        ('student', 'Siswa yang dapat mengakses materi, mengikuti video conference, dan mengerjakan tugas.') 
    ON CONFLICT (name) DO NOTHING",
    
    "INSERT INTO roles (name, description)
    VALUES 
        ('observer', 'Pengamat yang dapat melihat proses pembelajaran tanpa interaksi langsung.') 
    ON CONFLICT (name) DO NOTHING",
    
    "INSERT INTO roles (name, description)
    VALUES 
        ('class_admin', 'Administrator kelas yang membantu pengelolaan teknis dan administratif di tingkat kelas.') 
    ON CONFLICT (name) DO NOTHING",
    
    // Add role_description column to users table
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS role_description TEXT"
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
        <h2>User Roles System</h2>";

if (!in_array(false, $results)) {
    echo "<div class='alert alert-success mt-4'>
            <i class='fas fa-check-circle me-2'></i>All roles were created or updated successfully!
          </div>
          
          <div class='mt-4'>
            <a href='/dashboard/index.php' class='btn btn-primary'>
                <i class='fas fa-home me-1'></i>Back to Dashboard
            </a>
          </div>";
} else {
    echo "<div class='alert alert-danger mt-4'>
            <i class='fas fa-exclamation-triangle me-2'></i>There were errors creating some roles:
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