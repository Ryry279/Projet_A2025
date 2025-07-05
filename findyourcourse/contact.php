<?php
// contact.php
$page_title = "Contactez-Nous";
$page_description = "Contactez l'équipe de Find Your Course ou les responsables du projet pour toute question ou information.";
require_once 'includes/header.php'; // Contains functions.php for CSRF token

$form_message = '';
$form_success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $form_message = "Erreur de sécurité (jeton CSRF invalide). Veuillez réessayer.";
    } else {
        // Sanitize inputs
        $name = isset($_POST['name']) ? sanitizeInput($_POST['name']) : '';
        $email = isset($_POST['email']) ? filter_var(sanitizeInput($_POST['email']), FILTER_VALIDATE_EMAIL) : '';
        $subject = isset($_POST['subject']) ? sanitizeInput($_POST['subject']) : '';
        $message_body = isset($_POST['message']) ? sanitizeInput($_POST['message']) : '';

        // Basic validation
        if (empty($name) || empty($email) || empty($subject) || empty($message_body)) {
            $form_message = "Tous les champs sont requis.";
        } elseif (!$email) {
            $form_message = "L'adresse e-mail n'est pas valide.";
        } else {
            // Process the form (e.g., send an email)
            // This is a placeholder for actual email sending logic
            $to = "contact@findyourcourse.example.com"; // Replace with actual recipient
            $headers = "From: " . $name . " <" . $email . ">\r\n";
            $headers .= "Reply-To: " . $email . "\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $email_subject = "Contact Form: " . $subject;
            $email_body = "Vous avez reçu un nouveau message depuis le formulaire de contact de Find Your Course:\n\n";
            $email_body .= "Nom: " . $name . "\n";
            $email_body .= "Email: " . $email . "\n";
            $email_body .= "Sujet: " . $subject . "\n";
            $email_body .= "Message:\n" . $message_body . "\n";

            // In a real application, use a robust mail library (PHPMailer, SwiftMailer)
            // For now, we'll just simulate success for demonstration
            // if (mail($to, $email_subject, $email_body, $headers)) {
            //     $form_success = true;
            //     $form_message = "Merci pour votre message ! Nous vous répondrons dès que possible.";
            // } else {
            //     $form_message = "Désolé, une erreur est survenue lors de l'envoi de votre message. Veuillez réessayer plus tard.";
            // }

            // Simulate success for now:
            $form_success = true;
            $form_message = "Merci pour votre message ! Nous vous répondrons dès que possible. (Simulation d'envoi réussi)";

            // Clear POST data to prevent re-submission on refresh if successful
            if ($form_success) {
                $_POST = []; // Or redirect to a thank you page
            }
        }
    }
    // Regenerate CSRF token after processing
    unset($_SESSION['csrf_token']); // Remove old token
    $csrf_token = generateCsrfToken(); // Generate a new one for the form if it's re-displayed
}
?>

<div class="container">
    <section class="contact-header" style="text-align:center; margin-bottom:40px;">
        <h1>Contactez-Nous</h1>
        <p class="lead">Nous sommes là pour répondre à vos questions. N'hésitez pas à nous écrire.</p>
    </section>

    <div style="display: flex; flex-wrap: wrap; gap: 30px;">
        <section class="contact-form-section" style="flex: 2; min-width: 300px;">
            <h3>Envoyez-nous un Message</h3>

            <?php if ($form_message): ?>
                <p
                    style="padding: 10px; border-radius: 5px; background-color: <?php echo $form_success ? '#e6fffa' : '#ffebee'; ?>; color: <?php echo $form_success ? '#00695c' : '#c62828'; ?>; border: 1px solid <?php echo $form_success ? '#b2dfdb' : '#ef9a9a'; ?>;">
                    <?php echo htmlspecialchars($form_message); ?>
                </p>
            <?php endif; ?>

            <?php if (!$form_success): // Only show form if not successfully submitted or on initial load ?>
                <form action="contact.php" method="POST" class="contact-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <div class="form-group">
                        <label for="contact-name">Votre Nom :</label>
                        <input type="text" id="contact-name" name="name" required
                            value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="contact-email">Votre Email :</label>
                        <input type="email" id="contact-email" name="email" required
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="contact-subject">Sujet :</label>
                        <input type="text" id="contact-subject" name="subject" required
                            value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="contact-message">Votre Message :</label>
                        <textarea id="contact-message" name="message" rows="6"
                            required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="button">Envoyer le Message</button>
                    </div>
                </form>
            <?php endif; ?>
        </section>

        <aside class="contact-info-section"
            style="flex: 1; min-width: 250px; background-color: #f9f9f9; padding: 25px; border-radius: 10px;">
            <h3>Informations de Contact</h3>
            <p><strong>Pour toute question sur le projet annuel ESGI :</strong></p>
            <p>
                <strong>Frédéric Sananes</strong><br>
                Directeur pédagogique 1er Cycle<br>
                <a href="mailto:fsananes@esgi.fr">fsananes@esgi.fr</a>
            </p>
            <p>
                <strong>Danielle Fallot</strong><br>
                Consultante IT (Référente Projet)<br>
                <a href="mailto:dfallot@myges.fr">dfallot@myges.fr</a>
            </p>
            <hr style="margin: 20px 0;">
            <p><strong>Pour des questions générales sur Find Your Course :</strong></p>
            <p>
                Email : <a href="mailto:eme.findyourcourse@gmail.com">eme.findyourcourse@gmail.com</a><br>
                (Cette adresse est un exemple, à remplacer par une adresse réelle pour la plateforme)
            </p>
        </aside>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>