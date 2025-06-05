<?php
// admin/delete_category.php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php'; // For isAdmin, redirect, getBaseUrl, CSRF

if (!isAdmin()) {
    $_SESSION['message'] = '<p class="admin-error-message">Accès non autorisé.</p>';
    redirect(getBaseUrl() . '/admin/login.php');
    exit;
}

$category_id_to_delete = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
// It's better to use POST for delete actions and include CSRF token in a form.
// For simplicity with GET here, we should at least have a CSRF token in the URL.
// $csrf_token_get = $_GET['csrf_token'] ?? '';

// if (!$csrf_token_get || !validateCsrfToken($csrf_token_get)) {
//    $_SESSION['message'] = '<p class="admin-error-message">Erreur de sécurité (jeton CSRF invalide). Suppression annulée.</p>';
//    redirect(getBaseUrl() . '/admin/manage_categories.php?status=error');
//    exit;
// }
// NOTE: The manage_categories.php delete link doesn't currently pass CSRF. For a GET delete, this is a risk.
// For production, convert delete actions to POST forms.

if (!$category_id_to_delete) {
    $_SESSION['message'] = '<p class="admin-error-message">ID de catégorie invalide.</p>';
    redirect(getBaseUrl() . '/admin/manage_categories.php?status=error');
    exit;
}

// Check if category is empty (no courses assigned)
$stmt_check = $conn->prepare("SELECT COUNT(*) as course_count FROM courses WHERE category_id = ?");
$stmt_check->bind_param("i", $category_id_to_delete);
$stmt_check->execute();
$result_check = $stmt_check->get_result()->fetch_assoc();
$stmt_check->close();

if ($result_check['course_count'] > 0) {
    $_SESSION['message'] = '<p class="admin-error-message">Impossible de supprimer la catégorie : elle contient encore des formations. Veuillez d\'abord les déplacer ou les supprimer.</p>';
    redirect(getBaseUrl() . '/admin/manage_categories.php?status=error_notempty');
    exit;
}

// Proceed with deletion
$stmt_delete = $conn->prepare("DELETE FROM categories WHERE id = ?");
$stmt_delete->bind_param("i", $category_id_to_delete);

if ($stmt_delete->execute()) {
    if ($stmt_delete->affected_rows > 0) {
        $_SESSION['message'] = '<p class="admin-success-message">Catégorie supprimée avec succès.</p>';
        redirect(getBaseUrl() . '/admin/manage_categories.php?status=deleted');
    } else {
        $_SESSION['message'] = '<p class="admin-error-message">Catégorie non trouvée ou déjà supprimée.</p>';
        redirect(getBaseUrl() . '/admin/manage_categories.php?status=error');
    }
} else {
    $_SESSION['message'] = '<p class="admin-error-message">Erreur lors de la suppression de la catégorie: ' . $stmt_delete->error . '</p>';
    redirect(getBaseUrl() . '/admin/manage_categories.php?status=error');
}
$stmt_delete->close();
$conn->close();
exit;
?>