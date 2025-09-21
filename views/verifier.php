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
    $pdo = Connexion::pdo();

    try {
        // Vérifier si le token est valide
        $query = "SELECT id_utilisateur FROM Utilisateur WHERE verification_token = :token AND verification_expiry >= NOW()";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':token' => $token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $error_message = "Lien invalide ou expiré.";
        } else {
            $user_id = $user['id_utilisateur'];

            // Mettre à jour l'état de vérification
            $updateQuery = "UPDATE Utilisateur SET email_verified = 1, verification_token = NULL, verification_expiry = NULL WHERE id_utilisateur = :id";
            $updateStmt = $pdo->prepare($updateQuery);
            $updateStmt->execute([':id' => $user_id]);

            $success_message = "Votre email a été validé avec succès. Vous pouvez maintenant vous connecter.";
        }
    } catch (PDOException $e) {
        $error_message = "Erreur lors de la vérification : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validation de l'email</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/verifier.css">
</head>
<body>
<!-- Conteneur principal -->
<div class="body-container">
    <div class="login-container">
        <!-- Messages d'erreur ou de succès -->
        <?php if (!empty($error_message)) : ?>
            <div class="error">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($success_message)) : ?>
            <div class="success text-verifier">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
            <div class="success-image">
                <img src="../images/valider.png" alt="Succès" style="max-width: 200px; margin: 20px auto; display: block;">
            </div>
        <?php endif; ?>

        <!-- Lien de retour -->
        <div class="forgot-link">
            <a href="login.php">Retour à la connexion</a>
        </div>
    </div>
</div>
</body>
</html>
