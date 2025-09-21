<?php
require_once __DIR__ . '/../api/Connexion.php';
include_once '../modeles/NotificationController.php';

session_start();
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['token']) || !isset($_GET['action'])) {
    die("Lien invalide.");
}

$token = $_GET['token'];
$action = $_GET['action']; // accept ou decline

try {
    $pdo = Connexion::pdo();

    // Vérifier si le token est valide
    $sqlCheckToken = "SELECT * FROM Invitation WHERE token_invitation = :token AND etat = 'envoyee'";
    $stmtCheckToken = $pdo->prepare($sqlCheckToken);
    $stmtCheckToken->bindParam(':token', $token, PDO::PARAM_STR);
    $stmtCheckToken->execute();
    $invitation = $stmtCheckToken->fetch(PDO::FETCH_ASSOC);

    if (!$invitation) {
        error_log("Token invalide ou expiré : $token", 3, __DIR__ . '/logs/error.log');
        die("Token invalide ou expiré.");
    }

    // Traiter l'action
    if ($action === 'accept') {
        try {
            // Récupérer l'ID de l'utilisateur invité
            $sqlGetUserId = "SELECT id_utilisateur FROM Utilisateur WHERE email = :email";
            $stmtGetUserId = $pdo->prepare($sqlGetUserId);
            $stmtGetUserId->bindParam(':email', $invitation['email_invite'], PDO::PARAM_STR);
            $stmtGetUserId->execute();
            $user = $stmtGetUserId->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                error_log("Utilisateur introuvable pour l'email : " . $invitation['email_invite'], 3, __DIR__ . '/../logs/error.log');
                die("Utilisateur introuvable.");
            }

            // Ajouter l'utilisateur au groupe
            $sqlAddToGroup = "INSERT INTO Membre_Groupe (id_groupe, id_utilisateur, id_role) VALUES (:idGroupe, :idUtilisateur, 2)";
            $stmtAddToGroup = $pdo->prepare($sqlAddToGroup);
            $stmtAddToGroup->bindParam(':idGroupe', $invitation['id_groupe'], PDO::PARAM_INT);
            $stmtAddToGroup->bindParam(':idUtilisateur', $user['id_utilisateur'], PDO::PARAM_INT);
            $stmtAddToGroup->execute();

            // Mettre à jour l'état de l'invitation
            $sqlUpdateInvitation = "UPDATE Invitation SET etat = 'acceptee' WHERE token_invitation = :token";
            $stmtUpdateInvitation = $pdo->prepare($sqlUpdateInvitation);
            $stmtUpdateInvitation->bindParam(':token', $token, PDO::PARAM_STR);
            $stmtUpdateInvitation->execute();

            NotificationController::create($user['id_utilisateur'], "Vous avez été ajouté au groupe.");
            $success_message = "Invitation acceptée. Vous êtes maintenant membre du groupe.";
            echo "<a href='accueil.php'><button>Accéder au site</button></a>";
        } catch (PDOException $e) {
            error_log("Erreur lors de l'acceptation : " . $e->getMessage(), 3, __DIR__ . '/logs/error.log');
            die("Une erreur est survenue lors de l'acceptation de l'invitation.");
        }
    } elseif ($action === 'decline') {
        try {
            // Mettre à jour l'état de l'invitation
            $sqlUpdateInvitation = "UPDATE Invitation SET etat = 'refusee' WHERE token_invitation = :token";
            $stmtUpdateInvitation = $pdo->prepare($sqlUpdateInvitation);
            $stmtUpdateInvitation->bindParam(':token', $token, PDO::PARAM_STR);
            $stmtUpdateInvitation->execute();

            $error_message = "<p>Invitation refusée.</p>";
            echo "<a href='index.php'><button>Accéder au site</button></a>";
            NotificationController::create($user['id_utilisateur'], "Votre invitation au groupe  a été refusée.");
        } catch (PDOException $e) {
            error_log("Erreur lors du refus : " . $e->getMessage(), 3, __DIR__ . '/logs/error.log');
            die("Une erreur est survenue lors du refus de l'invitation.");
        }
    } else {
        error_log("Action non reconnue : $action", 3, __DIR__ . '/logs/error.log');
        die("Action non reconnue.");
    }
} catch (PDOException $e) {
    error_log("Erreur SQL générale : " . $e->getMessage(), 3, __DIR__ . '/logs/error.log');
    die("Une erreur est survenue. Veuillez réessayer plus tard.");
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
