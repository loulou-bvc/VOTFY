<?php
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

        // Log des options pour le débogage
        file_put_contents(__DIR__ . '/logs/debug.log', "Appel API URL : $url\nMéthode : $method\nDonnées : " . json_encode($data) . "\n", FILE_APPEND);

        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            $error = error_get_last();
            file_put_contents(__DIR__ . '/logs/error.log', "Erreur API : " . print_r($error, true) . "\n", FILE_APPEND);
            return ['status' => 'error', 'message' => 'Erreur lors de l\'appel API'];
        }

        file_put_contents(__DIR__ . '/logs/debug.log', "Réponse brute API : $response\n", FILE_APPEND);

        $decodedResponse = json_decode($response, true);
        /*if (json_last_error() !== JSON_ERROR_NONE) {
            file_put_contents(__DIR__ . '/logs/error.log', "Erreur JSON : " . json_last_error_msg() . "\n", FILE_APPEND);
            return ['status' => 'error', 'message' => 'Réponse JSON invalide'];
        }*/

        return $decodedResponse;
    }
}


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['id_utilisateur'];
$idProposition = isset($_GET['id_proposition']) ? (int)$_GET['id_proposition'] : null;
$groupId = isset($_GET['id_groupe']) ? (int)$_GET['id_groupe'] : null;
$requiredLevel = 2; // Niveau requis

if (!$idProposition) {
    header('Location: voterProposition.php');
    exit;
}

$apiClient = new callApi();

// Récupérer les données de la proposition via l'API
$response = $apiClient->callApi("propositions/$idProposition", 'GET');
if ($response['status'] === 'success') {
    $proposition = $response['proposition'];
} else {
    echo "<script>alert('Erreur lors de la récupération de la proposition : " . htmlspecialchars($response['message']) . "');</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = $_POST['nom'];
    $description = $_POST['description'];
    $duree = (int)$_POST['duree'];
    $budget = (float)$_POST['budget'];

    $data = [
        'titre' => $titre,
        'description' => $description,
        'duree' => $duree,
        'budget' => $budget,
        'user_id' => $userId,
        'group_id' => $groupId,
        'required_level' => $requiredLevel,
    ];
    
    file_put_contents(__DIR__ . '/logs/debug.log', "Données envoyées pour update : " . json_encode($data) . "\n", FILE_APPEND);
    

    $updateResponse = $apiClient->callApi("propositions/change/$idProposition", 'PUT', $data);

    if ($updateResponse['status'] === 'success') {
        echo "<script>
                alert('La proposition a été mise à jour avec succès !');
                window.location.href = 'propositions.php?id_groupe={$groupId}';
              </script>";
        exit;
    } else {
        echo "<script>alert('Erreur lors de la mise à jour : " . htmlspecialchars($updateResponse['message']) . "');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Proposition</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/modifierProposition.css">
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/footer.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<?php include_once 'includes/header.html'; ?>
<body>
<header class="header-container">
    <a href="javascript:history.back()" class="back-arrow">
        <i class="bi bi-chevron-left"></i>
    </a>
    <h1 class="header-title">Modifier une proposition</h1>
</header>
<div class="body-container">
    <div class="form-container">
        <form action="" method="post">
            <div class="group-form">
                <label for="nom">Titre</label>
                <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($proposition['titre']) ?>" required>
            </div>
            <div class="group-form">
                <label for="description">Description</label>
                <textarea id="description" name="description" required><?= htmlspecialchars($proposition['description']) ?></textarea>
            </div>
            <div class="group-form">
                <label for="duree">Durée (Heures)</label>
                <input type="number" id="duree" name="duree" value="<?= htmlspecialchars($proposition['duree']) ?>" required>
            </div>
            <div class="group-form">
                <label for="budget">Budget</label>
                <input type="number" id="budget" name="budget" value="<?= htmlspecialchars($proposition['budget']) ?>" required>
            </div>
            <button type="submit" class="btn-creer-groupe">Valider les modifications</button>
        </form>
    </div>
</div>
</body>
<?php include_once 'includes/footer.html'; ?>
</html>
