<?php

require_once __DIR__ . '/../controller/MessageController.php';

function handleMessageRoutes($method, $uri): void {
    $logFile = __DIR__ . '/../debug.log';
    file_put_contents($logFile, "Méthode : $method, URI : " . json_encode($uri) . "\n", FILE_APPEND);

    switch ($method) {
        case 'POST':
            // Envoi d'un message dans un groupe
            $data = json_decode(file_get_contents('php://input'), true);
            $idGroupe = $uri[4] ?? null;
            $idUtilisateur = $data['id_utilisateur'] ?? null;

            file_put_contents($logFile, "Données reçues (POST) : " . json_encode($data) . "\n", FILE_APPEND);

            if ($idGroupe && $idUtilisateur && isset($data['message'])) {
                $message = trim($data['message']); // Supprime les espaces inutiles
                if (empty($message)) {
                    http_response_code(400); // Mauvaise requête
                    echo json_encode(['message' => 'Le message ne peut pas être vide']);
                    return;
                }

                try {
                    $response = MessageController::sendMessage(
                        (int)$idGroupe,
                        (int)$idUtilisateur,
                        $message
                    );
                    file_put_contents($logFile, "Réponse MessageController::sendMessage : " . json_encode($response) . "\n", FILE_APPEND);
                    echo json_encode($response);
                } catch (Exception $e) {
                    file_put_contents($logFile, "Erreur lors de l'envoi du message : " . $e->getMessage() . "\n", FILE_APPEND);
                    http_response_code(500); // Erreur serveur
                    echo json_encode(['message' => 'Erreur lors de l\'envoi du message', 'error' => $e->getMessage()]);
                }
            } else {
                http_response_code(400); // Mauvaise requête
                file_put_contents($logFile, "ID du groupe, utilisateur ou message manquant\n", FILE_APPEND);
                echo json_encode(['message' => 'ID du groupe, utilisateur ou message manquant']);
            }
            break;

        case 'GET':
            // Récupération des messages d'un groupe
            $idGroupe = $uri[4] ?? null;

            if ($idGroupe) {
                try {
                    $response = MessageController::getMessages((int)$idGroupe);
                    file_put_contents($logFile, "Réponse MessageController::getMessages : " . json_encode($response) . "\n", FILE_APPEND);
                    echo json_encode($response);
                } catch (Exception $e) {
                    file_put_contents($logFile, "Erreur lors de la récupération des messages : " . $e->getMessage() . "\n", FILE_APPEND);
                    http_response_code(500); // Erreur serveur
                    echo json_encode(['message' => 'Erreur lors de la récupération des messages', 'error' => $e->getMessage()]);
                }
            } else {
                http_response_code(400); // Mauvaise requête
                file_put_contents($logFile, "ID du groupe manquant\n", FILE_APPEND);
                echo json_encode(['message' => 'ID du groupe manquant']);
            }
            break;

        default:
            http_response_code(405); // Méthode non autorisée
            file_put_contents($logFile, "Méthode non autorisée : $method\n", FILE_APPEND);
            echo json_encode(['message' => 'Méthode non autorisée']);
    }
}
