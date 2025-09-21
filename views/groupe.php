<?php
// Inclure les fichiers nécessaires
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$logFile = __DIR__ . '/logs/debug.log'; // Fichier de log
file_put_contents($logFile, "Accès à la page groupe.php\n", FILE_APPEND);

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['id_utilisateur'])) {
    file_put_contents($logFile, "Utilisateur non connecté, redirection vers login.php\n", FILE_APPEND);
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id_groupe']) || !filter_var($_GET['id_groupe'], FILTER_VALIDATE_INT)) {
    file_put_contents($logFile, "ID de groupe manquant, redirection vers mesgroupes.php\n", FILE_APPEND);
    header('Location: mesgroupes.php');
    exit;
}

$idGroupe = (int)$_GET['id_groupe'];
$userId = $_SESSION['id_utilisateur'];
$error_message = "";

// Fonction pour appeler l'API
function callApi($endpoint, $method = 'GET', $data = null) {
    global $logFile;
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
        file_put_contents($logFile, "Erreur API : " . print_r($error, true) . PHP_EOL, FILE_APPEND);
        return ['status' => 'error', 'message' => 'Erreur lors de l\'appel API'];
    }

    file_put_contents($logFile, "Réponse API ($endpoint) : $response\n", FILE_APPEND);
    return json_decode($response, true);
}

// Récupérer les détails du groupe via l'API
$result = callApi("groupes/$idGroupe");
if (!isset($result['id_groupe'])) {
    file_put_contents($logFile, "Erreur API : Groupe introuvable pour ID $idGroupe\n", FILE_APPEND);
    header('Location: mesgroupes.php');
    exit;
}
$groupe = $result;

// Quitter le groupe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leave'])) {
    file_put_contents($logFile, "leaveGroup() appelé avec userId=$userId, idGroupe=$idGroupe\n", FILE_APPEND);
    $leaveResult = callApi("groupes/leave/$idGroupe", 'DELETE', ['user_id' => $userId]);
    if ($leaveResult['status'] === 'success') {
        file_put_contents($logFile, "Sortie du groupe $idGroupe\n", FILE_APPEND);
        header('Location: mesgroupes.php');
        exit;
    } else {
        $error_message = $leaveResult['message'];
        file_put_contents($logFile, "Erreur lors de la sortie du groupe : {$leaveResult['message']}\n", FILE_APPEND);
    }
}
function luminance($hexColor) {
    // Convertir la couleur HEX en RGB
    $hexColor = str_replace('#', '', $hexColor);
    $r = hexdec(substr($hexColor, 0, 2));
    $g = hexdec(substr($hexColor, 2, 2));
    $b = hexdec(substr($hexColor, 4, 2));

    // Calculer la luminance relative
    return (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255;
}

// Affichage de l'option "Signalement" dans la navbar pour l'admin
$isAdmin = ($userId === $groupe['id_admin']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Groupe - <?= htmlspecialchars($groupe['nom']) ?></title>
    <link rel="stylesheet" href="../css/footer.css">
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/groupe.css">
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<?php include_once 'includes/header.php'; ?>
<div class="body-c">
    <div class="main-content">
        <header class="header-container" style="background: linear-gradient(to right,<?= htmlspecialchars($groupe['couleur']) ?>,rgba(255, 255, 255, 0.8));color: <?= (luminance($groupe['couleur']) > 0.5) ? '#000000' : '#FFFFFF' ?>;">
            <a href="javascript:history.back()" class="back-arrow">
                <i class="bi bi-chevron-left"></i>
            </a>
            <h1 class="header-title">Groupe : <?= htmlspecialchars($groupe['nom']) ?></h1>
        </header>
        <nav class="header-container header-container2" style="margin-bottom: 0; background: linear-gradient(to right,<?= htmlspecialchars($groupe['couleur']) ?>,rgba(255, 255, 255, 0.8));color: <?= (luminance($groupe['couleur']) > 0.5) ? '#000000' : '#FFFFFF' ?>;">
            <div class="nav-list">
                <a href="listeMembres.php?id_groupe=<?= $idGroupe ?>" class="nav-link"><i class="bi bi-people-fill"></i> Liste des membres</a>
                <a href="propositions.php?id_groupe=<?= $idGroupe ?>" class="nav-link"><i class="bi bi-card-list"></i> Propositions</a>
                <a href="chat.php?id_groupe=<?= $idGroupe ?>" class="nav-link"><i class="bi bi-chat"></i> Chat</a>
                <a href="historique.php?id_groupe=<?= $idGroupe ?>" class="nav-link"><i class="bi bi-clock-history"></i> Historique</a>
                <a href="inviter.php?id_groupe=<?= $idGroupe ?>&nom=<?= urlencode($groupe['nom']) ?>" class="nav-link"><i class="bi bi-person-plus-fill"></i> Inviter</a>
                <?php if ($isAdmin): ?>
                    <a href="signalements.php?id_groupe=<?= $idGroupe ?>" class="nav-link"><i class="bi bi-exclamation-triangle"></i> Signalements</a>
                <?php endif; ?>
            </div>
        </nav>
        <main style="background : linear-gradient(rgba(255, 255, 255, 0.83),rgba(255, 255, 255, 0.83)),url('<?= htmlspecialchars($groupe['image_url'] ?? "../uploads/default.png") ?>');background-repeat: no-repeat;background-size: cover;background-position: center; /* Optionnel : pour des bords arrondis */z-index: 0;: url('<?= htmlspecialchars($groupe['image_url'] ?? '../uploads/default.png') ?>');background-repeat: no-repeat;background-size: cover;background-position: center;min-height: 400px; /* Assurez-vous que le contenu ait une hauteur minimale */border-radius: 0; /* Facultatif : coins arrondis */padding: 20px; /* Facultatif : espace intérieur */">
            <div class="group-details">
                <p style="font-size: 1.6em;font-weight: 630;margin-top: 65px;"><?= htmlspecialchars($groupe['description']) ?></p>
            </div>
            <div class="btn-container">
                <a href="nouvelleProposition.php?id_groupe=<?= $idGroupe ?>" class="btn-nouvelle-prop">➕ Nouvelle Proposition</a>
            </div>
            <div class="btn-container1" style="justify-content: space-evenly;display: flex;align-items: center;">
                <div class="btn-container">
                    <a href="modifierGroupe.php?id_groupe=<?= $idGroupe ?>" style="font-size: 1.1em;" class="btn-nouvelle-prop">Modifier le Groupe</a>
                </div>
                <div class="btn-container">
                    <form method="post" action="">
                        <input type="hidden" name="leave" value="1">
                        <button type="submit" style="font-size: 1.1em;background: red;color: white;" class="btn-nouvelle-prop">
                            Quitter le Groupe
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>
<script>
    function toggleNavbar() {
        const navbar = document.querySelector('.side-navbar');
        navbar.classList.toggle('active');
    }
</script>
</body>
<?php include_once 'includes/footer.html'; ?>
</html>
