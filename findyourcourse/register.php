<?php
// register.php (User Registration)
$page_title = "Inscription Utilisateur - Find Your Course";
$page_description = "Créez votre compte Find Your Course pour accéder à nos formations en ligne sur les ERP, Salesforce et bien plus.";
// It's crucial to include db_connect.php before functions.php if functions.php might use $conn (though it shouldn't directly)
// and definitely before header.php which might also use $conn if extended.
require_once 'includes/db_connect.php';
require_once 'includes/functions.php'; // Starts session, provides CSRF, sanitizeInput, etc.

// If already logged in, redirect to profile or intended page
if (isLoggedIn()) {
    redirect(getBaseUrl() . '/profile.php'); // Or to the main page, or a dashboard if you create one for users
    exit;
}

$error_message = '';
$success_message = ''; // Not typically used here as we redirect on success

// To repopulate form fields on error (except passwords)
$reg_username = '';
$reg_email = '';
$reg_first_name = '';
$reg_last_name = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error_message = "Erreur de sécurité. Veuillez soumettre le formulaire à nouveau.";
    } else {
        // 2. Sanitize and retrieve inputs
        $reg_username = sanitizeInput($_POST['username'] ?? '');
        $reg_email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL); // Sanitize then validate
        $password = $_POST['password'] ?? ''; // Do not sanitize password before hashing
        $confirm_password = $_POST['confirm_password'] ?? '';
        $reg_first_name = sanitizeInput($_POST['first_name'] ?? '');
        $reg_last_name = sanitizeInput($_POST['last_name'] ?? '');

        // 3. Perform Validation
        if (empty($reg_username) || empty($reg_email) || empty($password) || empty($confirm_password)) {
            $error_message = "Tous les champs marqués d'un astérisque (*) sont requis.";
        } elseif (!filter_var($reg_email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "L'adresse e-mail fournie n'est pas valide.";
        } elseif (strlen($reg_username) < 3 || strlen($reg_username) > 50 || !preg_match('/^[a-zA-Z0-9_]+$/', $reg_username)) {
            $error_message = "Le nom d'utilisateur doit contenir entre 3 et 50 caractères alphanumériques ou underscores (_).";
        }
        // Password Policy (as per project document: 8 chars min, 1 upper, 1 lower, 1 digit, 1 special)
        elseif (strlen($password) < 8) {
            $error_message = "Le mot de passe doit contenir au moins 8 caractères.";
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $error_message = "Le mot de passe doit contenir au moins une lettre majuscule.";
        } elseif (!preg_match('/[a-z]/', $password)) {
            $error_message = "Le mot de passe doit contenir au moins une lettre minuscule.";
        } elseif (!preg_match('/[0-9]/', $password)) {
            $error_message = "Le mot de passe doit contenir au moins un chiffre.";
        } elseif (!preg_match('/[\W_]/', $password)) { // \W is non-alphanumeric, _ is underscore
            $error_message = "Le mot de passe doit contenir au moins un caractère spécial (ex: !@#$%^&*).";
        } elseif ($password !== $confirm_password) {
            $error_message = "Les mots de passe saisis ne correspondent pas.";
        } else {
            // Check if username or email already exists
            $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt_check->bind_param("ss", $reg_username, $reg_email);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows > 0) {
                $existing_user = $result_check->fetch_assoc();
                // More specific error (though sometimes less secure to reveal which one exists)
                if ($existing_user['username'] === $reg_username) {
                    $error_message = "Ce nom d'utilisateur est déjà pris. Veuillez en choisir un autre.";
                } elseif ($existing_user['email'] === $reg_email) {
                    $error_message = "Cette adresse e-mail est déjà associée à un compte.";
                } else { // Fallback generic message if somehow both match another user
                     $error_message = "Nom d'utilisateur ou email déjà utilisé.";
                }
            } else {
                // All checks passed, proceed to insert user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                // Default role is 'student' as per database schema

                $stmt_insert = $conn->prepare("INSERT INTO users (username, email, password, first_name, last_name) VALUES (?, ?, ?, ?, ?)");
                $stmt_insert->bind_param("sssss", $reg_username, $reg_email, $hashed_password, $reg_first_name, $reg_last_name);

                if ($stmt_insert->execute()) {
                    $_SESSION['success_message'] = "Inscription réussie ! Vous pouvez maintenant vous connecter avec vos identifiants.";
                    // Redirect to login page to avoid form re-submission on refresh
                    redirect(getBaseUrl() . '/login.php');
                    exit; // Important to prevent further script execution
                } else {
                    $error_message = "Une erreur est survenue lors de la création de votre compte. Veuillez réessayer.";
                    error_log("User Registration Error: " . $stmt_insert->error); // Log error for admin
                }
                $stmt_insert->close();
            }
            $stmt_check->close();
        }
    }
}

