<?php
// admin/edit_quiz.php
$admin_page_title = "Modifier un Quiz";
require_once '../includes/admin_header.php';

if (!isAdmin()) {
    redirect(getBaseUrl() . '/admin/login.php?error=unauthorized');
    exit;
}

$quiz_id_to_edit = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$quiz_data = null;
$errors = [];

if (!$quiz_id_to_edit) {
    $_SESSION['message'] = '<p class="admin-error-message">ID de quiz invalide.</p>';
    redirect(getBaseUrl() . '/admin/manage_quizzes.php');
    exit;
}

// Fetch existing quiz data
$stmt_fetch = $conn->prepare("SELECT id, title, description, course_id FROM quizzes WHERE id = ?");
$stmt_fetch->bind_param("i", $quiz_id_to_edit);
$stmt_fetch->execute();
$result_fetch = $stmt_fetch->get_result();
if ($result_fetch->num_rows === 1) {
    $quiz_data = $result_fetch->fetch_assoc();
} else {
    $_SESSION['message'] = '<p class="admin-error-message">Quiz non trouvé.</p>';
    redirect(getBaseUrl() . '/admin/manage_quizzes.php');
    exit;
}
$stmt_fetch->close();

// Populate form fields
$quiz_title = $quiz_data['title'];
$quiz_description = $quiz_data['description'];
$quiz_course_id_current = $quiz_data['course_id'];


// Fetch all courses for dropdown (current one + those without quiz)
$courses_for_quiz = [];
$sql_courses = "SELECT id, title FROM courses WHERE id = ? OR id NOT IN (SELECT DISTINCT course_id FROM quizzes WHERE id != ?) ORDER BY title ASC";
$stmt_courses_dd = $conn->prepare($sql_courses);
$stmt_courses_dd->bind_param("ii", $quiz_course_id_current, $quiz_id_to_edit);
$stmt_courses_dd->execute();
$course_result = $stmt_courses_dd->get_result();

if ($course_result) {
    while ($row = $course_result->fetch_assoc()) {
        $courses_for_quiz[] = $row;
    }
    $course_result->close();
}
$stmt_courses_dd->close();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $errors[] = "Erreur de sécurité (jeton CSRF invalide).";
    } else {
        $quiz_title = sanitizeInput($_POST['quiz_title'] ?? '');
        $quiz_description = sanitizeInput($_POST['quiz_description'] ?? '');
        $quiz_course_id_new = filter_var($_POST['quiz_course_id'] ?? null, FILTER_VALIDATE_INT);

        if (empty($quiz_title)) $errors[] = "Le titre du quiz est requis.";
        if (empty($quiz_course_id_new)) $errors[] = "Veuillez sélectionner une formation à associer.";

        // Check if the new selected course (if different from current) already has another quiz
        if ($quiz_course_id_new && $quiz_course_id_new != $quiz_course_id_current && empty($errors)) {
            $stmt_check_course = $conn->prepare("SELECT id FROM quizzes WHERE course_id = ? AND id != ?");
            $stmt_check_course->bind_param("ii", $quiz_course_id_new, $quiz_id_to_edit);
            $stmt_check_course->execute();
            if ($stmt_check_course->get_result()->num_rows > 0) {
                $errors[] = "La nouvelle formation sélectionnée a déjà un quiz associé.";
            }
            $stmt_check_course->close();
        }

        if (empty($errors)) {
            $stmt_update = $conn->prepare("UPDATE quizzes SET title = ?, description = ?, course_id = ? WHERE id = ?");
            $stmt_update->bind_param("ssii", $quiz_title, $quiz_description, $quiz_course_id_new, $quiz_id_to_edit);

            if ($stmt_update->execute()) {
                $_SESSION['message'] = '<p class="admin-success-message">Quiz mis à jour avec succès !</p>';
                redirect(getBaseUrl() . '/admin/manage_quizzes.php');
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

<h2><?php echo htmlspecialchars($admin_page_title); ?>: <?php echo htmlspecialchars($quiz_data['title']); ?></h2>

<?php if (!empty($errors)): ?>
    <div class="admin-error-message">
        <strong>Veuillez corriger :</strong><br>
        <ul><?php foreach ($errors as $error) echo '<li>' . htmlspecialchars($error) . '</li>'; ?></ul>
    </div>
<?php endif; ?>

<form action="edit_quiz.php?id=<?php echo $quiz_id_to_edit; ?>" method="POST" class="admin-form">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token_admin); ?>">

    <div class="form-group">
        <label for="quiz_title">Titre du Quiz <span style="color:red;">*</span></label>
        <input type="text" id="quiz_title" name="quiz_title" value="<?php echo htmlspecialchars($quiz_title); ?>" required>
    </div>

    <div class="form-group">
        <label for="quiz_course_id">Formation Associée <span style="color:red;">*</span></label>
        <select id="quiz_course_id" name="quiz_course_id" required>
            <option value="">-- Sélectionner une Formation --</option>
            <?php foreach ($courses_for_quiz as $course): ?>
                <option value="<?php echo $course['id']; ?>" <?php echo ($quiz_course_id_current == $course['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($course['title']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label for="quiz_description">Description du Quiz (optionnel)</label>
        <textarea id="quiz_description" name="quiz_description" rows="4"><?php echo htmlspecialchars($quiz_description); ?></textarea>
    </div>

    <div class="form-actions">
        <button type="submit" class="admin-button-primary">Mettre à Jour le Quiz</button>
        <a href="manage_questions.php?quiz_id=<?php echo $quiz_id_to_edit; ?>" class="admin-button-secondary" style="margin-left:10px;">Gérer les Questions</a>
        <a href="manage_quizzes.php" class="admin-button-secondary" style="margin-left:10px;">Annuler</a>
    </div>
</form>

<?php
if (isset($conn)) {
    $conn->close();
}
require_once '../includes/admin_footer.php';
?>