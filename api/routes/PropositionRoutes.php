<?php

require_once __DIR__ . '/../controller/PropositionController.php';

function handlePropositionRoutes($method, $uri) {
    switch ($method) {
        case 'POST':
            // Créer une proposition
            $data = json_decode(file_get_contents('php://input'), true);
            $userId = $data['user_id'] ?? null;
            $groupId = $data['group_id'] ?? null;
            $requiredLevel = $data['required_level'] ?? null;

            if ($userId && $groupId && isset($requiredLevel)) {
                echo json_encode(PropositionController::create($data, $userId, $groupId, $requiredLevel));
            } else {
                http_response_code(400);
                echo json_encode(['message' => 'Données manquantes pour la création']);
                exit;
            }
            break;

        case 'GET':
            if (isset($uri[4]) && is_numeric($uri[4]) && (int)$uri[4] > 0) {
                // Récupérer une proposition par ID
                $propositionId = (int)$uri[4];
                echo json_encode(PropositionController::readById($propositionId));
            } elseif (isset($uri[4]) && $uri[4] === 'group' && isset($uri[5]) && is_numeric($uri[5])) {
                // Récupérer les propositions d’un groupe
                $groupId = (int)$uri[5];
                echo json_encode(PropositionController::readByGroup($groupId));
            } elseif (isset($uri[4]) && $uri[4] === 'user' && isset($uri[5]) && is_numeric($uri[5])) {
                // Récupérer les propositions d’un utilisateur
                $userId = (int)$uri[5];
                echo json_encode(PropositionController::readByUser($userId));
            } elseif (isset($uri[4]) && $uri[4] === 'closed' && isset($uri[5]) && is_numeric($uri[5])) {
                // Récupérer les propositions fermées d’un groupe
                $groupId = (int)$uri[5];
                echo json_encode(PropositionController::readClosedProposals($groupId));
            } else {
                http_response_code(400);
                echo json_encode(['message' => 'Route ou ID invalide']);
                exit;
            }
            break;

            case 'PUT':
                if (isset($uri[4]) && $uri[4] === 'change' && isset($uri[5]) && is_numeric($uri[5]) && (int)$uri[5] > 0) {
                    // Modifier une proposition via /change/{id}
                    $propositionId = (int)$uri[5];
                    $data = json_decode(file_get_contents('php://input'), true);
    
                    // Log pour déboguer les données reçues
    
                    $userId = $data['user_id'] ?? null;
                    $groupId = $data['group_id'] ?? null;
                    $requiredLevel = $data['required_level'] ?? null;
    
                    if ($userId && $groupId && isset($requiredLevel)) {
                        echo json_encode(PropositionController::updateProposition($propositionId, $data, $userId, $groupId, $requiredLevel));
                    } else {
                        http_response_code(400);
                        echo json_encode(['message' => 'Données manquantes pour la mise à jour']);
                        exit;
                    }
                } elseif (isset($uri[4]) && $uri[4] === 'close' && isset($uri[5]) && is_numeric($uri[5]) && (int)$uri[5] > 0) {
                    // Fermer une proposition via /close/{id}
                    $propositionId = (int)$uri[5];
                    $data = json_decode(file_get_contents('php://input'), true);
    
                    // Log pour déboguer les données reçues
                    file_put_contents('../debug.log', "Données reçues pour close : " . json_encode($data) . PHP_EOL, FILE_APPEND);
    
                    $userId = $data['user_id'] ?? null;
                    $groupId = $data['group_id'] ?? null;
                    $requiredLevel = $data['required_level'] ?? null;
    
                    if ($userId && $groupId && isset($requiredLevel)) {
                        echo json_encode(PropositionController::close($propositionId, $data, $userId, $groupId, $requiredLevel));
                    } else {
                        http_response_code(400);
                        echo json_encode(['message' => 'Données manquantes pour la fermeture']);
                        exit;
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(['message' => 'ID proposition manquant ou invalide']);
                    exit;
                }
                break;

        default:
            http_response_code(405);
            echo json_encode(['message' => 'Méthode non autorisée']);
            exit;
    }
}
?>
