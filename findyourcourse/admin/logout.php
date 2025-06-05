<?php
// admin/logout.php
require_once '../includes/functions.php'; // Ensures session_start() is called and provides getBaseUrl()

// Unset all of the session variables related to the user.
unset($_SESSION['user_id']);
unset($_SESSION['username']);
unset($_SESSION['role']);

// If you want to destroy the entire session (including other potential session data not related to login):
// $_SESSION = array(); // Clear all session variables
// if (ini_get("session.use_cookies")) {
//     $params = session_get_cookie_params();
//     setcookie(session_name(), '', time() - 42000,
//         $params["path"], $params["domain"],
//         $params["secure"], $params["httponly"]
//     );
// }
// session_destroy(); // Destroy the session itself

// For a simpler logout that just clears user-specific session data:
// The above unset calls are often sufficient if you don't have other critical session data.
// However, for admin logout, fully destroying and regenerating is often good practice.

// Best practice: Regenerate session ID after logout to prevent session fixation if the user logs back in
// If not fully destroying, at least regenerate:
if (session_status() == PHP_SESSION_ACTIVE) {
    session_regenerate_id(true); // Regenerate ID and delete old session file
}


redirect(getBaseUrl() . '/admin/login.php?logged_out=true');
exit;
?>