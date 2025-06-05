<?php
// /includes/admin_header.php
require_once __DIR__ . '/functions.php'; // Use __DIR__ for reliable path, starts session
require_once __DIR__ . '/db_connect.php'; // For database connection, if needed by header elements

// Crucial: Check if user is admin. If not, redirect to admin login or main site.
if (!isAdmin()) {
    // If a non-admin tries to access any page that includes this header,
    // they will be redirected.
    $_SESSION['error_message'] = "Accès non autorisé à la section d'administration.";
    redirect(getBaseUrl() . '/admin/login.php?error=unauthorized');
    // It's important that redirect() calls exit, so no further code is processed.
}

$baseUrl = getBaseUrl();
$admin_current_page_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Generate CSRF token for admin forms
$csrf_token_admin = generateCsrfToken(); // Use the same function, or a separate one if needed for admin scope
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow"> <title>Admin - <?php echo isset($admin_page_title) ? htmlspecialchars($admin_page_title) : 'Dashboard'; ?> - Find Your Course</title>
    
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/admin_style.css?v=<?php echo time(); ?>">
    <link rel="icon" href="<?php echo $baseUrl; ?>/assets/images/favicon_admin.png" type="image/png"> </head>
<body class="admin-body">
    <header class="admin-header">
        <h1>Administration - Find Your Course</h1>
        <nav aria-label="Navigation de l'administration">
            <ul>
                <li><a href="<?php echo $baseUrl; ?>/admin/" class="<?php echo ($admin_current_page_path === '/admin/' || $admin_current_page_path === '/admin/index.php') ? 'active' : ''; ?>">Tableau de Bord</a></li>
                <li><a href="<?php echo $baseUrl; ?>/admin/manage_courses.php" class="<?php echo (strpos($admin_current_page_path, '/admin/manage_courses.php') !== false || strpos($admin_current_page_path, '/admin/add_course.php') !== false || strpos($admin_current_page_path, '/admin/edit_course.php') !== false) ? 'active' : ''; ?>">Formations</a></li>
                <li><a href="<?php echo $baseUrl; ?>/admin/manage_users.php" class="<?php echo (strpos($admin_current_page_path, '/admin/manage_users.php') !== false) ? 'active' : ''; ?>">Utilisateurs</a></li>
                <li><a href="<?php echo $baseUrl; ?>/admin/manage_categories.php" class="<?php echo (strpos($admin_current_page_path, '/admin/manage_categories.php') !== false) ? 'active' : ''; ?>">Catégories</a></li>
                <li><a href="<?php echo $baseUrl; ?>/admin/manage_quizzes.php" class="<?php echo (strpos($admin_current_page_path, '/admin/manage_quizzes.php') !== false || strpos($admin_current_page_path, '/admin/manage_questions.php') !== false) ? 'active' : ''; ?>">Quiz</a></li>
                <li><a href="<?php echo $baseUrl; ?>/" target="_blank" title="Ouvrir le site dans un nouvel onglet">Voir le Site</a></li>
                <li><a href="<?php echo $baseUrl; ?>/admin/logout.php">Déconnexion (<?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>)</a></li>
            </ul>
        </nav>
    </header>
    <main class="admin-main"> <?php
    // Display session-based messages (e.g., after a form submission and redirect)
    if (isset($_SESSION['success_message'])) {
        echo '<p class="admin-success-message">' . htmlspecialchars($_SESSION['success_message']) . '</p>';
        unset($_SESSION['success_message']);
    }
    if (isset($_SESSION['error_message'])) {
        echo '<p class="admin-error-message">' . htmlspecialchars($_SESSION['error_message']) . '</p>';
        unset($_SESSION['error_message']);
    }
    ?>