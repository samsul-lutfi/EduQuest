<?php
session_start();
require_once '../includes/functions.php';

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
redirect('/auth/login.php');
?>
