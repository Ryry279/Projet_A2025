<?php
// subscribe_premium.php
$page_title = "Devenir Membre Premium";
$page_description = "Accédez à du contenu exclusif en devenant membre Premium Find Your Course.";
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/header.php'; // Contains CSRF

if (!isLoggedIn()) {
    $_SESSION['info_message'] = "Vous devez être connecté pour souscrire à un abonnement Premium.";
    redirect(getBaseUrl() . '/login.php?redirect=' . urlencode(getBaseUrl() . '/subscribe_premium.php'));
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$success = false;

// Check current user role
$stmt_check_role = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt_check_role->bind_param("i", $user_id);
$stmt_check_role->execute();
$current_role_result = $stmt_check_role->get_result();
$current_user_data = $current_role_result->fetch_assoc();
$stmt_check_role->close();

$is_already_premium = ($current_user_data && ($current_user_data['role'] === 'premium_student' || $current_user_data['role'] === 'admin'));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_already_premium) {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $message = "Erreur de sécurité. Veuillez réessayer.";
    } else {
        // Simulate "payment" and upgrade user role
        $new_role = 'premium_student';
        $stmt_upgrade = $conn->prepare("UPDATE users SET role = ? WHERE id = ? AND role = 'student'"); // Only upgrade 'student'
        $stmt_upgrade->bind_param("si", $new_role, $user_id);

        if ($stmt_upgrade->execute()) {
            if ($stmt_upgrade->affected_rows > 0) {
                $_SESSION['role'] = $new_role; // Update role in current session
                $success = true;
                $message = "Félicitations ! Vous êtes maintenant un membre Premium. Vous avez accès à tout notre contenu exclusif.";
                $is_already_premium = true; // Update status for display
            } else {
                // This might happen if they were already premium or an admin, or if the update failed for some other reason
                 $message = "Votre statut n'a pas pu être mis à jour, ou vous êtes déjà Premium.";
            }
        } else {
            $message = "Une erreur est survenue lors de la mise à jour de votre abonnement. " . $stmt_upgrade->error;
            error_log("Premium subscription error: " . $stmt_upgrade->error);
        }
        $stmt_upgrade->close();
    }
}

$csrf_token = generateCsrfToken(); // For the form
?>

<div class="container" style="max-width: 700px;">
    <section class="page-header" style="text-align:center; margin-bottom:30px;">
        <h1>Accès Premium Find Your Course</h1>
    </section>

    <?php if ($message): ?>
        <p style="padding: 10px; border-radius: 5px; background-color: <?php echo $success ? '#e6fffa' : '#ffebee'; ?>; color: <?php echo $success ? '#00695c' : '#c62828'; ?>; border: 1px solid <?php echo $success ? '#b2dfdb' : '#ef9a9a'; ?>; text-align:center; margin-bottom:20px;">
            <?php echo htmlspecialchars($message); ?>
        </p>
    <?php endif; ?>

    <?php if ($is_already_premium): ?>
        <div style="text-align:center; padding: 20px; background-color:#f0fff0; border-radius:8px;">
            <p style="font-size:1.2em; color:green;">🎉 Vous êtes déjà un membre Premium (ou Administrateur) !</p>
            <p>Vous avez accès à toutes nos formations exclusives.</p>
            <a href="<?php echo getBaseUrl(); ?>/courses.php" class="button">Explorer les Formations</a>
        </div>
    <?php else: ?>
        <div class="premium-offer card-style-section" style="background-color: #fff; padding: 30px; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom:30px; text-align:center;">
            <h2>Devenez Membre Premium Gratuitement !</h2>
            <p class="lead">Dans le cadre de ce projet académique, l'accès Premium est offert.</p>
            <p>En tant que membre Premium, vous aurez accès à :</p>
            <ul style="list-style-position: inside; text-align: left; max-width: 400px; margin: 20px auto;">
                <li>Vidéos e-learning plus détaillées et pratiques [cite: 8]</li>
                <li>Focus sur Salesforce avec des démonstrations avancées [cite: 7, 8]</li>
                <li>Contenus additionnels exclusifs [cite: 19]</li>
                <li>Quiz et évaluations complètes</li>
            </ul>
            <form action="subscribe_premium.php" method="POST" style="margin-top:30px;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <button type="submit" class="button" style="font-size:1.1em; padding: 12px 30px;">Obtenir l'Accès Premium (0€)</button>
            </form>
        </div>
    <?php endif; ?>

     <p style="text-align:center; margin-top:30px;">
        <a href="<?php echo getBaseUrl(); ?>/profile.php">&laquo; Retour à mon profil</a>
    </p>
</div>

<?php
if (isset($conn)) {
    $conn->close();
}
require_once 'includes/footer.php';
?>