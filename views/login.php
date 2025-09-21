<?php
session_start();

// Inclure la connexion à la base de données
require_once __DIR__ . '/../api/Connexion.php';

// Initialisation d'une variable pour stocker les messages d'erreur
$error_message = "";

// Rediriger si l'utilisateur est déjà connecté
if (isset($_SESSION['id_utilisateur'])) {
    header('Location: accueil.php');
    exit;
}

// Chemin du fichier de log
$logFile = __DIR__ . '/logs/login.log';

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $captchaResponse = $_POST['g-recaptcha-response'] ?? '';
    $secretKey = '6Lf1Ra4qAAAAAIOaAtdRp6jX4AED73NFgygh5kH_';
    $verifyURL = "https://www.google.com/recaptcha/api/siteverify";

    // Requête cURL pour vérifier le CAPTCHA
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $verifyURL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['secret' => $secretKey, 'response' => $captchaResponse]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $captchaResult = curl_exec($ch);
    curl_close($ch);

    $responseKeys = json_decode($captchaResult, true);

    if (!$responseKeys['success']) {
        $error_message = "Le CAPTCHA est invalide. Veuillez réessayer.";
    } else {
        // Récupérer les données utilisateur
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $password = trim($_POST['password']);

        if (!empty($email) && !empty($password)) {
            $url = 'https://webdev.iut-orsay.fr/~nboulad/VOTFY/api/login';
            $data = ['email' => $email, 'mot_de_passe' => $password];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen(json_encode($data))
            ]);

            $result = curl_exec($ch);

            file_put_contents($logFile, "Réponse brute de l'API : " . $result . PHP_EOL, FILE_APPEND);


            // Log des erreurs cURL
            if (curl_errno($ch)) {
                $error_message = "Erreur cURL : " . curl_error($ch);
                file_put_contents($logFile, "Erreur cURL : " . curl_error($ch) . PHP_EOL, FILE_APPEND);
            } else {
                $response = json_decode($result, true);
                file_put_contents($logFile, "Réponse API brute : " . $result . PHP_EOL, FILE_APPEND);

                if ($response === null) {
                    $error_message = "Erreur lors du décodage JSON.";
                    file_put_contents($logFile, "Erreur JSON : " . json_last_error_msg() . PHP_EOL, FILE_APPEND);
                } elseif ($response['success']) {
                    if (!$response['user']['email_verified']) {
                        $error_message = "Votre compte n'est pas encore activé.";
                    } else {
                        $_SESSION['id_utilisateur'] = $response['user']['id_utilisateur'];
                        $_SESSION['user_name'] = $response['user']['prenom'];
                        header('Location: accueil.php');
                        exit;
                    }
                } else {
                    $error_message = $response['message'] ?? "Email ou mot de passe incorrect.";
                }
            }
            curl_close($ch);
        } else {
            $error_message = "Tous les champs sont requis.";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Votify</title>
    <link rel="stylesheet" href="../css/global.css"> <!-- Lien vers le CSS global -->
    <link rel="stylesheet" href="../css/login.css"> <!-- Lien vers le CSS spécifique -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
    <!-- Conteneur principal -->
    <div class="body-container">
        <!-- Logo en haut de la page -->
        <a href="accueil.php">
            <img src="../images/logo_votify.png" alt="Logo Votify" class="logo">
        </a>

        <div class="login-container">
            <!-- Titre de la page -->
            <h1>Connexion</h1>

            <!-- Affichage des messages d'erreur -->
            <?php if (!empty($error_message)) : ?>
                <div class="error">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Formulaire de connexion -->
            <form action="" method="post">
                <!-- Champ pour l'identifiant -->
                <label for="email">Identifiant</label>
                <input type="email" id="email" name="email" placeholder="e-mail" required>

                <!-- Champ pour le mot de passe -->
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" placeholder="Mot de passe" required>

                <!-- Case pour enregistrer les informations -->
                <div class="checkbox-container">
                    <input type="checkbox" id="remember-me">
                    <label for="remember-me" id="remember-me-text">Enregistrer mes informations de connexion</label>
                </div>
                <div class="g-recaptcha" data-sitekey="6Lf1Ra4qAAAAAFszltc7iObwHdvO6PvFDrtuHI72" style="display: flex;align-items: center;justify-content: center;margin: 40px;"></div>
                <!-- Bouton de soumission -->
                <button type="submit">Connexion</button>

            </form>

            <!-- Liens supplémentaires -->
            <div class="forgot-link">
                <a href="password_reset.php">Mot de passe oublié ?</a>
            </div>
            <div class="register-link">
                <a href="inscription.php">Pas encore de compte ? S'inscrire</a>
            </div>
        </div>
    </div>
</body>
</html>
