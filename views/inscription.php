<?php
require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Initialisation des variables pour les messages d'erreur et de succès
$error_message = "";
$success_message = "";

// Fonction pour appeler une API
function callApi($endpoint, $method = 'POST', $data = null) {
    $url = "https://webdev.iut-orsay.fr/~nboulad/VOTFY/api/$endpoint";

    $options = [
        'http' => [
            'header' => "Content-Type: application/json\r\n",
            'method' => $method,
        ],
    ];

    if ($data) {
        $options['http']['content'] = json_encode($data);
    }

    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        $error = error_get_last();
        return ['status' => 'error', 'message' => 'Erreur API : ' . $error['message']];
    }

    return json_decode($response, true);
}

// Fonction pour envoyer l'email de vérification
function sendVerificationEmail($to, $verificationLink) {
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'votify.com@gmail.com'; // Remplacez par votre email
        $mail->Password = 'gzflwwuhkojiujed'; // Remplacez par votre mot de passe d'application
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        $mail->setFrom('votify.com@gmail.com', 'Votify');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = 'Vérification de votre email';
        $mail->Body = "<p>Bonjour,</p>
                        <p>Veuillez cliquer sur le lien suivant pour vérifier votre email :</p>
                        <p><a href='$verificationLink'>$verificationLink</a></p>
                        <p>Ce lien expirera dans 24 heures.</p>
                        <p>Cordialement,<br>L'équipe VOTIFY.</p>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erreur lors de l'envoi de l'email : {$mail->ErrorInfo}");
        return false;
    }
}

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier la réponse du reCAPTCHA
    $captchaResponse = $_POST['g-recaptcha-response'] ?? '';
    $secretKey = '6Lf1Ra4qAAAAAIOaAtdRp6jX4AED73NFgygh5kH_';
    $verifyURL = "https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$captchaResponse";

    $response = file_get_contents($verifyURL);
    $responseKeys = json_decode($response, true);

    if (!$responseKeys['success']) {
        echo "<script>alert('Le CAPTCHA est invalide. Veuillez réessayer.'); window.location.href = window.location.href;</script>";
        exit;
    }

    // Récupérer les données du formulaire et les nettoyer
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $adresse_postale = trim($_POST['adresse_postale']);

    // Vérifier si tous les champs sont remplis
    if (!empty($nom) && !empty($prenom) && !empty($email) && !empty($password) && !empty($confirm_password) && !empty($adresse_postale)) {
        // Vérifier si les mots de passe correspondent
        if ($password === $confirm_password) {
            // Préparer les données pour l'API
            $data = [
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email,
                'mot_de_passe' => $password,
                'adresse_postale' => $adresse_postale
            ];

            // Appel de l'API pour créer l'utilisateur
            $result = callApi('utilisateurs/create', 'POST', $data);

            if ($result['status'] === 'success') {
                $verificationToken = $result['verificationToken'];
                $verificationLink = "https://webdev.iut-orsay.fr/~nboulad/VOTFY/views/verifier.php?token=$verificationToken";

                if (sendVerificationEmail($email, $verificationLink)) {
                    $success_message = "Utilisateur créé avec succès. Un email de vérification a été envoyé.";
                } else {
                    $error_message = "Utilisateur créé mais l'envoi de l'email a échoué.";
                }
            } else {
                $error_message = $result['message'];
            }
        } else {
            $error_message = "Les mots de passe ne correspondent pas.";
        }
    } else {
        $error_message = "Tous les champs sont requis.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Votify</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/login.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
<div class="body-container">
    <a href="accueil.php">
        <img src="../images/logo_votify.png" alt="Logo Votify" class="logo">
    </a>

    <div class="login-container">
        <h1>Inscription</h1>

        <?php if (!empty($error_message)) : ?>
            <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <?php if (!empty($success_message)) : ?>
            <div class="success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <form action="" method="post">
            <label for="nom">Nom</label>
            <input type="text" id="nom" name="nom" placeholder="Nom" required>

            <label for="prenom">Prénom</label>
            <input type="text" id="prenom" name="prenom" placeholder="Prénom" required>

            <label for="email">Adresse e-mail</label>
            <input type="email" id="email" name="email" placeholder="e-mail" required>

            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" placeholder="Mot de passe" required>

            <label for="confirm_password">Confirmer le mot de passe</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirmer le mot de passe" required>

            <label for="adresse_postale">Adresse postale</label>
            <input type="text" id="adresse_postale" name="adresse_postale" placeholder="Adresse postale" required>

            <div class="g-recaptcha" data-sitekey="6Lf1Ra4qAAAAAFszltc7iObwHdvO6PvFDrtuHI72" style="display: flex;align-items: center;justify-content: center;margin: 40px;"></div>
            
            <button type="submit">S'inscrire</button>
        </form>
    </div>
</div>
</body>
</html>
