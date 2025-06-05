<?php
// admin/delete_quiz.php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!isAdmin()) {
    $_SESSION['message'] = '<p class="admin-error-message">Accès non autorisé.</p>';
    redirect(getBaseUrl() . '/admin/login.php');
    exit;
}

// Recommended: Use POST for delete actions with CSRF in form.
// For GET, ensure CSRF token is passed in URL and validated.
$quiz_id_to_delete = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
// $csrf_token_get = $_GET['csrf_token'] ?? '';
// if (!$csrf_token_get || !validateCsrfToken($csrf_token_get)) { /* error */ }

if (!$quiz_id_to_delete) {
    $_SESSION['message'] = '<p class="admin-error-message">ID de quiz invalide.</p>';
    redirect(getBaseUrl() . '/admin/manage_quizzes.php?status=error');
    exit;
}

// Deleting a quiz will also delete its questions and answers due to ON DELETE CASCADE in DB schema.
$stmt_delete = $conn->prepare("DELETE FROM quizzes WHERE id = ?");
$stmt_delete->bind_param("i", $quiz_id_to_delete);

if ($stmt_delete->execute()) {
    if ($stmt_delete->affected_rows > 0) {
        $_SESSION['message'] = '<p class="admin-success-message">Quiz (et ses questions/réponses) supprimé avec succès.</p>';
        redirect(getBaseUrl() . '/admin/manage_quizzes.php?status=deleted');
    } else {
        $_SESSION['message'] = '<p class="admin-error-message">Quiz non trouvé ou déjà supprimé.</p>';
        redirect(getBaseUrl() . '/admin/manage_quizzes.php?status=error');
    }
} else {
    $_SESSION['message'] = '<p class="admin-error-message">Erreur lors de la suppression du quiz: ' . $stmt_delete->error . '</p>';
    redirect(getBaseUrl() . '/admin/manage_quizzes.php?status=error');
}
$stmt_delete->close();
$conn->close();
exit;
?>