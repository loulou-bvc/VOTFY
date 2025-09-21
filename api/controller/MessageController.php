<?php

require_once __DIR__ . '/../Connexion.php';

class MessageController {
    /**
     * Récupère les messages d'un groupe
     * @param int $idGroupe
     * @return array
     */
    public static function getMessages($idGroupe) {
        try {
            $pdo = Connexion::pdo();
    
            // Log de diagnostic
            file_put_contents(__DIR__ . '/../debug.log', "Appel à getMessages avec idGroupe : $idGroupe\n", FILE_APPEND);
    
            $sql = "SELECT m.message, m.date_envoi, u.nom AS utilisateur
                    FROM Message m
                    JOIN Utilisateur u ON m.id_utilisateur = u.id_utilisateur
                    WHERE m.id_groupe = :idGroupe
                    ORDER BY m.date_envoi ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':idGroupe', $idGroupe, PDO::PARAM_INT);
            $stmt->execute();
    
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            // Vérification des messages trouvés
            if (empty($messages)) {
                file_put_contents(__DIR__ . '/../debug.log', "Aucun message trouvé pour idGroupe : $idGroupe\n", FILE_APPEND);
                return ['status' => 'success', 'messages' => []];
            }
    
            // Log des messages récupérés
            file_put_contents(__DIR__ . '/../debug.log', "Messages récupérés : " . json_encode($messages) . "\n", FILE_APPEND);
    
            return ['status' => 'success', 'messages' => $messages];
    
        } catch (PDOException $e) {
            // Log de l'erreur SQL
            file_put_contents(__DIR__ . '/../debug.log', "Erreur SQL : " . $e->getMessage() . "\n", FILE_APPEND);
    
            return ['status' => 'error', 'message' => 'Erreur lors de la récupération des messages.', 'error' => $e->getMessage()];
        }
    }
    

    /**
     * Envoie un message dans un groupe
     * @param int $idGroupe
     * @param int $userId
     * @param string $message
     * @return array
     */
    public static function sendMessage($idGroupe, $userId, $message) {
        try {
            // Validation des entrées
            if (empty($message)) {
                return ['status' => 'error', 'message' => 'Le message ne peut pas être vide.'];
            }

            $pdo = Connexion::pdo();
            $sql = "INSERT INTO Message (id_groupe, id_utilisateur, message, date_envoi)
                    VALUES (:idGroupe, :idUtilisateur, :message, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':idGroupe', $idGroupe, PDO::PARAM_INT);
            $stmt->bindParam(':idUtilisateur', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':message', $message, PDO::PARAM_STR);

            $stmt->execute();

            return ['status' => 'success', 'message' => 'Message envoyé avec succès.'];

        } catch (PDOException $e) {
            // Retourne une erreur en cas de problème d'insertion
            http_response_code(500); // Erreur serveur
            return ['status' => 'error', 'message' => 'Erreur lors de l\'envoi du message.', 'error' => $e->getMessage()];
        }
    }
}
