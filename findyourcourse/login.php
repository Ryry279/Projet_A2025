<?php
// login.php (User Login)
$page_title = "Connexion Utilisateur";
$page_description = "Connectez-vous à votre compte Find Your Course pour accéder à vos formations, favoris et plus encore.";
require_once 'includes/db_connect.php';
require_once 'includes/functions.php'; // Starts session, provides functions

// If already logged in, redirect to profile or intended page
if (isLoggedIn() && !isAdmin()) { // Don't redirect admin from here if they land by mistake
    $redirect_url = $_SESSION['redirect_url'] ?? getBaseUrl() . '/profile.php';
    unset($_SESSION['redirect_url']);
    redirect($redirect_url);
    exit;
} elseif (isAdmin()){ // If admin lands here, send to admin panel
     redirect(getBaseUrl() . '/admin/');
    exit;
}


$error_message = '';
$info_message = ''; // For messages like "please login to continue"

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
if (isset($_SESSION['info_message'])) {
    $info_message = $_SESSION['info_message'];
    unset($_SESSION['info_message']);
}
if (isset($_SESSION['success_message'])) { // e.g., after registration
    $info_message = $_SESSION['success_message']; // Use info_message for success here
    unset($_SESSION['success_message']);
}


$login_email_username = ''; // To repopulate form field on error

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error_message = "Erreur de sécurité. Veuillez réessayer.";
    } else {
        $login_email_username = sanitizeInput($_POST['email_username'] ?? '');
        $password_posted = $_POST['password'] ?? '';

        if (empty($login_email_username) || empty($password_posted)) {
            $error_message = "L'email/nom d'utilisateur et le mot de passe sont requis.";
        } else {
            // Check if login is email or username
            $login_field_type = filter_var($login_email_username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

            $stmt = $conn->prepare("SELECT id, username, email, password, role FROM users WHERE $login_field_type = ?");
            if (!$stmt) {
                error_log("Login prepare failed: " . $conn->error);
                $error_message = "Une erreur de connexion est survenue. Veuillez réessayer.";
            } else {
                $stmt->bind_param("s", $login_email_username);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    if (password_verify($password_posted, $user['password'])) {
                        // Password is correct
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['role'] = $user['role'];
                        session_regenerate_id(true); // Security best practice

                        // Redirect logic
                        $redirect_to = $_SESSION['redirect_url'] ?? ($user['role'] === 'admin' ? getBaseUrl() . '/admin/' : getBaseUrl() . '/profile.php');
                        unset($_SESSION['redirect_url']);
                        redirect($redirect_to);
                        exit;
                    } else {
                        $error_message = "Email/nom d'utilisateur ou mot de passe incorrect.";
                    }
                } else {
                    $error_message = "Email/nom d'utilisateur ou mot de passe incorrect.";
                }
                $stmt->close();
            }
        }
    }
}

// Regenerate CSRF token for the form (or generate if first load)
$csrf_token = generateCsrfToken(); 
require_once 'includes/header.php'; // Call header after CSRF generation for this page
?>

<div class="container" style="max-width: 500px;">
    <section class="login-form-section">
        <h1 style="text-align:center; margin-bottom:30px;"><?php echo $page_title; ?></h1>

        <?php if ($info_message): ?>
            <p style="padding: 10px; border-radius: 5px; background-color: #e3f2fd; color: #0d47a1; border: 1px solid #bbdefb; text-align:center; margin-bottom:15px;">
                <?php echo htmlspecialchars($info_message); ?>
            </p>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <p style="padding: 10px; border-radius: 5px; background-color: #ffebee; color: #c62828; border: 1px solid #ef9a9a; text-align:center; margin-bottom:15px;">
                <?php echo htmlspecialchars($error_message); ?>
            </p>
        <?php endif; ?>

        <form action="login.php" method="POST" class="user-access-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            
            <div class="form-group">
                <label for="login-email-username">Email ou Nom d'utilisateur :</label>
                <input type="text" id="login-email-username" name="email_username" required 
                       value="<?php echo htmlspecialchars($login_email_username); ?>" autofocus>
            </div>
            
            <div class="form-group">
                <label for="login-password">Mot de passe :</label>
                <input type="password" id="login-password" name="password" required>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="button" style="width:100%;">Se Connecter</button>
            </div>
            
            <p style="text-align:center; margin-top:20px;">
                <a href="forgot_password.php">Mot de passe oublié ?</a> </p>
            <p style="text-align:center; margin-top:10px;">
                Pas encore de compte ? <a href="register.php">Inscrivez-vous ici</a>.
            </p>
        </form>
    </section>
</div>

<?php
if (isset($conn)) {
    $conn->close();
}
require_once 'includes/footer.php';
?>