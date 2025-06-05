<?php
// admin/delete_course.php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!isAdmin()) {
    $_SESSION['message'] = '<p class="admin-error-message">Accès non autorisé.</p>';
    redirect(getBaseUrl() . '/admin/login.php');
    exit;
}

// For GET requests, CSRF token should ideally be in the URL.
// A POST request with CSRF in the form body is much more secure for delete actions.
// Example: if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) { /* error */ }
//    $course_id_to_delete = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
// } else { $course_id_to_delete = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT); /* less secure */ }

$course_id_to_delete = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
// Add CSRF check here if you implement it for GET delete links (not recommended for production)

if (!$course_id_to_delete) {
    $_SESSION['message'] = '<p class="admin-error-message">ID de formation invalide pour la suppression.</p>';
    redirect(getBaseUrl() . '/admin/manage_courses.php?status=error');
    exit;
}

// First, get the thumbnail path to delete the file if the record is deleted
$stmt_thumb = $conn->prepare("SELECT thumbnail_url FROM courses WHERE id = ?");
$stmt_thumb->bind_param("i", $course_id_to_delete);
$stmt_thumb->execute();
$result_thumb = $stmt_thumb->get_result();
$thumbnail_to_delete = null;
if ($result_thumb->num_rows === 1) {
    $thumbnail_to_delete = $result_thumb->fetch_assoc()['thumbnail_url'];
}
$stmt_thumb->close();


// Proceed with deletion from database
// Note: Consider related data (enrollments, favorites, quizzes). Deleting a course might need to cascade
// or be prevented if there are active enrollments. For now, simple delete.
// Database foreign keys with ON DELETE CASCADE for `enrollments`, `favorites`, `quizzes` will handle this.
$stmt_delete = $conn->prepare("DELETE FROM courses WHERE id = ?");
$stmt_delete->bind_param("i", $course_id_to_delete);

if ($stmt_delete->execute()) {
    if ($stmt_delete->affected_rows > 0) {
        // Delete the thumbnail file from server
        if (!empty($thumbnail_to_delete) && file_exists('../' . $thumbnail_to_delete)) {
            unlink('../' . $thumbnail_to_delete);
        }
        $_SESSION['message'] = '<p class="admin-success-message">Formation supprimée avec succès.</p>';
        redirect(getBaseUrl() . '/admin/manage_courses.php?status=deleted');
    } else {
        $_SESSION['message'] = '<p class="admin-error-message">Formation non trouvée ou déjà supprimée.</p>';
        redirect(getBaseUrl() . '/admin/manage_courses.php?status=error');
    }
} else {
    $_SESSION['message'] = '<p class="admin-error-message">Erreur lors de la suppression: ' . $stmt_delete->error . '</p>';
    redirect(getBaseUrl() . '/admin/manage_courses.php?status=error');
}
$stmt_delete->close();
$conn->close();
exit;
?>