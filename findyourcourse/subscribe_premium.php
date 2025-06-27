<?php

$page_title = "Devenir Membre Premium";
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect(getBaseUrl() . '/login.php?redirect=' . urlencode(getBaseUrl() . '/subscribe_premium.php'));
    exit;
}

// Récupérer le prix depuis la base de données
$price_result = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'premium_subscription_price'");
$subscription_price = ($price_result && $price_result->num_rows > 0) ? $price_result->fetch_assoc()['setting_value'] : '9.99';

require_once 'includes/header.php';
?>

<div class="container" style="max-width: 550px;">
    <section class="payment-form-section">
        <h1 style="text-align:center;">Passer à Premium</h1>
        <p class="lead" style="text-align:center;">Accédez à tout notre contenu exclusif pour seulement <strong><?php echo htmlspecialchars($subscription_price); ?> €/mois</strong>.</p>
        
        <div class="fake-payment-form" style="background-color:#fff; padding:30px; border-radius:10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
            <form action="process_fake_payment.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                
                <div class="form-group">
                    <label for="card-holder">Nom sur la carte</label>
                    <input type="text" id="card-holder" placeholder="J. DUPONT" class="fake-input">
                </div>

                <div class="form-group">
                    <label for="card-number">Numéro de carte</label>
                    <input type="text" id="card-number" placeholder="4974 0000 0000 0000" class="fake-input">
                </div>
                
                <div style="display:flex; gap:15px;">
                    <div class="form-group" style="flex:1;">
                        <label for="expiry-date">Date d'expiration</label>
                        <input type="text" id="expiry-date" placeholder="MM / AA" class="fake-input">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label for="cvc">CVC</label>
                        <input type="text" id="cvc" placeholder="123" class="fake-input">
                    </div>
                </div>
                
                <div class="form-actions" style="margin-top:20px;">
                    <button type="submit" class="button" style="width:100%; font-size:1.1em; padding:12px;">
                        Payer <?php echo htmlspecialchars($subscription_price); ?> € 
                    </button>
                </div>
                <p style="font-size:0.8em; text-align:center; color:#888; margin-top:15px;">
                    
                </p>
            </form>
        </div>
    </section>
</div>
<style>.fake-input { pointer-events: none; background-color: #f9f9f9; opacity: 0.7; }</style>
<?php
if(isset($conn)) $conn->close();
require_once 'includes/footer.php';
?>