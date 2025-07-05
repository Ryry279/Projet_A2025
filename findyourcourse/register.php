<?php
// inscription + confirmation par mail avec un code à rentrer 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
require 'sendemail/phpmailer/src/Exception.php';
require 'sendemail/phpmailer/src/PHPMailer.php';
require 'sendemail/phpmailer/src/SMTP.php';

$page_title = "Inscription Utilisateur - Find Your Course";
$page_description = "Créez votre compte Find Your Course pour accéder à nos formations.";

if (isLoggedIn()) {
    redirect(getBaseUrl() . '/profile.php');
    exit;
}

$error_message = '';
$success_message = '';
$reg_username = '';
$reg_email = '';
$reg_first_name = '';
$reg_last_name = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error_message = "Erreur de sécurité. Veuillez soumettre le formulaire à nouveau.";
    } else {
        $reg_username = sanitizeInput($_POST['username'] ?? '');
        $reg_email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $reg_first_name = sanitizeInput($_POST['first_name'] ?? '');
        $reg_last_name = sanitizeInput($_POST['last_name'] ?? '');

        if (empty($reg_username) || empty($reg_email) || empty($password) || empty($confirm_password)) {
            $error_message = "Tous les champs marqués d'un astérisque (*) sont requis.";
        } elseif (!filter_var($reg_email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "L'adresse e-mail fournie n'est pas valide.";
        } elseif (strlen($reg_username) < 3 || strlen($reg_username) > 50 || !preg_match('/^[a-zA-Z0-9_]+$/', $reg_username)) {
            $error_message = "Le nom d'utilisateur doit contenir entre 3 et 50 caractères alphanumériques ou underscores (_).";
        } elseif (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[\W_]/', $password)) {
            $error_message = "Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.";
        } elseif ($password !== $confirm_password) {
            $error_message = "Les mots de passe saisis ne correspondent pas.";
        } else {
            $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt_check->bind_param("ss", $reg_username, $reg_email);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows > 0) {
                $error_message = "Nom d'utilisateur ou email déjà utilisé.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $code = random_int(100000, 999999);

                $stmt_insert = $conn->prepare("INSERT INTO users (username, email, password, first_name, last_name, code_verification) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt_insert->bind_param("sssssi", $reg_username, $reg_email, $hashed_password, $reg_first_name, $reg_last_name, $code);

                if ($stmt_insert->execute()) {
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'eme.findyourcourse@gmail.com';
                        $mail->Password = 'yqxc tbap dquz aqxt';
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                        $mail->Port = 465;

                        $mail->setFrom('eme.findyourcourse@gmail.com', 'Find Your Course');
                        $mail->addAddress($reg_email);
                        $mail->isHTML(true);
                        $mail->Subject = 'Vérification de votre adresse email';
                        $mail->Body = "Bonjour $reg_first_name,<br><br>Merci de vous être inscrit. Voici votre code de vérification : <strong>$code</strong>";

                        $mail->send();
                        redirect(getBaseUrl() . "/register_verify_code.php?email=" . urlencode($reg_email));
                        exit;
                    } catch (Exception $e) {
                        $error_message = "Erreur lors de l'envoi de l'email : {$mail->ErrorInfo}";
                    }
                } else {
                    $error_message = "Erreur lors de l'inscription. Veuillez réessayer.";
                }
                $stmt_insert->close();
            }
            $stmt_check->close();
        }
    }
}

$csrf_token = generateCsrfToken();
require_once 'includes/header.php';
?>

<div class="container" style="max-width: 650px;">
    <section class="registration-form-section">
        <h1 style="text-align:center; margin-bottom:30px;">
            <?php echo htmlspecialchars($page_title); ?>
        </h1>

        <?php if ($error_message): ?>
            <p class="error-message"
                style="padding: 12px 15px; border-radius: 6px; background-color: #ffebee; color: #c62828; border: 1px solid #ef9a9a; text-align:left; margin-bottom:20px; font-size:0.9em;">
                <?php echo htmlspecialchars($error_message); ?>
            </p>
        <?php endif; ?>

        <form action="register.php" method="POST" class="user-access-form" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <div class="form-group">
                <label for="reg-username">Nom d'utilisateur <span style="color:red;">*</span> :</label>
                <input type="text" id="reg-username" name="username" required
                    value="<?php echo htmlspecialchars($reg_username); ?>" pattern="^[a-zA-Z0-9_]{3,50}$"
                    title="Doit contenir entre 3 et 50 caractères alphanumériques ou underscores (_).">
            </div>

            <div class="form-group">
                <label for="reg-email">Adresse Email <span style="color:red;">*</span> :</label>
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
                <label for="reg-password">Mot de passe <span style="color:red;">*</span> :</label>
                <input type="password" id="reg-password" name="password" required
                    pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{8,}"
                    title="Min. 8 caractères, incluant au moins une majuscule, une minuscule, un chiffre, et un caractère spécial.">
            </div>

            <div class="form-group">
                <label for="reg-confirm-password">Confirmer le mot de passe <span style="color:red;">*</span> :</label>
                <input type="password" id="reg-confirm-password" name="confirm_password" required>
            </div>

            <div class="form-actions" style="margin-top: 25px;">
                <button type="submit" class="button" style="width:100%; font-size:1.05em; padding: 12px 20px;">Créer mon
                    Compte</button>
            </div>

            <p style="text-align:center; margin-top:25px; font-size:0.95em;">
                Vous avez déjà un compte ? <a href="<?php echo getBaseUrl(); ?>/login.php">Connectez-vous ici</a>.
            </p>
        </form>
    </section>
</div>

<?php require_once 'includes/footer.php'; ?>