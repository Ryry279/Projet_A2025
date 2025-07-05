<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require '../sendemail/phpmailer/src/Exception.php';
require '../sendemail/phpmailer/src/PHPMailer.php';
require '../sendemail/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isAdmin()) {
    redirect(getBaseUrl() . '/admin/login.php');
    exit;
}

$mail_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$mail_id) {
    $_SESSION['message'] = "ID de mail invalide.";
    redirect('manage_mails.php?status=error');
    exit;
}

// Récupère le contenu du mail
$stmt = $conn->prepare("SELECT titre, objet, message FROM mail_gestion WHERE id = ?");
$stmt->bind_param("i", $mail_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $_SESSION['message'] = "Mail introuvable.";
    redirect('manage_mails.php?status=error');
    exit;
}

$mail_data = $result->fetch_assoc();

// Récupère tous les abonnés
$subscribers_result = $conn->query("SELECT email FROM newsletter_subscriptions");
if (!$subscribers_result || $subscribers_result->num_rows === 0) {
    $_SESSION['message'] = "Aucun abonné trouvé pour l’envoi.";
    redirect('manage_mails.php?status=error');
    exit;
}

// Envoi du mail à chaque abonné
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'eme.findyourcourse@gmail.com';
    $mail->Password = 'ProjetannuelMCSI';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;

    $mail->setFrom('eme.findyourcourse@gmail.com', 'Find Your Course');
    $mail->isHTML(true);
    $mail->Subject = $mail_data['objet'];
    $mail->Body = nl2br($mail_data['message']);

    $sent = 0;
    while ($subscriber = $subscribers_result->fetch_assoc()) {
        $mail->clearAddresses();
        $mail->addAddress($subscriber['email']);
        $mail->send();
        $sent++;
    }

    $_SESSION['message'] = "<p class='admin-success-message'>Newsletter envoyée à $sent abonnés.</p>";
    redirect('manage_mails.php');

} catch (Exception $e) {
    $_SESSION['message'] = "<p class='admin-error-message'>Erreur lors de l'envoi : {$mail->ErrorInfo}</p>";
    redirect('manage_mails.php?status=error');
}
?>