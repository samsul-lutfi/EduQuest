<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Redirect to dashboard if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard/index.php");
    exit();
}

// Otherwise redirect to login page
header("Location: auth/login.php");
exit();
?>
