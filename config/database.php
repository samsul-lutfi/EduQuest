<?php
// Database configuration
$db_url = getenv('DATABASE_URL');

// Parse the database URL
$db_params = parse_url($db_url);
$db_host = $db_params['host'];
$db_port = isset($db_params['port']) ? $db_params['port'] : (getenv('PGPORT') ?: '5432');
$db_user = $db_params['user'];
$db_password = $db_params['pass'];
$db_name = ltrim($db_params['path'], '/');

// Create database connection
$conn_string = "host=$db_host port=$db_port dbname=$db_name user=$db_user password=$db_password sslmode=require";
$conn = pg_connect($conn_string);

// Check connection
if (!$conn) {
    // Try debug output
    error_log("Database connection failed. Connection string: " . preg_replace('/password=([^&\s]+)/', 'password=***', $conn_string));
    die("Database connection failed. Please check your configuration.");
}

// Set the port for the application
if (!defined('APP_PORT')) {
    define('APP_PORT', 5000);
}

/**
 * Execute a SQL query with proper error handling
 * 
 * @param string $sql The SQL query to execute
 * @param resource $connection The database connection
 * @return resource|bool The result of the query or false on failure
 */
function executeQuery($sql, $connection = null) {
    global $conn;
    $connection = $connection ?? $conn;
    
    $result = pg_query($connection, $sql);
    
    if (!$result) {
        // Log the error (in a production environment, use a proper logging system)
        error_log("PostgreSQL Error: " . pg_last_error($connection) . " in query: " . $sql);
        return false;
    }
    
    return $result;
}

/**
 * Sanitize input to prevent SQL injection
 * 
 * @param string $data The data to sanitize
 * @param resource $connection The database connection (not used in pg_escape_literal)
 * @return string The sanitized data
 */
function sanitizeInput($data, $connection = null) {
    global $conn;
    $connection = $connection ?? $conn;
    
    if (is_null($data)) {
        return '';
    }
    
    // Use pg_escape_string with explicit connection
    return pg_escape_string($connection, trim($data));
}

/**
 * Get a row from a result as an associative array
 * 
 * @param resource $result The result to fetch from
 * @return array|null The row as an associative array or null if no more rows
 */
function fetchAssoc($result) {
    return pg_fetch_assoc($result);
}

/**
 * Get all rows from a result as an associative array
 * 
 * @param resource $result The result to fetch from
 * @return array An array of rows
 */
function fetchAllAssoc($result) {
    $rows = [];
    while ($row = pg_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

/**
 * Get the number of rows in a result
 * 
 * @param resource $result The result to count
 * @return int The number of rows
 */
function numRows($result) {
    return pg_num_rows($result);
}

/**
 * Get the ID of the last inserted row
 * 
 * @param resource $connection The database connection
 * @param string $table The table to get the last ID from
 * @param string $id_column The column with the ID
 * @return int The ID of the last inserted row
 */
function lastInsertId($connection = null, $table = null, $id_column = 'id') {
    global $conn;
    $connection = $connection ?? $conn;
    
    if ($table) {
        $query = "SELECT CURRVAL(pg_get_serial_sequence('$table', '$id_column'))";
        $result = pg_query($connection, $query);
        if ($result && $row = pg_fetch_row($result)) {
            return $row[0];
        }
    }
    
    return 0;
}
?>
