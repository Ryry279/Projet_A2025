<?php
// /includes/functions.php

// --- Session Management ---
// Start session only if it hasn't been started yet to avoid errors.
if (session_status() == PHP_SESSION_NONE) {
    // Configure session cookie parameters for better security (optional but recommended)
    /*
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => $cookieParams['lifetime'], // Or set a specific lifetime, e.g., 0 for session-only
        'path' => $cookieParams['path'],         // Usually '/'
        'domain' => $cookieParams['domain'],     // Your domain
        'secure' => isset($_SERVER['HTTPS']),    // True if HTTPS
        'httponly' => true,                      // Prevent JavaScript access to session cookie
        'samesite' => 'Lax'                      // Mitigate CSRF attacks (Lax or Strict)
    ]);
    */
    session_start();
}

// --- User Authentication & Role Checks ---

/**
 * Checks if a user is currently logged in.
 * @return bool True if logged in, false otherwise.
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Checks if the logged-in user is an administrator.
 * @return bool True if admin, false otherwise.
 */
function isAdmin(): bool {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Checks if the logged-in user has premium student access (includes admins).
 * @return bool True if premium student or admin, false otherwise.
 */
function isPremiumStudent(): bool {
    return isLoggedIn() && isset($_SESSION['role']) && 
           ($_SESSION['role'] === 'premium_student' || $_SESSION['role'] === 'admin');
}

// --- Navigation & Redirection ---

/**
 * Redirects the user to a specified URL and exits the script.
 * @param string $url The URL to redirect to.
 */
function redirect(string $url): void {
    header("Location: " . $url);
    exit;
}
function createExcerpt(string $string, int $length = 100, string $suffix = '...'): string {
    // Check if mbstring extension is loaded for proper multibyte character handling
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($string, 'UTF-8') > $length) {
            return mb_substr($string, 0, $length, 'UTF-8') . $suffix;
        }
    } else {
        // Fallback for environments where mbstring is not enabled (less accurate for multibyte chars)
        if (strlen($string) > $length) {
            return substr($string, 0, $length) . $suffix;
        }
    }
    return $string; // Return the original string if it's shorter than $length
}

/**
 * Gets the base URL of the application.
 * Useful for constructing absolute URLs for assets and links.
 * @return string The base URL (e.g., "http://localhost/findyourcourse").
 */
function getBaseUrl(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost'; // Default to localhost if not set

    // If your project is in a subfolder of your web root (e.g., http://localhost/myproject/)
    // You need to define the subfolder.
    // Example: $subfolder = '/findyourcourse';
    // If your project is at the root, $subfolder should be an empty string.
    $subfolder = ''; // Assume project is at the root or configured via virtual host.
                     // If it's '/findyourcourse', change it here.

    // Remove trailing slash from subfolder if present, unless it's just "/"
    if ($subfolder !== '' && $subfolder !== '/' && substr($subfolder, -1) === '/') {
        $subfolder = rtrim($subfolder, '/');
    }
    
    return $protocol . $host . $subfolder;
}


// --- Input Sanitization & Security ---

/**
 * Sanitizes input data to prevent XSS and other attacks.
 * @param string $data The input string.
 * @return string The sanitized string.
 */
function sanitizeInput(string $data): string {
    $data = trim($data);
    $data = stripslashes($data); // Use with caution if magic quotes are on (though deprecated)
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8'); // ENT_QUOTES converts both double and single quotes
    return $data;
}

/**
 * Generates a CSRF token and stores it in the session.
 * @return string The generated CSRF token.
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validates a CSRF token.
 * @param string $token The token from the form submission.
 * @return bool True if valid, false otherwise.
 */
function validateCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}


// --- Database Interaction Helpers (Example) ---

/**
 * Checks if a course is favorited by the current logged-in user.
 * Requires an active database connection ($conn).
 * @param int $user_id The ID of the user.
 * @param int $course_id The ID of the course.
 * @param mysqli $db_connection The database connection object.
 * @return bool True if favorited, false otherwise.
 */
function isCourseFavorited(int $user_id, int $course_id, mysqli $db_connection): bool {
    if (!$user_id || !$db_connection) {
        return false;
    }
    $stmt = $db_connection->prepare("SELECT id FROM favorites WHERE user_id = ? AND course_id = ?");
    if (!$stmt) return false; // Failed to prepare statement
    
    $stmt->bind_param("ii", $user_id, $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result->num_rows > 0;
}

// --- Date & Time Formatting ---

/**
 * Formats a timestamp or date string into a user-friendly format.
 * @param string|int $timestamp The timestamp or date string.
 * @param string $format The desired date format (default: 'd/m/Y H:i').
 * @return string The formatted date string, or 'N/A' if invalid.
 */
function formatDisplayDate($timestamp, string $format = 'd/m/Y H:i'): string {
    if (empty($timestamp) || $timestamp === '0000-00-00 00:00:00') {
        return 'N/A';
    }
    try {
        $date = new DateTime($timestamp);
        return $date->format($format);
    } catch (Exception $e) {
        return 'N/A'; // Invalid date
    }
}

