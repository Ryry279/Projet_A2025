<?php
// profile.php
$page_title = "Mon Profil";
$page_description = "Gérez les informations de votre compte Find Your Course.";
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    $_SESSION['info_message'] = "Vous devez être connecté pour accéder à votre profil.";
    redirect(getBaseUrl() . '/login.php?redirect=' . urlencode(getBaseUrl() . '/profile.php'));
    exit;
}

$user_id = $_SESSION['user_id'];
$update_message = '';
$update_success = false;

// ... (toute la logique de traitement des formulaires de mise à jour reste identique) ...

// Récupérer les données utilisateur à jour pour l'affichage
$stmt_user = $conn->prepare("SELECT username, email, first_name, last_name, registration_date, role FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_result = $stmt_user->get_result();
$user_data = $user_result->fetch_assoc();
$stmt_user->close();

require_once 'includes/header.php'; // Contient le $csrf_token
?>

<div class="container">
    <section class="page-header" style="text-align:center; margin-bottom:30px;">
        <h1>Mon Profil</h1>
        <p class="lead">Bonjour, <?php echo htmlspecialchars($user_data['username']); ?> ! Gérez vos informations ici.</p>
    </section>

    <div style="display: flex; flex-wrap: wrap; gap: 30px;">
        <section class="profile-info-display" style="flex: 1; min-width: 280px; background-color: #f9f9f9; padding: 25px; border-radius: 10px;">
            <h3>Vos Informations</h3>
            <p><strong>Nom d'utilisateur :</strong> <?php echo htmlspecialchars($user_data['username']); ?></p>
            <p><strong>Email :</strong> <?php echo htmlspecialchars($user_data['email']); ?></p>
            <p><strong>Type de compte :</strong> <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $user_data['role']))); ?></p>
            
            <hr style="margin: 20px 0;">
            <h4>Abonnement Premium</h4>
            <?php if ($user_data['role'] === 'premium_student'): ?>
                <p>Statut : <span style="color:green; font-weight:bold;">Actif</span></p>
                <p>Vous avez accès à tout notre contenu exclusif.</p>
                <button id="show-unsubscribe-modal-btn" class="button" style="background-color: #dc3545; box-shadow:none;">Se désabonner</button>
            <?php elseif ($user_data['role'] === 'student'): ?>
                <p>Statut : Inactif</p>
                <a href="<?php echo getBaseUrl(); ?>/subscribe_premium.php" class="button button-secondary">Passer à Premium</a>
            <?php else: // Admin ?>
                 <p>Statut : <span style="color:blue; font-weight:bold;">Administrateur</span> (accès complet)</p>
            <?php endif; ?>
             </section>

        <section class="profile-update-forms" style="flex: 2; min-width: 300px;">
            </section>
    </div>
</div>


<div id="unsubscribe-modal" class="modal-backdrop" style="display: none;">
    <div class="modal-content">
        <button id="unsubscribe-modal-close-btn" class="modal-close-btn">&times;</button>
        <div id="unsubscribe-modal-body">
            <h3 style="text-align:center; margin-top:0;">Confirmer la résiliation</h3>
            <p>Êtes-vous sûr de vouloir résilier votre abonnement Premium ? Vous perdrez l'accès à toutes les formations et contenus exclusifs.</p>
            <p>Cette action est immédiate et irréversible.</p>
            <div style="display:flex; justify-content:flex-end; gap:15px; margin-top:25px;">
                <button id="unsubscribe-cancel-btn" class="button button-secondary">Annuler</button>
                <button id="unsubscribe-confirm-btn" class="button" style="background-color:#c62828;">Oui, je confirme</button>
            </div>
            <p id="unsubscribe-error-msg" style="color:red; text-align:center; margin-top:10px; display:none;"></p>
        </div>
    </div>
</div>

<style>
    .modal-backdrop { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.6); z-index: 2000; display: flex; justify-content: center; align-items: center; }
    .modal-content { background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); max-width: 500px; width: 90%; position: relative; }
    .modal-close-btn { position: absolute; top: 10px; right: 15px; background: none; border: none; font-size: 2.5rem; color: #aaa; cursor: pointer; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const showModalBtn = document.getElementById('show-unsubscribe-modal-btn');
    const modal = document.getElementById('unsubscribe-modal');
    const closeBtn = document.getElementById('unsubscribe-modal-close-btn');
    const cancelBtn = document.getElementById('unsubscribe-cancel-btn');
    const confirmBtn = document.getElementById('unsubscribe-confirm-btn');
    const errorMsg = document.getElementById('unsubscribe-error-msg');

    if (showModalBtn) {
        showModalBtn.addEventListener('click', () => {
            modal.style.display = 'flex';
        });
    }

    function closeModal() {
        modal.style.display = 'none';
        errorMsg.style.display = 'none';
        confirmBtn.disabled = false;
        confirmBtn.textContent = 'Oui, je confirme';
    }

    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    if (modal) modal.addEventListener('click', e => { if(e.target === modal) closeModal(); });

    if (confirmBtn) {
        confirmBtn.addEventListener('click', () => {
            confirmBtn.disabled = true;
            confirmBtn.textContent = 'Traitement...';
            errorMsg.style.display = 'none';

            // Récupérer le jeton CSRF du formulaire de la page
            const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';

            fetch('unsubscribe.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ csrf_token: csrfToken })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Action réussie, on recharge la page pour voir le changement de statut
                    window.location.reload();
                } else {
                    // Afficher l'erreur et réactiver le bouton
                    errorMsg.textContent = data.message || 'Une erreur est survenue.';
                    errorMsg.style.display = 'block';
                    confirmBtn.disabled = false;
                    confirmBtn.textContent = 'Oui, je confirme';
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                errorMsg.textContent = 'Erreur de connexion avec le serveur.';
                errorMsg.style.display = 'block';
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Oui, je confirme';
            });
        });
    }
});
</script>

<?php
if (isset($conn)) {
    $conn->close();
}
require_once 'includes/footer.php';
?>