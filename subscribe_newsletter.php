<?php
// /subscribe_newsletter.php
header('Content-Type: application/json'); // Important for AJAX response

require_once 'includes/db_connect.php';
require_once 'includes/functions.php'; // For CSRF, sanitizeInput

$response = ['success' => false, 'message' => 'Une erreur inconnue est survenue.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token (assuming it's sent with AJAX if your main.js adds it)
    // Or if it's a standard form submission, check $_POST['csrf_token']
    // For AJAX from footer, it might not have CSRF easily unless main.js adds it dynamically.
    // For now, we'll proceed, but CSRF is recommended for all POST.
    /*
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $response['message'] = "Erreur de sécurité. Veuillez rafraîchir la page et réessayer.";
        echo json_encode($response);
        exit;
    }
    */

    $email = filter_var(sanitizeInput($_POST['newsletter_email'] ?? ''), FILTER_VALIDATE_EMAIL);

    if (empty($email)) {
        $response['message'] = "Veuillez entrer une adresse e-mail valide.";
    } else {
        // Check if email already exists
        $stmt_check = $conn->prepare("SELECT id FROM newsletter_subscriptions WHERE email = ?");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $response['message'] = "Cette adresse e-mail est déjà inscrite à notre newsletter.";
            // Consider it a success for UX if already subscribed, or set success to true
            // $response['success'] = true; 
        } else {
            $stmt_insert = $conn->prepare("INSERT INTO newsletter_subscriptions (email) VALUES (?)");
            $stmt_insert->bind_param("s", $email);

            if ($stmt_insert->execute()) {
                $response['success'] = true;
                $response['message'] = "Merci ! Vous êtes maintenant inscrit(e) à notre newsletter.";
            } else {
                $response['message'] = "Erreur lors de l'inscription. Veuillez réessayer.";
                error_log("Newsletter subscription error: " . $stmt_insert->error);
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
} else {
    $response['message'] = "Méthode de requête invalide.";
}

$conn->close();

// Store message in session for non-AJAX fallback (though this script is designed for AJAX)
$_SESSION['newsletter_message'] = $response['message'];
$_SESSION['newsletter_success'] = $response['success'];

echo json_encode($response);
exit;
?>