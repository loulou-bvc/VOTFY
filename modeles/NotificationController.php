<?php

require_once __DIR__ . '/../api/Connexion.php';

class NotificationController {
    // Méthode pour créer une notification
    public static function create($userId, $message)
    {
        $db = Connexion::pdo();
    
        try {
            $query = "INSERT INTO Notification (id_utilisateur, message, redirectionPage, date_notification) 
                      VALUES (:userId, :message, :redirectionPage, :dateNotification)";
            $stmt = $db->prepare($query);
    
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':message', $message, PDO::PARAM_STR);
            $stmt->bindParam(':redirectionPage', $redirectionPage, PDO::PARAM_STR);
    
            // Fournir des valeurs
            $redirectionPage = ''; // ou une URL par défaut
            $dateNotification = date('Y-m-d H:i:s'); // Date et heure actuelles au format SQL
            $stmt->bindParam(':dateNotification', $dateNotification, PDO::PARAM_STR);
    
            $stmt->execute();
    
            return ['status' => 'success', 'message' => 'Notification créée avec succès.'];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    

    // Méthode pour marquer toutes les notifications comme lues
    public static function markAllAsRead($userId) {
        $pdo = Connexion::pdo();
        try {
            $sql = "UPDATE Notification SET lue = 1 WHERE id_utilisateur = :userId";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':userId' => $userId]);
            return ['status' => 'success', 'message' => 'Toutes les notifications ont été marquées comme lues'];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    // Méthode pour récupérer les notifications d'un utilisateur
    public static function getUserNotifications($userId) {
        $pdo = Connexion::pdo();
        try {
            $sql = "CALL GetUserNotifications(:userId)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':userId' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
