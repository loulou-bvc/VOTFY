<?php
header('Content-Type: text/html; charset=UTF-8'); // Forcer l'encodage UTF-8

// Inclusion de la classe CallApi
require_once '../modeles/callApi.php';

// Démarrage de session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialisation des logs
$logFile = './logs/debug.log';
if (!file_exists('./logs')) {
    mkdir('./logs', 0777, true); // Créer le dossier logs s'il n'existe pas
}
file_put_contents($logFile, "\xEF\xBB\xBF=== Début du script ===\n", FILE_APPEND); // Ajouter un BOM UTF-8

// Redirection si l'utilisateur n'est pas connecté
if (!isset($_SESSION['id_utilisateur'])) {
    file_put_contents($logFile, "Utilisateur non connecté, redirection vers login.php\n", FILE_APPEND);
    header('Location: login.php');
    exit;
}

// Initialisation des variables
$idGroupe = isset($_GET['id_groupe']) ? (int)$_GET['id_groupe'] : null;
$idUtilisateur = $_SESSION['id_utilisateur'];
$error_message = "";
$success_message = "";
$signalements = [];

// Supprimer un signalement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_signalement_id'])) {
    $signalementId = (int)$_POST['delete_signalement_id'];

    // Appel à l'API pour supprimer le signalement
    $response = CallApi::callApi("signalement/delete/$signalementId", 'DELETE');
    file_put_contents($logFile, "Réponse API suppression signalement $signalementId : " . print_r($response, true) . "\n", FILE_APPEND);

    if (isset($response['success']) && $response['success']) {
        $success_message = "Signalement supprimé avec succès.";
    } else {
        $error_message = $response['message'] ?? "Erreur lors de la suppression du signalement.";
    }
}

// Supprimer une proposition et ses signalements
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_proposition_id'])) {
    $propositionId = (int)$_POST['delete_proposition_id'];

    // Appel à l'API pour supprimer la proposition
    $response = CallApi::callApi("signalement/delete-proposition/$propositionId", 'DELETE');
    file_put_contents($logFile, "Réponse API suppression proposition $propositionId : " . print_r($response, true) . "\n", FILE_APPEND);

    if (isset($response['success']) && $response['success']) {
        $success_message = "Proposition et ses signalements supprimés avec succès.";
    } else {
        $error_message = $response['message'] ?? "Erreur lors de la suppression de la proposition.";
    }
}


// Logs des paramètres initiaux
file_put_contents($logFile, "ID Groupe : $idGroupe\nID Utilisateur : $idUtilisateur\n", FILE_APPEND);

// Récupérer les signalements pour le groupe
$response = CallApi::callApi("signalement/group/$idGroupe", 'GET');
file_put_contents($logFile, "Réponse API récupération signalements : " . print_r($response, true) . "\n", FILE_APPEND);

if (isset($response['signalements']) && is_array($response['signalements'])) {
    $signalements = $response['signalements'];
} else {
    $error_message = utf8_encode($response['message'] ?? "Aucun signalement trouvé pour le groupe $idGroupe.");
}

// Logs finaux avant rendu HTML
file_put_contents($logFile, "Signalements récupérés : " . print_r($signalements, true) . "\n", FILE_APPEND);
file_put_contents($logFile, "Message d'erreur : " . utf8_encode($error_message) . "\nMessage de succès : $success_message\n", FILE_APPEND);
?>



<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signalements</title>
    <link rel="stylesheet" href="../css/footer.css">
    <link rel="stylesheet" href="../css/listemembres.css">
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<?php include_once 'includes/header.php'; ?>
<header class="header-container">
    <a href="javascript:history.back()" class="back-arrow">
        <i class="bi bi-chevron-left"></i>
    </a>
    <h1 class="header-title">Signalements pour le groupe</h1>
</header>
<div class="container mt-5">
    <h1>Signalements pour le groupe</h1>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success" role="alert">
            <?= htmlspecialchars($success_message) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($signalements)): ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID Proposition</th>
                    <th>Titre</th>
                    <th>Description</th>
                    <th>Motif du Signalement</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($signalements as $signalement): ?>
                    <tr>
                        <td><?= htmlspecialchars($signalement['id_proposition']) ?></td>
                        <td><?= htmlspecialchars($signalement['titre']) ?></td>
                        <td><?= htmlspecialchars($signalement['description']) ?></td>
                        <td><?= htmlspecialchars($signalement['contenue']) ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="delete_signalement_id" value="<?= $signalement['id_signalement'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Supprimer le signalement</button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="delete_proposition_id" value="<?= $signalement['id_proposition'] ?>">
                                <button type="submit" class="btn btn-warning btn-sm">Supprimer la proposition</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Aucun signalement pour ce groupe.</p>
    <?php endif; ?>
</div>
</body>
<?php include_once 'includes/footer.php'; ?>
</html>
