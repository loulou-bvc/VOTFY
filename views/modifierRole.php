<?php

require_once __DIR__ . '/../api/Connexion.php';
require_once __DIR__ .'/../modeles/PermissionMiddleware.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Chemin du fichier de log
$logFile = __DIR__ . '/logs/debug.log';
file_put_contents($logFile, "Accès à la page modifierRole.php\n", FILE_APPEND);

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['id_utilisateur'])) {
    file_put_contents($logFile, "Utilisateur non connecté, redirection vers login.php\n", FILE_APPEND);
    header('Location: login.php');
    exit;
}

// Vérifiez les paramètres de l'URL
if (!isset($_GET['id_utilisateur'], $_GET['id_groupe']) || 
    !filter_var($_GET['id_utilisateur'], FILTER_VALIDATE_INT) || 
    !filter_var($_GET['id_groupe'], FILTER_VALIDATE_INT)) {
    file_put_contents($logFile, "Paramètres manquants ou invalides, redirection vers groupe.php\n", FILE_APPEND);
    header('Location: groupe.php');
    exit;
}

$idUtilisateur = (int)$_GET['id_utilisateur'];
$idGroupe = (int)$_GET['id_groupe'];
file_put_contents($logFile, "ID utilisateur : $idUtilisateur, ID groupe : $idGroupe\n", FILE_APPEND);

// Connexion à la base de données
$pdo = Connexion::pdo();

// Fonction pour récupérer les rôles disponibles
function getAvailableRoles($pdo) {
    global $logFile;
    try {
        $stmt = $pdo->prepare("SELECT id_role, nom_role FROM Role");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        file_put_contents($logFile, "Erreur lors de la récupération des rôles : " . $e->getMessage() . PHP_EOL, FILE_APPEND);
        die("Erreur lors de la récupération des rôles.");
    }
}

// Fonction pour mettre à jour le rôle d'un utilisateur
function updateUserRole($pdo, $idUtilisateur, $idGroupe, $newRole) {
    global $logFile;
    try {
        $check = PermissionMiddleware::checkLevel(3, $_SESSION['id_utilisateur'], $idGroupe);
        if ($check['status'] === 'error') {
            file_put_contents($logFile, "Permission refusée pour l'utilisateur {$_SESSION['id_utilisateur']} dans le groupe $idGroupe\n", FILE_APPEND);
            die("Vous n'avez pas la permission de modifier le rôle.");
        }
        $stmt = $pdo->prepare("
            UPDATE Membre_Groupe 
            SET id_role = :newRole 
            WHERE id_utilisateur = :idUtilisateur AND id_groupe = :idGroupe
        ");
        $stmt->execute([
            'newRole' => $newRole,
            'idUtilisateur' => $idUtilisateur,
            'idGroupe' => $idGroupe
        ]);
        if ($stmt->rowCount() > 0) {
            file_put_contents($logFile, "Rôle mis à jour avec succès pour l'utilisateur $idUtilisateur dans le groupe $idGroupe\n", FILE_APPEND);
        } else {
            file_put_contents($logFile, "Aucune ligne mise à jour pour l'utilisateur $idUtilisateur dans le groupe $idGroupe\n", FILE_APPEND);
        }
    } catch (PDOException $e) {
        file_put_contents($logFile, "Erreur lors de la mise à jour du rôle : " . $e->getMessage() . PHP_EOL, FILE_APPEND);
        die("Erreur lors de la mise à jour du rôle.");
    }
}

// Récupération des rôles disponibles
$roles = getAvailableRoles($pdo);

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['role'])) {
    $newRole = (int)$_POST['role'];
    updateUserRole($pdo, $idUtilisateur, $idGroupe, $newRole);
    header("Location: listeMembres.php?id_groupe=$idGroupe&success=1");
    exit;
}
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier le Rôle</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/footer.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
</head>
<body>
<?php include_once 'includes/header.php'; ?>
<header class="header-container">
    <a href="javascript:history.back()" class="back-arrow">
        <i class="bi bi-chevron-left"></i>
    </a>
    <h1 class="header-title">Modifier le Rôle</h1>
</header>
<div class="container mt-4">
    <form method="post">
        <div class="mb-3">
            <label for="role" class="form-label">Nouveau rôle</label>
            <select name="role" id="role" class="form-select">
                <?php foreach ($roles as $role): ?>
                    <option value="<?= htmlspecialchars($role['id_role']) ?>"><?= htmlspecialchars($role['nom_role']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Enregistrer</button>
    </form>
    <br>
</div>
<?php include_once 'includes/footer.php'; ?>
</body>
</html>
