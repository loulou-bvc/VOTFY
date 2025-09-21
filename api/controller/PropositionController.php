<?php

require_once __DIR__ . '/../Connexion.php';
require_once __DIR__ . '/NotificationController.php';
require_once __DIR__ . '/../PermissionMiddleware.php';


class PropositionController {
    public static function readByUser($userId) {
        $pdo = Connexion::pdo();
        try {
            $sql = "CALL GetUserProposals(:userId)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':userId' => $userId]);

            return [
                'status' => 'success',
                'propositions' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
        } catch (PDOException $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    public static function readById($propositionId) {
        $pdo = Connexion::pdo();
        try {
            $sql = "SELECT * FROM Proposition WHERE id_proposition = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $propositionId]);
            return [
                'status' => 'success',
                'proposition' => $stmt->fetch(PDO::FETCH_ASSOC)
            ];
        } catch (PDOException $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    public static function readByGroup($groupId) {
        $pdo = Connexion::pdo();
        try {
            $sql = "SELECT * FROM Proposition WHERE id_groupe = ? and etat = 'ouverte'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(isset($groupId) ? [$groupId] : []);
            return [
                'status' => 'success',
                'propositions' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
        } catch (PDOException $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    public static function updateProposition($propositionId, $data, $userId, $groupId, $requiredLevel) {
        global $logFile;
        file_put_contents($logFile, "close appelé avec propositionId: $propositionId, data: " . json_encode($data) . ", userId: $userId, groupId: $groupId, requiredLevel: $requiredLevel" . PHP_EOL, FILE_APPEND);
        // Log de tentative de mise à jour
        file_put_contents($logFile, "Tentative de mise à jour de la proposition $propositionId par l'utilisateur $userId dans le groupe $groupId\n", FILE_APPEND);

        // Vérifier les permissions
        $permissionCheck = PermissionMiddleware::checkLevel($requiredLevel, $userId, $groupId);
        file_put_contents($logFile, "Résultat de checkLevel : " . json_encode($permissionCheck) . "\n", FILE_APPEND);

        if ($permissionCheck['status'] === 'error') {
            file_put_contents($logFile, "Permission refusée : " . $permissionCheck['message'] . "\n", FILE_APPEND);
            return ['status' => 'error', 'message' => $permissionCheck['message']];
        }

        $pdo = Connexion::pdo();
        try {
            // Requête SQL directe pour mettre à jour une proposition
            $sql = "UPDATE Proposition SET 
                titre = :titre, 
                description = :description, 
                budget = :budget, 
                duree = :duree
                WHERE id_proposition = :id_proposition";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id_proposition' => $propositionId,
                ':titre' => $data['titre'],
                ':description' => $data['description'],
                ':budget' => $data['budget'],
                ':duree' => $data['duree']
            ]);

            NotificationController::create($userId, "Votre proposition ID $propositionId a été mise à jour.");

            // Ajouter un log de succès
            file_put_contents($logFile, "Mise à jour réussie de la proposition $propositionId par l'utilisateur $userId\n", FILE_APPEND);

            return ['status' => 'success', 'message' => 'Proposition mise à jour avec succès'];
        } catch (PDOException $e) {
            // Log d'erreur SQL
            file_put_contents($logFile, "Erreur SQL lors de la mise à jour : " . $e->getMessage() . "\n", FILE_APPEND);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public static function getPropositionDetails($propositionId) {
        $pdo = Connexion::pdo();
        try {
            $sql = "SELECT * FROM Proposition WHERE id_proposition = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$propositionId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            file_put_contents(__DIR__ . '/logs/error.log', "Erreur SQL : " . $e->getMessage() . "\n", FILE_APPEND);
            return null;
        }
    }
    public static function create($data, $userId, $groupId, $requiredLevel)
    {
        // Variables pour le retour
        $response = ['status' => 'error', 'message' => ''];

        // Vérification des permissions
        $permissionCheck = PermissionMiddleware::checkLevel($requiredLevel, $userId, $groupId);
        if ($permissionCheck['status'] !== 'success') {
            $response['message'] = $permissionCheck['message'];
            return $response;
        }

        // Vérification des données obligatoires
        $requiredFields = ['nom', 'description', 'duree', 'budget'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $response['message'] = "Le champ '$field' est obligatoire.";
                return $response;
            }
        }

        // Connexion à la base de données
        try {
            $pdo = Connexion::pdo();
            $sql = "INSERT INTO Proposition (id_groupe, id_createur, titre, description, date_proposition, etat, duree, numero_tour, budget) 
                    VALUES (:id_groupe, :id_createur, :titre, :description, CURDATE(), 'ouverte', :duree, 1, :budget)";
            $stmt = $pdo->prepare($sql);

            // Exécution de la requête
            $stmt->execute([
                ':id_groupe' => $groupId,
                ':id_createur' => $userId,
                ':titre' => $data['nom'],
                ':description' => $data['description'],
                ':duree' => $data['duree'],
                ':budget' => $data['budget']
            ]);

            // Créer une notification pour l'utilisateur
            NotificationController::create($userId, "Votre proposition '{$data['nom']}' a été créée avec succès dans le groupe ID $groupId.");

            // Réponse en cas de succès
            $response['status'] = 'success';
            $response['message'] = 'Proposition créée avec succès.';
        } catch (PDOException $e) {
            // Gestion des erreurs SQL
            $response['message'] = 'Erreur lors de la création de la proposition : ' . $e->getMessage();
        }

        return $response;
    }
    public static function readClosedProposals($groupId) {
        $pdo = Connexion::pdo();
        try {
            $sql = "SELECT * FROM Proposition WHERE id_groupe = ? AND etat = 'fermee'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$groupId]);
            return ['status' => 'success', 'propositions' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public static function close($propositionId, $data, $userId, $groupId, $requiredLevel) {
        $check = PermissionMiddleware::checkLevel($requiredLevel, $userId, $groupId);
        if ($check['status'] === 'error') {
            return $check;
        }
        $pdo = Connexion::pdo();
    
        try {
            $sql = "UPDATE Proposition SET etat = 'fermee' WHERE id_proposition = :propositionId";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':propositionId' => $propositionId]);
    
            NotificationController::create($userId, "Votre proposition ID $propositionId a été fermée.");
            return ['status' => 'success', 'message' => 'Proposition fermée avec succès'];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    
}
?>
