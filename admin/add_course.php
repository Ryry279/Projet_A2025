<?php
// admin/add_course.php
$admin_page_title = "Ajouter une Formation";
require_once '../includes/admin_header.php'; // Handles session check for isAdmin(), DB connection

if (!isAdmin()) {
    redirect(getBaseUrl() . '/admin/login.php?error=unauthorized');
    exit;
}

$course_title = '';
$course_description = '';
$course_content_body = '';
$course_content_type = 'text';
$course_content_url = '';
$course_thumbnail_url = ''; // Will be path after upload
$course_category_id = null;
$course_duration_minutes = '';
$course_is_premium = 0;

$errors = [];

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
        $course_description = sanitizeInput($_POST['course_description'] ?? ''); // Or allow some HTML if using a WYSIWYG
        $course_content_body = $_POST['course_content_body'] ?? ''; // Potentially HTML, sanitize carefully on display or use a purifier
        $course_content_type = sanitizeInput($_POST['course_content_type'] ?? 'text');
        $course_content_url = filter_var(sanitizeInput($_POST['course_content_url'] ?? ''), FILTER_SANITIZE_URL);
        $course_category_id = filter_var($_POST['course_category_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
        $course_duration_minutes = filter_var($_POST['course_duration_minutes'] ?? '', FILTER_VALIDATE_INT) ?: null;
        $course_is_premium = isset($_POST['course_is_premium']) ? 1 : 0;

        // Basic Validation
        if (empty($course_title)) $errors[] = "Le titre de la formation est requis.";
        if (empty($course_description)) $errors[] = "La description est requise.";
        if ($course_content_type === 'video' && empty($course_content_url)) $errors[] = "L'URL du contenu est requise pour les formations vidéo.";
        if ($course_duration_minutes !== null && ($course_duration_minutes <= 0 || $course_duration_minutes < 20) ) $errors[] = "La durée doit être un nombre positif d'au moins 20 minutes."; // implicit requirement

        // Thumbnail Upload Handling (Basic)
        if (isset($_FILES['course_thumbnail']) && $_FILES['course_thumbnail']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = '../assets/images/course_thumbnails/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $filename = uniqid('thumb_', true) . '_' . basename($_FILES['course_thumbnail']['name']);
            $target_file = $upload_dir . $filename;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

            // Check if image file is a actual image or fake image
            $check = getimagesize($_FILES['course_thumbnail']['tmp_name']);
            if ($check === false) {
                $errors[] = "Le fichier n'est pas une image valide.";
            } elseif ($_FILES['course_thumbnail']['size'] > 2000000) { // 2MB limit
                $errors[] = "Désolé, votre fichier est trop volumineux (max 2MB).";
            } elseif (!in_array($imageFileType, $allowed_types)) {
                $errors[] = "Désolé, seuls les fichiers JPG, JPEG, PNG & GIF sont autorisés.";
            } else {
                if (move_uploaded_file($_FILES['course_thumbnail']['tmp_name'], $target_file)) {
                    $course_thumbnail_url = 'assets/images/course_thumbnails/' . $filename; // Relative path from web root
                } else {
                    $errors[] = "Désolé, une erreur est survenue lors du téléchargement de votre fichier.";
                }
            }
        } elseif (isset($_FILES['course_thumbnail']) && $_FILES['course_thumbnail']['error'] != UPLOAD_ERR_NO_FILE) {
            $errors[] = "Erreur de téléchargement du fichier miniature (Code: ".$_FILES['course_thumbnail']['error'].").";
        }


        if (empty($errors)) {
            $stmt = $conn->prepare("INSERT INTO courses (title, description, content_body, content_type, content_url, thumbnail_url, category_id, duration_minutes, is_premium) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssiii", $course_title, $course_description, $course_content_body, $course_content_type, $course_content_url, $course_thumbnail_url, $course_category_id, $course_duration_minutes, $course_is_premium);

            if ($stmt->execute()) {
                $_SESSION['message'] = '<p class="admin-success-message">Formation ajoutée avec succès !</p>';
                redirect(getBaseUrl() . '/admin/manage_courses.php');
                exit;
            } else {
                $errors[] = "Erreur lors de l'ajout de la formation: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
// Regenerate CSRF token for the form
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

<form action="add_course.php" method="POST" enctype="multipart/form-data" class="admin-form">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token_admin); ?>">

    <div class="form-group">
        <label for="course_title">Titre de la Formation <span style="color:red;">*</span></label>
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
        <label for="course_content_body">Contenu Textuel Principal (si applicable, peut contenir du HTML simple)</label>
        <textarea id="course_content_body" name="course_content_body" class="wysiwyg" rows="10"><?php echo htmlspecialchars($course_content_body); ?></textarea>
        <small>Utilisez cet espace pour le contenu principal si le type est 'texte' ou 'mixte'.</small>
    </div>

    <div class="form-group">
        <label for="course_content_url">URL du Contenu Principal (pour Vidéo/Interactif/Mixte)</label>
        <input type="url" id="course_content_url" name="course_content_url" value="<?php echo htmlspecialchars($course_content_url); ?>" placeholder="https://example.com/video_or_resource">
    </div>

    <div class="form-group">
        <label for="course_thumbnail">Miniature de la Formation (JPG, PNG, GIF - max 2MB)</label>
        <input type="file" id="course_thumbnail" name="course_thumbnail" accept="image/jpeg,image/png,image/gif">
        <img id="thumbnailPreview" src="#" alt="Aperçu de la miniature" style="max-width: 200px; max-height: 150px; margin-top: 10px; display: none;"/>
    </div>
    
    <div class="form-group">
        <label for="course_category_id">Catégorie</label>
        <select id="course_category_id" name="course_category_id">
            <option value="">-- Sélectionner une catégorie --</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?php echo $category['id']; ?>" <?php echo ($course_category_id == $category['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($category['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label for="course_duration_minutes">Durée (en minutes) <span style="color:red;">*</span></label>
        <input type="number" id="course_duration_minutes" name="course_duration_minutes" value="<?php echo htmlspecialchars($course_duration_minutes); ?>" min="20" required>
         <small>Doit être d'au moins 20 minutes.</small>
    </div>

    <div class="form-group">
        <label for="course_is_premium" class="checkbox-label">
            <input type="checkbox" id="course_is_premium" name="course_is_premium" value="1" <?php echo ($course_is_premium == 1) ? 'checked' : ''; ?>>
            Formation Premium (accès payant)
        </label>
    </div>

    <div class="form-actions">
        <button type="submit" class="admin-button-primary">Ajouter la Formation</button>
        <a href="manage_courses.php" class="admin-button-secondary" style="margin-left:10px;">Annuler</a>
    </div>
</form>

<?php
if (isset($conn)) {
    $conn->close();
}
require_once '../includes/admin_footer.php';
?>