<?php
// Inclure les fichiers nécessaires, comme la connexion à la base de données
include_once '../api/Connexion.php';

// Initialisation des variables
$error_message = "";
$success_message = "";

// Vérifier si le token est présent dans l'URL
if (!isset($_GET['token']) || empty($_GET['token'])) {
    $error_message = "Lien invalide ou expiré.";
} else {
    $token = $_GET['token'];

    // Connecter à la base de données
    Connexion::pdo();
    $pdo = Connexion::pdo();

    // Vérifier si le token est valide
    $query = "SELECT id_utilisateur FROM Utilisateur WHERE reset_token = :token AND reset_token_sent_at >= NOW() - INTERVAL 1 HOUR";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['token' => $token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $error_message = "Lien invalide ou expiré.";
    } else {
        $user_id = $user['id_utilisateur'];

        // Si le formulaire est soumis
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = trim($_POST['password']);
            $confirm_password = trim($_POST['confirm_password']);

            // Vérifier si les mots de passe correspondent
            if ($password === $confirm_password) {
                // Hachage du mot de passe
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Mettre à jour le mot de passe dans la base de données
                $update_query = "UPDATE Utilisateur SET mot_de_passe = :password, reset_token = NULL, reset_token_sent_at = NULL WHERE id_utilisateur = :id_utilisateur";
                $update_stmt = $pdo->prepare($update_query);
                $update_stmt->execute([
                    'password' => $hashed_password,
                    'id_utilisateur' => $user_id
                ]);

                $success_message = "Votre mot de passe a été mis à jour avec succès. Vous pouvez maintenant vous connecter.";
            } else {
                $error_message = "Les mots de passe ne correspondent pas.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation du mot de passe</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/login.css">
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
        <h1>Réinitialiser votre mot de passe</h1>

        <!-- Messages d'erreur ou de succès -->
        <?php if (!empty($error_message)) : ?>
            <div class="error">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($success_message)) : ?>
            <div class="success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <!-- Afficher le formulaire uniquement si le token est valide -->
        <?php if (empty($success_message) && empty($error_message)) : ?>
            <form action="" method="post">
                <label for="password">Nouveau mot de passe</label>
                <input type="password" id="password" name="password" placeholder="Mot de passe" required>

                <label for="confirm_password">Confirmer le mot de passe</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirmer le mot de passe" required>

                <button type="submit">Mettre à jour</button>
            </form>
        <?php endif; ?>

        <!-- Lien de retour -->
        <div class="forgot-link">
            <a href="login.php">Retour à la connexion</a>
        </div>
    </div>
</div>
</body>
</html>
