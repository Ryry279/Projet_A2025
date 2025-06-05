<?php
// admin/manage_quizzes.php
$admin_page_title = "Gérer les Quiz";
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
        $message = '<p class="admin-success-message">Quiz supprimé avec succès.</p>';
    } elseif ($_GET['status'] === 'error') {
        $message = '<p class="admin-error-message">Une erreur est survenue.</p>';
    }
}

// Fetch quizzes with associated course titles
$stmt = $conn->prepare("SELECT q.id, q.title AS quiz_title, q.description AS quiz_description, c.id AS course_id, c.title AS course_title 
                        FROM quizzes q 
                        JOIN courses c ON q.course_id = c.id 
                        ORDER BY q.id DESC");
$stmt->execute();
$quizzes_result = $stmt->get_result();
?>

<h2><?php echo htmlspecialchars($admin_page_title); ?></h2>

<?php echo $message; ?>

<div style="margin-bottom: 20px;">
    <a href="add_quiz.php" class="admin-button-primary">Ajouter un Nouveau Quiz</a>
</div>

<?php if ($quizzes_result && $quizzes_result->num_rows > 0): ?>
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID Quiz</th>
                <th>Titre du Quiz</th>
                <th>Formation Associée</th>
                <th>Description du Quiz</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($quiz = $quizzes_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $quiz['id']; ?></td>
                <td><?php echo htmlspecialchars($quiz['quiz_title']); ?></td>
                <td>
                    <a href="edit_course.php?id=<?php echo $quiz['course_id']; ?>">
                        <?php echo htmlspecialchars($quiz['course_title']); ?> (ID: <?php echo $quiz['course_id']; ?>)
                    </a>
                </td>
                <td><?php echo htmlspecialchars(substr($quiz['quiz_description'] ?? '', 0, 70)) . (strlen($quiz['quiz_description'] ?? '') > 70 ? '...' : ''); ?></td>
                <td class="actions">
                    <a href="manage_questions.php?quiz_id=<?php echo $quiz['id']; ?>" class="view-btn" title="Gérer les Questions">❓</a>
                    <a href="edit_quiz.php?id=<?php echo $quiz['id']; ?>" class="edit-btn" title="Modifier le Quiz">✏️</a>
                    <a href="delete_quiz.php?id=<?php echo $quiz['id']; ?>" class="delete-btn confirm-delete" title="Supprimer le Quiz">🗑️</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>Aucun quiz trouvé. <a href="add_quiz.php">Ajouter un nouveau quiz ?</a></p>
<?php endif; ?>

<?php
$stmt->close();
if (isset($conn)) {
    $conn->close();
}
require_once '../includes/admin_footer.php';
?>