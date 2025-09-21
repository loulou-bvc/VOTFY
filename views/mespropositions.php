<?php
// Inclure les fichiers nécessaires
include_once '../api/Connexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$logFile = __DIR__ . '/logs/debug.log'; // Fichier de log
file_put_contents($logFile, "Démarrage de la session\n", FILE_APPEND);

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['id_utilisateur'])) {
    file_put_contents($logFile, "Utilisateur non connecté, redirection vers login.php\n", FILE_APPEND);
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['id_utilisateur'];
file_put_contents($logFile, "Session active, ID utilisateur : $userId\n", FILE_APPEND);

$propositions = [];
$error_message = "";

// Classe pour appeler l'API
class callApi {
    public function callApi($endpoint, $method = 'GET', $data = null) {
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

        file_put_contents(__DIR__ . '/logs/debug.log', "Réponse brute API ($endpoint) : $response\n", FILE_APPEND);

        if ($response === false) {
            $error = error_get_last();
            file_put_contents(__DIR__ . '/logs/error.log', "Erreur API : " . print_r($error, true) . "\n", FILE_APPEND);
            return ['status' => 'error', 'message' => 'Erreur lors de l\'appel API'];
        }

        $decodedResponse = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            file_put_contents(__DIR__ . '/logs/error.log', "Erreur JSON : " . json_last_error_msg() . " | Réponse brute : $response\n", FILE_APPEND);
            return ['status' => 'error', 'message' => 'Réponse JSON invalide'];
        }

        return $decodedResponse;
    }
}

// Récupérer les propositions via l'API
$apiClient = new callApi();
$result = $apiClient->callApi("propositions/user/$userId", 'GET');

if ($result['status'] === 'success') {
    $propositions = $result['propositions'];
} else {
    $error_message = $result['message'] ?? 'Erreur inconnue lors de la récupération des propositions.';
    file_put_contents($logFile, "Erreur lors de la récupération des propositions : $error_message\n", FILE_APPEND);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Propositions</title>
    <link rel="stylesheet" href="../css/mesPropositions.css">
    <link rel="stylesheet" href="../css/footer.css">
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/global.css"> <!-- CSS global -->
    <!-- CSS de Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- JavaScript de Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<?php include_once 'includes/header.php'; ?>
<header class="header-container">
    <a href="javascript:history.back()" class="back-arrow">
        <i class="bi bi-chevron-left"></i>
    </a>
    <h1 class="header-title">Mes Propositions</h1>
</header>
<main>
    <div class="groups-grid">
        <?php if (!empty($propositions)): ?>
            <?php foreach ($propositions as $proposition): ?>
                <div class="cardg">
                    <a href="voterProposition.php?id_proposition=<?= htmlspecialchars($proposition['id_proposition']) ?>"style="text-decoration: none; color: #1d3557; ">
                    <h3 id="title"><?= htmlspecialchars($proposition['titre']) ?></h3>
                    <div class="icon"><i class="bi bi-eye-fill"></i></div>
                    <p id="text">Durée : <?= htmlspecialchars($proposition['duree'] ?? '24H') ?></p>
                    <p id="text">Date : <?= htmlspecialchars($proposition['date_proposition'] ?? 'Non renseignée') ?></p>
                    <p id="text"><?= htmlspecialchars($proposition['description'] ?? 'Pas de description') ?></p>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Aucune proposition disponible.</p>
        <?php endif; ?>
    </div>
</main>
</body>
<?php include_once 'includes/footer.php'; ?>
</html>
