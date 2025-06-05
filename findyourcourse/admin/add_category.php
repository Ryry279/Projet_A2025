<?php
// admin/add_category.php
$admin_page_title = "Ajouter une Catégorie";
require_once '../includes/admin_header.php'; // Handles session check, DB connection

if (!isAdmin()) {
    redirect(getBaseUrl() . '/admin/login.php?error=unauthorized');
    exit;
}

$category_name = '';
$category_description = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $errors[] = "Erreur de sécurité (jeton CSRF invalide).";
    } else {
        $category_name = sanitizeInput($_POST['category_name'] ?? '');
        $category_description = sanitizeInput($_POST['category_description'] ?? '');

        if (empty($category_name)) {
            $errors[] = "Le nom de la catégorie est requis.";
        }

        // Check if category name already exists
        if (empty($errors)) {
            $stmt_check = $conn->prepare("SELECT id FROM categories WHERE name = ?");
            $stmt_check->bind_param("s", $category_name);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) {
                $errors[] = "Une catégorie avec ce nom existe déjà.";
            }
            $stmt_check->close();
        }

        if (empty($errors)) {
            $stmt_insert = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt_insert->bind_param("ss", $category_name, $category_description);

            if ($stmt_insert->execute()) {
                $_SESSION['message'] = '<p class="admin-success-message">Catégorie ajoutée avec succès !</p>';
                redirect(getBaseUrl() . '/admin/manage_categories.php');
                exit;
            } else {
                $errors[] = "Erreur lors de l'ajout de la catégorie: " . $stmt_insert->error;
            }
            $stmt_insert->close();
        }
    }
}
$csrf_token_admin = generateCsrfToken();
?>

<h2><?php echo htmlspecialchars($admin_page_title); ?></h2>

<?php if (!empty($errors)): ?>
    <div class="admin-error-message" style="margin-bottom: 15px;">
        <strong>Veuillez corriger les erreurs suivantes :</strong><br>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form action="add_category.php" method="POST" class="admin-form">
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
        <button type="submit" class="admin-button-primary">Ajouter la Catégorie</button>
        <a href="manage_categories.php" class="admin-button-secondary" style="margin-left:10px;">Annuler</a>
    </div>
</form>

<?php
if (isset($conn)) {
    $conn->close();
}
require_once '../includes/admin_footer.php';
?>