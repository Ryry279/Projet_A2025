<?php
// /includes/header.php
// Ensure functions.php (which starts session) and db_connect.php are loaded.
// __DIR__ ensures the path is correct regardless of where header.php is included from.
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/db_connect.php'; // db_connect might be needed for dynamic header elements

$baseUrl = getBaseUrl(); // Get base URL for asset paths and links
$current_page_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); // Get current path without query string

// Generate CSRF token for forms (if not already generated)
$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - Find Your Course' : 'Find Your Course'; ?></title>
    
    <meta name="description" content="<?php echo isset($page_description) ? htmlspecialchars($page_description) : 'Plateforme e-learning pour débutants sur les ERP avec un focus Salesforce.'; ?>">
    <meta name="keywords" content="<?php echo isset($page_keywords) ? htmlspecialchars($page_keywords) : 'e-learning, ERP, Salesforce, formations, cours en ligne, débutants'; ?>">
    <meta name="author" content="[Nom de votre cabinet de conseil étudiant]"> <meta property="og:title" content="<?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Find Your Course'; ?>">
    <meta property="og:description" content="<?php echo isset($page_description) ? htmlspecialchars($page_description) : 'Découvrez nos formations ERP et Salesforce.'; ?>">
    <meta property="og:image" content="<?php echo $baseUrl; ?>/assets/images/og_image_placeholder.jpg"> <meta property="og:url" content="<?php echo $baseUrl . htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
    <meta property="og:type" content="website">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Find Your Course'; ?>">
    <meta name="twitter:description" content="<?php echo isset($page_description) ? htmlspecialchars($page_description) : 'Découvrez nos formations ERP et Salesforce.'; ?>">
    <meta name="twitter:image" content="<?php echo $baseUrl; ?>/assets/images/twitter_card_placeholder.jpg"> <link rel="icon" href="<?php echo $baseUrl; ?>/assets/images/favicon.png" type="image/png">
    <link rel="apple-touch-icon" href="<?php echo $baseUrl; ?>/assets/images/apple-touch-icon.png"> <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/style.css?v=<?php echo time(); // Cache busting for development ?>">
    
    <?php if (isset($page_specific_css) && is_array($page_specific_css)): ?>
        <?php foreach ($page_specific_css as $css_file): ?>
            <link rel="stylesheet" href="<?php echo $baseUrl . htmlspecialchars($css_file); ?>?v=<?php echo time(); ?>">
        <?php endforeach; ?>
    <?php endif; ?>

</head>
<body>
    <a href="#main-content" class="sr-only sr-only-focusable">Passer au contenu principal</a>

    <header>
        <a href="<?php echo $baseUrl; ?>/" class="logo">
            <img src="<?php echo $baseUrl; ?>/assets/images/logo.png" alt="Find Your Course Logo">
            </a>
        <nav aria-label="Navigation principale">
            <ul>
                <li><a href="<?php echo $baseUrl; ?>/" class="<?php echo ($current_page_path === '/' || $current_page_path === '/index.php') ? 'active' : ''; ?>">Accueil</a></li>
                <li><a href="<?php echo $baseUrl; ?>/courses.php" class="<?php echo ($current_page_path === '/courses.php') ? 'active' : ''; ?>">Formations</a></li>
                <li><a href="<?php echo $baseUrl; ?>/about.php" class="<?php echo ($current_page_path === '/about.php') ? 'active' : ''; ?>">À Propos</a></li>
                <li><a href="<?php echo $baseUrl; ?>/contact.php" class="<?php echo ($current_page_path === '/contact.php') ? 'active' : ''; ?>">Contact</a></li>
                
                <?php if (isLoggedIn()): ?>
                    <li><a href="<?php echo $baseUrl; ?>/profile.php" class="<?php echo ($current_page_path === '/profile.php') ? 'active' : ''; ?>">Profil</a></li>
                    <li><a href="<?php echo $baseUrl; ?>/favorites.php" class="<?php echo ($current_page_path === '/favorites.php') ? 'active' : ''; ?>">Favoris</a></li>
                    <li><a href="<?php echo $baseUrl; ?>/logout.php">Déconnexion</a></li>
                <?php else: ?>
                    <li><a href="<?php echo $baseUrl; ?>/login.php" class="<?php echo ($current_page_path === '/login.php') ? 'active' : ''; ?>">Connexion</a></li>
                    <li><a href="<?php echo $baseUrl; ?>/register.php" class="<?php echo ($current_page_path === '/register.php') ? 'active' : ''; ?>">Inscription</a></li>
                <?php endif; ?>
                
                <?php if (isAdmin()): ?>
                     <li><a href="<?php echo $baseUrl; ?>/admin/" style="color:tomato; font-weight:bold; background-color: rgba(255,99,71,0.1);" class="<?php echo (strpos($current_page_path, '/admin/') === 0) ? 'active' : ''; ?>">Admin</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        <form action="<?php echo $baseUrl; ?>/search.php" method="GET" class="search-bar" role="search">
            <label for="header-search" class="sr-only">Rechercher une formation</label>
            <input type="search" id="header-search" name="query" placeholder="Rechercher une formation..." 
                   aria-label="Rechercher une formation" value="<?php echo isset($_GET['query']) ? htmlspecialchars($_GET['query']) : ''; ?>">
            <button type="submit">Rechercher</button>
        </form>
    </header>
    
    <main id="main-content"> ```


<?php
