<?php
require_once __DIR__ . '/../controller/VoteController.php';

function handleVoteRoutes($method, $uri) {
    switch ($method) {
        case 'POST':
            // Enregistrer un vote
            $data = json_decode(file_get_contents('php://input'), true);
            if (isset($data['user_id'], $data['proposition_id'], $data['choix'])) {
                echo json_encode(VoteController::createVote($data['user_id'], $data));
            } else {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Données manquantes pour enregistrer le vote']);
            }
            break;

        case 'GET':
            // Gestion des URI pour hasVoted et statistics
            if (isset($uri[4])) {
                switch ($uri[4]) {
                    case 'hasVoted':
                        // Vérifier si un utilisateur a voté pour une proposition
                        if (isset($uri[5], $uri[6]) && is_numeric($uri[5]) && is_numeric($uri[6])) {
                            $userId = (int)$uri[5];
                            $propositionId = (int)$uri[6];
                            echo json_encode([
                                'status' => 'success',
                                'hasVoted' => VoteController::hasAlreadyVoted($userId, $propositionId)
                            ]);
                        } else {
                            http_response_code(400);
                            echo json_encode(['status' => 'error', 'message' => 'ID utilisateur ou ID proposition invalide']);
                        }
                        break;

                    case 'statistics':
                        // Récupérer les statistiques des votes pour une proposition
                        if (isset($uri[5]) && is_numeric($uri[5])) {
                            $propositionId = (int)$uri[5];
                            $stats = VoteController::getVoteStatistics($propositionId);
                            if ($stats) {
                                echo json_encode([
                                    'status' => 'success',
                                    'statistics' => $stats
                                ]);
                            } else {
                                http_response_code(404);
                                echo json_encode(['status' => 'error', 'message' => 'Statistiques non trouvées']);
                            }
                        } else {
                            http_response_code(400);
                            echo json_encode(['status' => 'error', 'message' => 'ID proposition invalide']);
                        }
                        break;

                    default:
                        http_response_code(400);
                        echo json_encode(['status' => 'error', 'message' => 'Action invalide']);
                        break;
                }
            } else {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'URI invalide']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Méthode non autorisée']);
            break;
    }
}
?>

