<?php
// admin/login.php
require_once '../includes/db_connect.php'; // For database connection
require_once '../includes/functions.php';   // For session_start, redirect, getBaseUrl

$admin_page_title = "Connexion Administrateur"; // Used by a potential header if any before form

// If already logged in as admin, redirect to dashboard
if (isAdmin()) {
    redirect(getBaseUrl() . '/admin/index.php');
    exit;
}

$error_message = '';
$success_message = '';

if (isset($_GET['logged_out'])) {
    $success_message = "Vous avez été déconnecté avec succès.";
}
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'unauthorized') {
        $error_message = "Vous devez être connecté en tant qu'administrateur pour accéder à cette page.";
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['username']) || empty($_POST['password'])) {
        $error_message = "Le nom d'utilisateur et le mot de passe sont requis.";
    } else {
        $username = sanitizeInput($_POST['username']);
        $password_posted = $_POST['password']; // Don't sanitize password before verification

        // Prepare statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        if (!$stmt) {
            // Log error and show generic message
            error_log("Prepare failed: (" . $conn->errno . ") " . $conn->error);
            $error_message = "Une erreur est survenue. Veuillez réessayer.";
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                // Verify password and role
                if (password_verify($password_posted, $user['password']) && $user['role'] === 'admin') {
                    // Password is correct, start a new session and set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    
                    // Regenerate session ID for security
                    session_regenerate_id(true); 

                    redirect(getBaseUrl() . '/admin/index.php');
                    exit;
                } else {
                    $error_message = "Nom d'utilisateur, mot de passe ou rôle incorrect.";
                }
            } else {
                $error_message = "Nom d'utilisateur, mot de passe ou rôle incorrect.";
            }
            $stmt->close();
        }
    }
}
$conn->close(); // Close connection after processing
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($admin_page_title); ?> - Find Your Course</title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(getBaseUrl()); ?>/assets/css/admin_style.css">
    <link rel="icon" href="<?php echo htmlspecialchars(getBaseUrl()); ?>/assets/images/favicon.png" type="image/png">
</head>
<body class="admin-body"> <div class="admin-login-container">
        <h2><?php echo htmlspecialchars($admin_page_title); ?></h2>

        <?php if ($error_message): ?>
            <p class="admin-error-message"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <p class="admin-success-message"><?php echo htmlspecialchars($success_message); ?></p>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="username">Nom d'utilisateur :</label>
                <input type="text" id="username" name="username" required autofocus
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="password">Mot de passe :</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="admin-button">Se Connecter</button>
        </form>
        <p style="text-align: center; margin-top: 20px;">
            <a href="<?php echo htmlspecialchars(getBaseUrl()); ?>/" style="font-size:0.9em;">Retour au site principal</a>
        </p>
    </div>
</body>
</html>