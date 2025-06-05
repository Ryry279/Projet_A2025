<?php
// admin/add_quiz.php
$admin_page_title = "Ajouter un Quiz";
require_once '../includes/admin_header.php'; // Handles session check, DB connection

if (!isAdmin()) {
    redirect(getBaseUrl() . '/admin/login.php?error=unauthorized');
    exit;
}

$quiz_title = '';
$quiz_description = '';
$quiz_course_id = null;
$errors = [];

// Fetch courses for dropdown (only those without a quiz yet, or allow multiple quizzes per course if design changes)
$courses_for_quiz = [];
// This query finds courses that DO NOT yet have a quiz associated in the `quizzes` table.
// The `quizzes` table has a UNIQUE constraint on `course_id`.
$course_result = $conn->query("SELECT id, title FROM courses WHERE id NOT IN (SELECT DISTINCT course_id FROM quizzes) ORDER BY title ASC");
if ($course_result) {
    while ($row = $course_result->fetch_assoc()) {
        $courses_for_quiz[] = $row;
    }
    $course_result->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $errors[] = "Erreur de sécurité (jeton CSRF invalide).";
    } else {
        $quiz_title = sanitizeInput($_POST['quiz_title'] ?? '');
        $quiz_description = sanitizeInput($_POST['quiz_description'] ?? '');
        $quiz_course_id = filter_var($_POST['quiz_course_id'] ?? null, FILTER_VALIDATE_INT);

        if (empty($quiz_title)) $errors[] = "Le titre du quiz est requis.";
        if (empty($quiz_course_id)) $errors[] = "Veuillez sélectionner une formation à associer.";

        // Check if the selected course already has a quiz (due to UNIQUE constraint on quizzes.course_id)
        if ($quiz_course_id && empty($errors)) {
            $stmt_check_course = $conn->prepare("SELECT id FROM quizzes WHERE course_id = ?");
            $stmt_check_course->bind_param("i", $quiz_course_id);
            $stmt_check_course->execute();
            if ($stmt_check_course->get_result()->num_rows > 0) {
                $errors[] = "Cette formation a déjà un quiz associé. Modifiez l'existant ou choisissez une autre formation.";
            }
            $stmt_check_course->close();
        }


        if (empty($errors)) {
            $stmt_insert = $conn->prepare("INSERT INTO quizzes (title, description, course_id) VALUES (?, ?, ?)");
            $stmt_insert->bind_param("ssi", $quiz_title, $quiz_description, $quiz_course_id);

            if ($stmt_insert->execute()) {
                $new_quiz_id = $stmt_insert->insert_id;
                $_SESSION['message'] = '<p class="admin-success-message">Quiz ajouté avec succès ! Vous pouvez maintenant ajouter des questions.</p>';
                redirect(getBaseUrl() . '/admin/manage_questions.php?quiz_id=' . $new_quiz_id); // Redirect to manage questions for this new quiz
                exit;
            } else {
                $errors[] = "Erreur lors de l'ajout du quiz: " . $stmt_insert->error;
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
        <ul><?php foreach ($errors as $error) echo '<li>' . htmlspecialchars($error) . '</li>'; ?></ul>
    </div>
<?php endif; ?>

<form action="add_quiz.php" method="POST" class="admin-form">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token_admin); ?>">

    <div class="form-group">
        <label for="quiz_title">Titre du Quiz <span style="color:red;">*</span></label>
        <input type="text" id="quiz_title" name="quiz_title" value="<?php echo htmlspecialchars($quiz_title); ?>" required>
    </div>

    <div class="form-group">
        <label for="quiz_course_id">Formation Associée <span style="color:red;">*</span></label>
        <select id="quiz_course_id" name="quiz_course_id" required>
            <option value="">-- Sélectionner une Formation --</option>
            <?php if (empty($courses_for_quiz)): ?>
                 <option value="" disabled>Aucune formation disponible sans quiz.</option>
            <?php else: ?>
                <?php foreach ($courses_for_quiz as $course): ?>
                    <option value="<?php echo $course['id']; ?>" <?php echo ($quiz_course_id == $course['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($course['title']); ?>
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        <?php if (empty($courses_for_quiz)): ?>
             <small>Toutes les formations ont déjà un quiz. Pour ajouter un quiz à une formation existante, vous devez d'abord supprimer son quiz actuel ou modifier cette logique.</small>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="quiz_description">Description du Quiz (optionnel)</label>
        <textarea id="quiz_description" name="quiz_description" rows="4"><?php echo htmlspecialchars($quiz_description); ?></textarea>
    </div>

    <div class="form-actions">
        <button type="submit" class="admin-button-primary">Ajouter le Quiz et Gérer les Questions</button>
        <a href="manage_quizzes.php" class="admin-button-secondary" style="margin-left:10px;">Annuler</a>
    </div>
</form>

<?php
if (isset($conn)) {
    $conn->close();
}
require_once '../includes/admin_footer.php';
?>