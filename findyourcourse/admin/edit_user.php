<?php
// admin/edit_user.php
$admin_page_title = "Modifier un Utilisateur";
require_once '../includes/admin_header.php';

if (!isAdmin()) {
    redirect(getBaseUrl() . '/admin/login.php?error=unauthorized');
    exit;
}

$user_id_to_edit = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$user_data = null;
$errors = [];
$success_message = '';

if (!$user_id_to_edit) {
    $_SESSION['message'] = '<p class="admin-error-message">ID utilisateur invalide.</p>';
    redirect(getBaseUrl() . '/admin/manage_users.php');
    exit;
}

// Fetch user data
$stmt_fetch = $conn->prepare("SELECT id, username, email, first_name, last_name, role FROM users WHERE id = ?");
$stmt_fetch->bind_param("i", $user_id_to_edit);
$stmt_fetch->execute();
$result_fetch = $stmt_fetch->get_result();
if ($result_fetch->num_rows === 1) {
    $user_data = $result_fetch->fetch_assoc();
} else {
    $_SESSION['message'] = '<p class="admin-error-message">Utilisateur non trouvé.</p>';
    redirect(getBaseUrl() . '/admin/manage_users.php');
    exit;
}
$stmt_fetch->close();

// Populate form fields for display
$edit_username = $user_data['username']; // Username generally should not be editable by admin easily
$edit_email = $user_data['email'];
$edit_first_name = $user_data['first_name'];
$edit_last_name = $user_data['last_name'];
$edit_role = $user_data['role'];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $errors[] = "Erreur de sécurité (jeton CSRF invalide).";
    } else {
        $edit_email = filter_var(sanitizeInput($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $edit_first_name = sanitizeInput($_POST['first_name'] ?? '');
        $edit_last_name = sanitizeInput($_POST['last_name'] ?? '');
        $new_role = sanitizeInput($_POST['role'] ?? '');
        $new_password = $_POST['new_password'] ?? ''; // Optional new password

        if (empty($edit_email)) $errors[] = "L'adresse e-mail est requise et doit être valide.";
        if (!in_array($new_role, ['student', 'premium_student', 'admin'])) {
            $errors[] = "Rôle invalide sélectionné.";
        }
        // Prevent demoting the only admin or the 'admin' username easily
        if ($user_data['username'] === 'admin' && $new_role !== 'admin') {
            $errors[] = "Le rôle de l'utilisateur 'admin' principal ne peut pas être modifié.";
        }


        if (empty($errors)) {
            // Check if new email is already taken by another user
            $stmt_email_check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt_email_check->bind_param("si", $edit_email, $user_id_to_edit);
            $stmt_email_check->execute();
            if ($stmt_email_check->get_result()->num_rows > 0) {
                $errors[] = "Cette adresse e-mail est déjà utilisée par un autre compte.";
            }
            $stmt_email_check->close();
        }

        // Password change (optional)
        $password_update_sql_part = "";
        $password_param_type = "";
        $password_param_val = null;

        if (!empty($new_password)) {
            if (strlen($new_password) < 8 || !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $new_password)) {
                $errors[] = "Le nouveau mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.";
            } else {
                $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
                $password_update_sql_part = ", password = ?";
                $password_param_type = "s";
                $password_param_val = $hashed_new_password;
            }
        }


        if (empty($errors)) {
            $sql_update = "UPDATE users SET email = ?, first_name = ?, last_name = ?, role = ? $password_update_sql_part WHERE id = ?";
            $types = "ssss" . $password_param_type . "i";
            $params_to_bind = [$edit_email, $edit_first_name, $edit_last_name, $new_role];
            if ($password_param_val !== null) {
                $params_to_bind[] = $password_param_val;
            }
            $params_to_bind[] = $user_id_to_edit;
            
            $stmt_update = $conn->prepare($sql_update);
            // Pass parameters by reference for bind_param
            $bind_params_ref = [];
            foreach($params_to_bind as $key => $value) {
                $bind_params_ref[$key] = &$params_to_bind[$key];
            }
            array_unshift($bind_params_ref, $types); // Add types string at the beginning

            call_user_func_array([$stmt_update, 'bind_param'], $bind_params_ref);


            if ($stmt_update->execute()) {
                $_SESSION['message'] = '<p class="admin-success-message">Utilisateur mis à jour avec succès.</p>';
                redirect(getBaseUrl() . '/admin/manage_users.php');
                exit;
            } else {
                $errors[] = "Erreur lors de la mise à jour de l'utilisateur: " . $stmt_update->error;
            }
            $stmt_update->close();
        }
    }
}
$csrf_token_admin = generateCsrfToken();
?>

<h2><?php echo htmlspecialchars($admin_page_title); ?>: <?php echo htmlspecialchars($user_data['username']); ?></h2>

<?php if (!empty($errors)): ?>
    <div class="admin-error-message">
        <strong>Veuillez corriger les erreurs :</strong><br>
        <ul><?php foreach ($errors as $error) echo '<li>' . htmlspecialchars($error) . '</li>'; ?></ul>
    </div>
<?php endif; ?>

<form action="edit_user.php?id=<?php echo $user_id_to_edit; ?>" method="POST" class="admin-form">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token_admin); ?>">

    <div class="form-group">
        <label for="username">Nom d'utilisateur (Non modifiable)</label>
        <input type="text" id="username" name="username_display" value="<?php echo htmlspecialchars($edit_username); ?>" readonly disabled>
    </div>

    <div class="form-group">
        <label for="email">Email <span style="color:red;">*</span></label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($edit_email); ?>" required>
    </div>

    <div class="form-group">
        <label for="first_name">Prénom</label>
        <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($edit_first_name); ?>">
    </div>

    <div class="form-group">
        <label for="last_name">Nom</label>
        <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($edit_last_name); ?>">
    </div>
    
    <div class="form-group">
        <label for="role">Rôle <span style="color:red;">*</span></label>
        <select id="role" name="role" required <?php echo ($user_data['username'] === 'admin') ? 'disabled' : ''; ?>>
            <option value="student" <?php echo ($edit_role === 'student') ? 'selected' : ''; ?>>Étudiant</option>
            <option value="premium_student" <?php echo ($edit_role === 'premium_student') ? 'selected' : ''; ?>>Étudiant Premium</option>
            <option value="admin" <?php echo ($edit_role === 'admin') ? 'selected' : ''; ?>>Administrateur</option>
        </select>
         <?php if ($user_data['username'] === 'admin'): ?>
            <small>Le rôle de l'administrateur principal ne peut pas être modifié ici.</small>
        <?php endif; ?>
    </div>

    <hr style="margin: 20px 0;">
    <h4>Changer le mot de passe (optionnel)</h4>
     <div class="form-group">
        <label for="new_password">Nouveau mot de passe (laisser vide pour ne pas changer)</label>
        <input type="password" id="new_password" name="new_password" aria-describedby="newPasswordHelpAdmin">
        <small id="newPasswordHelpAdmin">Min. 8 caractères, 1 majuscule, 1 minuscule, 1 chiffre, 1 caractère spécial.</small>
    </div>


    <div class="form-actions">
        <button type="submit" class="admin-button-primary">Mettre à jour l'Utilisateur</button>
        <a href="manage_users.php" class="admin-button-secondary" style="margin-left:10px;">Annuler</a>
    </div>
</form>

<?php
if (isset($conn)) {
    $conn->close();
}
require_once '../includes/admin_footer.php';
?>