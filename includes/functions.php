<?php
/**
 * Redirect to a specified URL
 * 
 * @param string $url The URL to redirect to
 * @return void
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Display an error message
 * 
 * @param string $message The error message to display
 * @return string HTML for the error message
 */
function displayError($message) {
    return '<div class="alert alert-danger" role="alert">' . htmlspecialchars($message) . '</div>';
}

/**
 * Display a success message
 * 
 * @param string $message The success message to display
 * @return string HTML for the success message
 */
function displaySuccess($message) {
    return '<div class="alert alert-success" role="alert">' . htmlspecialchars($message) . '</div>';
}

/**
 * Display an info message
 * 
 * @param string $message The info message to display
 * @return string HTML for the info message
 */
function displayInfo($message) {
    return '<div class="alert alert-info" role="alert">' . htmlspecialchars($message) . '</div>';
}

/**
 * Check if the user is logged in
 * 
 * @return bool True if the user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if the user is an admin
 * 
 * @return bool True if the user is an admin, false otherwise
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Check if the user is a teacher
 * 
 * @return bool True if the user is a teacher, false otherwise
 */
function isTeacher() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'teacher';
}

/**
 * Check if the user is a student
 * 
 * @return bool True if the user is a student, false otherwise
 */
function isStudent() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'student';
}

/**
 * Ensure that the user is logged in, or redirect to the login page
 * 
 * @return void
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['error'] = "You must be logged in to access this page.";
        redirect("/auth/login.php");
    }
}

/**
 * Ensure that the user is an admin, or redirect to the dashboard
 * 
 * @return void
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        $_SESSION['error'] = "You must be an administrator to access this page.";
        redirect("/dashboard/index.php");
    }
}

/**
 * Ensure that the user is a teacher, or redirect to the dashboard
 * 
 * @return void
 */
function requireTeacher() {
    requireLogin();
    if (!isTeacher() && !isAdmin()) {
        $_SESSION['error'] = "You must be a teacher to access this page.";
        redirect("/dashboard/index.php");
    }
}

/**
 * Get the base URL of the application
 * 
 * @return string The base URL
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script = dirname($_SERVER['SCRIPT_NAME']);
    return $protocol . '://' . $host . $script;
}

/**
 * Format a date in a human-readable format
 * 
 * @param string $date The date to format in MySQL format (YYYY-MM-DD HH:MM:SS)
 * @param string $format The format to use (default: 'F j, Y g:i a')
 * @return string The formatted date
 */
function formatDate($date, $format = 'F j, Y g:i a') {
    return date($format, strtotime($date));
}

/**
 * Get user information by ID
 * 
 * @param int $userId The user ID
 * @return array|false The user information or false if not found
 */
function getUserById($userId) {
    global $conn;
    
    $userId = (int)$userId;
    $sql = "SELECT * FROM users WHERE id = $userId";
    $result = executeQuery($sql);
    
    if ($result && numRows($result) > 0) {
        return fetchAssoc($result);
    }
    
    return false;
}

/**
 * Get a list of all categories
 * 
 * @return array The list of categories
 */
function getAllCategories() {
    $sql = "SELECT * FROM achievement_categories ORDER BY name ASC";
    $result = executeQuery($sql);
    
    if (!$result) {
        return [];
    }
    
    return fetchAllAssoc($result);
}

/**
 * Get achievement category by ID
 * 
 * @param int $categoryId The category ID
 * @return array|false The category information or false if not found
 */
function getCategoryById($categoryId) {
    $categoryId = (int)$categoryId;
    $sql = "SELECT * FROM achievement_categories WHERE id = $categoryId";
    $result = executeQuery($sql);
    
    if ($result && numRows($result) > 0) {
        return fetchAssoc($result);
    }
    
    return false;
}

/**
 * Get a paginated list of students
 * 
 * @param int $page The page number (1-based)
 * @param int $perPage The number of items per page
 * @return array The list of students
 */
function getPaginatedStudents($page = 1, $perPage = 10) {
    $offset = ($page - 1) * $perPage;
    
    $sql = "SELECT id, name, email, created_at 
            FROM users 
            WHERE role = 'student' 
            ORDER BY name ASC 
            LIMIT $offset, $perPage";
    
    $result = executeQuery($sql);
    
    if (!$result) {
        return [];
    }
    
    return fetchAllAssoc($result);
}

/**
 * Count total number of students
 * 
 * @return int The total number of students
 */
function countStudents() {
    $sql = "SELECT COUNT(*) as count FROM users WHERE role = 'student'";
    $result = executeQuery($sql);
    
    if ($result && numRows($result) > 0) {
        $row = fetchAssoc($result);
        return (int)$row['count'];
    }
    
    return 0;
}

/**
 * Clean and validate input
 * 
 * @param string $data The data to clean
 * @return string The cleaned data
 */
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Generate a random token
 * 
 * @param int $length The length of the token
 * @return string The generated token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}
?>
