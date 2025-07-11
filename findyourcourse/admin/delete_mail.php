<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!isAdmin()) {
    $_SESSION['message'] = '<p class="admin-error-message">Accès non autorisé.</p>';
    redirect(getBaseUrl() . '/admin/login.php');
    exit;
}

$mail_id_to_delete = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$mail_id_to_delete) {
    $_SESSION['message'] = '<p class="admin-error-message">ID de mail invalide.</p>';
    redirect(getBaseUrl() . '/admin/manage_mails.php?status=error');
    exit;
}

$stmt_delete = $conn->prepare("DELETE FROM mail_gestion WHERE id = ?");
$stmt_delete->bind_param("i", $mail_id_to_delete);

if ($stmt_delete->execute()) {
    if ($stmt_delete->affected_rows > 0) {
        $_SESSION['message'] = '<p class="admin-success-message">Mail supprimé avec succès.</p>';
        redirect(getBaseUrl() . '/admin/manage_mails.php?status=deleted');
    } else {
        $_SESSION['message'] = '<p class="admin-error-message">Mail non trouvé ou déjà supprimé.</p>';
        redirect(getBaseUrl() . '/admin/manage_mails.php?status=error');
    }
} else {
    $_SESSION['message'] = '<p class="admin-error-message">Erreur lors de la suppression: ' . $stmt_delete->error . '</p>';
    redirect(getBaseUrl() . '/admin/manage_mails.php?status=error');
}
$stmt_delete->close();
$conn->close();
exit;
?>