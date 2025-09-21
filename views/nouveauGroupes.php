<?php
// ------- nouveauGroupes.php -------
session_start();

$error_message = "";
$success_message = "";

// Check if the user is logged in
$userId = $_SESSION['id_utilisateur'] ?? null;
if (!$userId) {
    $error_message = "Utilisateur non connecté.";
}

// Function to call the API
function callApi($endpoint, $method = 'GET', $data = null) {
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
        error_log("Erreur JSON : " . json_last_error_msg());
        return ['status' => 'error', 'message' => 'Réponse JSON invalide'];
    }

    return $decodedResponse;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize form data
    $nom = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $couleur = trim($_POST['couleur'] ?? '');
    $theme = trim($_POST['theme'] ?? '');

    // Handle image upload
    $imagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/';
        $imageName = basename($_FILES['image']['name']);
        $uniqueName = uniqid() . '_' . $imageName;
        $targetFile = $uploadDir . $uniqueName;

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
            $error_message = "Impossible de créer le répertoire de téléchargement.";
        } elseif (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            // Save the full URL
            $imagePath = "https://webdev.iut-orsay.fr/~nboulad/VOTFY/uploads/" . $uniqueName;
        } else {
            $error_message = "Échec du téléchargement de l'image.";
        }
    }

    if (!$error_message && !empty($nom) && !empty($description)) {
        $data = [
            'nom' => $nom,
            'description' => $description,
            'admin_id' => $userId,
            'couleur' => $couleur,
            'theme' => $theme,
            'image_url' => $imagePath,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $response = callApi('groupes/create', 'POST', $data);

        if ($response['status'] === 'success') {
            $success_message = "Groupe créé avec succès.";
        } else {
            $error_message = $response['message'] ?? "Erreur inconnue lors de la création du groupe.";
        }
    } elseif (empty($nom) || empty($description)) {
        $error_message = "Veuillez remplir tous les champs obligatoires.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Votify</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/nouveaugroupe.css">
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
    <a href="javascript:history.back()" class="back-arrow"><i class="bi bi-chevron-left"></i></a>
    <h1 class="header-title">Créer un nouveau groupe</h1>
</header>
<div class="body-container">
    <div class="form-container">
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        <form action="" method="post" enctype="multipart/form-data">
            <div class="group-form">
                <label for="nom">Nom</label>
                <input type="text" id="nom" name="nom" placeholder="Entrez le nom du groupe" required>
            </div>
            <div class="group-form">
                <label for="description">Description</label>
                <textarea id="description" name="description" placeholder="Entrez la description du groupe" required></textarea>
            </div>
            <div class="group-form">
                <label for="couleur">Couleur</label>
                <input type="color" id="couleur" name="couleur" value="#ffcad4" required>
            </div>
            <div class="group-form">
                <label for="theme">Thème du groupe :</label>
                <select id="theme" name="theme">
                    <option value="">Sélectionnez un thème</option>
                    <option value="Technologie">Technologie</option>
                    <option value="Arts">Arts</option>
                    <option value="Sciences">Sciences</option>
                    <option value="Éducation">Éducation</option>
                    <option value="Sport">Sport</option>
                    <option value="Musique">Musique</option>
                    <option value="Voyage">Voyage</option>
                </select>
            </div>
            <div class="group-form">
                <label for="image">Image du groupe :</label>
                <div class="upload-container">
                    <label for="image" class="image-upload-label">
                        <i class="bi bi-image" title="Importer une image"></i>
                    </label>
                    <input type="file" id="image" name="image" accept="image/*" onchange="previewImage(event)">
                </div>
                <div id="image-preview" class="image-preview"></div>
            </div>
            <button type="submit" class="btn-creer-groupe">Créer le Groupe</button>
        </form>
    </div>
</div>
<script src="../js/popup.js"></script>
</body>
</html>
