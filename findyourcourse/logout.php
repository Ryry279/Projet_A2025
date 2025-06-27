<?php
// logout.php (User-facing logout)
require_once 'includes/functions.php'; // Ensures session_start() is called and provides getBaseUrl()

// Unset all of the session variables related to the user login.
unset($_SESSION['user_id']);
unset($_SESSION['username']);
unset($_SESSION['email']); // If you store email in session
unset($_SESSION['role']);

// If you want to clear other specific session data related to user activity:
// unset($_SESSION['cart']); // Example

// Best practice: Regenerate session ID after logout to prevent session fixation issues
// if the same browser session is used to log in again by a different user or same user.
// It also helps ensure that the old session data (if any part of it was not unset)
// is not accessible with the old session ID if it was somehow compromised.
if (session_status() == PHP_SESSION_ACTIVE) {
    session_regenerate_id(true); // Regenerate ID and delete old session file
}

// Set a success message for the login page (optional)
$_SESSION['info_message'] = "Vous avez été déconnecté avec succès.";

// Redirect to the homepage or login page
redirect(getBaseUrl() . '/login.php');
exit;
?>