// Generate a new CSRF token for the form to use, or if one wasn't processed.
$csrf_token = generateCsrfToken();
require_once 'includes/header.php'; // Call header after CSRF generation and all PHP logic for this page
?>

<div class="container" style="max-width: 650px;"> <section class="registration-form-section">
        <h1 style="text-align:center; margin-bottom:30px;"><?php echo htmlspecialchars($page_title); ?></h1>

        <?php if ($error_message): ?>
            <p class="error-message" style="padding: 12px 15px; border-radius: 6px; background-color: #ffebee; color: #c62828; border: 1px solid #ef9a9a; text-align:left; margin-bottom:20px; font-size:0.9em;">
                <?php echo htmlspecialchars($error_message); ?>
            </p>
        <?php endif; ?>
        <?php if ($success_message): // This will likely not be shown due to redirect, but good for fallback ?>
            <p class="success-message" style="padding: 12px 15px; border-radius: 6px; background-color: #e6fffa; color: #00695c; border: 1px solid #b2dfdb; text-align:center; margin-bottom:20px;">
                <?php echo htmlspecialchars($success_message); ?>
            </p>
        <?php endif; ?>

        <form action="register.php" method="POST" class="user-access-form" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            
            <div class="form-group">
                <label for="reg-username">Nom d'utilisateur <span class="required-asterisk" style="color:red;">*</span> :</label>
                <input type="text" id="reg-username" name="username" required 
                       value="<?php echo htmlspecialchars($reg_username); ?>" 
                       pattern="^[a-zA-Z0-9_]{3,50}$" 
                       title="Doit contenir entre 3 et 50 caractères alphanumériques ou underscores (_).">
            </div>
            
            <div class="form-group">
                <label for="reg-email">Adresse Email <span class="required-asterisk" style="color:red;">*</span> :</label>
                <input type="email" id="reg-email" name="email" required 
                       value="<?php echo htmlspecialchars($reg_email); ?>">
            </div>

            <div style="display:flex; gap: 20px; flex-wrap:wrap;">
                <div class="form-group" style="flex:1; min-width:200px;">
                    <label for="reg-first-name">Prénom :</label>
                    <input type="text" id="reg-first-name" name="first_name" 
                           value="<?php echo htmlspecialchars($reg_first_name); ?>">
                </div>
                <div class="form-group" style="flex:1; min-width:200px;">
                    <label for="reg-last-name">Nom de famille :</label>
                    <input type="text" id="reg-last-name" name="last_name" 
                           value="<?php echo htmlspecialchars($reg_last_name); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="reg-password">Mot de passe <span class="required-asterisk" style="color:red;">*</span> :</label>
                <input type="password" id="reg-password" name="password" required 
                       aria-describedby="regPasswordHelp" 
                       pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{8,}"
                       title="Min. 8 caractères, incluant au moins une majuscule, une minuscule, un chiffre, et un caractère spécial.">
                <small id="regPasswordHelp" style="font-size:0.85em; color:#555; display:block; margin-top:5px;">Doit contenir au moins 8 caractères, une lettre majuscule, une lettre minuscule, un chiffre, et un caractère spécial (ex: !@#$%^&*).</small>
            </div>
            
            <div class="form-group">
                <label for="reg-confirm-password">Confirmer le mot de passe <span class="required-asterisk" style="color:red;">*</span> :</label>
                <input type="password" id="reg-confirm-password" name="confirm_password" required>
            </div>
            
            <div class="form-actions" style="margin-top: 25px;">
                <button type="submit" class="button" style="width:100%; font-size:1.05em; padding: 12px 20px;">Créer mon Compte</button>
            </div>
            
            <p style="text-align:center; margin-top:25px; font-size:0.95em;">
                Vous avez déjà un compte ? <a href="<?php echo getBaseUrl(); ?>/login.php">Connectez-vous ici</a>.
            </p>
        </form>
    </section>
</div>

<?php
if (isset($conn)) { // Close connection if it was opened
    $conn->close();
}
require_once 'includes/footer.php';
?>