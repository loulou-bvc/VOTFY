<?php
// Initialisation des variables pour les messages d'erreur et de succès
$error_message = "";
$success_message = "";

// Inclure le fichier EmailSender
include_once '../modeles/EmailSender.php';
include_once '../api/Connexion.php';

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (!empty($email)) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Veuillez entrer une adresse e-mail valide.";
        } else {
            try {
                Connexion::pdo();
                $pdo = Connexion::pdo();

                $query = "SELECT * FROM Utilisateur WHERE email = :email";
                $stmt = $pdo->prepare($query);
                $stmt->execute(['email' => $email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    $reset_token = bin2hex(openssl_random_pseudo_bytes(16));
                    $reset_link = "https://webdev.iut-orsay.fr/~nboulad/VOTFY/views/update_password.php?token=$reset_token";

                    $update_query = "UPDATE Utilisateur SET reset_token = :reset_token, reset_token_sent_at = NOW() WHERE email = :email";
                    $update_stmt = $pdo->prepare($update_query);
                    $update_stmt->execute(['reset_token' => $reset_token, 'email' => $email]);

                    if (EmailSender::sendPasswordResetEmail($email, $reset_link)) {
                        $success_message = "Un lien de réinitialisation a été envoyé à votre adresse e-mail.";
                    } else {
                        $error_message = "Erreur lors de l'envoi de l'email.";
                    }
                } else {
                    $success_message = "Si cet e-mail existe dans notre système, un lien de réinitialisation vous a été envoyé.";
                }
            } catch (PDOException $e) {
                error_log($e->getMessage());
                $error_message = "Une erreur est survenue. Veuillez réessayer plus tard.";
            }
        }
    } else {
        $error_message = "Veuillez entrer une adresse e-mail.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation - Votify</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/login.css">
    <link rel="stylesheet" href="../css/popup.css">
    <script src="../js/popup.js"></script>
</head>
<body>
<div class="body-container">
    <a href="accueil.php.php">
        <img src="../images/logo_votify.png" alt="Logo Votify" class="logo">
    </a>
    <div class="login-container">
        <h1>Réinitialiser le mot de passe</h1>
        <p>Entrez votre adresse e-mail pour recevoir un lien de réinitialisation.</p>

        <!-- Pop-up pour confirmation -->
        <?php if (!empty($success_message)) : ?>
            <div id="popup" class="popup-container" style="display: flex;">
                <div class="popup-content">
                    <p id="popup-message"><?php echo htmlspecialchars($success_message); ?></p>
                    <a href="login.php"><button onclick="closePopup()">Fermer</button></a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Formulaire -->
        <form action="" method="post" onsubmit="return <?php echo !empty($success_message) ? 'false' : 'true'; ?>">
            <label for="email">Adresse e-mail</label>
            <input type="email" id="email" name="email" placeholder="e-mail" required>
            <button type="submit">Envoyer</button>
        </form>

        <!-- Liens supplémentaires -->
        <div class="forgot-link"><a href="login.php">Retour à la connexion</a></div>
        <div class="register-link"><a href="inscription.php">Pas encore de compte ? S'inscrire</a></div>
    </div>
</div>
</body>
</html>
