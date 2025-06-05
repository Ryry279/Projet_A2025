<?php
// reset_password.php
$page_title = "Réinitialiser le Mot de Passe";
$page_description = "Choisissez un nouveau mot de passe pour votre compte Find Your Course.";
require_once 'includes/db_connect.php';
require_once 'includes/functions.php'; // For CSRF, sanitizeInput

$token = sanitizeInput($_GET['token'] ?? '');
$message = '';
$success = false;
$token_valid = false;
$user_id_for_reset = null;

if (empty($token)) {
    $message = "Jeton de réinitialisation invalide ou manquant.";
} else {
    // Validate token and check expiry
    $stmt_token = $conn->prepare("SELECT user_id, expires_at FROM password_resets WHERE token = ?");
    $stmt_token->bind_param("s", $token);
    $stmt_token->execute();
    $result_token = $stmt_token->get_result();

    if ($result_token->num_rows === 1) {
        $token_data = $result_token->fetch_assoc();
        if (time() > $token_data['expires_at']) {
            $message = "Ce lien de réinitialisation de mot de passe a expiré. Veuillez en <a href='forgot_password.php'>demander un nouveau</a>.";
        } else {
            $token_valid = true;
            $user_id_for_reset = $token_data['user_id'];
        }
    } else {
        $message = "Jeton de réinitialisation invalide ou déjà utilisé. Veuillez en <a href='forgot_password.php'>demander un nouveau</a>.";
    }
    $stmt_token->close();
}

if ($token_valid && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $message = "Erreur de sécurité. Veuillez réessayer.";
        $token_valid = false; // Invalidate form display
    } else {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($new_password) || empty($confirm_password)) {
            $message = "Veuillez entrer et confirmer votre nouveau mot de passe.";
        } elseif (strlen($new_password) < 8 || !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $new_password)) {
            $message = "Le nouveau mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.";
        } elseif ($new_password !== $confirm_password) {
            $message = "Les mots de passe ne correspondent pas.";
        } else {
            $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt_update_pass = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt_update_pass->bind_param("si", $hashed_new_password, $user_id_for_reset);

            if ($stmt_update_pass->execute()) {
                // Invalidate the token after successful reset
                $stmt_del_token = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
                $stmt_del_token->bind_param("s", $token);
                $stmt_del_token->execute();
                $stmt_del_token->close();

                $success = true;
                $message = "Votre mot de passe a été réinitialisé avec succès ! Vous pouvez maintenant <a href='login.php'>vous connecter</a> avec votre nouveau mot de passe.";
                $token_valid = false; // Hide form after success
            } else {
                $message = "Erreur lors de la mise à jour du mot de passe: " . $stmt_update_pass->error;
                error_log("Password reset update error: " . $stmt_update_pass->error);
            }
            $stmt_update_pass->close();
        }
    }
}

$csrf_token = generateCsrfToken(); // For the form
require_once 'includes/header.php';
?>

<div class="container" style="max-width: 500px;">
    <section class="reset-password-section">
        <h1 style="text-align:center; margin-bottom:30px;"><?php echo $page_title; ?></h1>

        <?php if ($message): ?>
            <p style="padding: 10px; border-radius: 5px; background-color: <?php echo $success ? '#e6fffa' : ($token_valid ? '#fff3cd' : '#ffebee'); ?>; color: <?php echo $success ? '#00695c' : ($token_valid ? '#856404' : '#c62828'); ?>; border: 1px solid <?php echo $success ? '#b2dfdb' : ($token_valid ? '#ffeeba' : '#ef9a9a'); ?>; text-align:center; margin-bottom:15px;">
                <?php echo $message; // Message already contains HTML link if needed, so don't escape ?>
            </p>
        <?php endif; ?>

        <?php if ($token_valid): ?>
        <form action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>" method="POST" class="user-access-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <p style="margin-bottom: 20px; text-align:center;">Veuillez choisir un nouveau mot de passe pour votre compte.</p>
            
            <div class="form-group">
                <label for="rp-new-password">Nouveau mot de passe :</label>
                <input type="password" id="rp-new-password" name="new_password" required autofocus aria-describedby="resetPasswordHelp">
                <small id="resetPasswordHelp" style="font-size:0.8em; color:#555;">Min. 8 caractères, 1 majuscule, 1 minuscule, 1 chiffre, 1 caractère spécial.</small>
            </div>
            
            <div class="form-group">
                <label for="rp-confirm-password">Confirmer le nouveau mot de passe :</label>
                <input type="password" id="rp-confirm-password" name="confirm_password" required>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="button" style="width:100%;">Réinitialiser le Mot de Passe</button>
            </div>
        </form>
        <?php elseif (!$success): // Token was invalid/expired and not successful yet, show login link ?>
            <p style="text-align:center; margin-top:20px;">
                <a href="login.php">Retour à la connexion</a>
            </p>
        <?php endif; ?>
    </section>
</div>

<?php
if (isset($conn)) {
    $conn->close();
}
require_once 'includes/footer.php';
?>