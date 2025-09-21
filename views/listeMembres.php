<?php
require_once __DIR__ . '/../api/Connexion.php';
require_once __DIR__ .'/../modeles/PermissionMiddleware.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Chemin du fichier de log
$logFile = __DIR__ . '/logs/debug.log';
file_put_contents($logFile, "Accès à la page listeMembres.php\n", FILE_APPEND);

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['id_utilisateur'])) {
    file_put_contents($logFile, "Utilisateur non connecté, redirection vers login.php\n", FILE_APPEND);
    header('Location: login.php');
    exit;
}

// Vérification et récupération de l'ID du groupe depuis l'URL
if (!isset($_GET['id_groupe']) || !filter_var($_GET['id_groupe'], FILTER_VALIDATE_INT)) {
    file_put_contents($logFile, "ID de groupe manquant, redirection vers groupe.php\n", FILE_APPEND);
    header('Location: groupe.php');
    exit;
}

$idGroupe = (int)$_GET['id_groupe'];
file_put_contents($logFile, "ID du groupe récupéré : $idGroupe\n", FILE_APPEND);

// Appeler l'API pour récupérer les membres du groupe
$apiUrl = "https://webdev.iut-orsay.fr/~nboulad/VOTFY/api/groupes/members/$idGroupe";
$response = file_get_contents($apiUrl);

// Vérifiez si une réponse a été reçue
if ($response === false) {
    $error = error_get_last();
    file_put_contents($logFile, "Erreur lors de l'appel API : " . print_r($error, true) . PHP_EOL, FILE_APPEND);
    die("Erreur lors de la récupération des membres du groupe.");
}

// Décoder la réponse JSON
$members = json_decode($response, true);

// Vérifiez si l'API a retourné une erreur
if (isset($members['error']) && $members['error']) {
    $errorMessage = $members['message'] ?? "Erreur lors de la récupération des membres.";
    file_put_contents($logFile, "Erreur API : $errorMessage\n", FILE_APPEND);
    $members = [];
}


// Fonction pour récupérer les rôles des membres depuis la base de données
function getRolesForMembers($idGroupe) {
    global $logFile;
    try {
        $check = PermissionMiddleware::checkLevel(3, $_SESSION['id_utilisateur'], $idGroupe);
        if (!$check['status']) {
            file_put_contents($logFile, "Permission refusée pour l'utilisateur {$_SESSION['id_utilisateur']} dans le groupe $idGroupe\n", FILE_APPEND);
            die("Vous n'avez pas la permission d'accéder à cette page.");
        }
        $pdo = Connexion::pdo();
        $stmt = $pdo->prepare("
            SELECT nom_role,
                Membre_Groupe.id_utilisateur,
                Role.nom_role
            FROM Membre_Groupe
            JOIN Role ON Membre_Groupe.id_role = Role.id_role
            WHERE Membre_Groupe.id_groupe = :idGroupe
        ");

        $stmt->execute(['idGroupe' => $idGroupe]);
        return $stmt->fetchAll();

    } catch (PDOException $e) {
        file_put_contents($logFile, "Erreur lors de la récupération des rôles des membres : " . $e->getMessage() . PHP_EOL, FILE_APPEND);
        die("Erreur lors de la récupération des rôles des membres.");
    }
}



// Récupération des rôles pour les membres
$memberRoles = getRolesForMembers($idGroupe);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Membres</title>
    <link rel="stylesheet" href="../css/listemembres.css">
    <link rel="stylesheet" href="../css/footer.css">
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let userIdToRemove = null; // Variable globale pour stocker l'ID utilisateur à supprimer

        function openConfirmationPopup(userId) {
            userIdToRemove = userId; // Stocker l'ID utilisateur dans la variable
            const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
            modal.show(); // Afficher le popup
        }

        function confirmRemoveMember() {
            if (!userIdToRemove) return;

            const apiUrl = `https://webdev.iut-orsay.fr/~nboulad/VOTFY/api/groupes/remove-member/${<?= $idGroupe ?>}`;
            const data = {
                user_id: userIdToRemove,
                admin_id: <?= json_encode($_SESSION['id_utilisateur']) ?>
            };

            fetch(apiUrl, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data),
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Erreur API : ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('confirmationModal'));
                modal.hide(); // Masquer le popup
                if (data.status === 'success') {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Erreur : ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur :', error);
                alert('Une erreur est survenue.');
            });
        }
    </script>
</head>
<body>
<?php include_once 'includes/header.php'; ?>
<header class="header-container">
    <a href="javascript:history.back()" class="back-arrow">
        <i class="bi bi-chevron-left"></i>
    </a>
    <h1 class="header-title">Liste des membres du Groupe</h1>
</header>
<div class="container mt-4">
    <main>
        <?php if (!empty($members)): ?>
            <div class="list-group">
    <?php foreach ($members as $member): ?>
        <?php 
        // Trouver le rôle du membre
        $role = "Aucun rôle"; // Valeur par défaut
        foreach ($memberRoles as $roleInfo) {
            if ((int)$roleInfo['id_utilisateur'] === (int)$member['id_utilisateur']) {
                $role = $roleInfo['nom_role'];
                break;
            }
        }
        ?>
        <div class="list-group-item d-flex justify-content-between align-items-center">
            <div style="display: flex; gap: 15px; align-items: center; justify-content: center;">
                <h5 style="margin: 5px;"><?= htmlspecialchars($member['nom']) ?> <?= htmlspecialchars($member['prenom']) ?></h5>
                <p style="margin: 5px;">Email : <?= htmlspecialchars($member['email']) ?></p>
                <p style="margin: 5px;">Rôle : <?= htmlspecialchars($role) ?></p>
            </div>
            <div class="dropdown">
                <button class="btn btn-link dropdown-toggle" type="button" id="dropdownMenuButton<?= $member['id_utilisateur'] ?>" data-bs-toggle="dropdown" aria-expanded="false" style="text-decoration: none;">
                    <i class="bi bi-three-dots"></i>
                </button>
                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton<?= $member['id_utilisateur'] ?>">
                    <li><a class="dropdown-item" href="signaler.php?id_utilisateur=<?= $member['id_utilisateur'] ?>&id_groupe=<?= $idGroupe ?>">Signaler</a></li>
                    <li><a class="dropdown-item" href="modifierRole.php?id_utilisateur=<?= $member['id_utilisateur'] ?>&id_groupe=<?= $idGroupe ?>">Modifier le rôle</a></li>
                    <li><button class="dropdown-item text-danger" onclick="openConfirmationPopup(<?= $member['id_utilisateur'] ?>)">Supprimer</button></li>
                </ul>
            </div>
        </div>
    <?php endforeach; ?>
</div>

        <?php else: ?>
            <p>Aucun membre trouvé pour ce groupe.</p>
        <?php endif; ?>
    </main>
</div>

<!-- Popup de confirmation -->
<div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmationModalLabel">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                Êtes-vous sûr de vouloir supprimer ce membre ?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" onclick="confirmRemoveMember()">Confirmer</button>
            </div>
        </div>
    </div>
</div>

<br>
<?php include_once 'includes/footer.php'; ?>
</body>
</html>
