<?php
// admin/delete_subscriber.php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!isAdmin()) {
    redirect(getBaseUrl() . '/admin/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(getBaseUrl() . '/admin/manage_newsletter.php');
    exit;
}

if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
    $_SESSION['message'] = '<p class="admin-error-message">Erreur de sécurité.</p>';
    redirect(getBaseUrl() . '/admin/manage_newsletter.php');
    exit;
}

$subscriber_id_to_delete = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$subscriber_id_to_delete) {
    $_SESSION['message'] = '<p class="admin-error-message">ID d\'abonné invalide.</p>';
    redirect(getBaseUrl() . '/admin/manage_newsletter.php');
    exit;
}

$stmt = $conn->prepare("DELETE FROM newsletter_subscriptions WHERE id = ?");
$stmt->bind_param("i", $subscriber_id_to_delete);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $_SESSION['message'] = '<p class="admin-success-message">Abonné supprimé avec succès.</p>';
    } else {
        $_SESSION['message'] = '<p class="admin-error-message">Abonné non trouvé ou déjà supprimé.</p>';
    }
} else {
    $_SESSION['message'] = '<p class="admin-error-message">Erreur lors de la suppression.</p>';
    error_log("Failed to delete subscriber: " . $stmt->error);
}

$stmt->close();
$conn->close();
redirect(getBaseUrl() . '/admin/manage_newsletter.php');
exit;
?>