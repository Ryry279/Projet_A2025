<?php
// admin/manage_courses.php
$admin_page_title = "Gérer les Formations";
require_once '../includes/admin_header.php'; // Handles session check for isAdmin(), DB connection

if (!isAdmin()) {
    redirect(getBaseUrl() . '/admin/login.php?error=unauthorized');
    exit;
}

// Handle messages from add/edit/delete operations
$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']); // Clear the message after displaying
} elseif (isset($_GET['status'])) {
    if ($_GET['status'] === 'deleted') {
        $message = '<p class="admin-success-message">Formation supprimée avec succès.</p>';
    } elseif ($_GET['status'] === 'error') {
        $message = '<p class="admin-error-message">Une erreur est survenue.</p>';
    }
}

// Fetch courses to display
$search_term = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$query = "SELECT c.*, cat.name as category_name 
          FROM courses c 
          LEFT JOIN categories cat ON c.category_id = cat.id";

if (!empty($search_term)) {
    $query .= " WHERE c.title LIKE ? OR c.description LIKE ?";
    $search_param = "%" . $search_term . "%";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $search_param, $search_param);
} else {
    $query .= " ORDER BY c.id DESC";
    $stmt = $conn->prepare($query);
}

$stmt->execute();
$courses_result = $stmt->get_result();

?>

<h2><?php echo htmlspecialchars($admin_page_title); ?></h2>

<?php echo $message; // Display any success/error messages ?>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
    <a href="add_course.php" class="admin-button-primary">Ajouter une Nouvelle Formation</a>
    <form action="manage_courses.php" method="GET" style="display: inline-flex; gap: 5px;">
        <input type="text" name="search" placeholder="Rechercher une formation..." value="<?php echo htmlspecialchars($search_term); ?>" style="padding: 8px; border-radius:4px; border:1px solid #ccc;">
        <button type="submit" class="admin-button-secondary" style="padding: 8px 12px;">Rechercher</button>
        <?php if (!empty($search_term)): ?>
            <a href="manage_courses.php" style="padding: 8px 12px; text-decoration:none; color:#555; font-size:0.9em; align-self:center;">Effacer</a>
        <?php endif; ?>
    </form>
</div>

<?php if ($courses_result && $courses_result->num_rows > 0): ?>
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Miniature</th>
                <th>Titre</th>
                <th>Catégorie</th>
                <th>Premium</th>
                <th>Durée (min)</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($course = $courses_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $course['id']; ?></td>
                <td>
                    <img src="<?php echo htmlspecialchars(getBaseUrl() . '/' . ($course['thumbnail_url'] ?: 'assets/images/default_thumbnail.jpg')); ?>" 
                         alt="Miniature" style="width: 80px; height: auto; border-radius: 4px;">
                </td>
                <td><?php echo htmlspecialchars($course['title']); ?></td>
                <td><?php echo htmlspecialchars($course['category_name'] ?? 'N/A'); ?></td>
                <td><?php echo $course['is_premium'] ? 'Oui <span style="color:gold;">★</span>' : 'Non'; ?></td>
                <td><?php echo htmlspecialchars($course['duration_minutes'] ?? 'N/A'); ?></td>
                <td class="actions">
                    <a href="<?php echo htmlspecialchars(getBaseUrl()); ?>/course_detail.php?id=<?php echo $course['id']; ?>" target="_blank" class="view-btn" title="Voir la formation">👁️</a>
                    <a href="edit_course.php?id=<?php echo $course['id']; ?>" class="edit-btn" title="Modifier">✏️</a>
                    <a href="delete_course.php?id=<?php echo $course['id']; ?>" class="delete-btn confirm-delete" title="Supprimer">🗑️</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>Aucune formation trouvée<?php echo !empty($search_term) ? ' pour "' . htmlspecialchars($search_term) . '"' : ''; ?>. <a href="add_course.php">Ajouter une nouvelle formation ?</a></p>
<?php endif; ?>

<?php
$stmt->close();
if (isset($conn)) {
    $conn->close();
}
require_once '../includes/admin_footer.php';
?>