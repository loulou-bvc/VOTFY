<?php
// Classe callApi intégrée dans le script
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

        if ($response === false) {
            $error = error_get_last();
            error_log("Erreur API : " . print_r($error, true));
            return ['status' => 'error', 'message' => 'Erreur lors de l\'appel API'];
        }

        $decodedResponse = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['status' => 'error', 'message' => 'Réponse JSON invalide'];
        }

        return $decodedResponse;
    }
}

// Démarrage de session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifiez si l'utilisateur est connecté
$userId = $_SESSION['id_utilisateur'] ?? null;
$idGroupe = $_GET['id_groupe'] ?? null;

// Variables pour les messages
$error_message = "";
$success_message = "";

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $duree = trim($_POST['duree'] ?? '');
    $budget = trim($_POST['budget'] ?? '');

    // Vérification des données
    if (!$userId) {
        $error_message = "Utilisateur non connecté.";
    } elseif (!$idGroupe) {
        $error_message = "Aucun groupe associé à cet utilisateur.";
    } elseif (empty($titre) || empty($description) || empty($duree) || empty($budget)) {
        $error_message = "Veuillez remplir tous les champs obligatoires.";
    } else {
        // Données pour l'API
        $postData = [
            'user_id' => $userId,
            'group_id' => $idGroupe,
            'required_level' => 3, // Niveau requis pour créer une proposition
            'nom' => $titre,
            'description' => $description,
            'duree' => $duree,
            'budget' => $budget,
        ];

        // Appel de l'API
        $apiClient = new callApi();
        $response = $apiClient->callApi('propositions', 'POST', $postData);

        // Gestion de la réponse
        if (isset($response['status']) && $response['status'] === 'success') {
            $success_message = $response['message'] ?? "Proposition créée avec succès.";
        } else {
            $error_message = $response['message'] ?? "Erreur inconnue lors de l'appel à l'API.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer une Proposition - Votify</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/nouvelleProposition.css">
    <link rel="stylesheet" href="../css/accueil.css"> <!-- CSS spécifique à l'accueil -->
    <link rel="stylesheet" href="../css/footer.css">
    <link rel="stylesheet" href="../css/header.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- JavaScript de Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/preview-image.js"></script>
</head>
<body>
<header class="header-container">
    <a href="javascript:history.back()" class="back-arrow">
        <i class="bi bi-chevron-left"></i>
    </a>
    <h1 class="header-title">Créer une nouvelle proposition</h1>
</header>
<div class="body-container">
    <a href="accueil.php">
        <img src="../images/logo_votify.png" alt="Logo Votify" class="logo">
    </a>
    <div class="form-container">
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>
        <form action="" method="post" enctype="multipart/form-data">
            <div class="group-form">
                <label for="nom">Titre</label>
                <input type="text" id="nom" name="nom" placeholder="Entrez le titre de la proposition" required>
            </div>
            <div class="group-form">
                <label for="description">Description</label>
                <textarea id="description" name="description" placeholder="Entrez la description de la proposition" required></textarea>
            </div>
            <div class="group-form">
                <label for="duree">Durée (Heure)</label>
                <input type="number" id="duree" name="duree" placeholder="Entrez la durée en heures" required>
            </div>
            <div class="group-form">
                <label for="budget">Budget (€)</label>
                <input type="number" id="budget" name="budget" placeholder="Entrez le budget en euros" required>
            </div>
            <button type="submit" class="btn-creer-groupe">Créer une proposition</button>
        </form>
    </div>
</div>
<script src="../js/popup.js"></script>
</body>
<?php include_once 'includes/footer.html'; ?>
</html>
