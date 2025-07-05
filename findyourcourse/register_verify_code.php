<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

$page_title = "Vérification de l'adresse email";
require_once 'includes/header.php';

$email = $_GET['email'] ?? '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $code = $_POST['code'] ?? '';

    if (strlen($code) === 6 && ctype_digit($code)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND code_verification = ?");
        $stmt->bind_param("si", $email, $code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $stmt_update = $conn->prepare("UPDATE users SET email_verified = 1, code_verification = NULL WHERE id = ?");
            $stmt_update->bind_param("i", $user['id']);
            $stmt_update->execute();
            $_SESSION['message'] = "Email vérifié avec succès. Vous pouvez maintenant vous connecter.";
            redirect(getBaseUrl() . '/login.php');
            exit;
        } else {
            $message = "Code de vérification invalide. Veuillez réessayer.";
        }
    } else {
        $message = "Veuillez entrer un code à 6 chiffres.";
    }
}
?>

<div class="container" style="max-width: 600px;">
    <h2 style="margin-top:40px; text-align:center;">Vérification de votre adresse email</h2>
    <?php if ($message): ?>
        <p class="error-message" style="margin:20px auto; color:#c62828; text-align:center; font-size:0.95em;">
            <?php echo htmlspecialchars($message); ?>
        </p>
    <?php endif; ?>
    <form action="register_verify_code.php" method="POST" class="user-access-form" style="margin-top:30px;">
        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
        <div class="form-group">
            <label for="code">Code de vérification <span style="color:red;">*</span> :</label>
            <input type="text" id="code" name="code" required pattern="\d{6}" class="form-control"
                placeholder="Ex: 123456">
        </div>
        <div class="form-actions" style="margin-top: 25px;">
            <button type="submit" class="button"
                style="width:100%; padding: 12px 20px; font-size:1.05em;">Vérifier</button>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>