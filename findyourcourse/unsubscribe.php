<?php
// /unsubscribe.php

header('Content-Type: application/json');

// Inclure les fichiers nécessaires
// L'utilisation de @suppress et de try/catch gère les erreurs si un fichier est manquant.
try {
    require_once __DIR__ . '/includes/db_connect.php';
    require_once __DIR__ . '/includes/functions.php';
    // Pour l'instant, l'envoi d'email est retiré, donc pas besoin de mailer_config.php
    // require_once __DIR__ . '/includes/mailer_config.php';
} catch (Throwable $t) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur de configuration du serveur.']);
    error_log("Failed to include required files in unsubscribe.php: " . $t->getMessage());
    exit;
}


$response = ['success' => false, 'message' => 'Une erreur est survenue.'];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode de requête non autorisée.', 405);
    }
    if (!isLoggedIn()) {
        throw new Exception('Authentification requise.', 401);
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['csrf_token']) || !validateCsrfToken($data['csrf_token'])) {
        throw new Exception('Erreur de sécurité.', 403);
    }

    $user_id = $_SESSION['user_id'];
    
    // Mettre à jour le rôle de l'utilisateur de 'premium_student' à 'student'
    $new_role = 'student';
    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ? AND role = 'premium_student'");
    $stmt->bind_param("si", $new_role, $user_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            // La mise à jour de la base de données a réussi
            $_SESSION['role'] = $new_role; // Mettre à jour la session en cours

            // La partie email est retirée pour le moment
            $response['success'] = true;
            $response['message'] = 'Abonnement résilié avec succès.';

        } else {
            // L'utilisateur n'était probablement pas premium ou l'ID était incorrect.
            throw new Exception("Impossible de mettre à jour l'abonnement. Votre statut est peut-être déjà à jour.");
        }
    } else {
        throw new Exception("Erreur de base de données lors de la résiliation.");
    }
    $stmt->close();

} catch (Exception $e) {
    $httpCode = $e->getCode() >= 400 ? $e->getCode() : 500;
    http_response_code($httpCode);
    $response['message'] = $e->getMessage();
}

if(isset($conn)) $conn->close();

echo json_encode($response);
exit();
?>