<?php
// admin/edit_course.php
$admin_page_title = "Modifier une Formation";
require_once '../includes/admin_header.php'; // Handles session check, DB connection

if (!isAdmin()) {
    redirect(getBaseUrl() . '/admin/login.php?error=unauthorized');
    exit;
}

$course_id_to_edit = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$course_data = null;
$errors = [];

if (!$course_id_to_edit) {
    $_SESSION['message'] = '<p class="admin-error-message">ID de formation invalide.</p>';
    redirect(getBaseUrl() . '/admin/manage_courses.php');
    exit;
}

// Fetch existing course data
$stmt_fetch = $conn->prepare("SELECT * FROM courses WHERE id = ?");
$stmt_fetch->bind_param("i", $course_id_to_edit);
$stmt_fetch->execute();
$result_fetch = $stmt_fetch->get_result();
if ($result_fetch->num_rows === 1) {
    $course_data = $result_fetch->fetch_assoc();
} else {
    $_SESSION['message'] = '<p class="admin-error-message">Formation non trouvée.</p>';
    redirect(getBaseUrl() . '/admin/manage_courses.php');
    exit;
}
$stmt_fetch->close();

// Populate form fields
$course_title = $course_data['title'];
$course_description = $course_data['description'];
$course_content_body = $course_data['content_body'];
$course_content_type = $course_data['content_type'];
$course_content_url = $course_data['content_url'];
$current_thumbnail_url = $course_data['thumbnail_url']; // Keep track of current thumbnail
$course_category_id = $course_data['category_id'];
$course_duration_minutes = $course_data['duration_minutes'];
$course_is_premium = $course_data['is_premium'];

