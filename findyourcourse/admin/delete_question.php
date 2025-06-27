<?php
// admin/delete_question.php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php'; // Pour isAdmin, redirect, getBaseUrl, et la gestion CSRF

// Étape 1 : Vérifier les permissions de l'administrateur
if (!isAdmin()) {
    $_SESSION['message'] = '<p class="admin-error-message">Accès non autorisé.</p>';
    redirect(getBaseUrl() . '/admin/login.php');
    exit;
}

// Étape 2 : Valider les paramètres GET
$question_id_to_delete = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$quiz_id_for_redirect = filter_input(INPUT_GET, 'quiz_id', FILTER_VALIDATE_INT);
$csrf_token_get = $_GET['csrf_token'] ?? '';

// Rediriger si les IDs sont manquants ou invalides
if (!$question_id_to_delete || !$quiz_id_for_redirect) {
    $_SESSION['message'] = '<p class="admin-error-message">ID de question ou de quiz invalide pour la suppression.</p>';
    redirect(getBaseUrl() . '/admin/manage_quizzes.php'); // Redirection vers la liste des quiz si on ne sait pas où retourner
    exit;
}

// Étape 3 : Valider le jeton CSRF pour la sécurité
if (!$csrf_token_get || !validateCsrfToken($csrf_token_get)) {
   $_SESSION['message'] = '<p class="admin-error-message">Erreur de sécurité (jeton CSRF invalide). Suppression annulée.</p>';
   redirect(getBaseUrl() . '/admin/manage_questions.php?quiz_id=' . $quiz_id_for_redirect);
   exit;
}

// Étape 4 : Procéder à la suppression
// Grâce à la contrainte de clé étrangère "ON DELETE CASCADE" définie dans le SQL pour la table `answers`,
// la suppression d'une question entraînera automatiquement la suppression de toutes ses réponses associées.
$stmt_delete = $conn->prepare("DELETE FROM questions WHERE id = ? AND quiz_id = ?");
// Inclure quiz_id dans la clause WHERE est une sécurité supplémentaire.
$stmt_delete->bind_param("ii", $question_id_to_delete, $quiz_id_for_redirect);

if ($stmt_delete->execute()) {
    // Étape 5 : Donner un retour à l'utilisateur via un message de session
    if ($stmt_delete->affected_rows > 0) {
        $_SESSION['message'] = '<p class="admin-success-message">Question supprimée avec succès.</p>';
    } else {
        $_SESSION['message'] = '<p class="admin-error-message">Question non trouvée ou déjà supprimée.</p>';
    }
} else {
    $_SESSION['message'] = '<p class="admin-error-message">Erreur lors de la suppression de la question : ' . htmlspecialchars($stmt_delete->error) . '</p>';
    // Pour le débogage, il est utile de logger l'erreur complète
    error_log("Failed to delete question: " . $stmt_delete->error);
}

$stmt_delete->close();
$conn->close();

// Étape 6 : Rediriger l'utilisateur vers la page de gestion des questions du quiz
redirect(getBaseUrl() . '/admin/manage_questions.php?quiz_id=' . $quiz_id_for_redirect);
exit;
?>