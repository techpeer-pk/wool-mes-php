<?php
/**
 * Error Handler - Hides sensitive errors from users
 * Save as: config/error-handler.php
 */

// In production, hide errors from users
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Custom error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Log the error
    error_log("Error [$errno]: $errstr in $errfile on line $errline");
    
    // Show friendly message to user
    if ($errno === E_ERROR || $errno === E_USER_ERROR) {
        die('An error occurred. Please contact support if this continues.');
    }
    
    return true;
});

// Database connection error handler
function safeDBConnect($host, $user, $pass, $db) {
    $conn = @mysqli_connect($host, $user, $pass, $db);
    
    if (!$conn) {
        // Log the real error
        error_log("Database connection failed: " . mysqli_connect_error());
        
        // Show friendly message
        die("Unable to connect to database. Please contact support.");
    }
    
    return $conn;
}
?>
