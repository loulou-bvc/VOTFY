<?php

require_once __DIR__ . '/../Connexion.php';
require_once __DIR__ . '/../PermissionMiddleware.php';
require_once __DIR__ . '/NotificationController.php';

class GroupeController {
    public static function getAll($userId) {
        $pdo = Connexion::pdo();
        try {
            $sql = "CALL get_user_groups(:userId)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();

            $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['status' => 'success', 'groups' => $groups];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public static function getGroupDetails($idGroupe) {
        $pdo = Connexion::pdo();
        try {
            $sql = "SELECT * FROM Groupe WHERE id_groupe = :idGroupe";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':idGroupe', $idGroupe, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            file_put_contents(__DIR__ . '/logs/error.log', "Erreur SQL : " . $e->getMessage() . "\n", FILE_APPEND);
            return null;
        }
    }

    public static function leaveGroup($userId, $idGroupe) {
        $pdo = Connexion::pdo();
        try {
            $sql = "DELETE FROM Membre_Groupe WHERE id_utilisateur = :userId AND id_groupe = :idGroupe";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':userId' => $userId,
                ':idGroupe' => $idGroupe
            ]);

            NotificationController::create($userId, "Vous avez quitté le groupe $idGroupe");
            global $logFile;
            file_put_contents($logFile, "Utilisateur $userId a quitté le groupe $idGroupe\n", FILE_APPEND);

            return ['status' => 'success', 'message' => 'Vous avez quitté le groupe avec succès.'];
        } catch (PDOException $e) {
            file_put_contents(__DIR__ . '/logs/error.log', "Erreur lors de la sortie du groupe : " . $e->getMessage() . "\n", FILE_APPEND);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }       

    
 public static function getGroupMembers($groupId) {
    global $logFile;
    $pdo = Connexion::pdo();

    file_put_contents($logFile, "Récupération des membres du groupe $groupId\n", FILE_APPEND);

    try {
        $sql = "CALL GetGroupMembers(:groupId)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':groupId', $groupId, PDO::PARAM_INT);
        $stmt->execute();

        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fermer le curseur pour éviter les conflits liés aux procédures stockées
        $stmt->closeCursor();

        file_put_contents($logFile, "Membres récupérés : " . print_r($members, true) . "\n", FILE_APPEND);

        return $members;
    } catch (PDOException $e) {
        $errorMessage = "Erreur SQL lors de la récupération des membres : " . $e->getMessage();
        file_put_contents($logFile, $errorMessage . "\n", FILE_APPEND);

        return [
            'error' => true,
            'message' => $errorMessage
        ];
    }
}

    

    public static function removeMember($groupId, $userId, $adminId) {
        global $logFile;
        file_put_contents($logFile, "Tentative de suppression du membre $userId par l'administrateur $adminId dans le groupe $groupId\n", FILE_APPEND);
    
        // Vérification des permissions
        $permissionCheck = PermissionMiddleware::checkLevel(3, $adminId, $groupId);
        file_put_contents($logFile, "Résultat de checkLevel : " . json_encode($permissionCheck) . "\n", FILE_APPEND);
    
        if ($permissionCheck['status'] === 'error') {
            file_put_contents($logFile, "Permission refusée : " . $permissionCheck['message'] . "\n", FILE_APPEND);
            return $permissionCheck;
        }
    
        $pdo = Connexion::pdo();
        try {
            $sql = "CALL remove_group_member(:groupId, :userId)";
            $stmt = $pdo->prepare($sql);
    
            // Exécution de la procédure
            $stmt->execute([
                ':groupId' => $groupId,
                ':userId' => $userId
            ]);
    
            // Log des informations retournées par la procédure (s'il y a des résultats)
            $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
            file_put_contents($logFile, "Réponse brute de la procédure : " . print_r($response, true) . "\n", FILE_APPEND);
    
            // Fermeture du curseur
            $stmt->closeCursor();
    
            // Notification de succès
            NotificationController::create($userId, "Vous avez été retiré du groupe.");
            file_put_contents($logFile, "Suppression réussie pour userId=$userId dans le groupe $groupId\n", FILE_APPEND);
    
            return ['status' => 'success', 'message' => 'Membre retiré du groupe avec succès'];
        } catch (PDOException $e) {
            // Log de l'erreur SQL
            file_put_contents($logFile, "Erreur SQL lors de la suppression : " . $e->getMessage() . "\n", FILE_APPEND);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }    
    public static function create($data)
    {
        $pdo = Connexion::pdo();
    
        try {
            if (!isset($data['nom'], $data['description'], $data['admin_id'])) {
                return ['status' => 'error', 'message' => 'Données manquantes ou invalides'];
            }
    
            $sql = "CALL add_group(:nom, :description, :admin_id, :couleur, :theme, :image_url, :created_at)";
            $stmt = $pdo->prepare($sql);
    
            $stmt->execute([
                ':nom' => $data['nom'],
                ':description' => $data['description'],
                ':admin_id' => $data['admin_id'],
                ':couleur' => $data['couleur'] ?? null,
                ':theme' => $data['theme'] ?? null,
                ':image_url' => $data['image_url'] ?? null,
                ':created_at' => date('Y-m-d H:i:s')
            ]);
    
            // Vérifiez si le groupe a été créé avec succès
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$result) {
                return ['status' => 'error', 'message' => 'Échec de la création du groupe'];
            }
            NotificationController::create($data['admin_id'], "Groupe créé avec succès");
            return ['status' => 'success', 'message' => 'Groupe créé avec succès', 'id_groupe' => $result['id_groupe'] ?? null];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Erreur lors de la création du groupe : ' . $e->getMessage()];
        }
    }    

    public static function updateGroup($groupId, $data) {
        $logFile = __DIR__ . '/../debug.log';
        try {
            // Valider les données reçues
            if (!isset($data['nom'], $data['description'], $data['couleur'], $data['theme'], $data['user_id'], $data['required_level'])) {
                file_put_contents($logFile, "Données PUT manquantes ou invalides : " . json_encode($data) . "\n", FILE_APPEND);
                return ['status' => 'error', 'message' => 'Données manquantes ou invalides.'];
            }

            // Connexion à la base de données
            $pdo = Connexion::pdo();
            $chekcLevel = PermissionMiddleware::checkLevel($data['required_level'], $data['user_id'], $groupId);

            if ($chekcLevel['status'] === 'error') {
                return $chekcLevel;
            }

            // Vérifier si le groupe existe
            $stmt = $pdo->prepare("SELECT id_admin FROM Groupe WHERE id_groupe = :id_groupe");
            $stmt->bindParam(':id_groupe', $groupId, PDO::PARAM_INT);
            $stmt->execute();
            $group = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$group) {
                return ['status' => 'error', 'message' => 'Le groupe spécifié n\'existe pas.'];
            }

            // Vérifier si l'utilisateur est l'administrateur du groupe
            if ((int)$group['id_admin'] !== (int)$data['user_id']) {
                return ['status' => 'error', 'message' => 'Vous n\'avez pas l\'autorisation de modifier ce groupe.'];
            }

            // Mettre à jour le groupe
            $stmt = $pdo->prepare("
                UPDATE Groupe
                SET nom = :nom, 
                    description = :description, 
                    couleur = :couleur, 
                    theme = :theme 
                WHERE id_groupe = :id_groupe
            ");
            $stmt->execute([
                ':nom' => $data['nom'],
                ':description' => $data['description'],
                ':couleur' => $data['couleur'],
                ':theme' => $data['theme'],
                ':id_groupe' => $groupId,
            ]);

            // Vérifier que la mise à jour a été effectuée
            if ($stmt->rowCount() === 0) {
                return ['status' => 'error', 'message' => 'Aucune modification effectuée.'];
            }

            file_put_contents($logFile, "Mise à jour réussie pour le groupe ID $groupId\n", FILE_APPEND);
            return ['status' => 'success', 'message' => 'Le groupe a été mis à jour avec succès.'];
        } catch (PDOException $e) {
            file_put_contents($logFile, "Erreur PDO lors de la mise à jour : " . $e->getMessage() . "\n", FILE_APPEND);
            return ['status' => 'error', 'message' => 'Erreur serveur lors de la mise à jour.'];
        }
    }

    public static function getRolesForMembers($idGroupe) {
        global $logFile;
        try {
            $check = PermissionMiddleware::checkLevel(3, $_SESSION['id_utilisateur'], $idGroupe);
            if (!$check['status']) {
                file_put_contents($logFile, "Permission refusée pour l'utilisateur {$_SESSION['id_utilisateur']} dans le groupe $idGroupe\n", FILE_APPEND);
                die("Vous n'avez pas la permission d'accéder à cette page.");
            }
            $pdo = Connexion::pdo();
            $stmt = $pdo->prepare("
                SELECT nom_role,
                    Membre_Groupe.id_utilisateur,
                    Role.nom_role
                FROM Membre_Groupe
                JOIN Role ON Membre_Groupe.id_role = Role.id_role
                WHERE Membre_Groupe.id_groupe = :idGroupe
            ");
    
            $stmt->execute(['idGroupe' => $idGroupe]);
            return $stmt->fetchAll();
    
        } catch (PDOException $e) {
            file_put_contents($logFile, "Erreur lors de la récupération des rôles des membres : " . $e->getMessage() . PHP_EOL, FILE_APPEND);
            die("Erreur lors de la récupération des rôles des membres.");
        }
    }

    public static function getAvailableRoles($pdo) {
        global $logFile;
        try {
            $stmt = $pdo->prepare("SELECT id_role, nom_role FROM Role");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            file_put_contents($logFile, "Erreur lors de la récupération des rôles : " . $e->getMessage() . PHP_EOL, FILE_APPEND);
            die("Erreur lors de la récupération des rôles.");
        }
    }

    public static function updateUserRole($idUtilisateur, $idGroupe, $newRole) {
        global $logFile;
        try {
            $pdo = Connexion::pdo();
            $check = PermissionMiddleware::checkLevel(3, $_SESSION['id_utilisateur'], $idGroupe);
            if ($check['status'] === 'error') {
                file_put_contents($logFile, "Permission refusée pour l'utilisateur {$_SESSION['id_utilisateur']} dans le groupe $idGroupe\n", FILE_APPEND);
                die("Vous n'avez pas la permission de modifier le rôle.");
            }
            $stmt = $pdo->prepare("
                UPDATE Membre_Groupe 
                SET id_role = :newRole 
                WHERE id_utilisateur = :idUtilisateur AND id_groupe = :idGroupe
            ");
            $stmt->execute([
                'newRole' => $newRole,
                'idUtilisateur' => $idUtilisateur,
                'idGroupe' => $idGroupe
            ]);
            if ($stmt->rowCount() > 0) {
                file_put_contents($logFile, "Rôle mis à jour avec succès pour l'utilisateur $idUtilisateur dans le groupe $idGroupe\n", FILE_APPEND);
            } else {
                file_put_contents($logFile, "Aucune ligne mise à jour pour l'utilisateur $idUtilisateur dans le groupe $idGroupe\n", FILE_APPEND);
            }
        } catch (PDOException $e) {
            file_put_contents($logFile, "Erreur lors de la mise à jour du rôle : " . $e->getMessage() . PHP_EOL, FILE_APPEND);
            die("Erreur lors de la mise à jour du rôle.");
        }
    }
    public static function isAdminOfGroup($idGroupe, $idUtilisateur){
        $pdo = Connexion::pdo();
        $sql = "SELECT id_admin FROM Groupe WHERE id_groupe = :id_groupe";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id_groupe', $idGroupe, PDO::PARAM_INT);
        $stmt->execute();
        $groupe = $stmt->fetch(PDO::FETCH_ASSOC);
        return $groupe && $groupe['id_admin'] == $idUtilisateur;
    }

}




?>
