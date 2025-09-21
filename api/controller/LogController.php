<?php

require_once __DIR__ . '/../Connexion.php';

class LogController {
    // 1️⃣ Ajouter un log
    public static function addLog($userId, $groupId, $action, $details = null) {
        $pdo = Connexion::pdo();
        try {
            // Appel de la procédure pour ajouter un log
            $sql = "CALL add_log(:userId, :groupId, :action, :details)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':userId' => $userId,
                ':groupId' => $groupId,
                ':action' => $action,
                ':details' => $details,
            ]);
            return ['status' => 'success', 'message' => 'Log ajouté avec succès'];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    // 2️⃣ Récupérer les logs d'un groupe
    public static function getLogsByGroup($groupId) {
        $pdo = Connexion::pdo();
        try {
            // Appel de la procédure pour récupérer les logs d'un groupe
            $sql = "CALL get_logs_by_group(:groupId)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':groupId' => $groupId]);
            return ['status' => 'success', 'logs' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    // 3️⃣ Récupérer les logs d'un utilisateur
    public static function getLogsByUser($userId) {
        $pdo = Connexion::pdo();
        try {
            // Appel de la procédure pour récupérer les logs d'un utilisateur
            $sql = "CALL get_logs_by_user(:userId)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':userId' => $userId]);
            return ['status' => 'success', 'logs' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    // 4️⃣ Supprimer un log
    public static function delete($logId) {
        $pdo = Connexion::pdo();
        try {
            // Appel de la procédure pour supprimer un log
            $sql = "CALL delete_log(:id)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $logId]);
            return ['status' => 'success', 'message' => 'Log supprimé avec succès'];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    // 5️⃣ Supprimer tous les logs d'un groupe
    public static function deleteAll($groupId) {
        $pdo = Connexion::pdo();
        try {
            // Appel de la procédure pour supprimer tous les logs d'un groupe
            $sql = "CALL delete_all_logs_by_group(:groupId)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':groupId' => $groupId]);
            return ['status' => 'success', 'message' => 'Logs supprimés avec succès'];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    // 6️⃣ Supprimer tous les logs d'un utilisateur
    public static function deleteAllByUser($userId) {
        $pdo = Connexion::pdo();
        try {
            // Appel de la procédure pour supprimer tous les logs d'un utilisateur
            $sql = "CALL delete_all_logs_by_user(:userId)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':userId' => $userId]);
            return ['status' => 'success', 'message' => 'Logs supprimés avec succès'];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}

?>
