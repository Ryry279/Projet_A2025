<?php
// admin/edit_category.php
$admin_page_title = "Modifier une Catégorie";
require_once '../includes/admin_header.php';

if (!isAdmin()) {
    redirect(getBaseUrl() . '/admin/login.php?error=unauthorized');
    exit;
}

$category_id_to_edit = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$category_data = null;
$errors = [];

if (!$category_id_to_edit) {
    $_SESSION['message'] = '<p class="admin-error-message">ID de catégorie invalide.</p>';
    redirect(getBaseUrl() . '/admin/manage_categories.php');
    exit;
}

// Fetch existing category data
$stmt_fetch = $conn->prepare("SELECT id, name, description FROM categories WHERE id = ?");
$stmt_fetch->bind_param("i", $category_id_to_edit);
$stmt_fetch->execute();
$result_fetch = $stmt_fetch->get_result();
if ($result_fetch->num_rows === 1) {
    $category_data = $result_fetch->fetch_assoc();
} else {
    $_SESSION['message'] = '<p class="admin-error-message">Catégorie non trouvée.</p>';
    redirect(getBaseUrl() . '/admin/manage_categories.php');
    exit;
}
$stmt_fetch->close();

// Populate form fields
$category_name = $category_data['name'];
$category_description = $category_data['description'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $errors[] = "Erreur de sécurité (jeton CSRF invalide).";
    } else {
        $category_name = sanitizeInput($_POST['category_name'] ?? '');
        $category_description = sanitizeInput($_POST['category_description'] ?? '');

        if (empty($category_name)) {
            $errors[] = "Le nom de la catégorie est requis.";
        }

        // Check if new category name already exists (excluding current category)
        if (empty($errors)) {
            $stmt_check = $conn->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
            $stmt_check->bind_param("si", $category_name, $category_id_to_edit);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) {
                $errors[] = "Une autre catégorie avec ce nom existe déjà.";
            }
            $stmt_check->close();
        }

        if (empty($errors)) {
            $stmt_update = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            $stmt_update->bind_param("ssi", $category_name, $category_description, $category_id_to_edit);

            if ($stmt_update->execute()) {
                $_SESSION['message'] = '<p class="admin-success-message">Catégorie mise à jour avec succès !</p>';
                redirect(getBaseUrl() . '/admin/manage_categories.php');
                exit;
            } else {
                $errors[] = "Erreur lors de la mise à jour: " . $stmt_update->error;
            }
            $stmt_update->close();
        }
    }
}
$csrf_token_admin = generateCsrfToken();
?>

<h2><?php echo htmlspecialchars($admin_page_title); ?>: <?php echo htmlspecialchars($category_data['name']); ?></h2>

<?php if (!empty($errors)): ?>
    <div class="admin-error-message">
        <strong>Veuillez corriger :</strong><br>
        <ul><?php foreach ($errors as $error) echo '<li>' . htmlspecialchars($error) . '</li>'; ?></ul>
    </div>
<?php endif; ?>

<form action="edit_category.php?id=<?php echo $category_id_to_edit; ?>" method="POST" class="admin-form">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token_admin); ?>">

    <div class="form-group">
        <label for="category_name">Nom de la Catégorie <span style="color:red;">*</span></label>
        <input type="text" id="category_name" name="category_name" value="<?php echo htmlspecialchars($category_name); ?>" required>
    </div>

    <div class="form-group">
        <label for="category_description">Description (optionnel)</label>
        <textarea id="category_description" name="category_description" rows="4"><?php echo htmlspecialchars($category_description); ?></textarea>
    </div>

    <div class="form-actions">
        <button type="submit" class="admin-button-primary">Mettre à Jour</button>
        <a href="manage_categories.php" class="admin-button-secondary" style="margin-left:10px;">Annuler</a>
    </div>
</form>

<?php
if (isset($conn)) {
    $conn->close();
}
require_once '../includes/admin_footer.php';
?>