<?php
require_once __DIR__ . '/../api/Connexion.php';

// Classe pour interagir avec l'API
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
            file_put_contents(__DIR__ . '/logs/error.log', "Erreur API : " . print_r($error, true) . "\n", FILE_APPEND);
            return ['status' => 'error', 'message' => 'Erreur lors de l\'appel API'];
        }

        $decodedResponse = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            file_put_contents(__DIR__ . '/logs/error.log', "Erreur JSON : " . json_last_error_msg() . " | Réponse brute : $response\n", FILE_APPEND);
            return ['status' => 'error', 'message' => 'Réponse JSON invalide', 'response' => $response];
        }

        return $decodedResponse;
    }
}

// Fonction pour se déconnecter via l'API
function logout() {
    session_start();
    session_unset();
    session_destroy();
    return ['status' => 'success', 'message' => 'Déconnexion réussie'];
}

// Fonction pour récupérer les informations utilisateur
function getUserInfo($userId) {
    $response = ApiClient::callApi("utilisateurs/$userId", 'GET');
    file_put_contents(__DIR__ . '/logs/debug.log', "Réponse API pour utilisateur $userId : " . json_encode($response) . "\n", FILE_APPEND);

    if ($response['status'] === 'success') {
        return $response['data'];
    }
    return null;
}

// Fonction pour supprimer un compte utilisateur
function deleteAccount($userId) {
    $response = ApiClient::callApi("utilisateurs/$userId", 'DELETE');
    return $response;
}

// Vérifie si l'utilisateur est connecté
session_start();
$userId = $_SESSION['id_utilisateur'] ?? null;

if (!$userId) {
    header('Location: login.php');
    exit;
}

// Charger les informations utilisateur
$user = getUserInfo($userId);

if (!$user) {
    echo '<script>alert("Erreur : Impossible de charger les informations utilisateur.");</script>';
    $user = [
        'nom' => '',
        'prenom' => '',
        'email' => '',
        'adresse_postale' => ''
    ];
}

// Gérer la déconnexion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    $response = logout();
    if ($response['status'] === 'success') {
        header('Location: login.php');
        exit;
    } else {
        echo '<script>alert("Erreur lors de la déconnexion.");</script>';
    }
}

// Gérer la suppression de compte
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $response = deleteAccount($userId);

    if ($response['status'] === 'success') {
        session_unset();
        session_destroy();
        header('Location: goodbye.php');
        exit;
    } else {
        echo '<script>alert("Erreur : ' . htmlspecialchars($response['message']) . '");</script>';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Compte - Votify</title>

    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/compte.css">
    <link rel="stylesheet" href="../css/footer.css">
    <link rel="stylesheet" href="../css/header.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<?php include_once 'includes/header.php'; ?>
<header class="header-container">
    <a href="javascript:history.back()" class="back-arrow">
        <i class="bi bi-chevron-left"></i>
    </a>
    <h1 class="header-title">Mon Compte</h1>
</header>
<div class="body-container content">
    <!-- Affichage des informations utilisateur -->
    <div class="info-container">
        <h2>Informations du Compte</h2>
        <ul class="user-info">
            <li><strong>Nom :</strong> <?= htmlspecialchars($user['nom']) ?></li>
            <li><strong>Prénom :</strong> <?= htmlspecialchars($user['prenom']) ?></li>
            <li><strong>Email :</strong> <?= htmlspecialchars($user['email']) ?></li>
            <li><strong>Adresse Postale :</strong> <?= htmlspecialchars($user['adresse_postale']) ?></li>
        </ul>
    </div>

    <!-- Boutons d'action -->
    <div class="action-buttons">
        <form action="update_info.php" method="get">
            <button type="submit" class="btn-update">Mettre à jour mes informations</button>
        </form>
        <form action="password_reset.php" method="get">
            <button type="submit" class="btn-update">Mettre à jour le mot de passe</button>
        </form>
        <form action="" method="post">
                <button type="submit" name="logout" class="btn-logout" id="btn-logout">Déconnexion</button>
        </form>
        <form action="" method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer votre compte ? Cette action est irréversible.');">
            <button type="submit" name="delete" class="btn-delete" id="btn-delete">Supprimer mon compte</button>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
<?php include_once 'includes/footer.php'; ?>
</body>
</html>
