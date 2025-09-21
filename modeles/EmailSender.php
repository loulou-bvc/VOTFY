<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
require '../vendor/autoload.php'; // Assurez-vous que Composer est configuré et que vous avez ajouté la dépendance PHPMailer
header('Content-Type: text/html; charset=utf-8');
class EmailSender {
    public static function sendPasswordResetEmail($to, $reset_link) {
        try {
            $mail = new PHPMailer(true);
    
            try {
                $mail->isSMTP(); // Utiliser le protocole SMTP
                // Activer le mode débogage
                $mail->SMTPDebug = 2; // Niveau de débogage (0 = aucun, 2 = détaillé)
                $mail->Debugoutput = 'error_log'; // Enregistrer les logs dans error_log
                $mail->Host = 'smtp.gmail.com'; // Serveur SMTP Gmail
                $mail->SMTPAuth = true;  // Activer l'authentification SMTP
                // Configuration SMTP
                $mail->Username = 'votify.com@gmail.com'; // Ton adresse Gmail
                $mail->Password = 'gzflwwuhkojiujed'; // Ton mot de passe d'application (remplace-le par un mot de passe d'application valide)
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // STARTTLS
                $mail->Port = 465; // Port utilisé par Gmail
            } catch (Exception $e) {
                error_log("Erreur de configuration SMTP : {$mail->ErrorInfo}");
                throw new Exception("Erreur de configuration SMTP : " . $e->getMessage());
            }
            try {
                // Configuration de l'expéditeur et du destinataire
                $mail->setFrom('votify.com@gmail.com', 'Votify');
                $mail->addAddress($to, 'Utilisateur');
            } catch (Exception $e) {
                error_log("Erreur dans la configuration des destinataires : {$mail->ErrorInfo}");
                throw new Exception("Erreur dans la configuration des destinataires : " . $e->getMessage());
            }
            try {
                // Contenu de l'email
                $mail->isHTML(true);
                $mail->Subject = 'Reinitialisation de votre mot de passe';
                $mail->Body    = "<p>Bonjour,</p>
                                  <p>Veuillez cliquer sur le lien suivant pour réinitialiser votre mot de passe :</p>
                                  <p><a href='$reset_link'>$reset_link</a></p>
                                  <p>Ce lien expirera dans 24 heures.</p>
                                  <p>Cordialement,</p>
                                  <p>L'équipe VOTIFY.</p>";
                $mail->AltBody = "Bonjour,\n\nVeuillez cliquer sur le lien suivant pour réinitialiser votre mot de passe :\n\n$reset_link\n\nCe lien expirera dans 24 heures.\n\nCordialement,\nL'équipe VOTIFY.";
            } catch (Exception $e) {
                error_log("Erreur dans le contenu de l'email : {$mail->ErrorInfo}");
                throw new Exception("Erreur dans le contenu de l'email : " . $e->getMessage());
            }
    
            try {
                // Envoyer l'email
                $mail->send();
                return true;
            } catch (Exception $e) {
                error_log("Erreur lors de l'envoi de l'email : {$mail->ErrorInfo}");
                throw new Exception("Erreur lors de l'envoi de l'email : " . $e->getMessage());
            }
    
        } catch (Exception $e) {
            // Journaliser l'erreur pour le débogage
            error_log("Erreur globale dans EmailSender : {$e->getMessage()}");
            return false;
        }
    }
    
}
