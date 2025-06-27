<?php
// admin/manage_settings.php
$admin_page_title = "Gérer les Paramètres";
require_once '../includes/admin_header.php';

if (!isAdmin()) {
    redirect(getBaseUrl() . '/admin/login.php?error=unauthorized');
    exit;
}

$message = '';
// Gérer la soumission du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $message = '<p class="admin-error-message">Erreur de sécurité.</p>';
    } else {
        $price = filter_var($_POST['premium_subscription_price'], FILTER_VALIDATE_FLOAT);
        if ($price === false || $price < 0) {
            $message = '<p class="admin-error-message">Le prix doit être un nombre valide.</p>';
        } else {
            $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'premium_subscription_price'");
            $stmt->bind_param("s", $price);
            if ($stmt->execute()) {
                $message = '<p class="admin-success-message">Paramètres mis à jour avec succès !</p>';
            } else {
                $message = '<p class="admin-error-message">Erreur lors de la mise à jour.</p>';
            }
            $stmt->close();
        }
    }
}

// Récupérer les paramètres actuels
$settings = [];
$result = $conn->query("SELECT setting_key, setting_value, setting_description FROM settings");
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row;
}

$csrf_token_admin = generateCsrfToken();
?>

<h2><?php echo htmlspecialchars($admin_page_title); ?></h2>
<?php echo $message; ?>

<form action="manage_settings.php" method="POST" class="admin-form">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token_admin); ?>">

    <div class="form-group">
        <label for="premium_price">Prix de l'Abonnement Premium (€)</label>
        <input type="text" id="premium_price" name="premium_subscription_price" 
               value="<?php echo htmlspecialchars($settings['premium_subscription_price']['setting_value'] ?? '9.99'); ?>">
        <small><?php echo htmlspecialchars($settings['premium_subscription_price']['setting_description'] ?? ''); ?></small>
    </div>

    <div class="form-actions">
        <button type="submit" class="admin-button-primary">Enregistrer les Modifications</button>
    </div>
</form>

<?php
if (isset($conn)) $conn->close();
require_once '../includes/admin_footer.php';
?>