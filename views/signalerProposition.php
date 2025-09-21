<?php
// Inclusion de la classe CallApi
require_once '../modeles/callApi.php';

// Démarrage de session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirection si l'utilisateur n'est pas connecté
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: login.php');
    exit;
}

// Initialisation des variables
$error_message = "";
$success_message = "";
$id_proposition = isset($_GET['id_proposition']) ? (int)$_GET['id_proposition'] : null;
$id_utilisateur = $_SESSION['id_utilisateur'];

// Vérification du formulaire soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contenu'])) {
    $contenu = trim($_POST['contenu']);

    if (!empty($contenu) && $id_proposition) {
        // Vérifier si l'utilisateur a déjà signalé la proposition
        $checkResponse = CallApi::callApi("signalement/check/$id_proposition/$id_utilisateur", 'PUT');
        
        if (isset($checkResponse['alreadyReported']) && $checkResponse['alreadyReported']) {
            $error_message = "Vous avez déjà signalé cette proposition.";
        } else {
            // Si l'utilisateur n'a pas encore signalé, envoyer le signalement
            $response = CallApi::callApi('signalement/create', 'POST', [
                'id_proposition' => $id_proposition,
                'id_utilisateur' => $id_utilisateur,
                'contenu' => $contenu,
            ]);

            if (isset($response['status']) && $response['status'] === 'error') {
                $error_message = $response['message'];
            } elseif (isset($response['success']) && $response['success']) {
                $success_message = "Signalement envoyé avec succès.";
            } else {
                $error_message = $response['message'] ?? "Une erreur inattendue s'est produite.";
            }
        }
    } else {
        $error_message = "Le contenu du signalement est requis.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signaler une Proposition</title>

    <link rel="stylesheet" href="../css/footer.css">
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/global.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<?php include_once 'includes/header.html'; ?>
<header class="header-container">
    <a href="javascript:history.back()" class="back-arrow">
        <i class="bi bi-chevron-left"></i>
    </a>
    <h1 class="header-title">Signaler une Proposition</h1>
</header>
<main>
    <div class="container mt-5">
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success" role="alert">
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php else: ?>
            <form method="POST" class="form-group">
                <div class="mb-3">
                    <label for="contenu" class="form-label">Expliquez la raison du signalement :</label>
                    <textarea name="contenu" id="contenu" class="form-control" rows="5" placeholder="Décrivez votre signalement..." required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Envoyer le signalement</button>
            </form>
        <?php endif; ?>
    </div>
    <br>
</main>
<?php include_once 'includes/footer.html'; ?>
</body>
</html>
