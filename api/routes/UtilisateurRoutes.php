<?php
require_once __DIR__ . '/../controller/UtilisateurController.php';

function handleUserRoutes($method, $uri) {
    switch ($method) {
        case 'POST':
            // Créer un utilisateur
            if (!empty($uri[4])) {
                $data = json_decode(file_get_contents('php://input'), true);
                echo json_encode(UtilisateurController::create($data));
            } else {
                http_response_code(400);
                echo json_encode(['message' => 'Route non valide']);
            }
            break;

        case 'GET':
            if (isset($uri[4]) && is_numeric($uri[4]) && (int)$uri[4] > 0) {
                // Récupérer un utilisateur par ID
                $userId = (int)$uri[4];
                echo json_encode(UtilisateurController::getById($userId));
            } else {
                http_response_code(400);
                echo json_encode(['message' => 'ID utilisateur invalide']);
            }
            break;

        case 'PUT':
            // Mettre à jour un utilisateur
            if (isset($uri[4]) && is_numeric($uri[4]) && (int)$uri[4] > 0) {
                $data = json_decode(file_get_contents('php://input'), true);
                $userId = (int)$uri[4];
                echo json_encode(UtilisateurController::updateUserInfo($userId, $data));
            } else {
                http_response_code(400);
                echo json_encode(['message' => 'ID utilisateur manquant ou invalide']);
            }
            break;

        case 'DELETE':
            // Supprimer un utilisateur
            if (isset($uri[4]) && is_numeric($uri[4]) && (int)$uri[4] > 0) {
                $userId = (int)$uri[4];
                echo json_encode(UtilisateurController::deleteAccount($userId));
            } else {
                http_response_code(400);
                echo json_encode(['message' => 'ID utilisateur manquant ou invalide']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['message' => 'Méthode non autorisée']);
    }
}

function handleUserLogin($method, $uri) {
    if ($method === 'POST') {
        // Se connecter
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode(UtilisateurController::login($data));
    } else {
        http_response_code(405);
        echo json_encode(['message' => 'Méthode non autorisée']);
    }
}
