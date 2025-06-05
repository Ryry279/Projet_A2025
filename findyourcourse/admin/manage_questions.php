<?php
// admin/manage_questions.php
$admin_page_title = "Gérer les Questions du Quiz";
require_once '../includes/admin_header.php';

if (!isAdmin()) {
    redirect(getBaseUrl() . '/admin/login.php?error=unauthorized');
    exit;
}

$quiz_id = filter_input(INPUT_GET, 'quiz_id', FILTER_VALIDATE_INT);
$quiz_title = '';
$questions = [];

if (!$quiz_id) {
    $_SESSION['message'] = '<p class="admin-error-message">ID de quiz non spécifié.</p>';
    redirect(getBaseUrl() . '/admin/manage_quizzes.php');
    exit;
}

// Fetch quiz title
$stmt_quiz = $conn->prepare("SELECT title FROM quizzes WHERE id = ?");
$stmt_quiz->bind_param("i", $quiz_id);
$stmt_quiz->execute();
$result_quiz = $stmt_quiz->get_result();
if ($result_quiz->num_rows === 1) {
    $quiz_title = $result_quiz->fetch_assoc()['title'];
} else {
    $_SESSION['message'] = '<p class="admin-error-message">Quiz non trouvé.</p>';
    redirect(getBaseUrl() . '/admin/manage_quizzes.php');
    exit;
}
$stmt_quiz->close();
$admin_page_title .= " : " . htmlspecialchars($quiz_title); // Update page title

// Fetch questions for this quiz
$stmt_q = $conn->prepare("SELECT id, question_text, type, points FROM questions WHERE quiz_id = ? ORDER BY id ASC");
$stmt_q->bind_param("i", $quiz_id);
$stmt_q->execute();
$result_q = $stmt_q->get_result();
while ($row = $result_q->fetch_assoc()) {
    // Fetch answers for each question
    $stmt_a = $conn->prepare("SELECT id, answer_text, is_correct FROM answers WHERE question_id = ? ORDER BY id ASC");
    $stmt_a->bind_param("i", $row['id']);
    $stmt_a->execute();
    $result_a = $stmt_a->get_result();
    $row['answers'] = $result_a->fetch_all(MYSQLI_ASSOC);
    $stmt_a->close();
    $questions[] = $row;
}
$stmt_q->close();

$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
?>

<h2><?php echo $admin_page_title; ?></h2>
<p><a href="manage_quizzes.php" class="admin-button-secondary">&laquo; Retour à la liste des Quiz</a></p>

<?php if ($message) echo $message; ?>

<div style="margin-bottom: 20px;">
    <a href="add_question.php?quiz_id=<?php echo $quiz_id; ?>" class="admin-button-primary">Ajouter une Question</a>
</div>

<?php if (!empty($questions)): ?>
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID Question</th>
                <th>Texte de la Question</th>
                <th>Type</th>
                <th>Points</th>
                <th>Réponses (Correcte en gras)</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($questions as $question): ?>
            <tr>
                <td><?php echo $question['id']; ?></td>
                <td><?php echo htmlspecialchars(createExcerpt($question['question_text'], 70)); ?></td>
                <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $question['type']))); ?></td>
                <td><?php echo htmlspecialchars($question['points']); ?></td>
                <td>
                    <ul style="margin:0; padding-left:15px; font-size:0.9em;">
                        <?php foreach ($question['answers'] as $answer): ?>
                            <li <?php if ($answer['is_correct']) echo 'style="font-weight:bold; color:green;"'; ?>>
                                <?php echo htmlspecialchars(createExcerpt($answer['answer_text'], 40)); ?>
                            </li>
                        <?php endforeach; ?>
                        <?php if (empty($question['answers'])) echo "<em>Aucune réponse définie</em>"; ?>
                    </ul>
                </td>
                <td class="actions">
                    <a href="edit_question.php?id=<?php echo $question['id']; ?>&quiz_id=<?php echo $quiz_id; ?>" class="edit-btn" title="Modifier Question & Réponses">✏️</a>
                    <a href="delete_question.php?id=<?php echo $question['id']; ?>&quiz_id=<?php echo $quiz_id; ?>&csrf_token=<?php echo htmlspecialchars(generateCsrfToken());?>" class="delete-btn confirm-delete" title="Supprimer Question">🗑️</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>Aucune question n'a encore été ajoutée à ce quiz. <a href="add_question.php?quiz_id=<?php echo $quiz_id; ?>">Ajoutez la première question !</a></p>
<?php endif; ?>

<?php
if (isset($conn)) {
    $conn->close();
}
require_once '../includes/admin_footer.php';
?>