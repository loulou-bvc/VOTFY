<?php
session_start();

if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['id_utilisateur'];
$groupId = isset($_GET['id_groupe']) ? (int)$_GET['id_groupe'] : null;
$requiredLevel = 2; // Niveau requis
$logFile = __DIR__ . '/logs/debug.log';

if (!$groupId) {
    header('Location: voterProposition.php');
    exit;
}

class ApiClient {
    private $baseUrl;

    public function __construct($baseUrl) {
        $this->baseUrl = $baseUrl;
    }

    public function call($endpoint, $method = 'GET', $data = null) {
        $url = $this->baseUrl . $endpoint;
        $options = [
            'http' => [
                'header' => "Content-Type: application/json\r\n",
                'method' => $method,
            ],
        ];

        if ($data) {
            $options['http']['content'] = json_encode($data);
        }

        file_put_contents(__DIR__ . '/logs/debug.log', "Appel API ($method) : $url\nDonnées : " . json_encode($data) . "\n", FILE_APPEND);
        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);

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

        file_put_contents(__DIR__ . '/logs/debug.log', "Réponse API ($method) : " . json_encode($decodedResponse) . "\n", FILE_APPEND);
        return $decodedResponse;
    }
}

$apiClient = new ApiClient('https://webdev.iut-orsay.fr/~nboulad/VOTFY/api/');

// Récupérer les données du groupe via l'API
$result = $apiClient->call("groupes/$groupId", 'GET');

if (isset($result['id_groupe'])) {
    $groupe = [
        'id_groupe' => $result['id_groupe'],
        'nom' => $result['nom'] ?? 'Nom non défini',
        'description' => $result['description'] ?? 'Pas de description disponible',
        'image_url' => $result['image_url'] ?? '../uploads/default.png',
        'couleur' => $result['couleur'] !== '0' ? $result['couleur'] : '#000000',
        'theme' => $result['theme'] !== '0' ? $result['theme'] : 'Thème non défini',
    ];
    file_put_contents($logFile, "Données du groupe récupérées : " . json_encode($groupe) . "\n", FILE_APPEND);
} else {
    $errorMessage = 'Erreur : données du groupe introuvables ou non conformes.';
    file_put_contents($logFile, "Réponse invalide ou non conforme : " . json_encode($result) . "\n", FILE_APPEND);
    echo "<script>alert('" . htmlspecialchars($errorMessage) . "'); window.location.href = 'mesgroupes.php';</script>";
    exit;
}

// Traitement du formulaire soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'];
    $description = $_POST['description'];
    $couleur = $_POST['couleur'];
    $theme = $_POST['theme'];

    $data = [
        'nom' => $nom,
        'description' => $description,
        'couleur' => $couleur,
        'theme' => $theme,
        'user_id' => $userId,
        'required_level' => $requiredLevel,
    ];

    $updateResult = $apiClient->call("groupes/change/$groupId", 'PUT', $data);

    if (isset($updateResult['status']) && $updateResult['status'] === 'success') {
        echo "<script>
                alert('Le groupe a été mis à jour avec succès !');
                window.location.href = 'groupe.php?id_groupe={$groupId}';
              </script>";
        exit;
    } else {
        $errorMessage = isset($updateResult['message']) ? $updateResult['message'] : 'Une erreur inconnue s\'est produite.';
        file_put_contents(__DIR__ . '/logs/error.log', "Erreur lors de la mise à jour du groupe : $errorMessage\n", FILE_APPEND);
        echo "<script>alert('Erreur lors de la mise à jour : " . htmlspecialchars($errorMessage) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Groupe</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/modifierProposition.css">
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/footer.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<?php include_once 'includes/header.php'; ?>
<body>
<header class="header-container">
    <a href="javascript:history.back()" class="back-arrow">
        <i class="bi bi-chevron-left"></i>
    </a>
    <h1 class="header-title">Modifier un Groupe</h1>
</header>
<div class="body-container">
    <div class="form-container">
        <form action="" method="post">
            <div class="group-form">
                <label for="nom">Nom</label>
                <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($groupe['nom']) ?>" required>
            </div>
            <div class="group-form">
                <label for="description">Description</label>
                <textarea id="description" name="description" required><?= htmlspecialchars($groupe['description']) ?></textarea>
            </div>
            <div class="group-form">
                <label for="couleur">Couleur</label>
                <input type="color" id="couleur" name="couleur" value="<?= htmlspecialchars($groupe['couleur']) ?>" required>
            </div>
            <div class="group-form">
                <label for="theme">Thème du groupe :</label>
                <select id="theme" name="theme" required>
                    <option value="">Sélectionnez un thème</option>
                    <option value="Technologie" <?= $groupe['theme'] === 'Technologie' ? 'selected' : '' ?>>Technologie</option>
                    <option value="Arts" <?= $groupe['theme'] === 'Arts' ? 'selected' : '' ?>>Arts</option>
                    <option value="Sciences" <?= $groupe['theme'] === 'Sciences' ? 'selected' : '' ?>>Sciences</option>
                    <option value="Éducation" <?= $groupe['theme'] === 'Éducation' ? 'selected' : '' ?>>Éducation</option>
                    <option value="Sport" <?= $groupe['theme'] === 'Sport' ? 'selected' : '' ?>>Sport</option>
                    <option value="Musique" <?= $groupe['theme'] === 'Musique' ? 'selected' : '' ?>>Musique</option>
                    <option value="Voyage" <?= $groupe['theme'] === 'Voyage' ? 'selected' : '' ?>>Voyage</option>
                </select>
            </div>
            <button type="submit" class="btn-modifier-groupe">Valider les modifications</button>
        </form>
    </div>
</div>
</body>
<?php include_once 'includes/footer.php'; ?>
</html>
