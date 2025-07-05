<?php
// admin/manage_mails.php
$admin_page_title = "Gérer les Mails";
require_once '../includes/admin_header.php';

if (!isAdmin()) {
    redirect(getBaseUrl() . '/admin/login.php?error=unauthorized');
    exit;
}

$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
} elseif (isset($_GET['status'])) {
    if ($_GET['status'] === 'deleted') {
        $message = '<p class="admin-success-message">Mail supprimé avec succès.</p>';
    } elseif ($_GET['status'] === 'updated') {
        $message = '<p class="admin-success-message">Mail mis à jour.</p>';
    } 
}

// Recherche
$search_term = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$query = "SELECT id, titre, objet, message FROM mail_gestion";

if (!empty($search_term)) {
    $query .= " WHERE titre LIKE ? OR objet LIKE ?";
    $search_param = "%" . $search_term . "%";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $search_param, $search_param);
} else {
    $query .= " ORDER BY id DESC";
    $stmt = $conn->prepare($query);
}

$stmt->execute();
$mails_result = $stmt->get_result();
?>

<h2><?php echo htmlspecialchars($admin_page_title); ?></h2>

<?php echo $message; ?>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
    <form action="manage_mails.php" method="GET" style="display: inline-flex; gap: 5px;">
        <input type="text" name="search" placeholder="Rechercher un mail..."
            value="<?php echo htmlspecialchars($search_term); ?>"
            style="padding: 8px; border-radius:4px; border:1px solid #ccc;">
        <button type="submit" class="admin-button-secondary" style="padding: 8px 12px;">Rechercher</button>
        <?php if (!empty($search_term)): ?>
            <a href="manage_mails.php"
                style="padding: 8px 12px; text-decoration:none; color:#555; font-size:0.9em; align-self:center;">Effacer</a>
        <?php endif; ?>
    </form>
</div>

<?php if ($mails_result && $mails_result->num_rows > 0): ?>
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Titre</th>
                <th>Objet</th>
                <th>Message</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($mail = $mails_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $mail['id']; ?></td>
                    <td><?php echo htmlspecialchars($mail['titre']); ?></td>
                    <td><?php echo htmlspecialchars($mail['objet']); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($mail['message'])); ?></td>
                    <td class="actions">
                        <a href="edit_mail.php?id=<?php echo $mail['id']; ?>" class="edit-btn" title="Modifier">✏️</a>
                        <a href="send_newsletter.php?id=<?php echo $mail['id']; ?>" class="send-btn" title="Envoyer">📤</a>
                        <a href="delete_mail.php?id=<?php echo $mail['id']; ?>" class="delete-btn confirm-delete"
                            title="Supprimer">🗑️</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>Aucun mail trouvé. <a href="add_mail.php">Ajouter un nouveau mail ?</a></p>
<?php endif; ?>

<?php
$stmt->close();
if (isset($conn)) {
    $conn->close();
}
require_once '../includes/admin_footer.php';
?>