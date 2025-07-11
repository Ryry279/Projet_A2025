<?php
$admin_page_title = "Modifier un Mail";
require_once '../includes/admin_header.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!isAdmin()) {
    $_SESSION['message'] = '<p class="admin-error-message">Accès non autorisé.</p>';
    redirect(getBaseUrl() . '/admin/login.php');
    exit;
}

$mail_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$mail_id) {
    $_SESSION['message'] = '<p class="admin-error-message">ID de mail invalide.</p>';
    redirect(getBaseUrl() . '/admin/manage_mails.php?status=error');
    exit;
}

$errors = [];
$titre = $objet = $message_content = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = sanitizeInput($_POST['titre']);
    $objet = sanitizeInput($_POST['objet']);
    $message_content = sanitizeInput($_POST['message']);

    if (empty($titre) || empty($objet) || empty($message_content)) {
        $errors[] = "Tous les champs sont requis.";
    } else {
        $stmt_update = $conn->prepare("UPDATE mail_gestion SET titre = ?, objet = ?, message = ? WHERE id = ?");
        $stmt_update->bind_param("sssi", $titre, $objet, $message_content, $mail_id);

        if ($stmt_update->execute()) {
            $_SESSION['message'] = '<p class="admin-success-message">Mail mis à jour avec succès.</p>';
            redirect(getBaseUrl() . '/admin/manage_mails.php?status=updated');
            exit;
        } else {
            $errors[] = "Erreur lors de la mise à jour: " . $stmt_update->error;
        }
        $stmt_update->close();
    }
}

$stmt = $conn->prepare("SELECT * FROM mail_gestion WHERE id = ?");
$stmt->bind_param("i", $mail_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $_SESSION['message'] = '<p class="admin-error-message">Mail non trouvé.</p>';
    redirect(getBaseUrl() . '/admin/manage_mails.php?status=error');
    exit;
}

$mail = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $titre = $mail['titre'];
    $objet = $mail['objet'];
    $message_content = $mail['message'];
}
?>

<h2><?php echo htmlspecialchars($admin_page_title); ?>: <?php echo htmlspecialchars($titre); ?></h2>

<?php if (!empty($errors)): ?>
    <div class="admin-error-message">
        <strong>Veuillez corriger les erreurs :</strong><br>
        <ul><?php foreach ($errors as $error)
            echo '<li>' . htmlspecialchars($error) . '</li>'; ?></ul>
    </div>
<?php elseif (isset($_SESSION['message'])): ?>
    <?php echo $_SESSION['message'];
    unset($_SESSION['message']); ?>
<?php endif; ?>

<form action="edit_mail.php?id=<?php echo $mail_id; ?>" method="POST" class="admin-form">
    <div class="form-group">
        <label for="titre">Titre <span style="color:red;">*</span></label>
        <input type="text" id="titre" name="titre" value="<?php echo htmlspecialchars($titre); ?>" required>
    </div>

    <div class="form-group">
        <label for="objet">Objet <span style="color:red;">*</span></label>
        <input type="text" id="objet" name="objet" value="<?php echo htmlspecialchars($objet); ?>" required>
    </div>

    <div class="form-group">
        <label for="message">Message <span style="color:red;">*</span></label>
        <textarea id="message" name="message" rows="10"
            required><?php echo htmlspecialchars($message_content); ?></textarea>
    </div>

    <div class="form-actions">
        <button type="submit" class="admin-button-primary">Mettre à jour</button>
        <a href="manage_mails.php" class="admin-button-secondary" style="margin-left:10px;">Annuler</a>
    </div>
</form>

<?php
if (isset($conn)) {
    $conn->close();
}
require_once '../includes/admin_footer.php';
?>