<?php

require_once __DIR__ . '/../controller/SignalementController.php';

function handleSignalementRoutes($method, $uri)
{
    $signalementController = new SignalementController();

    switch ($method) {
        case 'POST':
            // Créer un signalement
            if (isset($uri[4]) && $uri[4] === 'create') {
                $data = json_decode(file_get_contents('php://input'), true);
                $idProposition = $data['id_proposition'] ?? null;
                $idUtilisateur = $data['id_utilisateur'] ?? null;
                $contenu = $data['contenu'] ?? null;

                if ($idProposition && $idUtilisateur && $contenu) {
                    $result = $signalementController->createSignalement($idProposition, $idUtilisateur, $contenu);
                    echo json_encode(['success' => $result]);
                } else {
                    http_response_code(400);
                    echo json_encode(['message' => 'Données manquantes']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['message' => 'Route invalide']);
            }
            break;

            case 'GET':
                // Récupérer les signalements d'un groupe
                if (isset($uri[4]) && $uri[4] === 'group' && isset($uri[5]) && is_numeric($uri[5])) {
                    $idGroupe = (int)$uri[5];
                    $signalements = $signalementController->getSignalementsByGroupId($idGroupe);
            
                    // Encapsulation des données pour respecter le format JSON attendu
                    echo json_encode(['signalements' => $signalements]);
                } else {
                    http_response_code(400);
                    echo json_encode(['message' => 'Route invalide ou ID groupe manquant']);
                }
                break;
            

        case 'DELETE':
            // Supprimer un signalement
            if (isset($uri[4]) && $uri[4] === 'delete' && isset($uri[5]) && is_numeric($uri[5])) {
                $signalementId = (int)$uri[5];
                $result = $signalementController->deleteSignalement($signalementId);
                echo json_encode(['success' => $result]);
            }
            // Supprimer une proposition et ses signalements
            elseif (isset($uri[4]) && $uri[4] === 'delete-proposition' && isset($uri[5]) && is_numeric($uri[5])) {
                $propositionId = (int)$uri[5];
                try {
                    $result = $signalementController->deletePropositionAndReports($propositionId);
                    echo json_encode(['success' => $result]);
                } catch (Exception $e) {
                    http_response_code(500);
                    echo json_encode(['message' => 'Erreur lors de la suppression', 'error' => $e->getMessage()]);
                }
            } else {
                http_response_code(400);
                echo json_encode(['message' => 'Route ou ID invalide']);
            }
            break;

        case 'PUT':
            // Vérifier si un utilisateur a signalé une proposition
            if (isset($uri[4]) && $uri[4] === 'check' && isset($uri[5]) && isset($uri[6])) {
                $idProposition = (int)$uri[5];
                $idUtilisateur = (int)$uri[6];
                $alreadyReported = $signalementController->hasUserReportedProposition($idProposition, $idUtilisateur);
                echo json_encode(['alreadyReported' => $alreadyReported]);
            } else {
                http_response_code(400);
                echo json_encode(['message' => 'Route invalide ou données manquantes']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['message' => 'Méthode non autorisée']);
    }
}
