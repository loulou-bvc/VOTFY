<?php
require_once __DIR__ . '/../controller/GroupeController.php';

function handleGroupRoutes($method, $uri) {
    file_put_contents(__DIR__ . '/../debug.log', "Méthode : $method, URI : " . json_encode($uri) . PHP_EOL, FILE_APPEND);
    switch ($method) {
        case "POST":
            if (isset($uri[4]) && $uri[4] === 'create') {
                // Créer un groupe
                $data = json_decode(file_get_contents('php://input'), true);
                echo json_encode(GroupeController::create($data));
            } else {
                http_response_code(400);
                echo json_encode(['message' => 'Route invalide']);
            }
            break;

        case 'PUT':
            if (isset($uri[4]) && $uri[4] === 'change' && isset($uri[5]) && is_numeric($uri[5])) {
                // Récupérer les données PUT
                $data = json_decode(file_get_contents('php://input'), true);
                $groupId = (int)$uri[5];
                $response = GroupeController::updateGroup($groupId, $data);
                header('Content-Type: application/json');
                echo json_encode($response);
            } else {
                http_response_code(400);
                echo json_encode(['message' => 'Route ou ID groupe invalide']);
            }
            break;

        case 'GET':
            if (isset($uri[4]) && is_numeric($uri[4]) && (int)$uri[4] > 0) {
                // Récupérer les détails d’un groupe
                $groupId = (int)$uri[4];
                echo json_encode(GroupeController::getGroupDetails($groupId));
            } elseif (isset($uri[4]) && $uri[4] === 'all' && isset($uri[5]) && is_numeric($uri[5])) {
                // Récupérer tous les groupes d’un utilisateur
                $userId = (int)$uri[5];
                echo json_encode(GroupeController::getAll($userId));
            } elseif (isset($uri[4]) && $uri[4] === 'members' && isset($uri[5]) && is_numeric($uri[5])) {
                // Récupérer les membres d’un groupe
                $groupId = (int)$uri[5];
                echo json_encode(GroupeController::getGroupMembers($groupId));
            } elseif (isset($uri[4]) && $uri[4] === 'available-roles') {
                // Récupérer les rôles disponibles
                $pdo = Connexion::pdo();
                echo json_encode(GroupeController::getAvailableRoles($pdo));
            } elseif (isset($uri[4]) && $uri[4] === 'role' && isset($uri[5]) && is_numeric($uri[5])) {
                // Récupérer les rôles des membres
                $groupId = (int)$uri[5];
                echo json_encode(GroupeController::getRolesForMembers($groupId));
            } elseif (isset($uri[4]) && $uri[4] === 'is-admin' && isset($uri[5]) && is_numeric($uri[5])) {
                // Vérifier si un utilisateur est admin d’un groupe
                $data = json_decode(file_get_contents('php://input'), true);
                $userId = $data['id_utilisateur'] ?? null;
                $groupId = (int)$uri[5];

                if ($userId) {
                    echo json_encode(['isAdmin' => GroupeController::isAdminOfGroup($groupId, $userId)]);
                } else {
                    http_response_code(400);
                    echo json_encode(['message' => 'ID utilisateur manquant']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['message' => 'Route ou ID groupe invalide']);
            }
            break;

        case 'DELETE':
            if (isset($uri[5]) && $uri[4] === 'leave' && is_numeric($uri[5])) {
                // Quitter un groupe
                $data = json_decode(file_get_contents('php://input'), true);
                $userId = $data['user_id'] ?? null;
                $groupId = (int)$uri[5];
                if ($userId) {
                    echo json_encode(GroupeController::leaveGroup($userId, $groupId));
                } else {
                    http_response_code(400);
                    echo json_encode(['message' => 'ID utilisateur manquant']);
                }
            } elseif (isset($uri[5]) && $uri[4] === 'remove-member') {
                $data = json_decode(file_get_contents('php://input'), true);
                $groupId = (int)$uri[5];
                $userId = $data['user_id'] ?? null;
                $adminId = $data['admin_id'] ?? null;

                if ($userId && $adminId) {
                    echo json_encode(GroupeController::removeMember($groupId, $userId, $adminId));
                } else {
                    http_response_code(400);
                    echo json_encode(['message' => 'Données manquantes (user_id ou admin_id)']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['message' => 'Route ou ID groupe invalide']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['message' => 'Méthode non autorisée']);
    }
}