// Fetch categories for dropdown
$categories = [];
$cat_result = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
if ($cat_result) {
    while ($row = $cat_result->fetch_assoc()) {
        $categories[] = $row;
    }
    $cat_result->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $errors[] = "Erreur de sécurité (jeton CSRF invalide).";
    } else {
        $course_title = sanitizeInput($_POST['course_title'] ?? '');
        $course_description = sanitizeInput($_POST['course_description'] ?? '');
        $course_content_body = $_POST['course_content_body'] ?? '';
        $course_content_type = sanitizeInput($_POST['course_content_type'] ?? 'text');
        $course_content_url = filter_var(sanitizeInput($_POST['course_content_url'] ?? ''), FILTER_SANITIZE_URL);
        $course_category_id = filter_var($_POST['course_category_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
        $course_duration_minutes = filter_var($_POST['course_duration_minutes'] ?? '', FILTER_VALIDATE_INT) ?: null;
        $course_is_premium = isset($_POST['course_is_premium']) ? 1 : 0;

        // Validation
        if (empty($course_title)) $errors[] = "Le titre est requis.";
        if (empty($course_description)) $errors[] = "La description est requise.";
        if ($course_duration_minutes !== null && ($course_duration_minutes <=0 || $course_duration_minutes < 20)) $errors[] = "La durée doit être d'au moins 20 minutes.";

        // Thumbnail Upload Handling (if new one is provided)
        $new_thumbnail_path_db = $current_thumbnail_url; // Default to current if no new upload
        if (isset($_FILES['course_thumbnail']) && $_FILES['course_thumbnail']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = '../assets/images/course_thumbnails/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            
            $filename = uniqid('thumb_', true) . '_' . basename($_FILES['course_thumbnail']['name']);
            $target_file = $upload_dir . $filename;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

            $check = getimagesize($_FILES['course_thumbnail']['tmp_name']);
            if ($check === false) {
                $errors[] = "Le nouveau fichier n'est pas une image.";
            } elseif ($_FILES['course_thumbnail']['size'] > 2000000) {
                $errors[] = "Nouvelle miniature trop volumineuse (max 2MB).";
            } elseif (!in_array($imageFileType, $allowed_types)) {
                $errors[] = "Formats autorisés pour la nouvelle miniature : JPG, JPEG, PNG, GIF.";
            } else {
                if (move_uploaded_file($_FILES['course_thumbnail']['tmp_name'], $target_file)) {
                    $new_thumbnail_path_db = 'assets/images/course_thumbnails/' . $filename;
                    // Optionally delete the old thumbnail if it's different and exists
                    if (!empty($current_thumbnail_url) && $current_thumbnail_url !== $new_thumbnail_path_db && file_exists('../' . $current_thumbnail_url)) {
                        unlink('../' . $current_thumbnail_url);
                    }
                } else {
                    $errors[] = "Erreur lors du téléchargement de la nouvelle miniature.";
                }
            }
        } elseif (isset($_FILES['course_thumbnail']) && $_FILES['course_thumbnail']['error'] != UPLOAD_ERR_NO_FILE) {
            $errors[] = "Erreur de téléchargement (Code: ".$_FILES['course_thumbnail']['error'].").";
        }


        if (empty($errors)) {
            $stmt = $conn->prepare("UPDATE courses SET title = ?, description = ?, content_body = ?, content_type = ?, content_url = ?, thumbnail_url = ?, category_id = ?, duration_minutes = ?, is_premium = ? WHERE id = ?");
            $stmt->bind_param("ssssssiiii", $course_title, $course_description, $course_content_body, $course_content_type, $course_content_url, $new_thumbnail_path_db, $course_category_id, $course_duration_minutes, $course_is_premium, $course_id_to_edit);

            if ($stmt->execute()) {
                $_SESSION['message'] = '<p class="admin-success-message">Formation mise à jour avec succès !</p>';
                redirect(getBaseUrl() . '/admin/manage_courses.php');
                exit;
            } else {
                $errors[] = "Erreur lors de la mise à jour: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
$csrf_token_admin = generateCsrfToken();
?>

<h2><?php echo htmlspecialchars($admin_page_title); ?>: <?php echo htmlspecialchars($course_data['title']); ?></h2>

<?php if (!empty($errors)): ?>
    <div class="admin-error-message">
        <strong>Veuillez corriger :</strong><br>
        <ul><?php foreach ($errors as $error) echo '<li>' . htmlspecialchars($error) . '</li>'; ?></ul>
    </div>
<?php endif; ?>

<form action="edit_course.php?id=<?php echo $course_id_to_edit; ?>" method="POST" enctype="multipart/form-data" class="admin-form">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token_admin); ?>">

    <div class="form-group">
        <label for="course_title">Titre <span style="color:red;">*</span></label>
        <input type="text" id="course_title" name="course_title" value="<?php echo htmlspecialchars($course_title); ?>" required>
    </div>

    <div class="form-group">
        <label for="course_description">Description <span style="color:red;">*</span></label>
        <textarea id="course_description" name="course_description" rows="5" required><?php echo htmlspecialchars($course_description); ?></textarea>
    </div>

    <div class="form-group">
        <label for="course_content_type">Type de Contenu Principal</label>
        <select id="course_content_type" name="course_content_type">
            <option value="text" <?php echo ($course_content_type === 'text') ? 'selected' : ''; ?>>Texte</option>
            <option value="video" <?php echo ($course_content_type === 'video') ? 'selected' : ''; ?>>Vidéo</option>
            <option value="interactive" <?php echo ($course_content_type === 'interactive') ? 'selected' : ''; ?>>Interactif</option>
            <option value="mixed" <?php echo ($course_content_type === 'mixed') ? 'selected' : ''; ?>>Mixte</option>
        </select>
    </div>
    
    <div class="form-group">
        <label for="course_content_body">Contenu Textuel Principal</label>
        <textarea id="course_content_body" name="course_content_body" class="wysiwyg" rows="10"><?php echo htmlspecialchars($course_content_body); ?></textarea>
    </div>

    <div class="form-group">
        <label for="course_content_url">URL Contenu Principal</label>
        <input type="url" id="course_content_url" name="course_content_url" value="<?php echo htmlspecialchars($course_content_url); ?>" placeholder="https://example.com/video">
    </div>

    <div class="form-group">
        <label for="course_thumbnail">Miniature (laisser vide pour conserver l'actuelle)</label>
        <input type="file" id="course_thumbnail" name="course_thumbnail" accept="image/jpeg,image/png,image/gif">
        <?php if (!empty($current_thumbnail_url)): ?>
            <p style="margin-top:5px;">Actuelle : <img src="<?php echo getBaseUrl() . '/' . htmlspecialchars($current_thumbnail_url); ?>" alt="Miniature actuelle" style="max-width: 100px; max-height: 75px; vertical-align: middle;"></p>
        <?php endif; ?>
        <img id="thumbnailPreview" src="#" alt="Aperçu nouvelle miniature" style="max-width: 200px; max-height: 150px; margin-top: 10px; display: none;"/>
    </div>
    
    <div class="form-group">
        <label for="course_category_id">Catégorie</label>
        <select id="course_category_id" name="course_category_id">
            <option value="">-- Sélectionner --</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?php echo $category['id']; ?>" <?php echo ($course_category_id == $category['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($category['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label for="course_duration_minutes">Durée (minutes) <span style="color:red;">*</span></label>
        <input type="number" id="course_duration_minutes" name="course_duration_minutes" value="<?php echo htmlspecialchars($course_duration_minutes); ?>" min="20" required>
    </div>

    <div class="form-group">
        <label for="course_is_premium" class="checkbox-label">
            <input type="checkbox" id="course_is_premium" name="course_is_premium" value="1" <?php echo ($course_is_premium == 1) ? 'checked' : ''; ?>>
            Formation Premium
        </label>
    </div>

    <div class="form-actions">
        <button type="submit" class="admin-button-primary">Mettre à Jour</button>
        <a href="manage_courses.php" class="admin-button-secondary" style="margin-left:10px;">Annuler</a>
    </div>
</form>

<?php
if (isset($conn)) {
    $conn->close();
}
require_once '../includes/admin_footer.php';
?>