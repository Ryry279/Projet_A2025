<?php
// /toggle_favorite.php

// On définit le type de contenu en premier pour s'assurer que c'est bien du JSON qui est envoyé.
header('Content-Type: application/json');

// On prépare un tableau de réponse par défaut.
$response = ['success' => false, 'message' => 'Une erreur inattendue est survenue.'];

try {
    // On inclut les fichiers nécessaires à l'intérieur du bloc try.
    require_once 'includes/db_connect.php';
    require_once 'includes/functions.php';

    // On vérifie que la méthode est bien POST.
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode de requête non autorisée.', 405);
    }
    
    // On vérifie que l'utilisateur est connecté.
    if (!isLoggedIn()) {
        throw new Exception('Vous devez être connecté pour ajouter un favori.', 401);
    }

    // On récupère les données envoyées en JSON par le JavaScript.
    $data = json_decode(file_get_contents('php://input'), true);

    // On vérifie si le JSON est valide.
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Données JSON invalides.', 400);
    }

    // On valide l'ID du cours.
    $course_id = filter_var($data['course_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$course_id) {
        throw new Exception('ID de cours invalide.', 400);
    }
    
    $user_id = $_SESSION['user_id'];

    // On vérifie si le cours est déjà en favori.
    $stmt_check = $conn->prepare("SELECT id FROM favorites WHERE user_id = ? AND course_id = ?");
    $stmt_check->bind_param("ii", $user_id, $course_id);
    $stmt_check->execute();
    $is_favorited = $stmt_check->get_result()->num_rows > 0;
    $stmt_check->close();

    if ($is_favorited) {
        // S'il l'est déjà, on le supprime.
        $stmt_delete = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND course_id = ?");
        $stmt_delete->bind_param("ii", $user_id, $course_id);
        if ($stmt_delete->execute()) {
            $response = ['success' => true, 'action' => 'removed', 'message' => 'Retiré des favoris.'];
        } else {
            throw new Exception("Erreur lors de la suppression du favori.");
        }
        $stmt_delete->close();
    } else {
        // Sinon, on l'ajoute.
        $stmt_insert = $conn->prepare("INSERT INTO favorites (user_id, course_id) VALUES (?, ?)");
        $stmt_insert->bind_param("ii", $user_id, $course_id);
        if ($stmt_insert->execute()) {
            $response = ['success' => true, 'action' => 'added', 'message' => 'Ajouté aux favoris !'];
        } else {
            throw new Exception("Erreur lors de l'ajout du favori.");
        }
        $stmt_insert->close();
    }

} catch (Exception $e) {
    // Si une erreur (Exception) est attrapée, on la met dans la réponse JSON.
    $httpCode = $e->getCode() >= 400 ? $e->getCode() : 500;
    http_response_code($httpCode); // On envoie un code d'erreur HTTP approprié.
    $response['message'] = $e->getMessage();
    // On peut aussi logger l'erreur pour le débogage.
    error_log("toggle_favorite.php Error: " . $e->getMessage());
}

if(isset($conn)) $conn->close();

// On envoie la réponse JSON finale, quoi qu'il arrive.
echo json_encode($response);
exit();
?>