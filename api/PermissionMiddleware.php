<?php

require_once __DIR__ . '/Connexion.php';

class PermissionMiddleware {
    public static function checkLevel($requiredLevel, $userId, $idGroupe) {
        // Obtenir l'objet PDO
        $pdo = Connexion::pdo();

        // Vérifier si la connexion est bien établie
        if (!$pdo) {
            return ['status' => 'error', 'message' => 'Impossible d\'établir une connexion à la base de données'];
        }

        try {
            // Appel à la fonction SQL pour vérifier le niveau de permission
            $sql = "SELECT has_permission_level(:userId, :idGroupe, :requiredLevel) AS hasPermission";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':userId' => $userId,
                ':idGroupe' => $idGroupe,
                ':requiredLevel' => $requiredLevel
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result || !$result['hasPermission']) {
                return ['status' => 'error', 'message' => 'Permission refusée'];
            }

            return ['status' => 'success', 'message' => 'Permission accordée'];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
?>
