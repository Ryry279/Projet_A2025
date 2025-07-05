<?php
// /includes/admin_header.php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/db_connect.php';

if (!isAdmin()) {
    redirect(getBaseUrl() . '/admin/login.php?error=unauthorized');
}

$baseUrl = getBaseUrl();
$admin_current_page_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$csrf_token_admin = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - <?php echo isset($admin_page_title) ? htmlspecialchars($admin_page_title) : 'Dashboard'; ?></title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/admin_style.css?v=<?php echo time(); ?>">
</head>

<body class="admin-body">
    <header class="admin-header">
        <h1>Administration - Find Your Course</h1>
        <nav aria-label="Navigation de l'administration">
            <ul>
                <li><a href="<?php echo $baseUrl; ?>/admin/">Tableau de Bord</a></li>
                <li><a href="<?php echo $baseUrl; ?>/admin/manage_courses.php">Formations</a></li>
                <li><a href="<?php echo $baseUrl; ?>/admin/manage_users.php">Utilisateurs</a></li>
                <li><a href="<?php echo $baseUrl; ?>/admin/manage_categories.php">Catégories</a></li>
                <li><a href="<?php echo $baseUrl; ?>/admin/manage_quizzes.php">Quiz</a></li>
                <li><a href="<?php echo $baseUrl; ?>/admin/manage_newsletter.php">Newsletter</a></li>
                <li><a href="<?php echo $baseUrl; ?>/admin/manage_mails.php">Mails</a></li>
                <li><a href="<?php echo $baseUrl; ?>/admin/manage_settings.php">Paramètres</a></li>

                <li><a href="<?php echo $baseUrl; ?>/" target="_blank">Voir le Site</a></li>
                <li><a href="<?php echo $baseUrl; ?>/admin/logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>
    <main class="admin-main">
        <?php
        if (isset($_SESSION['message'])) {
            echo $_SESSION['message'];
            unset($_SESSION['message']);
        }
        ?>