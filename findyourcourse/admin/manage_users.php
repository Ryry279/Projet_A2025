<?php
// admin/manage_users.php
$admin_page_title = "Gérer les Utilisateurs";
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
        $message = '<p class="admin-success-message">Utilisateur supprimé avec succès.</p>';
    } elseif ($_GET['status'] === 'role_updated') {
         $message = '<p class="admin-success-message">Rôle de l\'utilisateur mis à jour.</p>';
    } elseif ($_GET['status'] === 'error') {
        $message = '<p class="admin-error-message">Une erreur est survenue.</p>';
    }
}


// Fetch users
$search_term = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$query = "SELECT id, username, email, role, registration_date FROM users";

if (!empty($search_term)) {
    $query .= " WHERE username LIKE ? OR email LIKE ?";
    $search_param = "%" . $search_term . "%";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $search_param, $search_param);
} else {
    $query .= " ORDER BY id DESC";
    $stmt = $conn->prepare($query);
}

$stmt->execute();
$users_result = $stmt->get_result();
?>

<h2><?php echo htmlspecialchars($admin_page_title); ?></h2>

<?php echo $message; ?>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
    <a href="add_user.php" class="admin-button-primary">Ajouter un Nouvel Utilisateur</a>
     <form action="manage_users.php" method="GET" style="display: inline-flex; gap: 5px;">
        <input type="text" name="search" placeholder="Rechercher un utilisateur..." value="<?php echo htmlspecialchars($search_term); ?>" style="padding: 8px; border-radius:4px; border:1px solid #ccc;">
        <button type="submit" class="admin-button-secondary" style="padding: 8px 12px;">Rechercher</button>
         <?php if (!empty($search_term)): ?>
            <a href="manage_users.php" style="padding: 8px 12px; text-decoration:none; color:#555; font-size:0.9em; align-self:center;">Effacer</a>
        <?php endif; ?>
    </form>
</div>


<?php if ($users_result && $users_result->num_rows > 0): ?>
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom d'utilisateur</th>
                <th>Email</th>
                <th>Rôle</th>
                <th>Date d'inscription</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($user = $users_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $user['id']; ?></td>
                <td><?php echo htmlspecialchars($user['username']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td><?php echo htmlspecialchars(ucfirst($user['role'])); ?></td>
                <td><?php echo date("d/m/Y H:i", strtotime($user['registration_date'])); ?></td>
                <td class="actions">
                    <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="edit-btn" title="Modifier">✏️</a>
                    <?php if ($user['id'] !== $_SESSION['user_id'] && $user['username'] !== 'admin'): // Prevent deleting self or the main admin ?>
                        <a href="delete_user.php?id=<?php echo $user['id']; ?>" class="delete-btn confirm-delete" title="Supprimer">🗑️</a>
                    <?php else: ?>
                        <span title="Non autorisé" style="opacity:0.5; cursor:not-allowed;">🗑️</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>Aucun utilisateur trouvé. <a href="add_user.php">Ajouter un nouvel utilisateur ?</a></p>
<?php endif; ?>

<?php
$stmt->close();
if (isset($conn)) {
    $conn->close();
}
require_once '../includes/admin_footer.php';
?>