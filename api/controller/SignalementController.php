<?php

require_once __DIR__ . '/../Connexion.php';

class SignalementController{
    public function deleteSignalement($signalementId){
        $pdo = Connexion::pdo();
        $sql = "DELETE FROM Signalement WHERE id_signalement = :id_signalement";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id_signalement', $signalementId, PDO::PARAM_INT);
        return $stmt->execute();   
    }

    public function deletePropositionAndReports($propositionId) {
        try {
            $pdo = Connexion::pdo();
            $pdo->beginTransaction();
    
            // Supprimer les signalements liés
            $sqlSignalements = "DELETE FROM Signalement WHERE id_proposition = :id_proposition";
            $stmtSignalements = $pdo->prepare($sqlSignalements);
            $stmtSignalements->bindParam(':id_proposition', $propositionId, PDO::PARAM_INT);
            $stmtSignalements->execute();
            file_put_contents('../debug.log', "Signalements supprimés pour la proposition $propositionId\n", FILE_APPEND);
    
            // Supprimer la proposition
            $sqlProposition = "DELETE FROM Proposition WHERE id_proposition = :id_proposition";
            $stmtProposition = $pdo->prepare($sqlProposition);
            $stmtProposition->bindParam(':id_proposition', $propositionId, PDO::PARAM_INT);
            $stmtProposition->execute();
            file_put_contents('../debug.log', "Proposition supprimée : $propositionId\n", FILE_APPEND);
    
            $pdo->commit();
            return true;
        } catch (PDOException $e) {
            $pdo->rollBack();
            file_put_contents('../debug.log', "Erreur suppression proposition ID $propositionId : " . $e->getMessage() . "\n", FILE_APPEND);
            return false;
        }
    }
    
    public function hasUserReportedProposition($idProposition, $idUtilisateur){
        $pdo = Connexion::pdo();
        $sql = "SELECT COUNT(*) FROM Signalement WHERE id_proposition = :id_proposition AND id_utilisateur = :id_utilisateur";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id_proposition', $idProposition, PDO::PARAM_INT);
        $stmt->bindParam(':id_utilisateur', $idUtilisateur, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    public function createSignalement($idProposition, $idUtilisateur, $contenu){
        $pdo = Connexion::pdo();
        $sql = "INSERT INTO Signalement (id_proposition, id_utilisateur, contenue) 
                VALUES (:id_proposition, :id_utilisateur, :contenu)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id_proposition', $idProposition, PDO::PARAM_INT);
        $stmt->bindParam(':id_utilisateur', $idUtilisateur, PDO::PARAM_INT);
        $stmt->bindParam(':contenu', $contenu, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function getSignalementsByGroupId($idGroupe) {
        try {
            $pdo = Connexion::pdo();
            $sql = "SELECT s.id_signalement, s.id_proposition, s.contenue, p.titre, p.description 
                    FROM Signalement s
                    JOIN Proposition p ON s.id_proposition = p.id_proposition
                    WHERE p.id_groupe = :id_groupe";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_groupe', $idGroupe, PDO::PARAM_INT);
            $stmt->execute();
            $signalements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            // Log de débogage
            file_put_contents('../debug.log', "Appel API pour le groupe $idGroupe\n", FILE_APPEND);
            file_put_contents('../debug.log', "Requête SQL exécutée : $sql\n", FILE_APPEND);
            file_put_contents('../debug.log', "Résultats SQL : " . print_r($signalements, true), FILE_APPEND);
            file_put_contents('../debug.log', "Signalements pour le groupe $idGroupe : " . print_r($signalements, true), FILE_APPEND);
    
            return $signalements;
        } catch (PDOException $e) {
            // Log d'erreur
            file_put_contents('./logs/api_debug.log', "Erreur SQL : " . $e->getMessage(), FILE_APPEND);
            return [];
        }
    }    
}