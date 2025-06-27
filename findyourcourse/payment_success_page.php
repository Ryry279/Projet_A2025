<?php

$page_title = "Abonnement Réussi !";
$page_description = "Confirmation de votre passage à l'abonnement Premium Find Your Course.";
require_once 'includes/functions.php';

// On s'assure que l'utilisateur est connecté pour voir cette page.
if (!isLoggedIn()) {
    redirect(getBaseUrl() . '/login.php');
    exit;
}

require_once 'includes/header.php';
?>

<div class="container" style="text-align: center; padding: 40px 20px;">
    <div style="max-width: 600px; margin: auto;">
        <h1 style="color: #28a745; font-size: 2.5em;">✅ Félicitations !</h1>
        <p class="lead" style="font-size: 1.3em;">Vous êtes maintenant membre Premium.</p>
        <p>Votre compte a été mis à jour et vous avez désormais accès à l'intégralité de notre catalogue de formations, y compris les cours et vidéos exclusifs.</p>
        
        <div style="margin-top: 30px; display:flex; justify-content:center; gap: 15px; flex-wrap:wrap;">
            <a href="<?php echo getBaseUrl(); ?>/courses.php" class="button">Explorer les Formations</a>
            <a href="<?php echo getBaseUrl(); ?>/profile.php" class="button button-secondary">Voir mon Profil</a>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>