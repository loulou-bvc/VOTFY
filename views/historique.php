<?php
// Inclure les fichiers nécessaires
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Classe pour gérer les appels à l'API
class ApiClient {
    public static function callApi($endpoint, $method = 'GET', $data = null) {
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
            error_log("Erreur API : " . print_r($error, true));
            return ['status' => 'error', 'message' => 'Erreur lors de l\'appel API'];
        }
    
        file_put_contents(__DIR__ . '/logs/api_response.log', $response . "\n", FILE_APPEND);
    
        $decodedResponse = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'status' => 'error',
                'message' => 'Réponse JSON invalide : ' . json_last_error_msg(),
                'raw_response' => $response
            ];
        }
    
        return $decodedResponse;
    }
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

// Récupérer les propositions fermées via l'API
if ($groupId) {
    $response = ApiClient::callApi("propositions/closed/$groupId", 'GET');
    if ($response['status'] === 'success') {
        $propositions = $response['propositions'];
    } else {
        $error_message = $response['message'];
    }
} else {
    $error_message = "ID de groupe manquant.";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique des Propositions</title>
    <link rel="stylesheet" href="../css/propositions.css">
    <link rel="stylesheet" href="../css/footer.css">
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/global.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<?php include_once 'includes/header.html'; ?>
<header class="header-container">
    <a href="javascript:history.back()" class="back-arrow">
        <i class="bi bi-chevron-left"></i>
    </a>
    <h1 class="header-title">Historique des Propositions</h1>
</header>
<main>
    <div class="groups-grid">
        <?php if (!empty($propositions)): ?>
            <?php foreach ($propositions as $proposition): ?>
                <div class="cardg" style="max-width: none;max-height: none;">
                    <h3 id="title">
                        <?= htmlspecialchars($proposition['titre']) ?>
                    </h3>
                    <div class="cardg-container">
                        <div class="cardg-text">
                            <p id="text">Durée : <?= htmlspecialchars($proposition['duree'] ?? '24H') ?></p>
                            <p id="text">Date : <?= htmlspecialchars($proposition['date_proposition'] ?? 'Non renseignée') ?></p>
                            <p id="text">Description : <?= htmlspecialchars($proposition['description'] ?? 'Pas de description') ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p><?= htmlspecialchars($error_message ?: "Aucune proposition fermée disponible.") ?></p>
        <?php endif; ?>
    </div>
</main>
</body>
<?php include_once 'includes/footer.html'; ?>
</html>
