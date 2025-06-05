<?php
// admin/add_user.php
$admin_page_title = "Ajouter un Utilisateur";
require_once '../includes/admin_header.php';

if (!isAdmin()) {
    redirect(getBaseUrl() . '/admin/login.php?error=unauthorized');
    exit;
}

$add_username = '';
$add_email = '';
$add_first_name = '';
$add_last_name = '';
$add_role = 'student'; // Default role
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $errors[] = "Erreur de sécurité.";
    } else {
        $add_username = sanitizeInput($_POST['username'] ?? '');
        $add_email = filter_var(sanitizeInput($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $add_first_name = sanitizeInput($_POST['first_name'] ?? '');
        $add_last_name = sanitizeInput($_POST['last_name'] ?? '');
        $add_role = sanitizeInput($_POST['role'] ?? 'student');

        // Validation
        if (empty($add_username)) $errors[] = "Nom d'utilisateur requis.";
        if (empty($add_email)) $errors[] = "Email valide requis.";
        if (empty($password)) $errors[] = "Mot de passe requis.";
        elseif (strlen($password) < 8 || !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password)) {
            $errors[] = "Le mot de passe doit faire 8 caractères min., incluant majuscule, minuscule, chiffre, et caractère spécial.";
        }
        if ($password !== $confirm_password) $errors[] = "Les mots de passe ne correspondent pas.";
        if (!in_array($add_role, ['student', 'premium_student', 'admin'])) $errors[] = "Rôle invalide.";

        // Check if username or email already exists
        if (empty($errors)) {
            $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt_check->bind_param("ss", $add_username, $add_email);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) {
                $errors[] = "Nom d'utilisateur ou email déjà existant.";
            }
            $stmt_check->close();
        }

        if (empty($errors)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt_insert = $conn->prepare("INSERT INTO users (username, email, password, first_name, last_name, role) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_insert->bind_param("ssssss", $add_username, $add_email, $hashed_password, $add_first_name, $add_last_name, $add_role);

            if ($stmt_insert->execute()) {
                $_SESSION['message'] = '<p class="admin-success-message">Utilisateur ajouté avec succès !</p>';
                redirect(getBaseUrl() . '/admin/manage_users.php');
                exit;
            } else {
                $errors[] = "Erreur lors de l'ajout: " . $stmt_insert->error;
            }
            $stmt_insert->close();
        }
    }
}
$csrf_token_admin = generateCsrfToken();
?>

<h2><?php echo htmlspecialchars($admin_page_title); ?></h2>

<?php if (!empty($errors)): ?>
    <div class="admin-error-message">
        <strong>Erreurs :</strong><br>
        <ul><?php foreach ($errors as $error) echo '<li>' . htmlspecialchars($error) . '</li>'; ?></ul>
    </div>
<?php endif; ?>

<form action="add_user.php" method="POST" class="admin-form">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token_admin); ?>">

    <div class="form-group">
        <label for="username">Nom d'utilisateur <span style="color:red;">*</span></label>
        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($add_username); ?>" required>
    </div>
    <div class="form-group">
        <label for="email">Email <span style="color:red;">*</span></label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($add_email); ?>" required>
    </div>
    <div class="form-group">
        <label for="first_name">Prénom</label>
        <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($add_first_name); ?>">
    </div>
    <div class="form-group">
        <label for="last_name">Nom</label>
        <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($add_last_name); ?>">
    </div>
    <div class="form-group">
        <label for="password">Mot de passe <span style="color:red;">*</span></label>
        <input type="password" id="password" name="password" required aria-describedby="addUserPasswordHelp">
        <small id="addUserPasswordHelp">Min. 8 caractères, 1 majuscule, 1 minuscule, 1 chiffre, 1 caractère spécial.</small>
    </div>
    <div class="form-group">
        <label for="confirm_password">Confirmer Mot de passe <span style="color:red;">*</span></label>
        <input type="password" id="confirm_password" name="confirm_password" required>
    </div>
    <div class="form-group">
        <label for="role">Rôle <span style="color:red;">*</span></label>
        <select id="role" name="role" required>
            <option value="student" <?php echo ($add_role === 'student') ? 'selected' : ''; ?>>Étudiant</option>
            <option value="premium_student" <?php echo ($add_role === 'premium_student') ? 'selected' : ''; ?>>Étudiant Premium</option>
            <option value="admin" <?php echo ($add_role === 'admin') ? 'selected' : ''; ?>>Administrateur</option>
        </select>
    </div>
    <div class="form-actions">
        <button type="submit" class="admin-button-primary">Ajouter Utilisateur</button>
        <a href="manage_users.php" class="admin-button-secondary" style="margin-left:10px;">Annuler</a>
    </div>
</form>

<?php
if (isset($conn)) {
    $conn->close();
}
require_once '../includes/admin_footer.php';
?>