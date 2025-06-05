<?php
// forgot_password.php
$page_title = "Mot de Passe Oublié";
$page_description = "Réinitialisez votre mot de passe Find Your Course si vous l'avez oublié.";
require_once 'includes/db_connect.php';
require_once 'includes/functions.php'; // Starts session, provides CSRF

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $message = "Erreur de sécurité. Veuillez réessayer.";
    } else {
        $email = filter_var(sanitizeInput($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);

        if (empty($email)) {
            $message = "Veuillez entrer une adresse e-mail valide.";
        } else {
            $stmt_user = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt_user->bind_param("s", $email);
            $stmt_user->execute();
            $result_user = $stmt_user->get_result();

            if ($result_user->num_rows === 1) {
                $user = $result_user->fetch_assoc();
                $user_id = $user['id'];

                // Generate token (e.g., 64 char hex)
                $token = bin2hex(random_bytes(32));
                $expires = time() + 3600; // Token expires in 1 hour

                // Ensure password_resets table exists:
                // CREATE TABLE `password_resets` (
                //  `id` INT AUTO_INCREMENT PRIMARY KEY,
                //  `user_id` INT NOT NULL,
                //  `token` VARCHAR(64) NOT NULL UNIQUE,
                //  `expires_at` INT NOT NULL,
                //  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                //  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
                // ) ENGINE=InnoDB;

                // Delete any existing tokens for this user
                $stmt_del_old = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
                $stmt_del_old->bind_param("i", $user_id);
                $stmt_del_old->execute();
                $stmt_del_old->close();

                // Store new token
                $stmt_token = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
                $stmt_token->bind_param("isi", $user_id, $token, $expires);
                
                if ($stmt_token->execute()) {
                    $reset_link = getBaseUrl() . '/reset_password.php?token=' . $token;
                    
                    // ** SIMULATE Email Sending **
                    // In a real app, use PHPMailer or similar library
                    $email_subject = "Réinitialisation de votre mot de passe - Find Your Course";
                    $email_body = "Bonjour,\n\n";
                    $email_body .= "Vous avez demandé une réinitialisation de mot de passe. Cliquez sur le lien ci-dessous pour continuer :\n";
                    $email_body .= $reset_link . "\n\n";
                    $email_body .= "Ce lien expirera dans une heure.\n\n";
                    $email_body .= "Si vous n'avez pas demandé cette réinitialisation, veuillez ignorer cet e-mail.\n\n";
                    $email_body .= "Cordialement,\nL'équipe Find Your Course";
                    
                    // mail($email, $email_subject, $email_body, "From: no-reply@findyourcourse.example.com");
                    error_log("Password Reset Email (Simulated) for $email: Link: $reset_link"); // Log for dev
                    
                    $success = true;
                } else {
                    $message = "Erreur lors de la génération du lien de réinitialisation. " . $stmt_token->error;
                    error_log("Forgot Pwd Token Insert Error: " . $stmt_token->error);
                }
                $stmt_token->close();
            }
            $stmt_user->close();
            
            // Always show a generic success message to prevent user enumeration,
            // even if the email wasn't found or token generation failed internally (log errors for admin).
            $message = "Si un compte avec cette adresse e-mail existe, un lien de réinitialisation de mot de passe a été envoyé. Veuillez vérifier votre boîte de réception (et vos spams).";
            $success = true; // To control form display
        }
    }
}
$csrf_token = generateCsrfToken();
require_once 'includes/header.php';
?>

<div class="container" style="max-width: 500px;">
    <section class="forgot-password-section">
        <h1 style="text-align:center; margin-bottom:30px;"><?php echo $page_title; ?></h1>

        <?php if ($message): ?>
            <p style="padding: 10px; border-radius: 5px; background-color: <?php echo $success ? '#e6fffa' : '#ffebee'; ?>; color: <?php echo $success ? '#00695c' : '#c62828'; ?>; border: 1px solid <?php echo $success ? '#b2dfdb' : '#ef9a9a'; ?>; text-align:center; margin-bottom:15px;">
                <?php echo htmlspecialchars($message); ?>
            </p>
        <?php endif; ?>

        <?php if (!$success): // Only show form if not "successfully processed" ?>
        <form action="forgot_password.php" method="POST" class="user-access-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <p style="margin-bottom: 20px; text-align:center;">Entrez l'adresse e-mail associée à votre compte et nous vous enverrons un lien pour réinitialiser votre mot de passe.</p>
            <div class="form-group">
                <label for="fp-email">Adresse Email :</label>
                <input type="email" id="fp-email" name="email" required autofocus>
            </div>
            <div class="form-actions">
                <button type="submit" class="button" style="width:100%;">Envoyer le Lien de Réinitialisation</button>
            </div>
        </form>
        <?php endif; ?>
        <p style="text-align:center; margin-top:20px;">
            <a href="login.php">Retour à la connexion</a>
        </p>
    </section>
</div>

<?php
if (isset($conn)) {
    $conn->close();
}
require_once 'includes/footer.php';
?>