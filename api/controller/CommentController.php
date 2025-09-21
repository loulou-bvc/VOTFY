<?php

require_once __DIR__ . '/../Connexion.php';
require_once __DIR__ . '/NotificationController.php';
require_once __DIR__ . '/LogController.php';

class CommentController {
    /**
     * Récupère les commentaires liés à une proposition spécifique.
     *
     * @param int $propositionId L'ID de la proposition.
     * @return array Les commentaires et leur statut de récupération.
     */
    public static function readByProposition($propositionId) {
        $pdo = Connexion::pdo();
        try {
            $sql = "SELECT * FROM Commentaire WHERE id_proposition = ? ORDER BY date_commentaire ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$propositionId]);

            return [
                'status' => 'success',
                'comments' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
        } catch (PDOException $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Crée un nouveau commentaire pour une proposition.
     *
     * @param array $data Données nécessaires pour créer le commentaire.
     * @return array Le statut de création du commentaire.
     */
    public static function createComment($data) {
        $pdo = Connexion::pdo();
        try {
            $sql = "INSERT INTO Commentaire (id_proposition, id_utilisateur, texte, date_commentaire) VALUES (:propositionId, :userId, :contenu, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':propositionId' => $data['proposition_id'],
                ':userId' => $data['utilisateur_id'],
                ':contenu' => $data['contenu'],
            ]);

            $logMessage = "Commentaire ajouté pour la proposition ID {$data['proposition_id']} par l'utilisateur ID {$data['utilisateur_id']}\n";
            file_put_contents(__DIR__ . '/logs/debug.log', $logMessage, FILE_APPEND);
            NotificationController::create([
                'utilisateur_id' => $data['utilisateur_id'],
                'message' => 'Votre commentaire a été ajouté avec succès.',
            ]);

            return [
                'status' => 'success',
                'message' => 'Commentaire ajouté avec succès'
            ];
        } catch (PDOException $e) {
            $errorMessage = "Erreur lors de l'ajout du commentaire : " . $e->getMessage() . "\n";
            file_put_contents(__DIR__ . '/logs/error.log', $errorMessage, FILE_APPEND);

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}


?>
