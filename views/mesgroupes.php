<?php
// Inclure les fichiers nécessaires
include_once '../api/Connexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$logFile = __DIR__ . '/logs/debug.log'; // Fichier de log

if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['id_utilisateur'];

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

// Récupérer les groupes via l'API
$apiClient = new callApi();
$result = $apiClient->callApi("groupes/all/$userId", 'GET');

// Gérer la réponse de l'API
if ($result['status'] === 'success') {
    $groupes = $result['groups'];
} else {
    $groupes = [];
    $error_message = $result['message'] ?? 'Erreur inconnue lors de la récupération des groupes.';
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Groupes</title>
    <link rel="stylesheet" href="../css/mesgroupes.css">
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
    <h1 class="header-title">Mes Groupes</h1>
</header>
<main>
    <div class="btn-container">
        <a href="nouveauGroupes.php" class="btn-nouveau-groupe">➕ Nouveau Groupe</a>
    </div>
    <div class="groups-grid">
        <?php if (!empty($groupes)): ?>
            <?php foreach ($groupes as $groupe): ?>
                <div class="cardg" style="box-shadow: 2px 4px 35px <?= htmlspecialchars($groupe['couleur']) ?>;">
                    <a href="groupe.php?id_groupe=<?= htmlspecialchars($groupe['id_groupe']) ?>"style="text-decoration: none; color: #1d3557; ">
                        <h3><?= htmlspecialchars($groupe['nom']) ?></h3>
                        <img src="<?= htmlspecialchars($groupe['image_url'] ?? '../uploads/default.png') ?>" alt="Image du groupe" class="group-image" style="height: 220px;border-radius: 50%;margin: 20px; width: 220px;border: 1mm double <?= htmlspecialchars($groupe['couleur'] ?? '#ccc') ?>;opacity: 93% ">
                        <p style="margin-top: 30px;font-weight: 500;font-size: 1.2em;color: #1d3557;"><?= htmlspecialchars($groupe['description'] ?? 'Pas de description') ?></p>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Aucun groupe disponible.</p>
        <?php endif; ?>
    </div>
</main>
</body>
<?php include_once 'includes/footer.php'; ?>
</html>
