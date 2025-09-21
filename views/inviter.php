<?php
require_once __DIR__ . '/../api/Connexion.php';
require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$logFile = __DIR__ . '/logs/debug.log';
$errorLogFile = __DIR__ . '/logs/error.log';
file_put_contents($logFile, "Accès à la page inviter.php\n", FILE_APPEND);

if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: login.php');
    exit;
}

$idGroupe = (int)$_GET['id_groupe'];
$userId = $_SESSION['id_utilisateur'];
$message = "";

function sendInvitationEmail($to, $invitationLinkAccept, $invitationLinkDecline) {
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'votify.com@gmail.com';
        $mail->Password = 'gzflwwuhkojiujed';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        $mail->setFrom('votify.com@gmail.com', 'Votify');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = 'Invitation à rejoindre un groupe';
        $mail->Body = "<p>Bonjour,</p>
                        <p>Vous avez été invité à rejoindre un groupe. Cliquez sur l'un des liens ci-dessous :</p>
                        <p><a href='$invitationLinkAccept'>Accepter l'invitation</a></p>
                        <p><a href='$invitationLinkDecline'>Refuser l'invitation</a></p>
                        <p>Ce lien expirera dans 24 heures.</p>
                        <p>Cordialement,<br>L'équipe VOTIFY.</p>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailInvite = filter_input(INPUT_POST, 'email_invite', FILTER_VALIDATE_EMAIL);

    if ($emailInvite) {
        try {
            $pdo = Connexion::pdo();

            // Vérifier si une invitation existe déjà pour cet email et ce groupe
            $sqlCheckInvitation = "SELECT COUNT(*) FROM Invitation WHERE id_groupe = :idGroupe AND email_invite = :email AND etat = 'envoyee'";
            $stmtCheckInvitation = $pdo->prepare($sqlCheckInvitation);
            $stmtCheckInvitation->bindParam(':idGroupe', $idGroupe, PDO::PARAM_INT);
            $stmtCheckInvitation->bindParam(':email', $emailInvite, PDO::PARAM_STR);
            $stmtCheckInvitation->execute();
            $invitationExists = $stmtCheckInvitation->fetchColumn();

            if ($invitationExists) {
                $message = "Une invitation a déjà été envoyée à cet utilisateur.";
                error_log("Invitation déjà envoyée pour l'email : $emailInvite dans le groupe $idGroupe\n", 3, $errorLogFile);
            } else {
                // Générer un token unique
                $token = bin2hex(random_bytes(32));

                // Ajouter l'invitation dans la base de données
                $sqlInsertInvitation = "INSERT INTO Invitation (id_groupe, email_invite, token_invitation, date_envoi, etat) VALUES (:idGroupe, :email, :token, NOW(), 'envoyee')";
                $stmtInsertInvitation = $pdo->prepare($sqlInsertInvitation);
                $stmtInsertInvitation->bindParam(':idGroupe', $idGroupe, PDO::PARAM_INT);
                $stmtInsertInvitation->bindParam(':email', $emailInvite, PDO::PARAM_STR);
                $stmtInsertInvitation->bindParam(':token', $token, PDO::PARAM_STR);
                $stmtInsertInvitation->execute();

                // Envoyer l'email d'invitation
                $invitationLinkAccept = "https://webdev.iut-orsay.fr/~nboulad/VOTFY/views/accept_invitation.php?token=$token&action=accept";
                $invitationLinkDecline = "https://projets.iut-orsay.fr/~nboulad/VOTFY/views/accept_invitation.php?token=$token&action=decline";
                if (sendInvitationEmail($emailInvite, $invitationLinkAccept, $invitationLinkDecline)) {
                    $message = "Invitation envoyée avec succès !";
                } else {
                    $message = "Erreur lors de l'envoi de l'email.";
                    error_log("Erreur lors de l'envoi de l'invitation à $emailInvite\n", 3, $errorLogFile);
                }
            }
        } catch (PDOException $e) {
            file_put_contents($errorLogFile, "Erreur SQL : " . $e->getMessage() . "\n", FILE_APPEND);
            $message = "Une erreur est survenue lors de l'invitation.";
        }
    } else {
        $message = "Adresse email invalide.";
        error_log("Adresse email invalide : $emailInvite\n", 3, $errorLogFile);
    }
}
$nomGroupe = $_GET['nom'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inviter - Votify</title>

    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/inviter.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<header class="header-container">
    <a href="javascript:history.back()" class="back-arrow">
        <i class="bi bi-chevron-left"></i>
    </a>
    <h1 class="header-title">Inviter Un Membre</h1>
</header>

<div class="body-container content">
    <div class="info-container">
        <h2 class="text-center">Inviter un membre dans le groupe <strong> <?= htmlspecialchars($nomGroupe)  ?> </strong></h2>
        <form action="" method="POST" class="mt-4">
            <div class="mb-3">
                <label for="email_invite" class="form-label">Adresse email de l'utilisateur :</label>
                <input type="email" class="form-control" id="email_invite" name="email_invite" required>
            </div>
            <button type="submit" id="btn-primary">Envoyer l'invitation</button>
        </form>
        <?php if ($message): ?>
            <div class="alert alert-info mt-3" role="alert">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>

