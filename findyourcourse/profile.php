<?php
// profile.php
$page_title = "Mon Profil";
$page_description = "Gérez les informations de votre compte Find Your Course, mettez à jour vos détails personnels et modifiez votre mot de passe.";
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/header.php'; // Contains CSRF token generation

if (!isLoggedIn()) {
    $_SESSION['info_message'] = "Vous devez être connecté pour accéder à votre profil.";
    redirect(getBaseUrl() . '/login.php?redirect=' . urlencode(getBaseUrl() . '/profile.php'));
    exit;
}

$user_id = $_SESSION['user_id'];
$update_message = '';
$update_success = false;

// Fetch current user data
$stmt_user = $conn->prepare("SELECT username, email, first_name, last_name, registration_date, role FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_result = $stmt_user->get_result();
$user_data = $user_result->fetch_assoc();
$stmt_user->close();

if (!$user_data) {
    // Should not happen if logged in, but as a failsafe
    session_destroy();
    redirect(getBaseUrl() . '/login.php?error=user_not_found');
    exit;
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $update_message = "Erreur de sécurité (jeton CSRF invalide). Veuillez réessayer.";
    } else {
        // Update general info
        if (isset($_POST['update_info'])) {
            $first_name = sanitizeInput($_POST['first_name'] ?? '');
            $last_name = sanitizeInput($_POST['last_name'] ?? '');
            $email = filter_var(sanitizeInput($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);

            if (empty($email)) {
                $update_message = "L'adresse e-mail n'est pas valide.";
            } else {
                // Check if email is already taken by another user
                $stmt_email_check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt_email_check->bind_param("si", $email, $user_id);
                $stmt_email_check->execute();
                if ($stmt_email_check->get_result()->num_rows > 0) {
                    $update_message = "Cette adresse e-mail est déjà utilisée par un autre compte.";
                } else {
                    $stmt_update = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?");
                    $stmt_update->bind_param("sssi", $first_name, $last_name, $email, $user_id);
                    if ($stmt_update->execute()) {
                        $update_success = true;
                        $update_message = "Vos informations ont été mises à jour avec succès.";
                        // Update session email if changed
                        $_SESSION['email'] = $email;
                        // Re-fetch user data to display updated info
                        $stmt_user = $conn->prepare("SELECT username, email, first_name, last_name, registration_date, role FROM users WHERE id = ?");
                        $stmt_user->bind_param("i", $user_id);
                        $stmt_user->execute();
                        $user_data = $stmt_user->get_result()->fetch_assoc();
                        $stmt_user->close();
                    } else {
                        $update_message = "Erreur lors de la mise à jour des informations.";
                        error_log("Profile update error: " . $stmt_update->error);
                    }
                    $stmt_update->close();
                }
                $stmt_email_check->close();
            }
        }
        // Update password
        elseif (isset($_POST['update_password'])) {
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $update_message = "Tous les champs de mot de passe sont requis.";
            } elseif (strlen($new_password) < 8) { // Basic length check
                $update_message = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
            } elseif ($new_password !== $confirm_password) {
                $update_message = "Le nouveau mot de passe et sa confirmation ne correspondent pas.";
            } else {
                // Verify current password
                $stmt_pass_check = $conn->prepare("SELECT password FROM users WHERE id = ?");
                $stmt_pass_check->bind_param("i", $user_id);
                $stmt_pass_check->execute();
                $pass_result = $stmt_pass_check->get_result()->fetch_assoc();
                $stmt_pass_check->close();

                if ($pass_result && password_verify($current_password, $pass_result['password'])) {
                    // Hash new password (assumes policy like min 8 chars, 1 upper, 1 lower, 1 digit, 1 special)
                    // Add more complex regex for password policy if needed as per
                    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $new_password)) {
                        $update_message = "Le nouveau mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.";
                    } else {
                        $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt_update_pass = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $stmt_update_pass->bind_param("si", $hashed_new_password, $user_id);
                        if ($stmt_update_pass->execute()) {
                            $update_success = true;
                            $update_message = "Votre mot de passe a été mis à jour avec succès.";
                        } else {
                            $update_message = "Erreur lors de la mise à jour du mot de passe.";
                            error_log("Password update error: " . $stmt_update_pass->error);
                        }
                        $stmt_update_pass->close();
                    }
                } else {
                    $update_message = "Le mot de passe actuel est incorrect.";
                }
            }
        }
    }
    // Regenerate CSRF token after processing
    unset($_SESSION['csrf_token']); // Remove old token
    $csrf_token = generateCsrfToken(); // Generate a new one for the forms
}
?>

