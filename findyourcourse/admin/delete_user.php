<?php
// admin/delete_user.php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!isAdmin()) {
    $_SESSION['message'] = '<p class="admin-error-message">Accès non autorisé.</p>';
    redirect(getBaseUrl() . '/admin/login.php');
    exit;
}

$user_id_to_delete = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
// CSRF check recommended here for GET delete (see delete_course.php comments)

if (!$user_id_to_delete) {
    $_SESSION['message'] = '<p class="admin-error-message">ID utilisateur invalide.</p>';
    redirect(getBaseUrl() . '/admin/manage_users.php?status=error');
    exit;
}

// Prevent deleting oneself or the primary 'admin' user
if ($user_id_to_delete === $_SESSION['user_id']) {
    $_SESSION['message'] = '<p class="admin-error-message">Vous ne pouvez pas supprimer votre propre compte.</p>';
    redirect(getBaseUrl() . '/admin/manage_users.php?status=error_self');
    exit;
}

$stmt_check_admin = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt_check_admin->bind_param("i", $user_id_to_delete);
$stmt_check_admin->execute();
$result_check_admin = $stmt_check_admin->get_result();
if ($result_check_admin->num_rows === 1) {
    $user_to_delete = $result_check_admin->fetch_assoc();
    if ($user_to_delete['username'] === 'admin') { // Assuming 'admin' is the primary admin username
        $_SESSION['message'] = '<p class="admin-error-message">L\'utilisateur administrateur principal ne peut pas être supprimé.</p>';
        redirect(getBaseUrl() . '/admin/manage_users.php?status=error_mainadmin');
        exit;
    }
}
$stmt_check_admin->close();


// Proceed with deletion
$stmt_delete = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt_delete->bind_param("i", $user_id_to_delete);

if ($stmt_delete->execute()) {
    if ($stmt_delete->affected_rows > 0) {
        $_SESSION['message'] = '<p class="admin-success-message">Utilisateur supprimé avec succès.</p>';
        redirect(getBaseUrl() . '/admin/manage_users.php?status=deleted');
    } else {
        $_SESSION['message'] = '<p class="admin-error-message">Utilisateur non trouvé ou déjà supprimé.</p>';
        redirect(getBaseUrl() . '/admin/manage_users.php?status=error');
    }
} else {
    $_SESSION['message'] = '<p class="admin-error-message">Erreur lors de la suppression: ' . $stmt_delete->error . '</p>';
    redirect(getBaseUrl() . '/admin/manage_users.php?status=error');
}
$stmt_delete->close();
$conn->close();
exit;
?>