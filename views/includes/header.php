<?php
function getUnreadNotificationsCount($userId) {
    $pdo = Connexion::pdo();
    try {
        $sql = "SELECT COUNT(*) AS unread_count FROM Notification WHERE id_utilisateur = :userId AND lue = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':userId' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['unread_count'] ?? 0;
    } catch (PDOException $e) {
        global $logFile;
        file_put_contents($logFile, "Erreur : " . $e->getMessage() . "\n", FILE_APPEND);
        return 0;
    }
}


include_once '../api/Connexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$unreadNotificationsCount = 0;

if (isset($_SESSION['id_utilisateur'])) {
    $userId = $_SESSION['id_utilisateur'];
    $unreadNotificationsCount = getUnreadNotificationsCount($userId);
}


?>

<header class="navbar navbar-expand-lg bg-light shadow-sm" >
    <div class="container-fluid" >
        <!-- Icône du logo -->
        <a href="accueil.php" class="navbar-brand d-flex align-items-center">
            <img src="../images/logo_votify.png" alt="Logo Votify" class="logo">
        </a>

        <!-- Bouton menu hamburger -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Menu de navigation -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link" href="mesgroupes.php">Groupes</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="mespropositions.php">Propositions</a>
                </li>
            </ul>
        </div>

        <!-- Notifications et avatar utilisateur -->
        <div class="d-flex">
            <!-- Icône de notification -->
            <div class="position-relative me-3" style="margin: 3rem;">
                <a href="notifications.php" >
                    <i class="bi bi-bell fs-5"></i>
                    <?php if ($unreadNotificationsCount > 0): ?>
                        <span class="badge bg-primary text-white position-absolute top-0 start-100 translate-middle p-1 rounded-circle">
                            <?= $unreadNotificationsCount ?>
                        </span>
                    <?php endif; ?>
                </a>
            </div>
            <a href="monCompte.php">
                <i class="bi bi-person-circle" alt="User Avatar" class="rounded-circle" style="width: 30px; height: 100%; margin: 4rem;" ></i>
            </a>
            <!-- Avatar utilisateur -->

        </div>
    </div>
</header>
