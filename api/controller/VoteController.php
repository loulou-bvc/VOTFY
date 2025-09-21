<?php

require_once __DIR__ . '/../Connexion.php';

class VoteController {
    public static function createVote($userId, $data) {
        $pdo = Connexion::pdo();
        $errorLogFile = __DIR__ . '/logs/error.log';
        $logFile = __DIR__ . '/logs/debug.log';

        try {
            $sql = "INSERT INTO Vote (id_proposition, id_utilisateur, choix) VALUES (:proposition_id, :user_id, :choix)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':proposition_id' => $data['proposition_id'],
                ':user_id' => $userId,
                ':choix' => $data['choix'],
            ]);

            NotificationController::create($userId, "Votre vote a été enregistré pour la proposition ID {$data['proposition_id']}.");
            file_put_contents($logFile, "Vote enregistré pour la proposition ID {$data['proposition_id']} par l'utilisateur ID $userId\n", FILE_APPEND);

            return ['status' => 'success', 'message' => 'Vote enregistré avec succès'];
        } catch (PDOException $e) {
            file_put_contents($errorLogFile, "Erreur lors de l'enregistrement du vote : " . $e->getMessage() . "\n", FILE_APPEND);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public static function hasAlreadyVoted($userId, $propositionId) {
        $pdo = Connexion::pdo();
        $errorLogFile = __DIR__ . '/logs/error.log';
    
        try {
            // Vérifier si l'utilisateur est le créateur de la proposition
            $creatorSql = "SELECT id_createur FROM Proposition WHERE id_proposition = :proposition_id";
            $creatorStmt = $pdo->prepare($creatorSql);
            $creatorStmt->execute([':proposition_id' => $propositionId]);
            $creatorId = $creatorStmt->fetchColumn();
    
            if ($creatorId == $userId) {
                return true; // L'utilisateur est le créateur, il ne peut pas voter
            }
    
            // Vérifier si l'utilisateur a déjà voté pour cette proposition
            $sql = "SELECT COUNT(*) FROM Vote WHERE id_utilisateur = :user_id AND id_proposition = :proposition_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':proposition_id' => $propositionId,
            ]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            file_put_contents($errorLogFile, "Erreur lors de la vérification du vote : " . $e->getMessage() . "\n", FILE_APPEND);
            return false;
        }
    }
    
    public static function getVoteStatistics($propositionId) {
        $pdo = Connexion::pdo();
        try {
            $sql = "SELECT choix, COUNT(*) AS total 
                    FROM Vote 
                    WHERE id_proposition = :proposition_id 
                    GROUP BY choix";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':proposition_id' => $propositionId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            $totalVotes = 0;
            $voteCounts = ['pour' => 0, 'contre' => 0, 'abstention' => 0];
    
            // Calcul des votes
            foreach ($results as $row) {
                $voteCounts[$row['choix']] = (int)$row['total'];
                $totalVotes += $row['total'];
            }
    
            // Calcul des pourcentages
            $percentages = [];
            foreach ($voteCounts as $choice => $count) {
                $percentages[$choice] = $totalVotes > 0 ? round(($count / $totalVotes) * 100, 2) : 0;
            }
    
            return [
                'totalVotes' => $totalVotes,
                'voteCounts' => $voteCounts,
                'percentages' => $percentages,
            ];
        } catch (PDOException $e) {
            return null;
        }
    }
}
?>