<div class="container">
    <section class="page-header" style="text-align:center; margin-bottom:30px;">
        <h1>Mon Profil</h1>
        <p class="lead">Bonjour, <?php echo htmlspecialchars($user_data['username']); ?> ! Gérez vos informations ici.</p>
    </section>

    <?php if ($update_message): ?>
        <p style="padding: 10px; border-radius: 5px; background-color: <?php echo $update_success ? '#e6fffa' : '#ffebee'; ?>; color: <?php echo $update_success ? '#00695c' : '#c62828'; ?>; border: 1px solid <?php echo $update_success ? '#b2dfdb' : '#ef9a9a'; ?>; text-align:center; margin-bottom:20px;">
            <?php echo htmlspecialchars($update_message); ?>
        </p>
    <?php endif; ?>

    <div style="display: flex; flex-wrap: wrap; gap: 30px;">
        <section class="profile-info-display" style="flex: 1; min-width: 280px; background-color: #f9f9f9; padding: 25px; border-radius: 10px;">
            <h3>Vos Informations Actuelles</h3>
            <p><strong>Nom d'utilisateur :</strong> <?php echo htmlspecialchars($user_data['username']); ?></p>
            <p><strong>Email :</strong> <?php echo htmlspecialchars($user_data['email']); ?></p>
            <p><strong>Prénom :</strong> <?php echo htmlspecialchars($user_data['first_name'] ?? 'Non renseigné'); ?></p>
            <p><strong>Nom :</strong> <?php echo htmlspecialchars($user_data['last_name'] ?? 'Non renseigné'); ?></p>
            <p><strong>Membre depuis :</strong> <?php echo formatDisplayDate($user_data['registration_date'], 'd F Y'); ?></p>
            <p><strong>Type de compte :</strong> <?php echo htmlspecialchars(ucfirst($user_data['role'])); ?></p>
            <?php if ($user_data['role'] === 'student'): ?>
                <p><a href="<?php echo getBaseUrl(); ?>/subscribe_premium.php" class="button button-secondary">Passer à Premium</a></p>
            <?php endif; ?>
        </section>

        <section class="profile-update-forms" style="flex: 2; min-width: 300px;">
            <div class="update-info-form" style="margin-bottom: 30px; background-color: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                <h4>Modifier mes informations</h4>
                <form action="profile.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <div class="form-group">
                        <label for="profile-first-name">Prénom :</label>
                        <input type="text" id="profile-first-name" name="first_name" value="<?php echo htmlspecialchars($user_data['first_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="profile-last-name">Nom :</label>
                        <input type="text" id="profile-last-name" name="last_name" value="<?php echo htmlspecialchars($user_data['last_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="profile-email">Email :</label>
                        <input type="email" id="profile-email" name="email" required value="<?php echo htmlspecialchars($user_data['email']); ?>">
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="update_info" class="button">Mettre à jour mes infos</button>
                    </div>
                </form>
            </div>

            <div class="update-password-form" style="background-color: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                <h4>Changer mon mot de passe</h4>
                <form action="profile.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <div class="form-group">
                        <label for="profile-current-password">Mot de passe actuel :</label>
                        <input type="password" id="profile-current-password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label for="profile-new-password">Nouveau mot de passe :</label>
                        <input type="password" id="profile-new-password" name="new_password" required aria-describedby="passwordHelp">
                        <small id="passwordHelp" style="font-size:0.8em; color:#555;">Min. 8 caractères, 1 majuscule, 1 minuscule, 1 chiffre, 1 caractère spécial.</small>
                    </div>
                    <div class="form-group">
                        <label for="profile-confirm-password">Confirmer le nouveau mot de passe :</label>
                        <input type="password" id="profile-confirm-password" name="confirm_password" required>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="update_password" class="button">Changer mon mot de passe</button>
                    </div>
                </form>
            </div>
        </section>
    </div>

    <section class="enrolled-courses" style="margin-top:40px;">
        <h3>Mes Formations Inscrites (Bientôt disponible)</h3>
        <p>Vous pourrez voir ici la liste des formations auxquelles vous êtes inscrit(e) et suivre votre progression.</p>
        <p><a href="<?php echo getBaseUrl(); ?>/courses.php" class="button button-secondary">Explorer les formations</a></p>
    </section>
</div>

<?php
if (isset($conn)) {
    $conn->close();
}
require_once 'includes/footer.php';
?>