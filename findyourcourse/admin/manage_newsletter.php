<?php
// admin/manage_newsletter.php
$admin_page_title = "Gérer les Abonnés à la Newsletter";
require_once '../includes/admin_header.php';

if (!isAdmin()) {
    redirect(getBaseUrl() . '/admin/login.php?error=unauthorized');
    exit;
}

// Récupérer tous les abonnés
$subscribers_result = $conn->query("SELECT id, email, subscribed_at FROM newsletter_subscriptions ORDER BY subscribed_at DESC");
?>

<h2><?php echo htmlspecialchars($admin_page_title); ?></h2>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
    <p>Liste de tous les e-mails inscrits à la newsletter.</p>
    <?php if ($subscribers_result && $subscribers_result->num_rows > 0): ?>
        <a href="export_newsletter.php" class="admin-button-secondary">Exporter en CSV</a>
    <?php endif; ?>
</div>

<?php if ($subscribers_result && $subscribers_result->num_rows > 0): ?>
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Email de l'abonné</th>
                <th>Date d'inscription</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($subscriber = $subscribers_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $subscriber['id']; ?></td>
                <td><?php echo htmlspecialchars($subscriber['email']); ?></td>
                <td><?php echo formatDisplayDate($subscriber['subscribed_at']); // Utilise la fonction de functions.php ?></td>
                <td class="actions">
                    <form action="delete_subscriber.php" method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet abonné ?');">
                        <input type="hidden" name="id" value="<?php echo $subscriber['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token_admin); ?>">
                        <button type="submit" class="delete-btn" title="Supprimer l'abonné" style="border:none; background:transparent; cursor:pointer; padding:0;">🗑️</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>Aucun abonné à la newsletter pour le moment.</p>
<?php endif; ?>

<?php
if(isset($conn)) $conn->close();
require_once '../includes/admin_footer.php';
?>