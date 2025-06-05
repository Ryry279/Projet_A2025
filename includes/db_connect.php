<?php
// /includes/db_connect.php

// --- Database Configuration ---
// Replace with your actual database credentials
define('DB_SERVER', 'localhost');       // Or your DB server address e.g., 127.0.0.1
define('DB_USERNAME', 'root');          // Your database username
define('DB_PASSWORD', 'root');              // Your database password (often empty for local XAMPP/MAMP default)
define('DB_NAME', 'findyourcourse_db'); // Your database name

// --- Create Connection ---
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// --- Check Connection ---
if ($conn->connect_error) {
    // In a production environment, you might want to log this error and display a user-friendly message.
    // For development, die() is okay for immediate feedback.
    error_log("Database Connection Failed: " . $conn->connect_error); // Log the error
    die("Sorry, we are experiencing some technical difficulties. Please try again later."); // User-friendly message
}

// --- Set Character Set to UTF-8 ---
// This is important for handling various languages and special characters correctly.
if (!$conn->set_charset("utf8mb4")) {
    error_log("Error loading character set utf8mb4: " . $conn->error);
    // Optional: die("Error setting character set.") if critical
}

// --- (Optional) Set Timezone for MySQL connection if needed ---
// $conn->query("SET time_zone = '+0:00'"); // Example: UTC

// --- (Optional) For Development: Echo success message ---
// Remove or comment out for production.
// echo "Database connected successfully and charset set to utf8mb4.";

// The $conn object is now available for use in any script that includes this file.
?>