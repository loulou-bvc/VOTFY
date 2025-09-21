<?php
session_start();

if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['id_utilisateur'];
$apiBaseUrl = 'https://webdev.iut-orsay.fr/~nboulad/VOTFY/api/utilisateurs'; // Remplacez par l'URL correcte de votre API

function callApi($method, $endpoint, $data = null) {
    global $apiBaseUrl;

    $url = $apiBaseUrl . $endpoint;
    $options = [
        'http' => [
            'method' => $method,
            'header' => "Content-Type: application/json\r\nAccept: application/json",
        ]
    ];

    if ($data) {
        $options['http']['content'] = json_encode($data);
    }

    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        $error = error_get_last();
        return ['status' => 'error', 'message' => $error['message']];
    }

    return json_decode($response, true);
}

// Récupération des données utilisateur via l'API
$user = callApi('GET', "/{$userId}");
if ($user['status'] !== 'success' || empty($user['data'])) {
    die("Utilisateur non trouvé.");
}
$user = $user['data'];

// Traitement de la soumission du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updatedData = [
        'nom' => $_POST['nom'] ?? '',
        'prenom' => $_POST['prenom'] ?? '',
        'email' => $_POST['email'] ?? '',
        'adresse_postale' => $_POST['adresse_postale'] ?? ''
    ];

    $response = callApi('PUT', "/{$userId}", $updatedData);

    if ($response['status'] === 'success') {
        header('Location: monCompte.php'); // Rediriger après mise à jour
        exit;
    } else {
        $errorMessage = $response['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mettre à jour mes informations - Votify</title>

    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/updateinfo.css">
    <link rel="stylesheet" href="../css/footer.css">
    <link rel="stylesheet" href="../css/header.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<header class="header-container">
    <a href="javascript:history.back()" class="back-arrow">
        <i class="bi bi-chevron-left"></i>
    </a>
    <h1 class="header-title">Mettre à jour mes informations</h1>
</header>
<div class="body-container content">
    <div class="info-container">
        <h2>Informations du Compte</h2>
        <form action="" method="post">
            <?php if (isset($errorMessage)): ?>
                <div class="alert alert-danger">Erreur : <?= htmlspecialchars($errorMessage) ?></div>
            <?php endif; ?>
            <ul class="user-info">
                <li><strong>Nom </strong><input type="text" id="nom" name="nom" value="<?= htmlspecialchars($user['nom']) ?>" required></li>
                <li><strong>Prénom </strong><input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($user['prenom']) ?>" required></li>
                <li><strong>Email </strong><input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required></li>
                <li><strong>Adresse Postale </strong><input type="text" id="adresse_postale" name="adresse_postale" value="<?= htmlspecialchars($user['adresse_postale']) ?>" required></li>
            </ul>
            <button type="submit" class="btn-valider-info" style="width: 100%;font-size: 1.8em;">Valider mes nouvelles informations</button>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
