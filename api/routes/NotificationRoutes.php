<?php
require_once __DIR__ . '/../controller/NotificationController.php';

function handleNotificationRoutes($method, $uri) {
    switch ($method) {
        case 'GET':
            if (isset($uri[4]) && is_numeric($uri[4]) && (int)$uri[4] > 0) {
                // Récupérer les notifications d'un utilisateur
                $userId = (int)$uri[4];
                echo json_encode(NotificationController::getUserNotifications($userId));
            } else {
                http_response_code(400);
                echo json_encode(['message' => 'ID utilisateur invalide']);
            }
            break;

        case 'PUT':
            if (isset($uri[4]) && $uri[4] === 'mark-read') {
                // Marquer toutes les notifications comme lues pour un utilisateur
                $userId = (int)$uri[5];
                echo json_encode(NotificationController::markAllNotificationsAsRead($userId));
            } elseif (isset($uri[5]) && is_numeric($uri[5]) && $uri[4] === 'mark') {
                // Marquer une notification spécifique comme lue
                $notificationId = (int)$uri[5];
                NotificationController::markNotificationAsRead($notificationId);
                echo json_encode(['status' => 'success', 'message' => 'Notification marquée comme lue']);
            } else {
                http_response_code(400);
                echo json_encode(['message' => 'Route ou ID invalide']);
            }
            break;

        case 'DELETE':
            if (isset($uri[4]) && $uri[4] === 'delete-all') {
                // Supprimer toutes les notifications pour un utilisateur
                $userId = (int)$uri[5];
                echo json_encode(NotificationController::deleteAllNotifications($userId));
            } elseif (isset($uri[5]) && is_numeric($uri[5]) && $uri[4] === 'delete') {
                // Supprimer une notification spécifique
                $notificationId = (int)$uri[5];
                NotificationController::deleteNotification($notificationId);
                echo json_encode(['status' => 'success', 'message' => 'Notification supprimée avec succès']);
            } else {
                http_response_code(400);
                echo json_encode(['message' => 'Route ou ID invalide']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['message' => 'Méthode non autorisée']);
    }
}
