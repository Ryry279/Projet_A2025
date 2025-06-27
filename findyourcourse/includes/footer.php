<?php
// /includes/footer.php
// Ensure functions.php is available for getBaseUrl() and potentially other functions
if (file_exists(__DIR__ . '/functions.php')) {
    require_once __DIR__ . '/functions.php';
    $baseUrl = getBaseUrl();
} else {
    // Fallback if functions.php is somehow not found, though it should be.
    // This prevents errors but indicates a structural problem.
    $baseUrl = ''; // Or a hardcoded default
    error_log("functions.php not found in /includes/footer.php");
}
?>
    </main> <footer>
        <div class="container">
            <section class="newsletter-signup reveal-on-scroll" style="margin-bottom: 30px;">
                <h4>Restez Informé de nos Nouveautés !</h4>
                <form action="<?php echo $baseUrl; ?>/subscribe_newsletter.php" method="POST" style="display:flex; flex-wrap:wrap; justify-content:center; align-items:center; gap:10px;">
                    <?php if (function_exists('generateCsrfToken')): ?>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
                    <?php endif; ?>
                    <label for="footer_newsletter_email" class="sr-only">Inscrivez-vous à notre newsletter :</label>
                    <input type="email" name="newsletter_email" id="footer_newsletter_email" placeholder="Votre adresse email" required 
                           style="flex-grow:1; max-width:350px; padding:12px; border-radius:8px; border:1px solid #ccc;">
                    <button type="submit" class="button">S'inscrire</button>
                </form>
                <div id="footer-newsletter-message" style="margin-top:10px; font-weight:500;">
                 <?php // Display newsletter subscription messages if they exist in the session
                 if (isset($_SESSION['newsletter_message'])): ?>
                    <p class="<?php echo (isset($_SESSION['newsletter_success']) && $_SESSION['newsletter_success']) ? 'success-message' : 'error-message'; ?>" 
                       style="color: <?php echo (isset($_SESSION['newsletter_success']) && $_SESSION['newsletter_success']) ? 'green' : 'red'; ?>;">
                        <?php 
                            echo htmlspecialchars($_SESSION['newsletter_message']); 
                            unset($_SESSION['newsletter_message']); 
                            if (isset($_SESSION['newsletter_success'])) {
                                unset($_SESSION['newsletter_success']); 
                            }
                        ?>
                    </p>
                <?php endif; ?>
                </div>
            </section>

            <div class="footer-links" style="margin-bottom: 20px; display:flex; flex-wrap:wrap; justify-content:center; gap: 10px 20px;">
                <a href="<?php echo $baseUrl; ?>/about.php">À Propos de Nous</a>
                <a href="<?php echo $baseUrl; ?>/courses.php">Nos Formations</a>
                <a href="<?php echo $baseUrl; ?>/contact.php">Contactez-Nous</a>
                <a href="<?php echo $baseUrl; ?>/faq.php">FAQ</a> </div>

            <p>&copy; <?php echo date("Y"); ?> Find Your Course. Tous droits réservés.</p>
            <p>Un projet réalisé par EME.</p> <p>
                <a href="<?php echo $baseUrl; ?>/terms.php">Conditions d'utilisation</a> | 
                <a href="<?php echo $baseUrl; ?>/privacy.php">Politique de confidentialité</a> |
                <a href="<?php echo $baseUrl; ?>/sitemap.php">Plan du site</a> </p>
            <p>
                <a href="<?php echo $baseUrl; ?>/security_info.php">Informations Sécurité</a> [cite: 38] | 
                <a href="<?php echo $baseUrl; ?>/maintenance_info.php">Contrat de Maintenance (Exemple)</a> [cite: 41] 
                </p>
        </div>
    </footer>

    <script src="<?php echo $baseUrl; ?>/assets/js/main.js?v=<?php echo time(); // Cache busting for development ?>"></script>
    
    <?php if (isset($page_specific_js) && is_array($page_specific_js)): ?>
        <?php foreach ($page_specific_js as $js_file): ?>
            <script src="<?php echo $baseUrl . htmlspecialchars($js_file); ?>?v=<?php echo time(); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

</body>
</html>