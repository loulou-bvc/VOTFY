<?php
// Classe callApi directement intégrée dans le script
class callApi {
    public function callApi($endpoint, $method = 'GET', $data = null) {
        $url = "https://webdev.iut-orsay.fr/~nboulad/VOTFY/api/$endpoint";
        $logFile = __DIR__ . '/logs/error.log'; // Fichier de log

        // Enregistrement des logs pour l'appel API
        file_put_contents($logFile, "Appel API : URL=$url, METHOD=$method" . PHP_EOL, FILE_APPEND);
        if ($data) {
            file_put_contents($logFile, "Données envoyées : " . json_encode($data) . PHP_EOL, FILE_APPEND);
        }

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
            file_put_contents($logFile, "Erreur API : " . print_r($error, true) . PHP_EOL, FILE_APPEND);
            return ['status' => 'error', 'message' => 'Erreur lors de l\'appel API'];
        }

        $decodedResponse = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            file_put_contents($logFile, "Erreur de décodage JSON : " . json_last_error_msg() . PHP_EOL, FILE_APPEND);
            return ['status' => 'error', 'message' => 'Réponse JSON invalide'];
        }

        file_put_contents($logFile, "Réponse API : " . json_encode($decodedResponse) . PHP_EOL, FILE_APPEND);
        return $decodedResponse;
    }
}

// Démarrage de session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['id_utilisateur'];
$groupId = isset($_GET['id_groupe']) ? (int)$_GET['id_groupe'] : null;

$propositions = [];
$error_message = "";
$success_message = "";

// API Client
$apiClient = new callApi();

// Récupérer les propositions via l'API
$response = $apiClient->callApi("propositions/group/$groupId", 'GET');
if ($response['status'] === 'success') {
    $propositions = $response['propositions'];
} else {
    $error_message = $response['message'] ?? "Erreur lors de la récupération des propositions.";
    file_put_contents(__DIR__ . '/logs/error.log', "Erreur lors de la récupération des propositions : $error_message" . PHP_EOL, FILE_APPEND);
}

// Vérifier et traiter la demande de clôture de proposition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['close_proposition_id'])) {
    $propositionId = (int)$_POST['close_proposition_id'];
    $data = [
        'user_id' => $userId,
        'group_id' => $groupId,
        'required_level' => 3, // Exemple de niveau requis pour fermer une proposition
    ];

    file_put_contents(__DIR__ . '/logs/error.log', "Tentative de clôture Proposition ID=$propositionId, Données=" . json_encode($data) . PHP_EOL, FILE_APPEND);
    $closeResponse = $apiClient->callApi("propositions/close/$propositionId", 'PUT', $data);

    if ($closeResponse['status'] === 'success') {
        $success_message = "Proposition clôturée avec succès.";
        header("Location: {$_SERVER['PHP_SELF']}?id_groupe=$groupId");
        exit;
    } else {
        $error_message = $closeResponse['message'] ?? "Erreur lors de la clôture de la proposition.";
        file_put_contents(__DIR__ . '/logs/error.log', "Erreur clôture : $error_message" . PHP_EOL, FILE_APPEND);
    }
}

