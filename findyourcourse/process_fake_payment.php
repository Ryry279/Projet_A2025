<?php

require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(getBaseUrl());
    exit;
}
if (!isLoggedIn()) {
    redirect(getBaseUrl() . '/login.php');
    exit;
}
if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
    die('Erreur de sécurité.');
}

// === SIMULATION DE PAIEMENT RÉUSSI ===

$user_id = $_SESSION['user_id'];
$new_role = 'premium_student';

// Mettre à jour le rôle de l'utilisateur
$stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
$stmt->bind_param("si", $new_role, $user_id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    // Mettre à jour la session
    $_SESSION['role'] = $new_role;
    // Rediriger vers une page de succès
    redirect(getBaseUrl() . '/payment_success_page.php');
    exit;
} else {
    // Gérer une erreur si la mise à jour échoue
    redirect(getBaseUrl() . '/subscribe_premium.php?error=update_failed');
    exit;
}
?>