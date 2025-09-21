<?php

require_once __DIR__ . '/../Connexion.php';

class NotificationController {

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
    
    public static function getUserNotifications($userId) {
        $pdo = Connexion::pdo();
        try {
            $sql = "CALL GetUserNotifications(:userId)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':userId' => $userId]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['status' => 'success', 'notifications' => $notifications];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public static function markAllNotificationsAsRead($userId) {
        $pdo = Connexion::pdo();
        try {
            $sql = "UPDATE Notification SET lue = 1 WHERE id_utilisateur = :userId";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':userId' => $userId]);
        } catch (PDOException $e) {
            global $logFile;
            file_put_contents($logFile, "Erreur : " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }

    public static function deleteAllNotifications($userId) {
        $pdo = Connexion::pdo();
        try {
            $sql = "DELETE FROM Notification WHERE id_utilisateur = :userId";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':userId' => $userId]);
        } catch (PDOException $e) {
            global $logFile;
            file_put_contents($logFile, "Erreur : " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }

    public static function markNotificationAsRead($notificationId) {
        $pdo = Connexion::pdo();
        try {
            $sql = "UPDATE Notification SET lue = 1 WHERE id_notification = :notificationId";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':notificationId' => $notificationId]);
        } catch (PDOException $e) {
            global $logFile;
            file_put_contents($logFile, "Erreur : " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }

    public static function deleteNotification($notificationId) {
        $pdo = Connexion::pdo();
        try {
            $sql = "DELETE FROM Notification WHERE id_notification = :notificationId";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':notificationId' => $notificationId]);
        } catch (PDOException $e) {
            global $logFile;
            file_put_contents($logFile, "Erreur : " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
}


?>
