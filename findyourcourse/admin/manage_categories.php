<?php
// admin/manage_categories.php
$admin_page_title = "Gérer les Catégories";
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
        $message = '<p class="admin-success-message">Catégorie supprimée avec succès.</p>';
    } elseif ($_GET['status'] === 'error_notempty') {
        $message = '<p class="admin-error-message">Erreur : Impossible de supprimer la catégorie car elle contient des formations.</p>';
    } elseif ($_GET['status'] === 'error') {
        $message = '<p class="admin-error-message">Une erreur est survenue.</p>';
    }
}

// Fetch categories
$stmt = $conn->prepare("SELECT id, name, description FROM categories ORDER BY name ASC");
$stmt->execute();
$categories_result = $stmt->get_result();
?>

<h2><?php echo htmlspecialchars($admin_page_title); ?></h2>

<?php echo $message; ?>

<div style="margin-bottom: 20px;">
    <a href="add_category.php" class="admin-button-primary">Ajouter une Nouvelle Catégorie</a>
</div>

<?php if ($categories_result && $categories_result->num_rows > 0): ?>
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($category = $categories_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $category['id']; ?></td>
                <td><?php echo htmlspecialchars($category['name']); ?></td>
                <td><?php echo htmlspecialchars(substr($category['description'] ?? '', 0, 100)) . (strlen($category['description'] ?? '') > 100 ? '...' : ''); ?></td>
                <td class="actions">
                    <a href="edit_category.php?id=<?php echo $category['id']; ?>" class="edit-btn" title="Modifier">✏️</a>
                    <a href="delete_category.php?id=<?php echo $category['id']; ?>" class="delete-btn confirm-delete" title="Supprimer">🗑️</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>Aucune catégorie trouvée. <a href="add_category.php">Ajouter une nouvelle catégorie ?</a></p>
<?php endif; ?>

<?php
$stmt->close();
if (isset($conn)) {
    $conn->close();
}
require_once '../includes/admin_footer.php';
?>