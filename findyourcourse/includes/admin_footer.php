<?php
// /includes/admin_footer.php
$baseUrl = getBaseUrl(); // From functions.php
?>
    </main> <footer class="admin-footer">
        <p>&copy; <?php echo date("Y"); ?> Find Your Course - Panneau d'Administration</p>
        <p>Tous droits réservés.</p>
    </footer>

    <script src="<?php echo $baseUrl; ?>/assets/js/admin.js?v=<?php echo time(); // Cache busting ?>"></script>
    
    <?php if (isset($admin_page_specific_js) && is_array($admin_page_specific_js)): ?>
        <?php foreach ($admin_page_specific_js as $js_file): ?>
            <script src="<?php echo $baseUrl . htmlspecialchars($js_file); ?>?v=<?php echo time(); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

</body>
</html>