// Traitement du formulaire de commentaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'], $_POST['id_proposition'])) {
    $comment = trim($_POST['comment']);
    $propositionId = (int)$_POST['id_proposition'];

    if (!empty($comment)) {
        $data = [
            'proposition_id' => $propositionId,
            'utilisateur_id' => $userId,
            'contenu' => $comment,
        ];

        file_put_contents(__DIR__ . '/logs/error.log', "Tentative d'ajout de commentaire Proposition ID=$propositionId, Données=" . json_encode($data) . PHP_EOL, FILE_APPEND);
        $commentResponse = $apiClient->callApi('commentaires', 'POST', $data);

        if ($commentResponse['status'] === 'success') {
            $success_message = "Commentaire ajouté avec succès.";
            header("Location: {$_SERVER['PHP_SELF']}?id_groupe=$groupId"); // Rafraîchit la page
            exit;
        } else {
            $error_message = $commentResponse['message'] ?? "Erreur lors de l'ajout du commentaire.";
            file_put_contents(__DIR__ . '/logs/error.log', "Erreur ajout commentaire : $error_message" . PHP_EOL, FILE_APPEND);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Propositions</title>

    <link rel="stylesheet" href="../css/footer.css">
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/propositions.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<?php include_once 'includes/header.php'; ?>
<header class="header-container">
    <a href="javascript:history.back()" class="back-arrow">
        <i class="bi bi-chevron-left"></i>
    </a>
    <h1 class="header-title">Propositions</h1>
</header>
<main>
    <div class="groups-grid">
        <?php if (!empty($propositions)): ?>
            <?php foreach ($propositions as $proposition): ?>
                <div class="cardg" style="max-width: none;max-height: none;">
                    <h3 id="title"><?= htmlspecialchars($proposition['titre']) ?></h3>
                    <div class="cardg-container">
                        <div class="cardg-text" style="gap: 25px;">
                            <p id="text">Durée : <?= htmlspecialchars($proposition['duree'] ?? '24H') ?></p>
                            <p id="text">Date : <?= htmlspecialchars($proposition['date_proposition'] ?? 'Non renseignée') ?></p>
                            <p id="text" style="font-size: 1.3em;">Description : <?= htmlspecialchars($proposition['description'] ?? 'Pas de description') ?></p>
                            <p id="text" style="font-size: 2.2em;font-weight: 400;margin-top: 30px;">Budget : <strong><?= htmlspecialchars($proposition['budget'] ?? 'Pas de budget') ?>€</strong></p>
                        </div>
                        <?php
                        $commentsResponse = $apiClient->callApi("commentaires/{$proposition['id_proposition']}", 'GET');
                        if ($commentsResponse['status'] === 'success' && !empty($commentsResponse['comments'])): ?>
                            <div id="comments">
                                <div class="comments-section">
                                    <h5>Commentaires :</h5>
                                    <?php foreach ($commentsResponse['comments'] as $comment): ?>
                                        <div class="comment">
                                            <p><strong>Utilisateur <?= htmlspecialchars($comment['id_utilisateur']) ?>:</strong> <?= htmlspecialchars($comment['texte']) ?></p>
                                            <p><small>Posté le <?= htmlspecialchars($comment['date_commentaire']) ?></small></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                        <?php else: ?>
                            <div id="comments">
                                <div class="comments-section">
                                    <p>Aucun commentaire pour cette proposition.</p>
                                </div>
                        <?php endif; ?>
                            <form method="POST" class="mt-3">
                                <input type="hidden" name="id_proposition" value="<?= $proposition['id_proposition'] ?>">
                                <textarea name="comment" class="form-control" placeholder="Ajouter un commentaire..." required></textarea>
                                <button type="submit" class="btn btn-primary btn-sm mt-2">Commenter</button>
                            </form>
                        </div>
                    </div>
                    <div class="cardg-container">
                        <div class="btn-container">
                            <a href="voterProposition.php?id_proposition=<?= $proposition['id_proposition'] ?>" class="btn-voter" style="font-size: 3.5em;padding: 25px 55px;">➕ Voter</a>
                        </div>
                        <div class="btn-container">
                            <a href="modifierProposition.php?id_proposition=<?= $proposition['id_proposition'] ?>&id_groupe=<?= $groupId ?>" class="btn-voter btn-containerr" style="font-size: 1em;padding: 15px 20px;">modifier la proposition</a>
                        </div>
                        <div class="btn-container">
                            <a href="signalerProposition.php?id_proposition=<?= $proposition['id_proposition'] ?>&id_groupe=<?= $groupId ?>" 
                               class="btn-voter btn-containerr" 
                               style="font-size: 1em;padding: 15px 20px;background:rgba(255, 0, 0, 0.69);color: white;">
                                Signaler la proposition
                            </a>
                        </div>
                        <div class="btn-container">
                            <form method="POST">
                                <input type="hidden" name="close_proposition_id" value="<?= $proposition['id_proposition'] ?>">
                                <button type="submit" class="btn-voter btn-containerr" style="font-size: 1em;padding: 15px 20px;background: #ff1515b0;color: white;">Cloturer la proposition</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Aucune proposition disponible.</p>
        <?php endif; ?>
    </div>
</main>
</body>
<?php include_once 'includes/footer.html'; ?>
</html>
