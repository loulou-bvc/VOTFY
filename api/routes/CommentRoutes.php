<?php
require_once __DIR__ . '/../controller/CommentController.php';

function handleCommentRoutes($method, $uri) {
    switch ($method) {
        case 'GET':
            if (isset($uri[4]) && is_numeric($uri[4]) && (int)$uri[4] > 0) {
                // Récupérer les commentaires d'une proposition
                $propositionId = (int)$uri[4];
                echo json_encode(CommentController::readByProposition($propositionId));
            } else {
                http_response_code(400);
                echo json_encode(['message' => 'ID proposition invalide']);
            }
            break;

        case 'POST':
            // Ajouter un commentaire
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode(CommentController::createComment($data));
            break;

        default:
            http_response_code(405);
            echo json_encode(['message' => 'Méthode non autorisée']);
    }
}
