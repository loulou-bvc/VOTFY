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

// Classe pour appeler l'API
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

// Récupérer les groupes
$result = $apiClient->call("groupes/all/$userId", 'GET');
if (isset($result['status']) && $result['status'] === 'success') {
    $groupes = $result['groups'] ?? [];
} else {
    $groupes = [];
    $error_message = $result['message'] ?? 'Erreur inconnue lors de la récupération des groupes';
    file_put_contents($logFile, "Erreur lors de la récupération des groupes : $error_message\n", FILE_APPEND);
}

// Récupérer les propositions
$result = $apiClient->call("propositions/user/$userId", 'GET');
if (isset($result['status']) && $result['status'] === 'success') {
    $propositions = $result['propositions'] ?? [];
} else {
    $propositions = [];
    $error_message = $result['message'] ?? 'Erreur inconnue lors de la récupération des propositions';
    file_put_contents($logFile, "Erreur lors de la récupération des propositions : $error_message\n", FILE_APPEND);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - Votify</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/global.css"> <!-- CSS global -->
    <link rel="stylesheet" href="../css/accueil.css"> <!-- CSS spécifique à l'accueil -->
    <link rel="stylesheet" href="../css/footer.css">
    <link rel="stylesheet" href="../css/header.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<?php include_once 'includes/header.php'; ?>
<div class="body-container">
    <h1 id="bienvenue">Bienvenue dans VOTIFY !</h1>
    <!-- Logo et bienvenue -->
    <!-- Bouton flottant pour "Nouveau Groupe" -->
    <a href="nouveauGroupes.php" class="btn-nouveau-groupe" style="text-decoration: none"><button class="action-button" >➕ Nouveau Groupe</button></a>
    <!-- Bouton pour ouvrir la modale -->
    <i class="bi bi-info-circle" data-bs-toggle="modal" data-bs-target="#infoModal" style="display: flex;height: 50px;align-items: center;justify-content: center;font-size: 5.5em;margin: 60px;color: #fd7e8c;"></i>
    <!-- Modale Bootstrap -->
    <div class="modal fade" id="infoModal" tabindex="-1" aria-labelledby="infoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <!-- En-tête de la modale -->
                <div class="modal-header">
                    <h5 class="modal-title" id="infoModalLabel">Comment utiliser le site ?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- Corps de la modale -->
                <div class="modal-body">
                    <p>Bienvenue sur notre site ! Voici quelques conseils pour commencer :</p>
                    <ul>
                        <li>Naviguez à travers les menus pour accéder à différentes fonctionnalités.</li>
                        <li>Utilisez le bouton "<strong><a href="nouveauGroupes.php">Créer un Groupe</a></strong>" pour démarrer.</li>
                        <li>Consultez la liste des groupes pour voir vos propositions et pouvoir voter.</li>
                    </ul>
                    <p>Si vous avez des questions, n'hésitez pas à nous contacter via la section "Aide".</p>
                </div>
                <!-- Pied de la modale -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <section class="pt-5 pb-5">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <a class="nav-link" href="mesgroupes.php">
                        <h3 class="mb-3">Mes groupes</h3>
                    </a>
                </div>
            </div>
            <div class="position-relative">
                <!-- Flèche gauche -->

                <!-- Carrousel -->
                <div id="carouselGroups" class="carousel slide" data-bs-ride="carousel">
                    <a class="carousel-control-prev" href="#carouselGroups" role="button" data-bs-slide="prev" style="width: 5%; z-index: 1000;">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </a>
                    <div class="carousel-inner">
                        <?php
                        $activeClass = 'active';
                        foreach (array_chunk($groupes, 3) as $groupSet): ?>
                            <div class="carousel-item <?= $activeClass ?>">
                                <div class="row">
                                    <?php foreach ($groupSet as $groupe): ?>
                                        <div class="col-md-4 mb-3">
                                            <div class="card" style="box-shadow: 0px 2px 10px <?= htmlspecialchars($groupe['couleur'] ?? '#ccc') ?>;">
                                                <a href="groupe.php?id_groupe=<?= htmlspecialchars($groupe['id_groupe']) ?>"style="text-decoration: none; color: #1d3557; ">
                                                <div class="icon"><img src="<?= htmlspecialchars($groupe['image_url'] ?? '../uploads/default.png') ?>" alt="Image du groupe" class="group-image" style="height: 100px;border-radius: 50%;margin: 20px; width: 100px;border: 1mm double;opacity: 93% "></div>
                                                <p class="title"><?= htmlspecialchars($groupe['nom']) ?></p>
                                                <p class="text">Description : <?= htmlspecialchars($groupe['description'] ?? 'Pas de description') ?></p>
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php
                            $activeClass = '';
                        endforeach; ?>
                    </div>
                    <a class="carousel-control-next" href="#carouselGroups" role="button" data-bs-slide="next" style="width: 5%; z-index: 1000;">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </a>
                </div>
                <!-- Flèche droite -->

            </div>
        </div>
    </section>

    <section class="pt-5 pb-5">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <a class="nav-link" href="mespropositions.php">
                        <h3 class="mb-3">Mes Propositions</h3>
                    </a>
                </div>
            </div>
            <div class="position-relative">
                <!-- Flèche gauche -->

                <!-- Carrousel -->
                <div id="carouselGroups" class="carousel slide" data-bs-ride="carousel">
                    <a class="carousel-control-prev" href="#carouselGroups" role="button" data-bs-slide="prev" style="width: 5%; z-index: 1000;">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </a>
                    <div class="carousel-inner">
                        <?php
                        $activeClass = 'active';
                        foreach (array_chunk($propositions, 3) as $propositionSet): ?>
                            <div class="carousel-item <?= $activeClass ?>">
                                <div class="row">
                                    <?php foreach ($propositionSet as $proposition): ?>
                                        <div class="col-md-4 mb-3">
                                            <div class="card">
                                                <a href="voterProposition.php?id_proposition=<?= htmlspecialchars($proposition['id_proposition']) ?>"style="text-decoration: none; color: #1d3557; ">
                                                <div class="icon"><i class="bi bi-eye-fill"></i></div>
                                                <p class="title">Titre : <?= htmlspecialchars($proposition['titre']) ?></p>
                                                <p class="text">Description : <?= htmlspecialchars($proposition['description'] ?? 'Pas de description') ?></p>
                                                <p class="text">Date : <?= htmlspecialchars($proposition['date_proposition'] ?? 'Non renseignée') ?></p>
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php
                            $activeClass = '';
                        endforeach; ?>
                    </div>
                    <a class="carousel-control-next" href="#carouselGroups" role="button" data-bs-slide="next" style="width: 5%; z-index: 1000;">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </a>
                </div>
                <!-- Flèche droite -->

            </div>
        </div>
    </section>
</div>
<?php include_once 'includes/footer.php'; ?>
</body>
</html>
